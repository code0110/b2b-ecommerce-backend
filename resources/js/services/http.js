// resources/js/services/http.js
import axios from 'axios';
import { useAuthStore } from '@/store/auth';

// Instanța principală pentru front (/api/...)
const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
  },
});

// Instanță separată pentru admin (/api/admin/...)
const adminApi = axios.create({
  baseURL: '/api/admin',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
  },
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
      
      // Impersonare client (Agent/Director)
      const impersonatedClientId = localStorage.getItem('impersonated_client_id');
      if (impersonatedClientId) {
        config.headers['X-Impersonated-Client-Id'] = impersonatedClientId;
        // Backend CartController expects X-Customer-ID for resolving the cart
        // Only set if not already set (e.g. by QuickOrder)
        if (!config.headers['X-Customer-ID']) {
            config.headers['X-Customer-ID'] = impersonatedClientId;
        }
      }
    }

    return config;
  });

  instance.interceptors.response.use(
    (response) => response,
    async (error) => {
      const isLogoutRequest = error.config?.url?.includes('/auth/logout');
      
      if (axios.isCancel(error) || error.code === 'ERR_CANCELED') {
        return new Promise(() => {});
      }

      if (error.response?.status === 401 && !isLogoutRequest) {
        const authStore = useAuthStore?.();
        if (authStore) {
          // Putem curăța starea locală direct fără să mai apelăm API-ul dacă suntem deja 401
          // Sau apelăm logout care face try/catch pe request
          await authStore.logout(); 
          
          // Check if current route requires auth before redirecting
          // Use dynamic import to avoid circular dependency
          try {
            const router = (await import('@/router')).default;
            if (router.currentRoute.value.meta.requiresAuth) {
               router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } });
            }
          } catch (e) {
             // Fallback if router access fails
             window.location.href = '/login';
          }
          
          return new Promise(() => {});
        }
      }
      return Promise.reject(error);
    }
  );
}

// atașăm interceptorii
attachInterceptors(api, { attachCartSession: true });
attachInterceptors(adminApi, { attachCartSession: false });

// export default pentru front, named export pentru admin
export { adminApi };
export default api;
