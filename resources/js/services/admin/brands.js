import { adminApi } from '@/services/http';

export async function fetchAdminBrands(params = {}) {
  const { data } = await adminApi.get('/brands', { params });
  if (Array.isArray(data)) {
    return data;
  }
  if (Array.isArray(data.data)) {
    return data.data;
  }
  return [];
}
