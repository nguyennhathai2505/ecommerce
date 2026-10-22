<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ClothesVariant;
use App\Models\Product;
use App\Models\ShoesVariant;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


class CartService{

        private const GUEST_CART_SESSION_KEY = 'cart.guest_items';
        private const GUEST_CART_COOKIE = 'guest_cart_token';
        private const GUEST_CART_LIFETIME_MINUTES = 10080;

        protected Request $request;
        protected CookieFactory $cookies;

        public function __construct(
            Request $request,
            CookieFactory $cookies,
        ) {
            $this->request = $request;
            $this->cookies = $cookies;
        }

        public function getItemIssues(Cart $cart): array
        {
            $cart->loadMissing('items.product');
            $variants = [];

            foreach (['shoe' => ShoesVariant::class, 'cloth' => ClothesVariant::class] as $type => $model) {
                $ids = $cart->items->where('variant_type', $type)->pluck('variant_id')->unique();
                $variants[$type] = $ids->isEmpty() ? collect() : $model::whereIn('id', $ids)->get()->keyBy('id');
            }

            $quantities = $cart->items->groupBy(fn ($item) => $item->variant_type . ':' . $item->variant_id)
                ->map(fn ($items) => $items->sum('quantity'));
            $issues = [];

            foreach ($cart->items as $item) {
                $variant = ($variants[$item->variant_type] ?? collect())->get($item->variant_id);
                $issue = $this->getItemIssue($item, $item->product, $variant, $quantities[$item->variant_type . ':' . $item->variant_id]);
                if ($issue) {
                    $issues[$item->id] = $issue;
                }
            }

            return $issues;
        }

        public function getItemIssue(CartItem $item, ?Product $product, ShoesVariant|ClothesVariant|null $variant, int $quantity): ?array
        {
            $options = array_filter([
                $item->color ? 'màu ' . $item->color : null,
                $item->size ? 'size ' . $item->size : null,
                $item->stud_type ? 'loại đinh ' . $item->stud_type : null,
            ]);
            $name = ($product?->name ?? 'Sản phẩm không còn tồn tại') . ($options ? ' (' . implode(', ', $options) . ')' : '');

            if (!$product || $product->status !== 'active' || !$variant || $variant->status !== 'active') {
                return [
                    'status' => 'unavailable',
                    'label' => 'Sản phẩm ngừng bán',
                    'message' => $name . ': Sản phẩm ngừng bán. Vui lòng xóa khỏi giỏ hàng.',
                ];
            }

            if ((int) $variant->product_id !== (int) $product->id
                || $product->product_type !== ($item->variant_type === 'shoe' ? 'SHOE' : 'CLOTH')
                || (string) $item->size !== (string) $variant->size
                || (string) $item->color !== (string) $variant->color
                || (string) $item->stud_type !== (string) ($item->variant_type === 'shoe' ? $variant->stud_type : null)) {
                return [
                    'status' => 'changed',
                    'label' => 'Cần chọn lại sản phẩm',
                    'message' => $name . ': Lựa chọn sản phẩm đã thay đổi. Vui lòng chọn lại size, màu hoặc loại đinh.',
                ];
            }

            if ($variant->quantity <= 0) {
                return [
                    'status' => 'out_of_stock',
                    'label' => 'Tạm hết hàng',
                    'message' => $name . ': Tạm hết hàng. Vui lòng chọn sản phẩm khác hoặc xóa khỏi giỏ hàng.',
                ];
            }

            if ($quantity > $variant->quantity) {
                return [
                    'status' => 'insufficient_stock',
                    'label' => 'Số lượng không đủ',
                    'message' => $name . ': Chỉ còn ' . $variant->quantity . ' sản phẩm. Vui lòng giảm số lượng.',
                ];
            }

            return null;
        }

        protected function resolveCart(){
            if(Auth::check()){
                $cart =Cart::firstOrCreate([
                    'user_id'=>Auth::id(),
                ]);
            }else{
                return $this->getGuestCart();
            }

            return $cart->load('items.product');
        }

        protected function getGuestCart(){
            $cart = $this->findGuestCart();
            $legacyItems = session()->get(self::GUEST_CART_SESSION_KEY, []);

            if(!$cart && empty($legacyItems)){
                $cart = new Cart(['total'=>0]);
                $cart->setRelation('items', collect());
                return $cart;
            }

            $cart ??= $this->createGuestCart();
            $this->moveLegacySessionItemsToCart($cart, $legacyItems);
            $this->refreshGuestCartExpiration($cart);

            return $cart->load('items.product');
        }

        protected function findGuestCart(): ?Cart
        {
            $token = $this->getGuestCartToken();
            if(!is_string($token) || !preg_match('/^[A-Za-z0-9]{64}$/', $token)){
                return null;
            }

            $cart = Cart::whereNull('user_id')
                        ->where('session_id', hash('sha256', $token))
                        ->where('expires_at', '>', now())
                        ->first();

            if(!$cart){
                $this->forgetGuestCartCookie();
            }

            return $cart;
        }

        protected function getGuestCartToken(): ?string
        {
            $queuedCookie = collect($this->cookies->getQueuedCookies())
                ->last(fn ($cookie) => $cookie->getName() === self::GUEST_CART_COOKIE);

            if($queuedCookie){
                if($queuedCookie->getExpiresTime() <= time()){
                    return null;
                }

                return $queuedCookie->getValue();
            }

            $token = $this->request->cookie(self::GUEST_CART_COOKIE);
            return is_string($token) ? $token : null;
        }

        protected function getOrCreateGuestCart(): Cart
        {
            $cart = $this->findGuestCart() ?? $this->createGuestCart();
            $this->moveLegacySessionItemsToCart(
                $cart,
                session()->get(self::GUEST_CART_SESSION_KEY, [])
            );
            $this->refreshGuestCartExpiration($cart);

            return $cart;
        }

        protected function createGuestCart(): Cart
        {
            $token = Str::random(64);
            $cart = Cart::create([
                'user_id'=>null,
                'session_id'=>hash('sha256', $token),
                'total'=>0,
                'expires_at'=>now()->addMinutes(self::GUEST_CART_LIFETIME_MINUTES),
            ]);

            $this->queueGuestCartCookie($token);

            return $cart;
        }

        protected function queueGuestCartCookie(string $token): void
        {
            $this->cookies->queue($this->cookies->make(
                self::GUEST_CART_COOKIE,
                $token,
                self::GUEST_CART_LIFETIME_MINUTES,
                config('session.path', '/'),
                config('session.domain'),
                config('session.secure'),
                true,
                false,
                config('session.same_site', 'lax')
            ));
        }

        protected function refreshGuestCartExpiration(Cart $cart): void
        {
            if(!$cart->expires_at || $cart->expires_at->lte(now()->addDays(6))){
                $token = $this->getGuestCartToken();
                if(!$token){
                    return;
                }

                $cart->update([
                    'expires_at'=>now()->addMinutes(self::GUEST_CART_LIFETIME_MINUTES),
                ]);
                $this->queueGuestCartCookie($token);
            }
        }

        protected function forgetGuestCartCookie(): void
        {
            $this->cookies->queue($this->cookies->forget(
                self::GUEST_CART_COOKIE,
                config('session.path', '/'),
                config('session.domain')
            ));
        }

        protected function moveLegacySessionItemsToCart(Cart $cart, array $legacyItems): void
        {
            if(empty($legacyItems)){
                return;
            }

            DB::transaction(function () use ($cart, $legacyItems) {
                foreach($legacyItems as $legacyItem){
                    $item = CartItem::where('cart_id', $cart->id)
                                    ->where('product_id', $legacyItem['product_id'])
                                    ->where('variant_type', $legacyItem['variant_type'])
                                    ->where('variant_id', $legacyItem['variant_id'])
                                    ->first();

                    if($item){
                        $item->quantity += $legacyItem['quantity'];
                        $item->save();
                    }else{
                        CartItem::create([
                            'cart_id'=>$cart->id,
                            'product_id'=>$legacyItem['product_id'],
                            'variant_type'=>$legacyItem['variant_type'],
                            'variant_id'=>$legacyItem['variant_id'],
                            'size'=>$legacyItem['size'],
                            'color'=>$legacyItem['color'],
                            'stud_type'=>$legacyItem['stud_type'],
                            'quantity'=>$legacyItem['quantity'],
                            'price'=>$legacyItem['price'],
                        ]);
                    }
                }

                $this->updateCartTotal($cart);
            });

            session()->forget(self::GUEST_CART_SESSION_KEY);
        }

        protected function updateCartTotal(Cart $cart): void
        {
            $total = CartItem::where('cart_id', $cart->id)->sum(DB::raw('price * quantity'));
            $cart->update(['total'=>$total]);
        }

        // public function addItem(Product $product, array $data){
        //     $cart = $this->resolveCart();

        //     $variant = null;
        //     $variantType = null;
        //     $variantId = null;

        //     if($product->product_type == 'SHOE'){
        //         $variant = ShoesVariant::where('product_id', $product->id)
        //                                 ->where('size', $data['size'])
        //                                 ->where('color', $data['color'])
        //                                 ->where('stud_type', $data['stud_type'] ?? 'TF')->first();
        //         $variantType = 'shoe';
        //     }
        //     else{
        //         $variant = ClothesVariant::where('product_id', $product->id)
        //                                     ->where('size', $data['size'])
        //                                     ->where('color', $data['color'])
        //                                     ->first();

        //         $variantType = 'cloth';
        //     }
        //     if(!$variant){
        //         throw new Exception('Variant not found');
        //     }

        //     $price = $variant->price_override ?? $product->base_price;

        //     $cartItem = CartItem::where('cart_id', $cart->id)
        //                         ->where('product_id', $product->id)
        //                         ->where('variant_type', $variantType)
        //                         ->where('variant_id', $variant->id)
        //                         ->where('size', $data['size'])
        //                         ->where('color', $data['color'])
        //                         ->where('stud_type', $data['stud_type'] ?? null)
        //                         ->first();

        //     if($cartItem){
        //         $cartItem->quantity +=$data['quantity'];
        //         $cartItem->save();
        //     }else{
        //         CartItem::create([
        //             'cart_id'=>$cart->id,
        //             'product_id'=>$product->id,
        //             'variant_type'=>$variantType,
        //             'variant_id'=>$variant->id,
        //             'size'=>$data['size'],
        //             'color'=>$data['color'],
        //             'stud_type'=>$data['stud_type'] ?? null,
        //             'quantity'=>$data['quantity'],
        //             'price'=>$price
        //         ]);
        //     }

        //     return $cart->fresh('item');
        // }



public function addItem(Product $product, array $data)
{
    [$variant, $variantType] = $this->resolveAvailableVariant($product, $data);
    $requestedQuantity = (int) $data['quantity'];
    $price = $variant->price_override ?? $product->base_price;
    $cart = Auth::check() ? $this->resolveCart() : $this->getOrCreateGuestCart();
    $cartItem = CartItem::where('cart_id', $cart->id)
        ->where('product_id', $product->id)
        ->where('variant_type', $variantType)
        ->where('variant_id', $variant->id)
        ->first();

    $this->ensureStockAvailable($variant, $requestedQuantity, $cartItem?->quantity ?? 0);

    if ($cartItem) {
        $cartItem->quantity += $requestedQuantity;
        $cartItem->price = $price;
        $cartItem->save();
    } else {
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_type' => $variantType,
            'variant_id' => $variant->id,
            'size' => $variant->size,
            'color' => $variant->color,
            'stud_type' => $variantType === 'shoe' ? $variant->stud_type : null,
            'quantity' => $requestedQuantity,
            'price' => $price,
        ]);
    }

    $this->updateCartTotal($cart);
    if (!Auth::check()) {
        $this->refreshGuestCartExpiration($cart);
    }

    return $cart->fresh()->load('items.product');
}

protected function resolveAvailableVariant(Product $product, array $data): array
{
    if ($product->status !== 'active') {
        throw ValidationException::withMessages([
            'product_id' => 'Sản phẩm hiện không khả dụng.',
        ]);
    }

    $variantType = $product->product_type === 'SHOE' ? 'shoe' : 'cloth';
    if (($data['variant_type'] ?? null) !== $variantType) {
        throw ValidationException::withMessages([
            'variant_type' => 'Loại biến thể không phù hợp với sản phẩm.',
        ]);
    }

    $variantClass = $variantType === 'shoe' ? ShoesVariant::class : ClothesVariant::class;
    $variant = $variantClass::where('product_id', $product->id)
        ->where('status', 'active')
        ->find($data['variant_id'] ?? null);

    if (!$variant) {
        throw ValidationException::withMessages([
            'variant_id' => 'Biến thể sản phẩm không tồn tại hoặc đã ngừng bán.',
        ]);
    }

    return [$variant, $variantType];
}

protected function ensureStockAvailable($variant, int $requestedQuantity, int $currentQuantity = 0): void
{
    if ($variant->quantity < 1) {
        throw ValidationException::withMessages([
            'quantity' => 'Sản phẩm tạm hết hàng.',
        ]);
    }

    if ($requestedQuantity < 1 || $currentQuantity + $requestedQuantity > $variant->quantity) {
        throw ValidationException::withMessages([
            'quantity' => "Sản phẩm chỉ còn {$variant->quantity} sản phẩm trong kho.",
        ]);
    }
}

protected function resolveActiveCartVariant(string $variantType, int $variantId)
{
    $variantClass = $variantType === 'shoe' ? ShoesVariant::class : ClothesVariant::class;
    $variant = $variantClass::where('status', 'active')->find($variantId);

    if (!$variant || $variant->product?->status !== 'active') {
        throw ValidationException::withMessages([
            'variant_id' => 'Biến thể sản phẩm không tồn tại hoặc đã ngừng bán.',
        ]);
    }

    return $variant;
}
        public function getCart(){
            return $this->resolveCart()->loadMissing(['items.product.shoe.images', 'items.product.cloth.images']);
        }

        public function updateQuantity($itemId, $quantity){
            if(!Auth::check()){
                $legacyItems = session()->get(self::GUEST_CART_SESSION_KEY, []);
                if(isset($legacyItems[$itemId])){
                    $variant = $this->resolveActiveCartVariant(
                        $legacyItems[$itemId]['variant_type'],
                        $legacyItems[$itemId]['variant_id']
                    );
                    $this->ensureStockAvailable($variant, (int) $quantity);
                    $legacyItems[$itemId]['quantity'] = $quantity;
                    session()->put(self::GUEST_CART_SESSION_KEY, $legacyItems);
                    return $this->getGuestCart();
                }

                $cart = $this->getGuestCart();
                $item = CartItem::where('cart_id', $cart->id)
                                ->where('id', $itemId)
                                ->first();

                if(!$item){
                    throw new Exception('Item not found');
                }

                $variant = $this->resolveActiveCartVariant($item->variant_type, $item->variant_id);
                $this->ensureStockAvailable($variant, (int) $quantity);
                $item->quantity = $quantity;
                $item->save();
                $this->updateCartTotal($cart);
                $this->refreshGuestCartExpiration($cart);

                return $cart->fresh()->load('items.product');
            }

            $cart = $this->resolveCart();
            $item = CartItem::where('cart_id', $cart->id)
                            ->where('id', $itemId)
                            ->first();

            if(!$item){
                throw new Exception('Item not found');
            }
            $variant = $this->resolveActiveCartVariant($item->variant_type, $item->variant_id);
            $this->ensureStockAvailable($variant, (int) $quantity);
            if($quantity<=0){
                $item->delete();
            }
            else{
                $item->quantity = $quantity;
                $item->save();
            }

            $this->updateCartTotal($cart);
            return $cart->fresh('items');
        }


        public function removeItem($itemId){
            if(!Auth::check()){
                $legacyItems = session()->get(self::GUEST_CART_SESSION_KEY, []);
                if(isset($legacyItems[$itemId])){
                    unset($legacyItems[$itemId]);
                    session()->put(self::GUEST_CART_SESSION_KEY, $legacyItems);
                    return $this->getGuestCart();
                }

                $cart = $this->getGuestCart();
                $deleted = CartItem::where('cart_id', $cart->id)
                                   ->where('id', $itemId)
                                   ->delete();

                if(!$deleted){
                    throw new Exception('Item not found');
                }

                $this->updateCartTotal($cart);
                $this->refreshGuestCartExpiration($cart);

                return $cart->fresh()->load('items.product');
            }

            $cart = $this->resolveCart();
            $item =  CartItem::where('cart_id', $cart->id)
                                ->where('id', $itemId)->delete();

            return $cart->fresh('items');
        }

        public function clearCart(){
            if(!Auth::check()){
                session()->forget(self::GUEST_CART_SESSION_KEY);
                $cart = $this->findGuestCart();
                if($cart){
                    $cart->delete();
                    $this->forgetGuestCartCookie();
                }
                return $this->getGuestCart();
            }

            $cart = $this->resolveCart();
            $cart->items()->delete();
            return $cart;
        }

        public function getCartTotal(){
            $cart = $this->resolveCart();
            return $cart->items->sum(function ($item){
                return $item->price * $item->quantity;
            });
        }

    public function getCartCount()
    {
        $cart = $this->resolveCart();
        return $cart->items->sum('quantity');
    }

    public function mergeGuestCartIntoUser(User $user)
    {
        $guestCart = $this->findGuestCart();
        $legacyItems = session()->get(self::GUEST_CART_SESSION_KEY, []);

        if(!$guestCart && empty($legacyItems)){
            return;
        }

        $guestCart ??= $this->createGuestCart();
        $this->moveLegacySessionItemsToCart($guestCart, $legacyItems);
        $guestCart->load('items');

        DB::transaction(function () use ($user, $guestCart) {
            $cart = Cart::firstOrCreate(['user_id'=>$user->id]);

            foreach($guestCart->items as $guestItem){
                $item = CartItem::where('cart_id', $cart->id)
                                ->where('product_id', $guestItem->product_id)
                                ->where('variant_type', $guestItem->variant_type)
                                ->where('variant_id', $guestItem->variant_id)
                                ->first();

                if($item){
                    $item->quantity += $guestItem->quantity;
                    $item->save();
                }else{
                    CartItem::create([
                        'cart_id'=>$cart->id,
                        'product_id'=>$guestItem->product_id,
                        'variant_type'=>$guestItem->variant_type,
                        'variant_id'=>$guestItem->variant_id,
                        'size'=>$guestItem->size,
                        'color'=>$guestItem->color,
                        'stud_type'=>$guestItem->stud_type,
                        'quantity'=>$guestItem->quantity,
                        'price'=>$guestItem->price,
                    ]);
                }
            }

            $this->updateCartTotal($cart);
            $guestCart->delete();
        });

        session()->forget(self::GUEST_CART_SESSION_KEY);
        $this->forgetGuestCartCookie();
    }


}