// resources/js/services/admin/collections.js
import { adminApi } from '@/services/http'

// Încasări gestionate din backend (mapate pe payments)
export async function fetchCollections(params = {}) {
  // Backend-ul expune /api/admin/payments
  const { data } = await adminApi.get('/payments', { params })
  return data
}

export async function createCollection(payload) {
  const { data } = await adminApi.post('/payments', payload)
  return data
}
