// resources/js/services/account/offers.js
import  api  from '../http';

export const fetchOffers = async (params = {}) => {
  const { data } = await api.get('/account/offers', { params });
  return data;
};

export const fetchOffer = async (id) => {
  const { data } = await api.get(`/account/offers/${id}`);
  return data;
};

export const requestOfferForCart = async (payload = {}) => {
  const { data } = await api.post('/account/offers/request-from-cart', payload);
  return data;
};
