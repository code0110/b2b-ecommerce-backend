// resources/js/services/admin/offers.js
import { adminApi } from '@/services/http'

export async function fetchOffers(params = {}) {
  // Backend: /api/admin/quotes
  const { data } = await adminApi.get('/quotes', { params })
  return data
}

export async function fetchOffer(id) {
  const { data } = await adminApi.get(`/quotes/${id}`)
  return data
}

export async function updateOffer(id, payload) {
  const { data } = await adminApi.put(`/quotes/${id}`, payload)
  return data
}

export async function convertOfferToOrder(id) {
  const { data } = await adminApi.post(
    `/quotes/${id}/convert-to-order`
  )
  return data
}
