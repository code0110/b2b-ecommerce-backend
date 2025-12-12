// resources/js/services/admin/customerGroups.js
import { adminApi } from '@/services/http';

// listă pentru dropdownuri / listă admin
export async function fetchCustomerGroups(params = {}) {
  const { data } = await adminApi.get('/customer-groups', { params });
  if (Array.isArray(data)) return data;
  return data.data ?? [];
}

export async function fetchCustomerGroup(id) {
  const { data } = await adminApi.get(`/customer-groups/${id}`);
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
