<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ClothesVariant;
use App\Models\ShoesVariant;
use App\Jobs\SendOrderNotificationJob;
use App\Events\OrderStatusUpdated;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{

    private const PER_PAGE = 15;
    private const MAX_INVENTORY_QUANTITY = 1000000;
    private const MAX_ORDER_AMOUNT_CENTS = 999999999999;

    protected CartService $cartService;
    public function __construct(CartService $cartService){
        $this->cartService = $cartService;
    }

    public function getCheckoutSummary(): array
    {
        return $this->prepareCheckout($this->cartService->getCart());
    }

    public function createOrder(array $data){
        $user = Auth::user();

        if (!$user) {
            throw new DomainException('Vui lòng đăng nhập để đặt hàng.');
        }

        return DB::transaction(function () use ($user, $data) {
            // Khóa giỏ trước khi đọc các món để hai yêu cầu không cùng tạo đơn từ một giỏ.
            $cart = Cart::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$cart) {
                throw new DomainException('Giỏ hàng trống. Vui lòng kiểm tra lại giỏ hàng.');
            }

            $cart->setRelation('items', $cart->items()->orderBy('id')->lockForUpdate()->get());
            $summary = $this->prepareCheckout($cart, true);

            if (!hash_equals($summary['checkoutFingerprint'], (string) ($data['checkout_fingerprint'] ?? ''))) {
                throw new DomainException('Giá hoặc lựa chọn trong giỏ đã thay đổi. Vui lòng xem lại đơn hàng trước khi đặt.');
            }

            $orderCode = 'ORD-' . date('YMD') . '-' . strtoupper(Str::random(5));
            $order = Order::create([
                'user_id'=>$user->id,
                'order_code'=> $orderCode,
                'customer_name'=>$data['customer_name'],
                'customer_phone'=>$data['customer_phone'],
                'shipping_address'=>$data['shipping_address'],
                'note' => $data['note'] ?? null,
                'total_amount' => $summary['cartTotal'],
                'shipping_fee' => 0,
                'discount_amount' => 0,
                'payment_method' => $data['payment_method'] ?? 'cod',
                'status' => 'pending',
            ]);

            $order->items()->createMany($summary['orderItems']);
            $cart->items()->whereIn('id', $cart->items->modelKeys())->delete();
            $cart->update(['total' => 0]);

            // SendOrderNotificationJob::dispatch($order);
            return $order->load('items');

        }, 3);
    }

    private function prepareCheckout(Cart $cart, bool $lock = false): array
    {
        if ($cart->items->isEmpty()) {
            throw new DomainException('Giỏ hàng trống. Vui lòng kiểm tra lại giỏ hàng.');
        }

        if ($cart->items->contains(fn ($item) => !in_array($item->variant_type, ['shoe', 'cloth'], true)
            || $item->quantity < 1 || $item->quantity > self::MAX_INVENTORY_QUANTITY)) {
            throw new DomainException('Có lựa chọn hoặc số lượng không hợp lệ trong giỏ. Vui lòng kiểm tra lại.');
        }

        $products = Product::query()->whereIn('id', $cart->items->pluck('product_id'))->orderBy('id')
            ->when($lock, fn ($query) => $query->sharedLock())->get()->keyBy('id');
        $variants = [];

        foreach (['shoe' => ShoesVariant::class, 'cloth' => ClothesVariant::class] as $type => $model) {
            $ids = $cart->items->where('variant_type', $type)->pluck('variant_id')->unique();
            $variants[$type] = $model::query()->whereIn('id', $ids)->orderBy('id')
                ->when($lock, fn ($query) => $query->sharedLock())->get()->keyBy('id');
        }

        $quantities = $cart->items->groupBy(fn ($item) => $item->variant_type . ':' . $item->variant_id)
            ->map(fn ($items) => $items->sum('quantity'));
        $totalCents = 0;
        $orderItems = [];
        $pricesChanged = false;

        foreach ($cart->items->sortBy('id') as $item) {
            $product = $products->get($item->product_id);
            $variant = $variants[$item->variant_type]->get($item->variant_id);

            $issue = $this->cartService->getItemIssue($item, $product, $variant, $quantities[$item->variant_type . ':' . $item->variant_id]);
            if ($issue) {
                throw new DomainException($issue['message']);
            }

            $price = (string) ($variant->price_override ?? $product->base_price);

            if (!preg_match('/^\d{1,10}\.\d{2}$/', $price)) {
                throw new DomainException('Có giá sản phẩm không hợp lệ. Vui lòng liên hệ cửa hàng.');
            }

            // Tính theo đơn vị 1/100 đồng bằng số nguyên để tránh sai số số thực.
            $priceCents = (int) str_replace('.', '', $price);
            $lineCents = $priceCents * $item->quantity;
            $totalCents += $lineCents;

            if ($totalCents > self::MAX_ORDER_AMOUNT_CENTS) {
                throw new DomainException('Tổng giá trị đơn vượt giới hạn cho phép. Vui lòng giảm số lượng hoặc chia đơn.');
            }

            $pricesChanged = $pricesChanged || (string) $item->price !== $price;
            $item->price = $price;
            $item->setRelation('product', $product);
            $orderItems[] = [
                'product_id' => $product->id,
                'variant_type' => $item->variant_type,
                'variant_id' => $variant->id,
                'size' => $variant->size,
                'color' => $variant->color,
                'stud_type' => $item->variant_type === 'shoe' ? $variant->stud_type : null,
                'quantity' => $item->quantity,
                'price' => $price,
                'total' => $this->formatCheckoutAmount($lineCents),
            ];
        }

        return [
            'cart' => $cart,
            'cartTotal' => $this->formatCheckoutAmount($totalCents),
            'checkoutFingerprint' => hash('sha256', json_encode([$cart->id, $cart->items->sortBy('id')->modelKeys(), $orderItems], JSON_THROW_ON_ERROR)),
            'pricesChanged' => $pricesChanged,
            'orderItems' => $orderItems,
        ];
    }

    private function formatCheckoutAmount(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public function getOrdersByUser($userId){
        return Order::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->paginate(self::PER_PAGE);
    }

    public function getOrderDetail($orderId){
        return Order::with('items.product.shoe.images', 'items.product.cloth.images')
                ->where('user_id', Auth::id())
                ->findOrFail($orderId);
    }

    //admin method

    public function getAllOrders(){
        return Order::query()
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->paginate(self::PER_PAGE);
    }

    public function searchOrdersByPhoneOrDate(string $phone, string $orderDates){
        $query = Order::query()
                    ->select([
                        'id',
                        'order_code',
                        'customer_name',
                        'customer_phone',
                        'total_amount',
                        'status',
                        'created_at',
                    ]);

        if ($phone !== '') {
            $query->where('customer_phone', $phone);
        }

        if ($orderDates !== '') {
            $date = Carbon::createFromFormat('d-m-Y', $orderDates)->toDateString();
            $query->whereDate('created_at', $date);
        }

        return $query->orderBy('created_at', 'desc')
                     ->orderBy('id', 'desc')
                     ->paginate(self::PER_PAGE)
                     ->withQueryString();
    }

    public function getOrderById($orderId){
        return Order::with('user', 'items.product.shoe.images', 'items.product.cloth.images')
                ->findOrFail($orderId);
    }

    public function getOrderStatuses(array $ids, ?int $customerId = null): array
    {
        return Order::query()
            ->select(['id', 'status'])
            ->whereIn('id', $ids)
            ->when($customerId !== null, fn ($query) => $query->where('user_id', $customerId))
            ->get()
            ->map(fn (Order $order) => $order->statusData())
            ->all();
    }

    public function updateOrderStatus(int $orderId, string $status, string $currentStatus): Order
    {
        return $this->transitionOrder($orderId, $status, $currentStatus);
    }

    public function cancelOrderByCustomer(int $orderId, int $customerId): Order
    {
        return $this->transitionOrder($orderId, 'cancelled', 'pending', $customerId);
    }

    private function transitionOrder(int $orderId, string $status, string $currentStatus, ?int $customerId = null): Order
    {
        return DB::transaction(function () use ($orderId, $status, $currentStatus, $customerId) {
            $order = Order::query()
                ->when($customerId !== null, fn ($query) => $query->where('user_id', $customerId))
                ->lockForUpdate()
                ->findOrFail($orderId);

            if ($order->status !== $currentStatus) {
                throw new DomainException('Trạng thái đơn đã thay đổi. Vui lòng kiểm tra lại trước khi thao tác.');
            }

            if (!in_array($status, $order->allowedNextStatuses(), true)) {
                throw new DomainException('Không thể chuyển đơn sang trạng thái đã chọn.');
            }

            if ($status === 'processing') {
                $this->adjustOrderStock($order, false);
            } elseif ($status === 'cancelled' && $order->status === 'processing') {
                $this->adjustOrderStock($order, true);
            }

            $order->status = $status;
            $order->save();

            DB::afterCommit(function () use ($order) {
                try {
                    event(new OrderStatusUpdated($order));
                } catch (\Throwable $exception) {
                    // Đơn đã lưu thành công; lỗi gửi thông báo không được báo thành lỗi cập nhật đơn.
                    report($exception);
                }
            });

            return $order;
        }, 3);
    }

    private function adjustOrderStock(Order $order, bool $restore): void
    {
        $items = $order->items()->get();

        if ($items->isEmpty() || $items->contains(fn ($item) => !in_array($item->variant_type, ['shoe', 'cloth'], true) || $item->quantity <= 0)) {
            throw new DomainException('Chi tiết đơn hàng không hợp lệ. Vui lòng kiểm tra trước khi xử lý.');
        }

        foreach (['shoe' => ShoesVariant::class, 'cloth' => ClothesVariant::class] as $type => $model) {
            $groups = $items->where('variant_type', $type)->groupBy('variant_id');

            if ($groups->isEmpty()) {
                continue;
            }

            $variants = $model::query()
                ->whereIn('id', $groups->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($groups as $variantId => $group) {
                $variant = $variants->get($variantId);

                if (!$variant || $group->contains(fn ($item) => (int) $item->product_id !== (int) $variant->product_id)) {
                    throw new DomainException('Có biến thể của đơn không còn tồn tại hoặc không khớp sản phẩm.');
                }

                $quantity = (int) $group->sum('quantity');
                $newQuantity = $restore ? $variant->quantity + $quantity : $variant->quantity - $quantity;

                if ($newQuantity < 0) {
                    throw new DomainException('Không đủ tồn kho cho biến thể: ' . $variant->size . ' / ' . $variant->color . '.');
                }

                if ($newQuantity > self::MAX_INVENTORY_QUANTITY) {
                    throw new DomainException('Tồn kho sau xử lý vượt giới hạn. Vui lòng kiểm tra lại số lượng của biến thể.');
                }

                $variant->quantity = $newQuantity;
                $variant->save();
            }
        }
    }

}