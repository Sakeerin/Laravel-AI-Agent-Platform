import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { useTasksStore } from './stores/tasks';

let echoInstance = null;

/**
 * Subscribe to private user channels (Soketi / Pusher protocol).
 * Requires BROADCAST_CONNECTION=pusher and matching .env / VITE_* vars.
 */
export function bootRealtime(token, userId) {
    stopRealtime();

    const key = import.meta.env.VITE_PUSHER_APP_KEY;
    if (!token || !userId || !key) {
        return;
    }

    window.Pusher = Pusher;

    const scheme = import.meta.env.VITE_PUSHER_SCHEME || 'https';
    const forceTLS = scheme === 'https';

    const options = {
        broadcaster: 'pusher',
        key,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
        forceTLS,
        encrypted: forceTLS,
        disableStats: true,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
            },
        },
    };

    if (import.meta.env.VITE_PUSHER_HOST) {
        options.wsHost = import.meta.env.VITE_PUSHER_HOST;
        const port = Number(import.meta.env.VITE_PUSHER_PORT || (forceTLS ? 443 : 6001));
        options.wsPort = port;
        options.wssPort = port;
    }

    echoInstance = new Echo(options);

    echoInstance
        .private(`user.${userId}`)
        .listen('.task.updated', (payload) => {
            useTasksStore().applyBroadcastTask(payload);
        });
}

export function stopRealtime() {
    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
    }
    if (window.Pusher) {
        delete window.Pusher;
    }
}
