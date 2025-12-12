// resources/js/services/admin/dashboard.js
import { adminApi } from '@/services/http';

export async function fetchAdminDashboard() {
  const { data } = await adminApi.get('/dashboard');
  return data;
}
