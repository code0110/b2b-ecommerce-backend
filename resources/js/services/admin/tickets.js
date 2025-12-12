// resources/js/services/admin/tickets.js
import { adminApi } from '@/services/http'

// Dacă backend-ul nu are încă endpoint-ul, UI va afișa un mesaj de eroare.
export async function fetchTickets(params = {}) {
  const { data } = await adminApi.get('/tickets', { params })
  return data
}

export async function fetchTicket(id) {
  const { data } = await adminApi.get(`/tickets/${id}`)
  return data
}

export async function updateTicket(id, payload) {
  const { data } = await adminApi.put(`/tickets/${id}`, payload)
  return data
}
