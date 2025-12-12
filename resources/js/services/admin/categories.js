// resources/js/services/admin/categories.js
import { adminApi } from '@/services/http';

// listă categorii (poate fi paginată sau nu, tratăm ambele cazuri)
export async function fetchAdminCategories(params = {}) {
  const { data } = await adminApi.get('/categories', { params });

  // Dacă backend-ul întoarce direct array
  if (Array.isArray(data)) {
    return data;
  }

  // Dacă e resursă paginată: { data: [...], meta: {...} }
  if (Array.isArray(data.data)) {
    return data.data;
  }

  return [];
}

export async function fetchAdminCategoriesPaginated(params = {}) {
  const { data } = await adminApi.get('/categories', { params });
  return data; // aici lăsăm forma brută pentru listă cu paginare
}

export async function fetchAdminCategory(id) {
  const { data } = await adminApi.get(`/categories/${id}`);
  return data;
}

export async function createAdminCategory(payload) {
  const { data } = await adminApi.post('/categories', payload);
  return data;
}

export async function updateAdminCategory(id, payload) {
  const { data } = await adminApi.put(`/categories/${id}`, payload);
  return data;
}

export async function deleteAdminCategory(id) {
  return adminApi.delete(`/categories/${id}`);
}
