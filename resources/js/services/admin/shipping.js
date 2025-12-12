// resources/js/services/admin/shipping.js
import { adminApi } from '@/services/http';

export async function fetchShippingMethods() {
  const { data } = await adminApi.get('/shipping/config');
  // ShippingController@index: ShippingMethod::with('rules')->orderBy('name')->get();
  return data;
}

export async function createShippingMethod(payload) {
  const { data } = await adminApi.post('/shipping/config', payload);
  return data;
}

export async function updateShippingMethod(id, payload) {
  const { data } = await adminApi.put(`/shipping/config/${id}`, payload);
  return data;
}

export async function deleteShippingMethod(id) {
  await adminApi.delete(`/shipping/config/${id}`);
}
