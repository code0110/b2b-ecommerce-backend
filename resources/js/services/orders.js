// resources/js/services/orders.js
import api from './http';


import axios from 'axios';

/**
 * Comenzi din contul clientului logat
 * Folosește /api/orders (vezi routes/api.php, secțiunea "Orders in client account")
 */

export const fetchMyOrders = (params = {}) =>
  api.get('/orders', { params }).then((r) => r.data);

export const fetchMyOrder = (id) =>
  api.get(`/orders/${id}`).then((r) => r.data);

export const reorderOrder = (id) =>
  api.post(`/orders/${id}/reorder`).then((r) => r.data);
