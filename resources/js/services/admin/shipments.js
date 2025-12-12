// resources/js/services/admin/shipments.js
import { adminApi } from '@/services/http'

export async function fetchShipments(params = {}) {
  const { data } = await adminApi.get('/shipments', { params })
  return data
}

export async function createShipment(payload) {
  const { data } = await adminApi.post('/shipments', payload)
  return data
}

export async function updateShipmentStatus(id, payload) {
  // backend: POST /api/admin/shipments/{id}/status
  const { data } = await adminApi.post(`/shipments/${id}/status`, payload)
  return data
}
