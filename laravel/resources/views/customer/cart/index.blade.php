@extends('layouts.customer')

@section('title', 'Giỏ hàng')

@section('breadcrumb')
    @include('customer.partials.breadcrumb', ['items' => [['label' => 'Giỏ hàng']]])
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Giỏ hàng</h1>

    @if($cart->items->count() > 0)
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left text-sm font-medium text-gray-500 px-4 py-3">Sản phẩm</th>
                            <th class="text-left text-sm font-medium text-gray-500 px-4 py-3">Biến thể</th>
                            <th class="text-center text-sm font-medium text-gray-500 px-4 py-3">Số lượng</th>
                            <th class="text-right text-sm font-medium text-gray-500 px-4 py-3">Thành tiền</th>
                            <th class="text-center text-sm font-medium text-gray-500 px-4 py-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items">
                        @foreach($cart->items as $item)
                            <tr class="border-b cart-item" data-item-id="{{ $item->id }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 bg-gray-100 rounded flex items-center justify-center flex-shrink-0">
                                            @php
                                                $image = $item->product->product_type === 'SHOE' 
                                                    ? ($item->product->shoe?->primary_image ?? null)
                                                    : ($item->product->cloth?->primary_image ?? null);
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
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-600">
                                        <div>Size: <span class="font-medium">{{ $item->size }}</span></div>
                                        <div>Màu: <span class="font-medium">{{ $item->color }}</span></div>
                                        @if($item->stud_type)
                                            <div>Loại đinh: <span class="font-medium">{{ $item->stud_type }}</span></div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button class="quantity-decrease border border-gray-300 rounded-lg px-2 py-1 hover:bg-gray-50 text-sm">−</button>
                                        <input type="number" class="quantity-input w-14 text-center border border-gray-300 rounded-lg py-1 text-sm" value="{{ $item->quantity }}" min="1">
                                        <button class="quantity-increase border border-gray-300 rounded-lg px-2 py-1 hover:bg-gray-50 text-sm">+</button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">
                                    {{ number_format($item->price * $item->quantity, 0, ',', '.') }} ₫
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button class="remove-item text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-gray-50 px-4 py-3 border-t">
                <div class="flex flex-col sm:flex-row justify-between items-center">
                    <div class="text-lg font-bold text-gray-900">
                        Tổng tiền: <span id="cart-total">{{ number_format($cartTotal, 0, ',', '.') }} ₫</span>
                    </div>
                    <div class="flex space-x-3 mt-3 sm:mt-0">
                        <a href="{{ route('products.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm">
                            Tiếp tục mua sắm
                        </a>
                        <a href="{{ route('checkout') }}" class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition text-sm">
                            Tiến hành thanh toán
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <p class="mt-4 text-gray-500">Giỏ hàng của bạn đang trống.</p>
            <a href="{{ route('products.index') }}" class="mt-4 inline-block bg-black text-white px-6 py-2 rounded-lg hover:bg-gray-800 transition">
                Tiếp tục mua sắm
            </a>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cập nhật số lượng
    document.querySelectorAll('.quantity-decrease').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.closest('.cart-item').querySelector('.quantity-input');
            let val = parseInt(input.value) || 1;
            if (val > 1) val--;
            input.value = val;
            updateCartItem(this.closest('.cart-item'));
        });
    });

    document.querySelectorAll('.quantity-increase').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.closest('.cart-item').querySelector('.quantity-input');
            let val = parseInt(input.value) || 1;
            val++;
            input.value = val;
            updateCartItem(this.closest('.cart-item'));
        });
    });

    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            if (parseInt(this.value) < 1) this.value = 1;
            updateCartItem(this.closest('.cart-item'));
        });
    });

    // Xóa sản phẩm
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
                const row = this.closest('.cart-item');
                const itemId = row.dataset.itemId;
                removeCartItem(itemId, row);
            }
        });
    });

    function updateCartItem(row) {
        const itemId = row.dataset.itemId;
        const quantity = row.querySelector('.quantity-input').value;

        fetch('/api/cart/update', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                item_id: itemId,
                quantity: quantity,
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateHeaderCartCount(data.cart_count);
                document.getElementById('cart-total').textContent = 
                    new Intl.NumberFormat('vi-VN').format(data.cart_total) + ' ₫';
                location.reload();
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function removeCartItem(itemId, row) {
        fetch('/cart/remove', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                item_id: itemId,
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                row.remove();
                updateHeaderCartCount(data.cart_count);
                document.getElementById('cart-total').textContent = 
                    new Intl.NumberFormat('vi-VN').format(data.cart_total) + ' ₫';
                if (document.querySelectorAll('.cart-item').length === 0) {
                    location.reload();
                }
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function updateHeaderCartCount(count) {
        const badge = document.querySelector('#cart-toggle span');
        if (badge) {
            badge.textContent = count;
        }
    }
});
</script>
@endsection