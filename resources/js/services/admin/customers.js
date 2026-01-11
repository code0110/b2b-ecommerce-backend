// resources/js/services/admin/customers.js
import { adminApi } from '@/services/http';

export async function fetchCustomers(params = {}) {
  const { data } = await adminApi.get('/customers', { params });
  return data; // paginator Laravel: { data, meta, links }
}

export async function fetchCustomer(id) {
  const { data } = await adminApi.get(`/customers/${id}`);
  return data;
}

export async function createCustomer(payload) {
  const { data } = await adminApi.post('/customers', payload);
  return data;
}

export async function updateCustomer(id, payload) {
  const { data } = await adminApi.put(`/customers/${id}`, payload);
  return data;
}

export async function deleteCustomer(id) {
  await adminApi.delete(`/customers/${id}`);
}

export async function fetchFinancialRisk(id) {
  const { data } = await adminApi.get(`/customers/${id}/financial-risk`);
  return data;
}

export async function acknowledgeRisk(id) {
  const { data } = await adminApi.post(`/customers/${id}/financial-risk/acknowledge`);
  return data;
}

export async function grantDerogation(id, payload) {
  const { data } = await adminApi.post(`/customers/${id}/financial-risk/derogation`, payload);
  return data;
}
