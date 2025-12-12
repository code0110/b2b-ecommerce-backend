// resources/js/services/catalog.js
import api from './http';

// Homepage – promoții, produse noi, recomandate etc.
export async function fetchHomeData() {
  // axios are baseURL = '/api', deci aici apelăm GET /api/home
  const { data } = await api.get('/home');
  return data;
}

// Detalii categorie: /api/catalog/categories/{slug}
// sau, pentru "toate produsele", putem apela searchProducts
export async function fetchCategory(slug, params = {}) {
  // dacă ai în backend GET /api/catalog/categories/{slug}
  const url = `/categories/${slug}`;
  const { data } = await api.get(url, { params });
  return data;
}

// Detalii produs după slug: /api/catalog/products/{slug}
export async function fetchProductBySlug(slug) {
  const { data } = await api.get(`/products/${slug}`);
  return data;
}

// Căutare produse: /api/catalog/search
// folosit și pentru paginile /noutati, /reduceri etc.
export async function searchProducts(params = {}) {
  const { data } = await api.get('/search', { params });
  return data;
}
export const fetchCategoryTree = () =>
  api.get('/catalog/categories-tree').then((r) => r.data);
