import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT),
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

window.Echo
    .channel('print-agent-test')
    .listen('.print.test', (event) => {
        console.log('Received from Laravel:', event);

        document.body.innerHTML += `
            <div style="
                padding:20px;
                margin:20px;
                background:#111;
                color:#0f0;
                font-size:20px;
            ">
                WebSocket Received: ${event.message}
            </div>
        `;
    });