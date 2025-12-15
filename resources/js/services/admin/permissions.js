import { adminApi } from '@/services/http';

export function fetchPermissions(params = {}) {
  return adminApi.get('/permissions', { params }).then(r => r.data);
}

export function createPermission(payload) {
  return adminApi.post('/permissions', payload).then(r => r.data);
}

export function updatePermission(id, payload) {
  return adminApi.put(`/permissions/${id}`, payload).then(r => r.data);
}

export function deletePermission(id) {
  return adminApi.delete(`/permissions/${id}`);
}
