import { adminApi } from '@/services/http';

export function fetchRoutes(params = {}) {
  return adminApi.get('/agent-routes', { params }).then(r => r.data);
}

export function createRoute(payload) {
  return adminApi.post('/agent-routes', payload).then(r => r.data);
}

export function updateRoute(id, payload) {
  return adminApi.put(`/agent-routes/${id}`, payload).then(r => r.data);
}

export function deleteRoute(id) {
  return adminApi.delete(`/agent-routes/${id}`);
}
