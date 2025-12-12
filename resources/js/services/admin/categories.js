// resources/js/services/admin/categories.js
import { adminApi } from '@/services/http'

export async function fetchCategories(params = {}) {
  const { data } = await adminApi.get('/categories', { params })
  return data
}
export async function fetchAdminCategories(params = {}) {
  const { data } = await adminApi.get('/categories', { params })
  return data
}
export async function fetchAllCategories() {
  // Încercăm să luăm „toate” – backend-ul poate folosi paginare.
  const resp = await fetchCategories({ per_page: 1000 })
  return resp.data || resp
}

export async function fetchCategory(id) {
  const { data } = await adminApi.get(`/categories/${id}`)
  return data
}

export async function createCategory(payload) {
  const { data } = await adminApi.post('/categories', payload)
  return data
}

export async function updateCategory(id, payload) {
  const { data } = await adminApi.put(`/categories/${id}`, payload)
  return data
}

export async function deleteCategory(id) {
  await adminApi.delete(`/categories/${id}`)
}
