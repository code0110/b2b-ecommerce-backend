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
    setRoleFromUser(user) {
  // folosim fie r.code, fie r.slug, în funcție de ce întoarce backend-ul
  const roles = (user.roles || []).map((r) => r.code || r.slug);

  if (
    roles.some((r) =>
      ['admin', 'operator', 'marketer', 'sales_agent', 'sales_director'].includes(
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
  },
});
