// resources/js/services/account/orders.js
import  api  from '../http';

export const fetchOrders = async (params = {}) => {
  const { data } = await api.get('/account/orders', { params });
  return data;
};

export const fetchOrder = async (id) => {
  const { data } = await api.get(`/account/orders/${id}`);
  return data;
};

export const reorderOrder = async (id) => {
  const { data } = await api.post(`/account/orders/${id}/reorder`);
  return data;
};
