// resources/js/services/account/recurringOrders.js
import  api  from '../http';

export const fetchOrderTemplates = async () => {
  const { data } = await api.get('/account/order-templates');
  return data;
};

export const createOrderTemplate = async (payload) => {
  const { data } = await api.post('/account/order-templates', payload);
  return data;
};

export const updateOrderTemplate = async (id, payload) => {
  const { data } = await api.put(`/account/order-templates/${id}`, payload);
  return data;
};

export const deleteOrderTemplate = async (id) => {
  const { data } = await api.delete(`/account/order-templates/${id}`);
  return data;
};

export const triggerTemplateToCart = async (id) => {
  const { data } = await api.post(`/account/order-templates/${id}/add-to-cart`);
  return data;
};
