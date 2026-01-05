import { defineStore } from 'pinia';
import {
  apiLogin,
  apiLogout,
  apiFetchMe,
} from '@/services/auth';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('current_user') || 'null'),
    token: localStorage.getItem('access_token') || null,
    role: localStorage.getItem('role') || null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
  },

  actions: {
    hasRole(roleName) {
      if (!this.user || !this.user.roles) return false;
      return this.user.roles.some(r => (r.slug === roleName || r.code === roleName));
    },

    setRoleFromUser(user) {
  // folosim fie r.code, fie r.slug, în funcție de ce întoarce backend-ul
  const roles = (user.roles || []).map((r) => r.code || r.slug);

  if (
      roles.some((r) =>
        ['admin', 'operator'].includes(
          String(r).toLowerCase(),
        ),
      )
    ) {
      this.role = 'admin';
    } else {
      this.role = 'customer';
    }

  localStorage.setItem('role', this.role);
},

    async login({ email, password, remember }) {
      this.loading = true;
      this.error = null;

      try {
        const { token, user } = await apiLogin(email, password, remember);

        this.token = token;
        this.user = user;

        localStorage.setItem('access_token', token);
        localStorage.setItem('current_user', JSON.stringify(user));

        this.setRoleFromUser(user);

        return user;
      } catch (error) {
        this.error =
          error.response?.data?.message || 'Autentificare eșuată';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      try {
        await apiLogout();
      } catch (_) {}

      this.token = null;
      this.user = null;
      this.role = null;
      this.error = null;

      localStorage.removeItem('access_token');
      localStorage.removeItem('current_user');
      localStorage.removeItem('role');
    },

    async refreshUser() {
      if (!this.token) return;
      try {
        const data = await apiFetchMe();
        this.user = data;
        localStorage.setItem('current_user', JSON.stringify(data));
        this.setRoleFromUser(data);
      } catch (_) {
        await this.logout();
      }
    },

    async startImpersonation(client) {
      if (!client || !client.id) return;

      // Import store dynamically to avoid circular dependency (visit -> adminApi -> auth -> visit)
      const { useVisitStore } = await import('./visit');
      const visitStore = useVisitStore();

      // Dacă utilizatorul este agent, pornim automat o vizită
      const isAgent = this.user?.roles?.some(r => 
        (r.slug === 'sales_agent' || r.code === 'sales_agent')
      );

      if (isAgent) {
        try {
          await visitStore.startVisit(client.id);
        } catch (e) {
          console.error('Eroare la pornirea vizitei automate:', e);
          if (!confirm('Nu s-a putut înregistra vizita automată. Continuați impersonarea?')) {
            return;
          }
        }
      }

      localStorage.setItem('impersonated_client_id', client.id);
      localStorage.setItem('impersonated_client_name', client.name || 'Client');
      // Force reload to apply interceptor
      window.location.href = '/';
    },

    async stopImpersonation() {
      // Opțional: Putem întreba agentul dacă vrea să închidă vizita, sau o lăsăm deschisă.
      // Cerința: "sa poata intra in vizita si sa inchida vizita".
      // Nu specifică "auto-close on stop impersonation".
      // Dar ar fi util. Totuși, poate vrea să rămână în vizită și după ce iese din impersonare?
      // Mai sigur: lăsăm vizita deschisă, dar îi oferim UI să o închidă.
      
      localStorage.removeItem('impersonated_client_id');
      localStorage.removeItem('impersonated_client_name');
      window.location.href = '/';
    },
  },
});
