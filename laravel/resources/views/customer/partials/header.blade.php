<header class="bg-white border-b border-gray-200 sticky top-0 z-50">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16 md:h-20">
            <!-- Logo -->
            <a href="/" class="text-2xl font-bold text-gray-900 flex-shrink-0">LOGO</a>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-6 lg:space-x-8">
                <a href="{{ route('home') }}" class="text-gray-700 hover:text-black text-sm font-medium {{ request()->routeIs('home') ? 'font-semibold text-black' : '' }}">Trang chủ</a>
                <a href="{{ route('products.index') }}" class="text-gray-700 hover:text-black text-sm font-medium {{ request()->routeIs('products.index') ? 'font-semibold text-black' : '' }}">Sản phẩm</a>
                <a href="#" class="text-gray-700 hover:text-black text-sm font-medium">Giới thiệu</a>
                <a href="#" class="text-gray-700 hover:text-black text-sm font-medium">Liên hệ</a>
            </nav>

            <!-- Right Actions -->
            <div class="flex items-center space-x-3 sm:space-x-4">
                <!-- Search (Desktop) -->
                <form action="{{ route('products.index') }}" method="GET" class="hidden lg:flex items-center border border-gray-300 rounded-lg px-3 py-1.5">
                    <input type="text" name="search" placeholder="Tìm kiếm..." class="outline-none text-sm w-40 xl:w-56" value="{{ request('search') }}">
                    <button type="submit" class="ml-2 text-gray-500 hover:text-black">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>

                <!-- Cart -->
                <div class="relative">
                    <button id="cart-toggle" class="text-gray-700 hover:text-black relative">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span class="absolute -top-1 -right-1 bg-black text-white text-[10px] leading-none rounded-full h-4 w-4 flex items-center justify-center">0</span>
                    </button>
                    @include('customer.partials.cart-mini')
                </div>

                <!-- Wishlist -->
                <a href="{{ route('customer.wishlist.index') }}"
                   class="relative text-gray-500 transition hover:text-red-500"
                   aria-label="Danh sách yêu thích">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span id="wishlist-count" class="absolute -right-2 -top-2 hidden h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] leading-none text-white">0</span>
                </a>

                <!-- Auth -->
                @auth
                    <a href="{{ route('customer.dashboard') }}" class="text-gray-700 hover:text-black flex items-center space-x-1 text-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline text-gray-700 hover:text-black text-sm">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="hidden sm:inline bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition text-sm">Đăng ký</a>
                @endauth

                <!-- Mobile Menu Toggle -->
                <button id="mobile-menu-toggle" class="md:hidden text-gray-700 hover:text-black">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="hidden md:hidden pb-4 border-t border-gray-200">
            <nav class="flex flex-col space-y-3 pt-4">
                <a href="{{ route('home') }}" class="text-gray-700 hover:text-black text-sm">Trang chủ</a>
                <a href="{{ route('products.index') }}" class="text-gray-700 hover:text-black text-sm">Sản phẩm</a>
                <a href="#" class="text-gray-700 hover:text-black text-sm">Giới thiệu</a>
                <a href="#" class="text-gray-700 hover:text-black text-sm">Liên hệ</a>
                <!-- Mobile Search -->
                <form action="{{ route('products.index') }}" method="GET" class="flex items-center border border-gray-300 rounded-lg px-3 py-1.5 mt-2">
                    <input type="text" name="search" placeholder="Tìm kiếm..." class="outline-none text-sm w-full" value="{{ request('search') }}">
                    <button type="submit" class="ml-2 text-gray-500 hover:text-black">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
                @guest
                    <div class="flex space-x-3 pt-2">
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-black text-sm">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition text-sm">Đăng ký</a>
                    </div>
                @endguest
            </nav>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        menuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });

        // Cart toggle
        const cartToggle = document.getElementById('cart-toggle');
        const cartMini = document.querySelector('#cart-toggle + div');
        if (cartToggle && cartMini) {
            cartToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                cartMini.classList.toggle('hidden');
            });
            document.addEventListener('click', function() {
                cartMini.classList.add('hidden');
            });
            cartMini.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });

// ===== CẬP NHẬT SỐ LƯỢNG GIỎ HÀNG =====
function updateCartCount(count) {
    const badge = document.querySelector('#cart-toggle span');
    if (badge) {
        badge.textContent = count;
    }
}

function getCartCount() {
    fetch('/cart/count', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.cart_count !== undefined) {
            updateCartCount(data.cart_count);
        }
    })
    .catch(error => console.error('Error fetching cart count:', error));
}

// Gọi khi trang load
document.addEventListener('DOMContentLoaded', function() {
    getCartCount();
});

// Gọi lại khi có sự kiện thêm vào giỏ hàng
window.updateCartCount = updateCartCount;

// ===== CẬP NHẬT SỐ LƯỢNG WISHLIST =====
function updateWishlistCount(count) {
    const badge = document.getElementById('wishlist-count');
    if (!badge) return;

    badge.textContent = count;
    badge.classList.toggle('hidden', count <= 0);
    badge.classList.toggle('flex', count > 0);
}

function getWishlistCount() {
    fetch('{{ route("customer.wishlist.count") }}', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.wishlist_count !== undefined) {
            updateWishlistCount(data.wishlist_count);
        }
    })
    .catch(error => console.error('Error fetching wishlist count:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    getWishlistCount();
});

// ===== CẬP NHẬT CART MINI =====
function loadCartMini() {
    // Reload lại cart mini bằng AJAX
    fetch('{{ route("cart.mini") }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    })
    .then(response => response.text())
    .then(html => {
        const cartMiniContainer = document.querySelector('#cart-mini');
        if (cartMiniContainer) {
            // Cập nhật nội dung cart mini
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const newCartMini = tempDiv.querySelector('#cart-mini');
            if (newCartMini) {
                cartMiniContainer.outerHTML = newCartMini.outerHTML;
            }
            // Cập nhật badge
            const count = parseInt(document.querySelector('#cart-mini .flex.justify-between.items-center span.text-xs')?.textContent) || 0;
            updateCartCount(count);
        }
    })
    .catch(error => console.error('Error loading cart mini:', error));
}

// Gắn hàm vào global để sử dụng ở các nơi khác
window.loadCartMini = loadCartMini;
    
</script>