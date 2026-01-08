import { adminApi } from '@/services/http'

export async function fetchAttributes(params = {}) {
  const { data } = await adminApi.get('/attributes', { params })
  return data
}

export async function fetchAllAttributes() {
  const resp = await fetchAttributes({ per_page: 1000 })
  return resp.data || resp
}

export async function fetchAttribute(id) {
  const { data } = await adminApi.get(`/attributes/${id}`)
  return data
}

export async function createAttribute(payload) {
  const { data } = await adminApi.post('/attributes', payload)
  return data
}

export async function updateAttribute(id, payload) {
  const { data } = await adminApi.put(`/attributes/${id}`, payload)
  return data
}

export async function deleteAttribute(id) {
  await adminApi.delete(`/attributes/${id}`)
}
