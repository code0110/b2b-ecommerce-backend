import { adminApi } from '@/services/http';

export const fetchSalesRepresentatives = (params = {}) =>
  adminApi.get('/sales-representatives', { params }).then((r) => r.data);

export const getSalesRepresentative = (id) =>
  adminApi.get(`/sales-representatives/${id}`).then((r) => r.data);

export const createSalesRepresentative = (data) =>
  adminApi.post('/sales-representatives', data).then((r) => r.data);

export const updateSalesRepresentative = (id, data) =>
  adminApi.put(`/sales-representatives/${id}`, data).then((r) => r.data);

export const deleteSalesRepresentative = (id) =>
  adminApi.delete(`/sales-representatives/${id}`).then((r) => r.data);
