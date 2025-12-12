// resources/js/services/admin/shipping.js
import { adminApi } from '@/services/http'

// Config reguli transport
export async function fetchShippingConfig() {
  const { data } = await adminApi.get('/shipping/config')
  return data
}

export async function createShippingConfig(payload) {
  const { data } = await adminApi.post('/shipping/config', payload)
  return data
}

export async function updateShippingConfig(id, payload) {
  const { data } = await adminApi.put(`/shipping/config/${id}`, payload)
  return data
}

export async function deleteShippingConfig(id) {
  await adminApi.delete(`/shipping/config/${id}`)
}
