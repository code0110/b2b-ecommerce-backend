// resources/js/services/admin/quotes.js
import { adminApi } from '@/services/http';

/**
 * Listă de oferte (quotes) din backend.
 * Acceptă parametri pentru filtrare/paginare (status, customer, page etc.)
 */
export async function fetchQuotes(params = {}) {
  const { data } = await adminApi.get('/admin/quotes', { params });
  return data;
}

/**
 * Detalii ofertă (quote).
 */
export async function fetchQuote(id) {
  const { data } = await adminApi.get(`/admin/quotes/${id}`);
  return data;
}

/**
 * Actualizare ofertă (ex: status, note interne).
 * payload poate conține doar câmpurile permise de backend.
 */
export async function updateQuote(id, payload) {
  const { data } = await adminApi.put(`/admin/quotes/${id}`, payload);
  return data;
}

/**
 * Conversie ofertă în comandă (endpoint existent în backend).
 */
export async function convertQuoteToOrder(id) {
  const { data } = await adminApi.post(
    `/admin/quotes/${id}/convert-to-order`
  );
  return data;
}
