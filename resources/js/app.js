import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        // Use ZiggyVue plugin to make route available globally
        app.use(plugin);
        app.use(ZiggyVue);
        
        // Error handling for debugging
        app.config.errorHandler = (err, instance, info) => {
            console.error('Vue Error:', err);
            console.error('Component:', instance);
            console.error('Info:', info);
        };
        
        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
