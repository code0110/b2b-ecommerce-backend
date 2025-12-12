// resources/js/services/promotions.js
import api from '@/services/http';

// Listă de promoții (pentru /promotii)
export async function fetchPromotions(params = {}) {
  const { data } = await api.get('/promotions', { params });

  // dacă backend-ul returnează direct array
  if (Array.isArray(data)) {
    return {
      items: data,
      meta: {
        current_page: 1,
        last_page: 1,
        total: data.length,
      },
    };
  }

  // dacă backend-ul folosește resursă paginată { data: [...], meta: {...} }
  if (Array.isArray(data.data)) {
    return {
      items: data.data,
      meta: data.meta || {
        current_page: 1,
        last_page: 1,
        total: data.data.length,
      },
    };
  }

  return {
    items: [],
    meta: {
      current_page: 1,
      last_page: 1,
      total: 0,
    },
  };
}

// Detalii promoție + produse incluse (pentru /promotii/:slug)
export async function fetchPromotionBySlug(slug, params = {}) {
  const { data } = await api.get(`/promotions/${slug}`, { params });
  return data;
}
