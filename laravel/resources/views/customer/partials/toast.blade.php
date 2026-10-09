<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2 max-w-xs sm:max-w-sm w-full" aria-live="polite">
    @if (session('success'))
        <div class="toast-message bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-center justify-between animate-slide-in" role="status">
            <span class="flex items-center text-sm">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </span>
            <button type="button" onclick="this.parentElement.remove()" class="ml-3 text-white hover:text-gray-200 flex-shrink-0" aria-label="Đóng thông báo">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="toast-message bg-red-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-center justify-between animate-slide-in" role="alert">
            <span class="flex items-center text-sm">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('error') }}
            </span>
            <button type="button" onclick="this.parentElement.remove()" class="ml-3 text-white hover:text-gray-200 flex-shrink-0" aria-label="Đóng thông báo">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif
</div>

<style>
    .animate-slide-in { animation: slideInRight 0.5s ease forwards; }
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
</style>

<script>
    (function() {
        function dismissToast(toast) {
            toast.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(function() { toast.remove(); }, 500);
        }

        function scheduleToastDismiss(toast) {
            setTimeout(function() { dismissToast(toast); }, 5000);
        }

        window.showToast = function(message, type = 'success') {
            const toast = document.createElement('div');
            const isError = type === 'error';

            toast.className = `toast-message ${isError ? 'bg-red-500' : 'bg-green-500'} text-white px-4 py-3 rounded-lg shadow-lg flex items-center justify-between animate-slide-in`;
            toast.setAttribute('role', isError ? 'alert' : 'status');
            toast.innerHTML = `
                <span class="flex items-center text-sm">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${isError ? 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}" />
                    </svg>
                    <span class="toast-text"></span>
                </span>
                <button type="button" class="toast-close ml-3 text-white hover:text-gray-200 flex-shrink-0" aria-label="Đóng thông báo">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;
            toast.querySelector('.toast-text').textContent = message;
            toast.querySelector('.toast-close').addEventListener('click', function() {
                dismissToast(toast);
            });

            document.getElementById('toast-container').appendChild(toast);
            scheduleToastDismiss(toast);
        };

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toast-message').forEach(function(toast) {
                scheduleToastDismiss(toast);
            });
        });
    })();
</script>