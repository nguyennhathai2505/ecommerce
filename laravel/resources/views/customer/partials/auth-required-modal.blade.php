<div id="auth-required-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/40 px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="auth-required-title">
    <div class="w-full max-w-[440px] rounded-2xl bg-white p-5 shadow-xl sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="auth-required-title" class="text-lg font-semibold text-gray-900"></h2>
                <p id="auth-required-message" class="mt-1 text-sm leading-5 text-gray-600"></p>
            </div>
            <button type="button" id="close-auth-modal" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Đóng">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-2">
            <a id="auth-required-primary" href="{{ route('login') }}" class="rounded-lg bg-black px-4 py-2.5 text-center text-sm font-medium text-white transition hover:bg-gray-800"></a>
            <a id="auth-required-secondary" href="{{ route('register') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-center text-sm font-medium text-gray-700 transition hover:bg-gray-50"></a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('auth-required-modal');
    const title = document.getElementById('auth-required-title');
    const message = document.getElementById('auth-required-message');
    const primaryAction = document.getElementById('auth-required-primary');
    const secondaryAction = document.getElementById('auth-required-secondary');
    const closeButton = document.getElementById('close-auth-modal');

    function hideModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    window.showAuthRequiredModal = function(options) {
        title.textContent = options.title;
        message.textContent = options.message;
        primaryAction.textContent = options.primaryText;
        primaryAction.href = options.primaryUrl;
        secondaryAction.textContent = options.secondaryText;
        secondaryAction.href = options.secondaryUrl;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        closeButton.focus();
    };

    closeButton.addEventListener('click', hideModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            hideModal();
        }
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('flex')) {
            hideModal();
        }
    });

    function showWishlistLogin(trigger, event) {
        if (!trigger) return;

        event.preventDefault();
        window.showAuthRequiredModal({
            title: 'Đăng nhập để sử dụng danh sách yêu thích',
            message: trigger.dataset.authMessage || 'Đăng nhập để lưu và quản lý những sản phẩm bạn yêu thích.',
            primaryText: 'Đăng nhập',
            primaryUrl: '{{ route('login') }}',
            secondaryText: 'Đăng ký',
            secondaryUrl: '{{ route('register') }}',
        });
    }

    document.addEventListener('click', function(e) {
        showWishlistLogin(e.target.closest('[data-auth-required="wishlist"]'), e);
    });
    document.addEventListener('submit', function(e) {
        showWishlistLogin(e.target.closest('[data-auth-required="wishlist"]'), e);
    });
});
</script>
