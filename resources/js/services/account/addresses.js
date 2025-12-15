// resources/js/services/account/addresses.js
import  api  from '../http';

export const fetchAddresses = async () => {
  const { data } = await api.get('/account/addresses');
  return data;
};

export const createAddress = async (payload) => {
  const { data } = await api.post('/account/addresses', payload);
  return data;
};

export const updateAddress = async (id, payload) => {
  const { data } = await api.put(`/account/addresses/${id}`, payload);
  return data;
};

export const deleteAddress = async (id) => {
  const { data } = await api.delete(`/account/addresses/${id}`);
  return data;
};

export const setDefaultAddress = async (id) => {
  const { data } = await api.post(`/account/addresses/${id}/set-default`);
  return data;
};
