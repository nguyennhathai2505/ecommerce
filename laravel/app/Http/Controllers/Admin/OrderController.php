<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use App\Services\OrderService;
use App\Models\Order;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected ProductService $productService;

    public function __construct(OrderService $orderService, ProductService $productService)
    {
        $this->orderService = $orderService;
        $this->productService = $productService;
    }

    /**
     * Danh sách đơn hàng cho admin
     */
    public function index()
    {
        $orders = $this->orderService->getAllOrders();

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Tìm kiếm đơn hàng theo số điện thoại và ngày đặt
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'nullable|string|max:20',
            'orderDates' => 'nullable|date_format:d-m-Y',
        ]);

        $orders = $this->orderService->searchOrdersByPhoneOrDate(
            trim((string) ($validated['phone'] ?? '')),
            (string) ($validated['orderDates'] ?? '')
        );

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Chi tiết đơn hàng cho admin
     */
    public function show($id)
    {
        $order = $this->orderService->getOrderById($id);
        $itemImages = $this->productService->getItemImages($order->items);
        return view('admin.orders.show', compact('order', 'itemImages'));
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'current_status' => ['required', Rule::in(array_keys(Order::STATUSES))],
        ]);

        try {
            $order = $this->orderService->updateOrderStatus($id, $validated['status'], $validated['current_status']);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Cập nhật trạng thái đơn hàng thành công!',
                    'data' => $order->statusData(),
                ]);
            }
            
            return redirect()->back()->with('success', 'Cập nhật trạng thái đơn hàng thành công!');
        } catch (DomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }

            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

    /**
     * API cập nhật trạng thái (cho AJAX)
     */
    public function updateStatusApi(Request $request, $id)
    {
        $request->headers->set('Accept', 'application/json');

        return $this->updateStatus($request, $id);
    }

    public function statuses(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:25',
            'ids.*' => 'required|integer|min:1|distinct',
        ]);

        return response()->json([
            'data' => $this->orderService->getOrderStatuses($validated['ids']),
        ])->header('Cache-Control', 'no-store, private');
    }
}
