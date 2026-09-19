@extends('layouts.customer')

@section('title', $product->name)

@section('breadcrumb')
    @include('customer.partials.breadcrumb', ['items' => [
        ['label' => 'Sản phẩm', 'url' => route('products.index')],
        ['label' => $product->name]
    ]])
@endsection

@section('content')
    @php
        $shoe = $product->shoe;
        $cloth = $product->cloth;
        $images = $shoe ? $shoe->images()->orderBy('display_order')->get() : ($cloth ? $cloth->images()->orderBy('display_order')->get() : collect());
        $colors = $images->pluck('color')->unique();
        $primaryImage = $images->where('is_primary', true)->first();
        $firstImage = $images->first();
        $defaultImage = $primaryImage ?? $firstImage;
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
        <!-- Cột trái: Ảnh chính + Gallery -->
        <div class="space-y-4">
            <!-- Ảnh chính -->
            <div class="bg-gray-100 rounded-lg aspect-square flex items-center justify-center relative overflow-hidden">
                <div id="main-image-container">
                    @if($defaultImage)
                        <img src="{{ $defaultImage->image_url }}" id="main-product-image" class="w-full h-full object-cover">
                    @else
                        <svg class="w-16 h-16 sm:w-24 sm:h-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    @endif
                </div>
            </div>

            <!-- Gallery ảnh (nằm dưới ảnh chính) -->
            @if($images->count() > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($images->groupBy('color') as $color => $colorImages)
                    <div class="flex-shrink-0">
                        <div class="text-xs text-gray-500 mb-1">{{ $color }}</div>
                        <div class="flex gap-1">
                            @foreach($colorImages as $image)
                                <img src="{{ $image->image_url }}" 
                                     class="w-12 h-12 object-cover rounded border-2 {{ $loop->first && $color == ($defaultImage->color ?? $firstImage->color ?? '') ? 'border-black' : 'border-transparent' }} hover:border-gray-400 cursor-pointer thumbnail"
                                     data-full="{{ $image->image_url }}"
                                     data-color="{{ $color }}"
                                     onclick="changeMainImage(this)">
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Cột phải: Thông tin sản phẩm -->
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2">{{ number_format($product->base_price, 0, ',', '.') }} ₫</p>
            <div class="mt-3 flex items-center space-x-2">
                <span class="text-yellow-400">★★★★★</span>
                <span class="text-xs sm:text-sm text-gray-500">(0 đánh giá)</span>
            </div>

            <div class="mt-5 sm:mt-6 space-y-4">
                <!-- Size -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Size</label>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @php $sizes = $product->variants->pluck('size')->unique()->sort()->values(); @endphp
                        @forelse($sizes as $size)
                            <button class="size-option border border-gray-300 rounded-lg px-3 py-1.5 sm:px-4 sm:py-2 text-sm hover:border-black transition" data-size="{{ $size }}">
                                {{ $size }}
                            </button>
                        @empty
                            <span class="text-gray-500 text-sm">Không có size</span>
                        @endforelse
                    </div>
                </div>

                <!-- Color -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Màu sắc</label>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @php $colors = $product->variants->pluck('color')->unique()->values(); @endphp
                        @forelse($colors as $color)
                            <button class="color-option border border-gray-300 rounded-lg px-3 py-1.5 sm:px-4 sm:py-2 text-sm hover:border-black transition" data-color="{{ $color }}">
                                {{ $color }}
                            </button>
                        @empty
                            <span class="text-gray-500 text-sm">Không có màu</span>
                        @endforelse
                    </div>
                </div>

                <!-- Stud Type (if shoe) -->
                @if($product->product_type === 'SHOE')
                <div>
                    <label class="block text-sm font-medium text-gray-700">Loại đinh</label>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @php $studTypes = $product->variants->pluck('stud_type')->unique()->values(); @endphp
                        @forelse($studTypes as $type)
                            <button class="stud-option border border-gray-300 rounded-lg px-3 py-1.5 sm:px-4 sm:py-2 text-sm hover:border-black transition" data-stud="{{ $type }}">
                                {{ $type }}
                            </button>
                        @empty
                            <span class="text-gray-500 text-sm">Không có loại đinh</span>
                        @endforelse
                    </div>
                </div>
                @endif

                <!-- Quantity -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Số lượng</label>
                    <div class="flex items-center space-x-2 mt-1">
                        <button id="decrease-qty" class="border border-gray-300 rounded-lg px-3 py-1.5 hover:bg-gray-50 text-sm">−</button>
                        <input type="number" id="quantity" value="1" min="1" class="w-14 sm:w-16 text-center border border-gray-300 rounded-lg py-1.5 text-sm">
                        <button id="increase-qty" class="border border-gray-300 rounded-lg px-3 py-1.5 hover:bg-gray-50 text-sm">+</button>
                    </div>
                </div>

            <!-- Buttons -->
            <div class="mt-5 sm:mt-6 flex flex-col sm:flex-row gap-3">
                <button id="add-to-cart" class="flex-1 bg-black text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg hover:bg-gray-800 transition font-medium text-sm sm:text-base">
                    Thêm vào giỏ hàng
                </button>
                <button id="buy-now" class="flex-1 bg-green-600 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg hover:bg-green-700 transition font-medium text-sm sm:text-base">
                    Mua ngay
                </button>
                @include('customer.partials.wishlist-button', [
                    'product' => $product,
                    'variant' => 'detail',
                ])
            </div>
            </div>
        </div>
    </div>

    <!-- Description -->
    <div class="mt-10 sm:mt-12">
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3">Mô tả sản phẩm</h3>
        <div class="prose prose-sm max-w-none text-gray-600">
            {!! nl2br(e($product->description ?? 'Chưa có mô tả cho sản phẩm này.')) !!}
        </div>
    </div>

    <!-- Related Products -->
    @if($relatedProducts->count())
        <div class="mt-10 sm:mt-12">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Sản phẩm liên quan</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4">
                @foreach($relatedProducts as $related)
                    @include('customer.partials.product-card', ['product' => $related])
                @endforeach
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity
        const qtyInput = document.getElementById('quantity');
        document.getElementById('decrease-qty').addEventListener('click', function() {
            let val = parseInt(qtyInput.value) || 1;
            if (val > 1) qtyInput.value = val - 1;
        });
        document.getElementById('increase-qty').addEventListener('click', function() {
            let val = parseInt(qtyInput.value) || 1;
            qtyInput.value = val + 1;
        });

        // Variant selection visual feedback
        document.querySelectorAll('.size-option, .color-option, .stud-option').forEach(btn => {
            btn.addEventListener('click', function() {
                const parent = this.parentElement;
                parent.querySelectorAll('button').forEach(b => b.classList.remove('border-black', 'bg-gray-100'));
                this.classList.add('border-black', 'bg-gray-100');
            });
        });

        // Add to cart (demo)
        document.getElementById('add-to-cart')?.addEventListener('click', function() {
            alert('Thêm vào giỏ hàng thành công!');
        });

        // Hàm changeMainImage
        window.changeMainImage = function(el) {
            document.getElementById('main-product-image').src = el.dataset.full;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('border-black'));
            el.classList.add('border-black');
        };

            // Mua ngay
document.getElementById('buy-now')?.addEventListener('click', function() {
    const sizeBtn = document.querySelector('.size-option.border-black');
    const colorBtn = document.querySelector('.color-option.border-black');
    const studBtn = document.querySelector('.stud-option.border-black');
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
    
    if (!sizeBtn || !colorBtn) {
        alert('Vui lòng chọn size và màu sắc.');
        return;
    }
    
    const size = sizeBtn.dataset.size;
    const color = colorBtn.dataset.color;
    const studType = studBtn ? studBtn.dataset.stud : null;
    
    // Thêm vào giỏ hàng trước khi chuyển đến checkout
    const productId = {{ $product->id }};
    
    fetch('{{ route("api.cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            product_id: productId,
            size: size,
            color: color,
            stud_type: studType,
            quantity: quantity,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (window.updateCartCount) {
                window.updateCartCount(data.cart_count);
            }
            window.location.href = '{{ route("checkout") }}';
        } else {
            alert(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
    });
});


    });
</script>
@endpush