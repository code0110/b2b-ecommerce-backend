// resources/js/services/promotions.js
import  api  from './http';

export async function fetchPromotions(params = {}) {
  const response = await api.get('/promotions', { params });
  return response.data;
}

export async function fetchPromotionDetails(slug) {
  const response = await api.get(`/promotions/${slug}`);
  return response.data;
}
