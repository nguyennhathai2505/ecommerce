@extends('layouts.admin')

@section('title', 'Chi tiết đơn hàng #' . $order->order_code)

@section('content')
<div class="max-w-5xl mx-auto" data-order-realtime data-order-channel="admin.orders" data-order-status-url="{{ route('admin.orders.statuses') }}">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold">Đơn hàng #{{ $order->order_code }}</h1>
        <div class="flex items-center gap-3">
            <x-admin.order-status :order="$order" size="md" />
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:border-gray-900 hover:text-black focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15 18-6-6 6-6" />
                </svg>
                Quay lại
            </a>
        </div>
    </div>

    @include('customer.partials.toast')
    @include('customer.partials.confirm-modal')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Thông tin đơn hàng -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Thông tin đơn hàng</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-600">Mã đơn hàng:</dt>
                    <dd class="font-medium">{{ $order->order_code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Ngày đặt:</dt>
                    <dd class="font-medium">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Phương thức thanh toán:</dt>
                    <dd class="font-medium capitalize">{{ $order->payment_method }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Trạng thái:</dt>
                    <dd>
                        <x-admin.order-status :order="$order" :editable="true" />
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Thông tin giao hàng -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Thông tin giao hàng</h2>
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="text-gray-600">Người nhận:</dt>
                    <dd class="font-medium">{{ $order->customer_name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600">Số điện thoại:</dt>
                    <dd class="font-medium">{{ $order->customer_phone }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600">Địa chỉ:</dt>
                    <dd class="font-medium">{{ $order->shipping_address }}</dd>
                </div>
                @if($order->note)
                <div>
                    <dt class="text-gray-600">Ghi chú:</dt>
                    <dd class="font-medium">{{ $order->note }}</dd>
                </div>
                @endif
                @if($order->user)
                <div>
                    <dt class="text-gray-600">Khách hàng:</dt>
                    <dd class="font-medium">{{ $order->user->name }} ({{ $order->user->email }})</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Chi tiết sản phẩm -->
    <div class="bg-white rounded-lg shadow-sm mt-6 p-6">
        <h2 class="text-lg font-semibold mb-4">Chi tiết sản phẩm</h2>
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
                                        <a href="{{ route('products.show', $item->product->slug) }}" class="text-sm font-medium text-gray-900 hover:text-black" target="_blank">
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
</div>
@endsection