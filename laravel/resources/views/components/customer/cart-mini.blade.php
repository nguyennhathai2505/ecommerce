{{-- <div id="cart-mini" class="absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-lg shadow-lg border border-gray-200 py-2 hidden z-50">
    <div class="px-4 py-2 border-b border-gray-200">
        <h4 class="font-semibold text-gray-900 text-sm">Giỏ hàng (0)</h4>
    </div>
    <div class="p-4 text-center text-gray-500 text-sm">
        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
        </svg>
        <p class="mt-2">Giỏ hàng trống</p>
    </div>
    <div class="px-4 py-2 border-t border-gray-200">
        <a href='{{ route('cart.index') }}' class="block text-center bg-black text-white py-2 rounded-lg hover:bg-gray-800 transition text-sm">
            Xem giỏ hàng
        </a>
    </div>
</div> --}}
<div id="cart-mini" class="absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-lg shadow-lg border border-gray-200 py-2 hidden z-50">
    <div class="px-4 py-2 border-b border-gray-200 flex justify-between items-center">
        <h4 class="font-semibold text-gray-900 text-sm">Giỏ hàng</h4>
        <span class="text-xs text-gray-500">{{ $cartCount }} sản phẩm</span>
    </div>
    
    <div class="max-h-64 overflow-y-auto">
        @if($cartItems->count() > 0)
            @foreach($cartItems as $item)
                <div class="px-4 py-2 hover:bg-gray-50 flex items-center space-x-3 border-b border-gray-100">
                    <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center flex-shrink-0">
                        @php
                            $image = $item->product->product_type === 'SHOE' 
                                ? ($item->product->shoe?->primary_image ?? null)
                                : ($item->product->cloth?->primary_image ?? null);
                        @endphp
                        @if($image)
                            <img src="{{ $image }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover rounded">
                        @else
                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-900 truncate">{{ $item->product->name }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $item->size }} / {{ $item->color }}
                            @if($item->stud_type) / {{ $item->stud_type }} @endif
                            × {{ $item->quantity }}
                        </p>
                    </div>
                    <span class="text-xs font-medium text-gray-900 flex-shrink-0">
                        {{ number_format($item->price * $item->quantity, 0, ',', '.') }} ₫
                    </span>
                </div>
            @endforeach
        @else
            <div class="p-4 text-center text-gray-500 text-sm">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <p class="mt-2">Giỏ hàng trống</p>
            </div>
        @endif
    </div>
    
    @if($cartItems->count() > 0)
        <div class="px-4 py-2 border-t border-gray-200">
            <div class="flex justify-between text-sm font-medium text-gray-900">
                <span>Tổng cộng:</span>
                <span>{{ number_format($cartTotal, 0, ',', '.') }} ₫</span>
            </div>
        </div>
    @endif
    
    <div class="px-4 py-2 border-t border-gray-200">
        <a href="{{ route('cart.index') }}" class="block text-center bg-black text-white py-2 rounded-lg hover:bg-gray-800 transition text-sm">
            Xem giỏ hàng
        </a>
    </div>
</div>