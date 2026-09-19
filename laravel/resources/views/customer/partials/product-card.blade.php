{{-- @props(['product'])

<div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden group">
    {{-- <a href="{{ route('products.show', $product->slug) }}" class="block">
        <div class="aspect-square bg-gray-100 flex items-center justify-center relative overflow-hidden">
            <!-- Placeholder ảnh sản phẩm -->
            <svg class="w-12 h-12 sm:w-16 sm:h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
    </a> --}}
    {{-- @php
        $primaryImage = $product->product_type === 'SHOE' 
            ? ($product->shoe?->primary_image ?? null)
            : ($product->cloth?->primary_image ?? null);
        $imageUrl = $primaryImage ?: asset('images/placeholder.jpg');
    @endphp

    <a href="{{ route('products.show', $product->slug) }}" class="block">
        <div class="aspect-square bg-gray-100 flex items-center justify-center relative overflow-hidden">
            <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
        </div>
    </a>
    
    <div class="p-3 sm:p-4">
        <a href="{{ route('products.show', $product->slug) }}" class="block">
            <h3 class="text-xs sm:text-sm font-medium text-gray-900 hover:text-black line-clamp-2 min-h-[2.5rem]">{{ $product->name }}</h3>
        </a>
        <div class="mt-2 flex items-center justify-between flex-wrap gap-1">
            <span class="text-sm sm:text-lg font-bold text-gray-900">{{ number_format($product->base_price, 0, ',', '.') }} ₫</span>
            <div class="flex items-center space-x-1">
                <span class="text-yellow-400 text-xs sm:text-sm">★</span>
                <span class="text-xs sm:text-sm text-gray-500">4.5</span>
            </div>
        </div>
        <div class="mt-3 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
            <!-- Button -->
            <button class="w-full sm:flex-1 bg-black text-white text-xs sm:text-sm px-3 py-1.5 sm:py-2 rounded-lg hover:bg-gray-800 transition add-to-cart-btn" 
                    data-product="{{ $product->id }}"
                    data-url="{{ route('api.cart.add') }}">
                Thêm vào giỏ
            </button>
            <button class="w-full sm:w-auto p-1.5 sm:p-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition wishlist-toggle flex items-center justify-center" data-product="{{ $product->id }}">
                <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>
        </div>
    </div>
</div> --}} 
{{-- <script>
// ===== XỬ LÝ THÊM VÀO GIỎ HÀNG =====
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.add-to-cart-btn');
    if (!btn) return;
    
    const productId = btn.dataset.product;
    const url = btn.dataset.url;
    
    // Lấy thông tin variant (mặc định nếu không có)
    // Trên product card, chúng ta không có chọn variant, nên lấy variant đầu tiên
    // Hoặc hiển thị modal chọn variant (sẽ làm sau)
    
    // Tạm thời thêm với variant mặc định
    const defaultVariant = {
        size: '39',
        color: 'Đỏ',
        stud_type: 'TF',
        quantity: 1
    };
    
    // Hiển thị loading
    btn.textContent = 'Đang thêm...';
    btn.disabled = true;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            product_id: productId,
            size: defaultVariant.size,
            color: defaultVariant.color,
            stud_type: defaultVariant.stud_type,
            quantity: defaultVariant.quantity,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật số lượng trên header
            if (window.updateCartCount) {
                window.updateCartCount(data.cart_count);
            }
            
            // Hiển thị thông báo thành công
            btn.textContent = '✓ Đã thêm';
            btn.classList.remove('bg-black', 'hover:bg-gray-800');
            btn.classList.add('bg-green-600', 'hover:bg-green-700');
            
            setTimeout(() => {
                btn.textContent = 'Thêm vào giỏ';
                btn.classList.remove('bg-green-600', 'hover:bg-green-700');
                btn.classList.add('bg-black', 'hover:bg-gray-800');
                btn.disabled = false;
            }, 2000);
        } else {
            alert(data.message || 'Có lỗi xảy ra');
            btn.textContent = 'Thêm vào giỏ';
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
        btn.textContent = 'Thêm vào giỏ';
        btn.disabled = false;
    });
});

</script> --}}



@props(['product'])

<div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden group">
    @php
        $primaryImage = $product->product_type === 'SHOE' 
            ? ($product->shoe?->primary_image ?? null)
            : ($product->cloth?->primary_image ?? null);
        $imageUrl = $primaryImage ?: asset('images/placeholder.jpg');
        
        // Lấy variant đầu tiên của sản phẩm
        $firstVariant = $product->product_type === 'SHOE' 
            ? $product->shoesVariants->first() 
            : $product->clothesVariants->first();
        
        // Lấy size, color, stud_type từ variant
        $defaultSize = $firstVariant?->size ?? '';
        $defaultColor = $firstVariant?->color ?? '';
        $defaultStudType = $firstVariant?->stud_type ?? '';
    @endphp

    <a href="{{ route('products.show', $product->slug) }}" class="block">
        <div class="aspect-square bg-gray-100 flex items-center justify-center relative overflow-hidden">
            <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
        </div>
    </a>
    
    <div class="p-3 sm:p-4">
        <a href="{{ route('products.show', $product->slug) }}" class="block">
            <h3 class="text-xs sm:text-sm font-medium text-gray-900 hover:text-black line-clamp-2 min-h-[2.5rem]">{{ $product->name }}</h3>
        </a>
        <div class="mt-2 flex items-center justify-between flex-wrap gap-1">
            <span class="text-sm sm:text-lg font-bold text-gray-900">{{ number_format($product->base_price, 0, ',', '.') }} ₫</span>
            <div class="flex items-center space-x-1">
                <span class="text-yellow-400 text-xs sm:text-sm">★</span>
                <span class="text-xs sm:text-sm text-gray-500">4.5</span>
            </div>
        </div>
        <div class="mt-3 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
            @if($firstVariant)
                <button class="w-full sm:flex-1 bg-black text-white text-xs sm:text-sm px-3 py-1.5 sm:py-2 rounded-lg hover:bg-gray-800 transition add-to-cart-btn" 
                        data-product-id="{{ $product->id }}"
                        data-variant-id="{{ $firstVariant->id }}"
                        data-variant-type="{{ $product->product_type === 'SHOE' ? 'shoe' : 'cloth' }}"
                        data-size="{{ $defaultSize }}"
                        data-color="{{ $defaultColor }}"
                        data-stud-type="{{ $defaultStudType }}">
                    Thêm vào giỏ
                </button>
            @else
                <button class="w-full sm:flex-1 bg-gray-300 text-gray-500 text-xs sm:text-sm px-3 py-1.5 sm:py-2 rounded-lg cursor-not-allowed" disabled>
                    Hết hàng
                </button>
            @endif
            @include('customer.partials.wishlist-button', [
                'product' => $product,
                'variant' => 'card',
            ])
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra nếu đã attach thì không attach lại
    if (window._cartListenerAttached) {
        console.log('Cart listener already attached, skipping');
        return;
    }
    window._cartListenerAttached = true;

    console.log('Attaching cart listeners for', document.querySelectorAll('.add-to-cart-btn').length, 'buttons');

    // Gắn event trực tiếp cho từng button - KHÔNG DÙNG EVENT DELEGATION
    document.querySelectorAll('.add-to-cart-btn').forEach(function(btn) {
        // Xóa listener cũ bằng cách clone và replace
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        
        // Gắn event mới
        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Ngăn click nhiều lần
            if (this.dataset.processing === 'true') {
                console.log('Already processing, ignoring');
                return;
            }
            
            console.log('Add to cart clicked for product:', this.dataset.productId);
            this.dataset.processing = 'true';
            
            const productId = this.dataset.productId;
            const variantId = this.dataset.variantId;
            const variantType = this.dataset.variantType;
            const size = this.dataset.size;
            const color = this.dataset.color;
            const studType = this.dataset.studType || null;
            
            // Hiển thị loading
            const originalText = this.textContent;
            this.textContent = 'Đang thêm...';
            this.disabled = true;
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            
            fetch('/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    product_id: parseInt(productId),
                    variant_id: parseInt(variantId),
                    variant_type: variantType,
                    size: size,
                    color: color,
                    stud_type: studType || null,
                    quantity: 1,
                }),
            })
            .then(response => response.json())
            .then(data => {
                this.dataset.processing = 'false';
                console.log('Response:', data);
                
                if (data.success) {
                    // Cập nhật số lượng trên header
                    if (window.updateCartCount) {
                        window.updateCartCount(data.cart_count);
                    }
                    
                    // Hiển thị thành công
                    this.textContent = '✓ Đã thêm';
                    this.classList.remove('bg-black', 'hover:bg-gray-800');
                    this.classList.add('bg-green-600', 'hover:bg-green-700');
                    
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.classList.remove('bg-green-600', 'hover:bg-green-700');
                        this.classList.add('bg-black', 'hover:bg-gray-800');
                        this.disabled = false;
                    }, 2000);
                } else {
                    alert(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
                    this.textContent = originalText;
                    this.disabled = false;
                }
            })
            .catch(error => {
                this.dataset.processing = 'false';
                console.error('Error:', error);
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
                this.textContent = originalText;
                this.disabled = false;
            });
        });
    });
});
</script>
@endpush