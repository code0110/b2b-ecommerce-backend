import { adminApi } from '@/services/http';

export function fetchRoles() {
  return adminApi.get('/roles').then(r => r.data);
}

export function createRole(payload) {
  return adminApi.post('/roles', payload).then(r => r.data);
}

export function updateRole(id, payload) {
  return adminApi.put(`/roles/${id}`, payload).then(r => r.data);
}

export function deleteRole(id) {
  return adminApi.delete(`/roles/${id}`);
}
