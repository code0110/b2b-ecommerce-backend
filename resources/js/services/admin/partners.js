// resources/js/services/admin/partners.js
import { adminApi } from '@/services/http'

export async function fetchPartnerRequests(params = {}) {
  const { data } = await adminApi.get('/partner-requests', { params })
  return data
}

export async function updatePartnerRequestStatus(id, payload) {
  const { data } = await adminApi.put(`/partner-requests/${id}`, payload)
  return data
}
