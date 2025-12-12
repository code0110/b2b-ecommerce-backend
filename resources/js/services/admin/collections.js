// resources/js/services/admin/collections.js
import { adminApi } from '@/services/http'

// Încasări gestionate din backend
export async function fetchCollections(params = {}) {
  const { data } = await adminApi.get('/collections', { params })
  return data
}

export async function createCollection(payload) {
  const { data } = await adminApi.post('/collections', payload)
  return data
}
