// resources/js/services/http.js
import axios from 'axios'

// Config comun pentru toate instanțele
const baseConfig = {
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json'
  }
}

/**
 * API general (front-office, cont client etc.)
 * Bazează-se pe prefixul /api
 */
const api = axios.create({
  ...baseConfig,
  baseURL: '/api'
})

/**
 * API pentru zona de admin
 * Bazează-se pe prefixul /api/admin
 */
const adminApi = axios.create({
  ...baseConfig,
  baseURL: '/api/admin'
})

// Adăugăm token-ul Bearer din localStorage pentru ambele instanțe
const attachAuthInterceptor = (instance) => {
  instance.interceptors.request.use(
    (config) => {
      try {
        const token = localStorage.getItem('auth_token') || localStorage.getItem('token')
        if (token) {
          config.headers = config.headers || {}
          config.headers.Authorization = `Bearer ${token}`
        }
      } catch (e) {
        // dacă localStorage nu e disponibil, ignorăm
      }
      return config
    },
    (error) => Promise.reject(error)
  )
}

attachAuthInterceptor(api)
attachAuthInterceptor(adminApi)

// Exporturi named – ce folosim în toate serviciile
export { api, adminApi }

// Export default pentru compatibilitate cu eventuale importuri vechi:
// import api from '@/services/http'
export default api
