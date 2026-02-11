import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

// Laravel CSRF: send XSRF-TOKEN cookie as X-XSRF-TOKEN header
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Always use same-origin: force relative URL so we never hit wrong APP_URL (avoids Network Error on production)
window.axios.interceptors.request.use((config) => {
  if (typeof config.url === 'string' && (config.url.startsWith('http://') || config.url.startsWith('https://'))) {
    try {
      const u = new URL(config.url);
      config.url = u.pathname + u.search;
    } catch (_) {}
  }
  return config;
});

// Handle network errors so we don't get unhandled promise rejection
window.axios.interceptors.response.use(
  (r) => r,
  (err) => {
    if (err.message === 'Network Error' && typeof window.showToast === 'function') {
      window.showToast('Connection problem. Please check your network and try again.', 'error');
    }
    return Promise.reject(err);
  }
);
