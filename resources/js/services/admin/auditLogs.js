// resources/js/services/admin/auditLogs.js
import { adminApi } from '@/services/http'

export async function fetchAuditLogs(params = {}) {
  const { data } = await adminApi.get('/audit-logs', { params })
  return data
}

export async function fetchAuditLog(id) {
  const { data } = await adminApi.get(`/audit-logs/${id}`)
  return data
}
