// resources/js/services/admin/brands.js
import { adminApi } from '@/services/http';

export async function fetchAdminBrands() {
  const { data } = await adminApi.get('/brands');
  return data;
}

export async function fetchBrands() {
  const { data } = await adminApi.get('/brands');
  return data;
}

export async function fetchAdminBrand(id) {
  const { data } = await adminApi.get(`/brands/${id}`);
  return data;
}

export async function createAdminBrand(payload) {
  const { data } = await adminApi.post('/brands', payload);
  return data;
}

export async function updateAdminBrand(id, payload) {
  const { data } = await adminApi.put(`/brands/${id}`, payload);
  return data;
}

export async function deleteAdminBrand(id) {
  const { data } = await adminApi.delete(`/brands/${id}`);
  return data;
}
