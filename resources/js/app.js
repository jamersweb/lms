import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Tazkiya Tarbya - Courses';

// Show runtime errors on page; keep overlay on top (don't clear body) so it stays visible and isn't replaced
function showErrorOnPage(err, context) {
  try {
    if (document.getElementById('inertia-error-overlay')) return; // already showing
    var msg = err && (err.message || (typeof err.toString === 'function' ? err.toString() : String(err))) || String(err);
    var stack = (err && err.stack) ? err.stack : '';
    var text = (context || 'App Error') + '\n\n' + msg + '\n\n' + stack;
    try { sessionStorage.setItem('inertia_last_error', text); } catch (_) {}
    var div = document.createElement('div');
    div.id = 'inertia-error-overlay';
    div.style.cssText = 'position:fixed;inset:0;background:#1e293b;color:#f1f5f9;padding:24px;font-family:monospace;font-size:14px;overflow:auto;z-index:2147483647;white-space:pre-wrap;';
    div.innerHTML = '<strong style="color:#f87171;">' + (context || 'App Error') + '</strong>\n\n' + msg + '\n\n' + stack +
      '<div style="margin-top:16px;"><button type="button" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById(\'inertia-error-overlay\').innerText);this.textContent=\'Copied!\';" style="padding:8px 16px;background:#475569;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;">Copy error</button></div>';
    document.body.appendChild(div);
  } catch (_) {}
}
window.addEventListener('error', function (e) {
  showErrorOnPage(e.error || new Error(e.message), 'Uncaught');
});
window.addEventListener('unhandledrejection', function (e) {
  const r = e.reason;
  if (r && r.isAxiosError === true && r.message === 'Network Error') {
    e.preventDefault();
    if (typeof window.showToast === 'function') window.showToast('Connection problem. Please check your network and try again.', 'error');
    return;
  }
  showErrorOnPage(r instanceof Error ? r : new Error(String(r)), 'Unhandled promise');
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin);
        app.use(ZiggyVue);

        app.config.errorHandler = (err, instance, info) => {
            showErrorOnPage(err, 'Vue Error (' + (info || '') + '):');
        };

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
