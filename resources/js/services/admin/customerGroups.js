// resources/js/services/admin/customerGroups.js
import { adminApi } from '@/services/http';

export async function fetchCustomerGroups() {
  const { data } = await adminApi.get('/customer-groups');
  // aici controllerul returnează un simplu array (nu paginator)
  return data;
}

export async function createCustomerGroup(payload) {
  const { data } = await adminApi.post('/customer-groups', payload);
  return data;
}

export async function updateCustomerGroup(id, payload) {
  const { data } = await adminApi.put(`/customer-groups/${id}`, payload);
  return data;
}

export async function deleteCustomerGroup(id) {
  await adminApi.delete(`/customer-groups/${id}`);
}
