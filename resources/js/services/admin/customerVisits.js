import { adminApi } from '@/services/http';

export function fetchVisits(params = {}) {
  return adminApi.get('/customer-visits', { params }).then(r => r.data);
}

export function startVisit(payload) {
  return adminApi.post('/customer-visits/start', payload).then(r => r.data);
}

export function endVisit(id, payload) {
  return adminApi.post(`/customer-visits/${id}/end`, payload).then(r => r.data);
}
