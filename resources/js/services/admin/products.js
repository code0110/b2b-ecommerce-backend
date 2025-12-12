// resources/js/services/admin/products.js
import { adminApi } from '@/services/http';

// Lista de produse (paginată)
export async function fetchAdminProducts(params = {}) {
  const { data } = await adminApi.get('/products', { params });
  // Laravel paginator -> { data: [...], current_page, last_page, ... }
  return data;
}

// Detaliu produs (pt. edit)
export async function fetchAdminProduct(id) {
  const { data } = await adminApi.get(`/products/${id}`);
  return data;
}

// Creare produs
export async function createAdminProduct(payload) {
  const { data } = await adminApi.post('/products', payload);
  return data;
}

// Update produs
export async function updateAdminProduct(id, payload) {
  const { data } = await adminApi.put(`/products/${id}`, payload);
  return data;
}

// Delete produs
export async function deleteAdminProduct(id) {
  const { data } = await adminApi.delete(`/products/${id}`);
  return data;
}
