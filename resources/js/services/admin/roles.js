// resources/js/services/admin/roles.js
import { adminApi } from '@/services/http'

export async function fetchRoles(params = {}) {
  const { data } = await adminApi.get('/roles', { params })
  return data
}

export async function fetchRole(id) {
  const { data } = await adminApi.get(`/roles/${id}`)
  return data
}

export async function createRole(payload) {
  const { data } = await adminApi.post('/roles', payload)
  return data
}

export async function updateRole(id, payload) {
  const { data } = await adminApi.put(`/roles/${id}`, payload)
  return data
}

export async function deleteRole(id) {
  await adminApi.delete(`/roles/${id}`)
}
