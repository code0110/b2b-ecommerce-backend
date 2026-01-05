// resources/js/services/admin/orders.js
import { adminApi } from '@/services/http';

export function fetchOrders(params = {}) {
  return adminApi.get('/orders', { params }).then(response => response.data);
}

export function fetchOrder(id) {
  return adminApi.get(`/orders/${id}`).then(response => response.data);
}

export function updateOrder(id, payload) {
  return adminApi.put(`/orders/${id}`, payload).then(response => response.data);
}

export function updateOrderStatus(id, payload) {
  return adminApi.post(`/orders/${id}/status`, payload).then(response => response.data);
}

export function updateOrderPaymentStatus(id, payload) {
  return adminApi.post(`/orders/${id}/payment-status`, payload).then(response => response.data);
}
