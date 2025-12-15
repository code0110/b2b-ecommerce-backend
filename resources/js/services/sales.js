// resources/js/services/sales.js
import  api  from './http';

export async function fetchSalesRepresentatives(params = {}) {
  const response = await api.get('/sales-representatives', { params });
  return response.data;
}
