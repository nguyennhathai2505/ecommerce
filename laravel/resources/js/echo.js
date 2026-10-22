import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Chỉ mở kết nối khi một trang cần realtime gọi hàm này.
export function getEcho() {
    if (!import.meta.env.VITE_REVERB_APP_KEY) {
        return null;
    }

    if (!window.Echo) {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            Pusher,
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT || 80),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    }

    return window.Echo;
}
