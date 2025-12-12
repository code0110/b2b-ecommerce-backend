// resources/js/services/admin/promotions.js
import { adminApi } from '@/services/http';

export async function fetchPromotions(params = {}) {
  const { data } = await adminApi.get('/promotions', { params });
  // paginator: { data: [...], meta, links }
  return data;
}

export async function fetchPromotion(id) {
  const { data } = await adminApi.get(`/promotions/${id}`);
  return data;
}

export async function createPromotion(payload) {
  const { data } = await adminApi.post('/promotions', payload);
  return data;
}

export async function updatePromotion(id, payload) {
  const { data } = await adminApi.put(`/promotions/${id}`, payload);
  return data;
}

export async function deletePromotion(id) {
  await adminApi.delete(`/promotions/${id}`);
}
