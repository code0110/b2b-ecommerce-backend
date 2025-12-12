// resources/js/services/admin/dashboard.js
import { adminApi } from '@/services/http'

export async function fetchDashboardSummary() {
  const { data } = await adminApi.get('/dashboard')
  return data
}

export async function fetchDashboardOverview(params = {}) {
  const { data } = await adminApi.get('/dashboard/overview', { params })
  return data
}
