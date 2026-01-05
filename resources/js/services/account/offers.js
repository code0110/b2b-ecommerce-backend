import api from '@/services/http'; // Assuming standard http service

export const fetchOffers = async (params = {}) => {
  const { data } = await api.get('/account/client-offers', { params });
  return data;
};

export const fetchOffer = async (id) => {
  const { data } = await api.get(`/account/client-offers/${id}`);
  return data;
};

export const updateOfferStatus = async (id, status) => {
  const { data } = await api.post(`/account/client-offers/${id}/status`, { status });
  return data;
};

export const sendOfferMessage = async (id, message) => {
  const { data } = await api.post(`/account/client-offers/${id}/messages`, { message });
  return data;
};
