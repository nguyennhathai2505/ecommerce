<div id="confirm-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/40 px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-message">
    <div class="w-full max-w-[400px] rounded-2xl bg-white p-5 shadow-xl sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="confirm-modal-title" class="text-lg font-semibold text-gray-900"></h2>
                <p id="confirm-modal-message" class="mt-1 text-sm leading-5 text-gray-600"></p>
            </div>
            <button type="button" id="close-confirm-modal" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Đóng">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" id="cancel-confirm-modal" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Hủy
            </button>
            <button type="button" id="confirm-modal-action" class="rounded-lg bg-red-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-600">
                Xác nhận
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('confirm-modal');
    const title = document.getElementById('confirm-modal-title');
    const message = document.getElementById('confirm-modal-message');
    const confirmButton = document.getElementById('confirm-modal-action');
    const cancelButton = document.getElementById('cancel-confirm-modal');
    const closeButton = document.getElementById('close-confirm-modal');
    let confirmAction = null;
    let previousActiveElement = null;

    function hideModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        confirmAction = null;
        previousActiveElement?.focus();
    }

    window.showConfirmModal = function(options) {
        previousActiveElement = document.activeElement;
        title.textContent = options.title;
        message.textContent = options.message;
        confirmButton.textContent = options.confirmText || 'Xác nhận';
        confirmAction = options.onConfirm;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        confirmButton.focus();
    };

    confirmButton.addEventListener('click', function() {
        const action = confirmAction;
        hideModal();
        action?.();
    });
    cancelButton.addEventListener('click', hideModal);
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
});
</script>
