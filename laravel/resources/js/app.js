import './bootstrap';
import Alpine from 'alpinejs';
import '@fortawesome/fontawesome-free/css/all.min.css';
import $ from 'jquery';

window.Alpine = Alpine;
Alpine.start();

const orderPage = document.querySelector('[data-order-realtime]');
if (orderPage) {
    import('./orders-realtime').then(({ initOrderRealtime }) => initOrderRealtime(orderPage)).catch(() => {
        const label = orderPage.querySelector('[data-order-connection]');
        if (label) label.textContent = 'Chưa tải được cập nhật tự động. Tải lại trang để xem trạng thái mới.';
    });
}

$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
})