import { adminApi } from '@/services/http';

export function fetchUsers(params = {}) {
  return adminApi.get('/users', { params }).then(r => r.data);
}

export function fetchUser(id) {
  return adminApi.get(`/users/${id}`).then(r => r.data);
}

export function createUser(payload) {
  return adminApi.post('/users', payload).then(r => r.data);
}

export function updateUser(id, payload) {
  return adminApi.put(`/users/${id}`, payload).then(r => r.data);
}

export function deleteUser(id) {
  return adminApi.delete(`/users/${id}`);
}
