import { adminApi } from '@/services/http';

// listă paginată cu filtre
export async function fetchAdminProducts(params = {}) {
  const { data } = await adminApi.get('/products', { params });
  return data;
}

export async function fetchAdminProduct(id) {
  const { data } = await adminApi.get(`/products/${id}`);
  return data;
}

export async function createAdminProduct(payload) {
  const { data } = await adminApi.post('/products', payload);
  return data;
}

export async function updateAdminProduct(id, payload) {
  const { data } = await adminApi.put(`/products/${id}`, payload);
  return data;
}

export async function deleteAdminProduct(id) {
  return adminApi.delete(`/products/${id}`);
}
