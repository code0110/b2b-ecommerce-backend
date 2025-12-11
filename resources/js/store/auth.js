import { defineStore } from 'pinia'

/**
 * Store foarte simplu pentru demo.
 * Într-un proiect real trebuie conectat la API-ul de autentificare.
 */
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null
  }),
  getters: {
    isAuthenticated: (state) => !!state.user,
    role: (state) => state.user?.role ?? 'guest'
  },
  actions: {
    /**
     * Login dummy – doar pentru testare de flux.
     * Acceptă roluri de tip: 'admin', 'b2b', 'b2c', 'agent', 'director'.
     */
    loginDummy({ email, role = 'b2c' }) {
      this.user = {
        id: 1,
        name: 'Utilizator Demo',
        email,
        role
      }
    },
    logout() {
      this.user = null
    }
  }
})
