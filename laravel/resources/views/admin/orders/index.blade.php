@extends('layouts.admin')

@section('title', 'Quản lý đơn hàng')

@section('content')
<div class="max-w-6xl mx-auto" data-order-realtime data-order-channel="admin.orders" data-order-status-url="{{ route('admin.orders.statuses') }}">
    <h1 class="text-2xl font-bold mb-6">Quản lý đơn hàng</h1>

    @include('customer.partials.toast')

    <form action="{{ route('admin.orders.search') }}" method="GET" id="order-search-form" class="mb-6 rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="flex w-full max-w-3xl flex-col rounded-lg border border-gray-300 bg-white sm:flex-row sm:divide-x sm:divide-gray-200">
                <div class="relative flex-1">
                    <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4h3l1.5 4-2 1.5a15 15 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2C10.82 19 5 13.18 5 6a2 2 0 0 1 2-2Z" />
                    </svg>
                    <input
                        type="tel"
                        name="phone"
                        id="phone"
                        aria-label="Số điện thoại khách hàng"
                        value="{{ old('phone', request()->routeIs('admin.orders.search') ? request('phone', '') : '') }}"
                        maxlength="20"
                        placeholder="Số điện thoại khách hàng"
                        class="w-full rounded-t-lg bg-transparent py-3 pl-12 pr-4 text-sm text-gray-900 outline-none sm:rounded-l-lg sm:rounded-tr-none"
                    >
                </div>
                <div class="relative flex-1 border-t border-gray-200 sm:border-t-0">
                    <button type="button" id="open-order-date-picker" aria-label="Chọn ngày đặt" class="absolute left-0 top-0 flex h-full w-12 items-center justify-center rounded-lg text-gray-400 hover:text-black focus-visible:outline focus-visible:outline-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                    </svg>
                    </button>
                    <input type="date" id="order-date-picker" tabindex="-1" aria-label="Lịch chọn ngày đặt" class="absolute bottom-0 left-0 h-px w-px opacity-0 pointer-events-none">
                    <input
                        type="text"
                        name="orderDates"
                        id="orderDates"
                        aria-label="Ngày đặt hàng"
                        placeholder="Ngày đặt (dd-mm-yyyy)"
                        maxlength="10"
                        inputmode="numeric"
                        value="{{ old('orderDates', request()->routeIs('admin.orders.search') ? request('orderDates', '') : '') }}"
                        class="w-full rounded-b-lg bg-transparent py-3 pl-12 pr-4 text-sm text-gray-900 outline-none sm:rounded-b-none sm:rounded-r-lg"
                    >
                </div>
            </div>
            <button type="submit" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-black">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
                </svg>
                Tìm kiếm
            </button>
            @if(request()->routeIs('admin.orders.search') && (request()->filled('phone') || request()->filled('orderDates')))
                <a href="{{ route('admin.orders.index') }}" class="shrink-0 px-2 py-3 text-center text-sm font-medium text-gray-600 hover:text-black hover:underline">
                    Xóa bộ lọc
                </a>
            @endif
        </div>
        @if($errors->has('phone') || $errors->has('orderDates'))
            <div class="mt-2 space-y-1">
                @error('phone')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
                @error('orderDates')
                    <p class="text-sm text-red-500">Vui lòng nhập ngày hợp lệ theo định dạng dd-mm-yyyy.</p>
                @enderror
            </div>
        @endif
    </form>

    <p class="mb-4 text-sm text-gray-600">
        Hiển thị <span class="font-medium text-gray-900">{{ $orders instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $orders->total() : $orders->count() }}</span> đơn hàng
    </p>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left text-sm font-medium text-gray-500 px-4 py-3">Mã đơn hàng</th>
                    <th class="text-left text-sm font-medium text-gray-500 px-4 py-3">Khách hàng</th>
                    <th class="text-left text-sm font-medium text-gray-500 px-4 py-3">Ngày đặt</th>
                    <th class="text-left text-sm font-medium text-gray-500 px-4 py-3">Tổng tiền</th>
                    <th class="text-left text-sm font-medium text-gray-500 px-4 py-3">Trạng thái</th>
                    <th class="text-center text-sm font-medium text-gray-500 px-4 py-3">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $order->order_code }}</td>
                        <td class="px-4 py-3">
                            <div class="text-sm">
                                <div class="font-medium text-gray-900">{{ $order->customer_name }}</div>
                                <div class="text-gray-500">{{ $order->customer_phone }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ number_format($order->total_amount, 0, ',', '.') }} ₫</td>
                        <td class="px-4 py-3">
                            <x-admin.order-status :order="$order" />
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-black focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Xem chi tiết
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            Chưa có đơn hàng nào.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $orders->hasPages())
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('order-search-form');
        const dateInput = document.getElementById('orderDates');
        const picker = document.getElementById('order-date-picker');

        function syncPicker() {
            const match = dateInput.value.trim().match(/^(\d{2})-(\d{2})-(\d{4})$/);
            picker.value = match ? `${match[3]}-${match[2]}-${match[1]}` : '';
        }

        syncPicker();
        dateInput.addEventListener('input', syncPicker);
        picker.addEventListener('change', function () {
            const [year, month, day] = picker.value.split('-');
            dateInput.value = picker.value ? `${day}-${month}-${year}` : '';
        });

        document.getElementById('open-order-date-picker').addEventListener('click', function () {
            syncPicker();
            if (typeof picker.showPicker === 'function') {
                picker.showPicker();
            } else {
                picker.className = 'block w-full rounded-lg border border-gray-300 p-2';
                picker.tabIndex = 0;
                picker.focus();
                picker.click();
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const url = new URL(form.action);
            const phone = document.getElementById('phone').value.trim();
            const orderDates = document.getElementById('orderDates').value.trim();

            if (phone) url.searchParams.set('phone', phone);
            if (orderDates) url.searchParams.set('orderDates', orderDates);

            window.location.assign(url.toString());
        });
    });
</script>
@endpush
