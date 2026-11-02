@extends('layouts.customer')

@section('title', 'Lịch sử đơn hàng')

@section('content')
<div class="max-w-4xl mx-auto" data-order-realtime data-order-channel="customer.orders.{{ auth()->id() }}" data-order-status-url="{{ route('customer.orders.statuses') }}">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Lịch sử đơn hàng</h1>

    @if($orders->count() > 0)
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left text-sm font-medium text-gray-500 px-4 py-3">Mã đơn hàng</th>
                        <th class="text-left text-sm font-medium text-gray-500 px-4 py-3">Ngày đặt</th>
                        <th class="text-left text-sm font-medium text-gray-500 px-4 py-3">Tổng tiền</th>
                        <th class="text-left text-sm font-medium text-gray-500 px-4 py-3">Trạng thái</th>
                        <th class="text-center text-sm font-medium text-gray-500 px-4 py-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $order->order_code }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ number_format($order->total_amount, 0, ',', '.') }} ₫</td>
                            <td class="px-4 py-3">
                                <x-order-status :order="$order" />
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('customer.orders.show', $order->id) }}" class="text-sm text-black hover:underline">
                                    Xem chi tiết
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <p class="mt-4 text-gray-500">Bạn chưa có đơn hàng nào.</p>
            <a href="{{ route('products.index') }}" class="mt-4 inline-block bg-black text-white px-6 py-2 rounded-lg hover:bg-gray-800 transition">
                Mua sắm ngay
            </a>
        </div>
    @endif
    @if($orders->hasPages())
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</div>
@endsection