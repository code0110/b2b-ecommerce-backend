// resources/js/services/admin/orders.js
import { adminApi } from '../http'

export const fetchAdminOrders = (params = {}) =>
  adminApi.get('/orders', { params }).then((r) => r.data)

export const fetchAdminOrder = (id) =>
  adminApi.get(`/orders/${id}`).then((r) => r.data)

export const updateAdminOrder = (id, payload) =>
  adminApi.put(`/orders/${id}`, payload).then((r) => r.data)

export const updateAdminOrderStatus = (id, status) =>
  adminApi.post(`/orders/${id}/status`, { status }).then((r) => r.data)

export const updateAdminOrderPaymentStatus = (id, paymentStatus) =>
  adminApi
    .post(`/orders/${id}/payment-status`, { payment_status: paymentStatus })
    .then((r) => r.data)
