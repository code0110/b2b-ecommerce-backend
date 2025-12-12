// resources/js/services/admin/permissions.js
import { adminApi } from '@/services/http'

export async function fetchPermissions(params = {}) {
  const { data } = await adminApi.get('/permissions', { params })
  return data
}
