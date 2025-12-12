// resources/js/services/http.js
import axios from 'axios';
import { useAuthStore } from '@/store/auth';

// Instanța principală pentru front (/api/...)
const api = axios.create({
  baseURL: '/api',
  withCredentials: true, // pentru Sanctum / cookie-uri, dacă le folosești
});

// Instanță separată pentru admin (/api/admin/...)
const adminApi = axios.create({
  baseURL: '/api/admin',
  withCredentials: true,
});

// Helper: ID stabil de "sesiune coș" pentru utilizatori guest
function getOrCreateCartSessionId() {
  const key = 'cart_session_id';

  try {
    let id = localStorage.getItem(key);

    if (!id) {
      if (window.crypto?.randomUUID) {
        id = window.crypto.randomUUID();
      } else {
        id =
          'cart_' +
          Math.random().toString(36).slice(2) +
          Date.now().toString(36);
      }

      localStorage.setItem(key, id);
    }

    return id;
  } catch (e) {
    // dacă localStorage nu e disponibil (SSR / incognito strict),
    // generăm un ID pe request (mai puțin ideal, dar nu rupe aplicația)
    return (
      'cart_' +
      Math.random().toString(36).slice(2) +
      Date.now().toString(36)
    );
  }
}

// Funcție comună de atașare a interceptorilor
function attachInterceptors(instance, { attachCartSession = false } = {}) {
  instance.interceptors.request.use((config) => {
    const authStore = useAuthStore?.();

    // Token Bearer, pentru utilizatori autentificați
    if (authStore?.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`;
    }

    // Pentru front (api) – coș guest via X-Cart-Session
    if (attachCartSession) {
      config.headers['X-Cart-Session'] = getOrCreateCartSessionId();
    }

    return config;
  });

  // Poți adăuga aici și interceptoare de răspuns, dacă ai nevoie,
  // ex. pentru 401 / 419 etc.
}

// atașăm interceptorii
attachInterceptors(api, { attachCartSession: true });
attachInterceptors(adminApi, { attachCartSession: false });

// export default pentru front, named export pentru admin
export { adminApi };
export default api;
