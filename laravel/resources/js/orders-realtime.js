import { getEcho } from './echo';

export function initOrderRealtime(root) {
    const badges = [...root.querySelectorAll('[data-order-status]')];
    const ids = [...new Set(badges.map(node => Number(node.dataset.orderStatus)))];
    const states = new Map(badges.map(node => [Number(node.dataset.orderStatus), { status: node.dataset.orderState }]));
    const busyForms = new Set();
    const connectionLabel = root.querySelector('[data-order-connection]');
    let syncing = null;
    let refreshAgain = false;
    let notifyPending = false;
    let refreshTimer = null;
    let echo = null;
    let subscribed = false;

    function setConnection(message) {
        if (connectionLabel) connectionLabel.textContent = message;
    }

    function renderStatus(data) {
        const id = Number(data.id);
        const changed = states.has(id) && states.get(id).status !== data.status;
        states.set(id, data);

        root.querySelectorAll(`[data-order-status="${id}"]`).forEach(node => {
            node.classList.remove(...node.dataset.statusClasses.split(' '));
            node.classList.add(...data.classes.split(' '));
            node.dataset.statusClasses = data.classes;
            node.dataset.orderState = data.status;
            node.querySelector('[data-order-label]').textContent = data.label;
        });

        root.querySelectorAll(`[data-order-form][data-order-id="${id}"]`).forEach(form => {
            const currentInput = form.querySelector('[name="current_status"]');
            if (currentInput) currentInput.value = data.status;

            const editor = form.querySelector('[data-order-editor]');
            if (editor) {
                editor.disabled = busyForms.has(form) || data.allowed_statuses.length === 0;
                form.querySelector('[data-order-chevron]').classList.toggle('hidden', data.allowed_statuses.length === 0);
            }

            form.querySelectorAll('[data-order-option]').forEach(option => {
                option.classList.toggle('hidden', !data.allowed_statuses.includes(option.dataset.orderOption));
                option.disabled = busyForms.has(form);
            });

            if (changed) form.dispatchEvent(new CustomEvent('order-status-synced', { bubbles: true }));
        });

        root.querySelectorAll(`[data-order-cancel="${id}"]`).forEach(node => node.classList.toggle('hidden', !data.can_cancel));
        root.querySelectorAll(`[data-order-contact="${id}"]`).forEach(node => node.classList.toggle('hidden', data.status !== 'processing'));
        return changed;
    }

    // Gộp các sự kiện gần nhau; chỉ lấy các đơn đang hiển thị trên trang hiện tại.
    function refresh(notify = false) {
        notifyPending ||= notify;
        refreshAgain = true;
        if (syncing) return syncing;

        syncing = (async () => {
            while (refreshAgain) {
                refreshAgain = false;
                const shouldNotify = notifyPending;
                notifyPending = false;

                try {
                    const response = await window.axios.get(root.dataset.orderStatusUrl, {
                        params: { ids },
                        timeout: 10000,
                        headers: { Accept: 'application/json' },
                    });
                    const changed = response.data.data.filter(data => {
                        const didChange = renderStatus(data);
                        const ownRequest = [...busyForms].some(form => Number(form.dataset.orderId) === Number(data.id));
                        return didChange && !ownRequest;
                    });

                    if (shouldNotify && changed.length) {
                        const data = changed[0];
                        const code = badges.find(node => Number(node.dataset.orderStatus) === Number(data.id))?.dataset.orderCode;
                        window.showToast?.(changed.length === 1
                            ? `Đơn ${code || '#' + data.id}: ${data.label}.`
                            : `Có ${changed.length} đơn vừa cập nhật trạng thái.`);
                    }

                    if (subscribed) setConnection('Trạng thái đơn được cập nhật tự động.');
                } catch {
                    setConnection('Chưa lấy được trạng thái mới. Vui lòng kiểm tra kết nối hoặc tải lại trang.');
                }
            }
        })().finally(() => { syncing = null; });

        return syncing;
    }

    function scheduleRefresh() {
        notifyPending = true;
        if (document.hidden || refreshTimer) return;
        refreshTimer = setTimeout(() => {
            refreshTimer = null;
            refresh(true);
        }, 150);
    }

    async function save(form, payload) {
        if (busyForms.has(form)) return;
        busyForms.add(form);
        form.querySelectorAll('button').forEach(button => { button.disabled = true; });

        try {
            const response = await window.axios.post(form.action, payload, {
                timeout: 10000,
                headers: { Accept: 'application/json' },
            });
            renderStatus(response.data.data);
            window.showToast?.(response.data.message);
        } catch (error) {
            const status = error.response?.status;
            const errors = error.response?.data?.errors;
            const message = status === 422 && errors
                ? Object.values(errors).flat()[0]
                : ([403, 404, 409, 419, 429].includes(status)
                    ? (error.response.data.message || 'Không thể thực hiện thao tác với đơn này.')
                    : 'Chưa xác nhận được kết quả. Vui lòng kiểm tra trạng thái đơn trước khi thử lại.');
            window.showToast?.(message, 'error');
        } finally {
            busyForms.delete(form);
            form.querySelectorAll('button').forEach(button => { button.disabled = false; });
            const latest = states.get(Number(form.dataset.orderId));
            if (latest?.allowed_statuses) renderStatus(latest);
            await refresh();
        }
    }

    root.addEventListener('submit', event => {
        const form = event.target.closest('[data-order-form]');
        if (!form || !root.contains(form)) return;
        event.preventDefault();
        if (busyForms.has(form)) return;

        const payload = new FormData(form);
        const cancelling = form.hasAttribute('data-order-cancel-form') || payload.get('status') === 'cancelled';
        if (cancelling && window.showConfirmModal) {
            window.showConfirmModal({
                title: 'Hủy đơn hàng',
                message: payload.get('current_status') === 'processing'
                    ? 'Chỉ hủy khi cửa hàng đã kiểm tra và còn có thể hủy. Tồn kho của đơn sẽ được hoàn lại.'
                    : 'Bạn chắc chắn muốn hủy đơn hàng này? Đơn đã hủy không thể mở lại.',
                confirmText: 'Xác nhận hủy',
                onConfirm: () => save(form, payload),
            });
        } else {
            save(form, payload);
        }
    });

    if (!ids.length) {
        setConnection('Chưa có đơn hàng để cập nhật.');
        return;
    }

    refresh();
    window.addEventListener('focus', () => refresh());
    window.addEventListener('online', () => refresh());
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refresh();
    });

    try {
        echo = getEcho();
        if (!echo) {
            setConnection('Cập nhật tự động chưa được cấu hình. Tải lại trang để xem trạng thái mới.');
            return;
        }

        echo.private(root.dataset.orderChannel)
            .listen('OrderStatusUpdated', event => {
                if (ids.includes(Number(event.order_id))) scheduleRefresh();
            })
            .subscribed(() => {
                subscribed = true;
                setConnection('Trạng thái đơn được cập nhật tự động.');
                refresh();
            })
            .error(() => {
                subscribed = false;
                setConnection('Chưa kết nối được kênh cập nhật. Vui lòng kiểm tra đăng nhập hoặc tải lại trang.');
            });

        echo.connector.pusher.connection.bind('state_change', ({ current }) => {
            if (current !== 'connected') {
                subscribed = false;
                setConnection('Kết nối cập nhật tự động đang gián đoạn. Tải lại trang để xem trạng thái mới.');
            }
        });

        window.addEventListener('pagehide', () => {
            clearTimeout(refreshTimer);
            refreshTimer = null;
            echo.disconnect();
        });
        window.addEventListener('pageshow', event => {
            if (event.persisted) {
                echo.connect();
                refresh();
            }
        });
    } catch {
        setConnection('Chưa kết nối được cập nhật tự động. Bạn vẫn có thể xử lý đơn và tải lại trang.');
    }
}
