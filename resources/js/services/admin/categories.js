// resources/js/services/admin/categories.js
import { adminApi } from '@/services/http';

// Lista tuturor categoriilor (nu e paginată în backend)
export async function fetchAdminCategories() {
  const { data } = await adminApi.get('/categories');
  return data;
}

// Detaliu categorie
export async function fetchAdminCategory(id) {
  const { data } = await adminApi.get(`/categories/${id}`);
  return data;
}

// Creare
export async function createAdminCategory(payload) {
  const { data } = await adminApi.post('/categories', payload);
  return data;
}

// Update
export async function updateAdminCategory(id, payload) {
  const { data } = await adminApi.put(`/categories/${id}`, payload);
  return data;
}

// Delete
export async function deleteAdminCategory(id) {
  const { data } = await adminApi.delete(`/categories/${id}`);
  return data;
}
