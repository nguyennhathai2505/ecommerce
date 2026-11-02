@extends('layouts.customer')

@section('title', 'Chi tiết đơn hàng #' . $order->order_code)

@section('breadcrumb')
    @include('customer.partials.breadcrumb', ['items' => [
        ['label' => 'Lịch sử đơn hàng', 'url' => route('customer.orders')],
        ['label' => '#' . $order->order_code]
    ]])
@endsection

@section('content')
<div class="max-w-4xl mx-auto" data-order-realtime data-order-channel="customer.orders.{{ auth()->id() }}" data-order-status-url="{{ route('customer.orders.statuses') }}">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Đơn hàng #{{ $order->order_code }}</h1>
        <x-order-status :order="$order" size="md" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Thông tin đơn hàng -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Thông tin đơn hàng</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-600">Mã đơn hàng:</dt>
                    <dd class="font-medium text-gray-900">{{ $order->order_code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Ngày đặt:</dt>
                    <dd class="font-medium text-gray-900">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Phương thức thanh toán:</dt>
                    <dd class="font-medium text-gray-900 capitalize">{{ $order->payment_method }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Trạng thái:</dt>
                    <dd><x-order-status :order="$order" /></dd>
                </div>
            </dl>
        </div>

        <!-- Thông tin giao hàng -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Thông tin giao hàng</h2>
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="text-gray-600">Người nhận:</dt>
                    <dd class="font-medium text-gray-900">{{ $order->customer_name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600">Số điện thoại:</dt>
                    <dd class="font-medium text-gray-900">{{ $order->customer_phone }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600">Địa chỉ:</dt>
                    <dd class="font-medium text-gray-900">{{ $order->shipping_address }}</dd>
                </div>
                @if($order->note)
                <div>
                    <dt class="text-gray-600">Ghi chú:</dt>
                    <dd class="font-medium text-gray-900">{{ $order->note }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Chi tiết sản phẩm -->
    <div class="bg-white rounded-lg shadow-sm mt-6 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Chi tiết sản phẩm</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left text-sm font-medium text-gray-500 px-4 py-2">Sản phẩm</th>
                        <th class="text-left text-sm font-medium text-gray-500 px-4 py-2">Biến thể</th>
                        <th class="text-center text-sm font-medium text-gray-500 px-4 py-2">Số lượng</th>
                        <th class="text-right text-sm font-medium text-gray-500 px-4 py-2">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr class="border-b">
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-gray-100 rounded flex items-center justify-center flex-shrink-0">
                                        @php
                                            $image = $itemImages[$item->id] ?? null;
                                        @endphp
                                        @if($image)
                                            <img src="{{ $image }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover rounded">
                                        @else
                                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('products.show', $item->product->slug) }}" class="text-sm font-medium text-gray-900 hover:text-black">
                                            {{ $item->product->name }}
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <div>Size: <span class="font-medium">{{ $item->size }}</span></div>
                                <div>Màu: <span class="font-medium">{{ $item->color }}</span></div>
                                @if($item->stud_type)
                                    <div>Loại đinh: <span class="font-medium">{{ $item->stud_type }}</span></div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">× {{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">
                                {{ number_format($item->total, 0, ',', '.') }} ₫
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right font-medium text-gray-900">Tổng cộng:</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900">
                            {{ number_format($order->total_amount, 0, ',', '.') }} ₫
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-start justify-between gap-4">
        <a href="{{ route('customer.orders') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-black">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15 18-6-6 6-6" />
            </svg>
            Quay lại lịch sử đơn hàng
        </a>
        <div data-order-cancel="{{ $order->id }}" @class(['ml-auto flex flex-col items-end gap-2', 'hidden' => $order->status !== 'pending'])>
            <form data-order-form data-order-cancel-form data-order-id="{{ $order->id }}" action="{{ route('customer.orders.cancel', $order->id) }}" method="POST">
                @csrf
                <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-50">Hủy đơn</button>
            </form>
            <p class="max-w-xs text-right text-xs text-gray-500">Có thể hủy trước khi cửa hàng nhận đơn.</p>
        </div>
    </div>
    <div data-order-contact="{{ $order->id }}" @class(['mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600', 'hidden' => $order->status !== 'processing'])>
        Đơn đã được cửa hàng tiếp nhận. Nếu cần hủy, vui lòng liên hệ cửa hàng và cung cấp mã <strong class="text-gray-900">{{ $order->order_code }}</strong> để được kiểm tra hỗ trợ.
    </div>
</div>
@endsection