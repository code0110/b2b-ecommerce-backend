import axios from 'axios'

// IMPORTANT: dacă ai deja instanță axios în proiect, înlocuiește cu ea.
// Ex: import api from '@/api' și folosești api.get(...)
const api = axios.create({
  baseURL: '/api',
})

export const accountApi = {
  dashboard: () => api.get('/account/dashboard'),
  orders: (params) => api.get('/account/orders', { params }),
  order: (id) => api.get(`/account/orders/${id}`),
  documents: (params) => api.get('/account/documents', { params }),
  notifications: (params) => api.get('/account/notifications', { params }),
}
