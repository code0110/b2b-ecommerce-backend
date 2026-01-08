// resources/js/services/admin/products.js
import { adminApi } from '@/services/http'

export function fetchProducts(params = {}) {
  return adminApi.get('/products', { params }).then(r => r.data);
}

export function fetchProduct(id) {
  return adminApi.get(`/products/${id}`).then(r => r.data);
}

export function createProduct(payload) {
  return adminApi.post('/products', payload).then(r => r.data);
}

export function updateProduct(id, payload) {
  return adminApi.put(`/products/${id}`, payload).then(r => r.data);
}

export function deleteProduct(id) {
  return adminApi.delete(`/products/${id}`);
}

export function generateSeo(payload) {
  return adminApi.post('/products/generate-seo', payload).then(r => r.data);
}