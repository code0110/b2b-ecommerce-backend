// resources/js/services/admin/products.js
import { adminApi } from '@/services/http'

export async function fetchProducts(params = {}) {
  const { data } = await adminApi.get('/products', { params })
  return data
}

export async function fetchProduct(id) {
  const { data } = await adminApi.get(`/products/${id}`)
  return data
}

export async function createProduct(payload) {
  const { data } = await adminApi.post('/products', payload)
  return data
}

export async function updateProduct(id, payload) {
  const { data } = await adminApi.put(`/products/${id}`, payload)
  return data
}

export async function deleteProduct(id) {
  await adminApi.delete(`/products/${id}`)
}
