// resources/js/services/admin/notifications.js
import { adminApi } from '@/services/http';

export async function fetchAdminNotifications(params = {}) {
  const { data } = await adminApi.get('/notifications', { params });
  return data;
}

export async function fetchUnreadCount() {
  const { data } = await adminApi.get('/notifications/unread-count');
  return data; // { unread: 5 }
}

export async function markNotificationRead(id) {
  const { data } = await adminApi.post(`/notifications/${id}/read`);
  return data;
}

export async function markAllNotificationsRead() {
  const { data } = await adminApi.post('/notifications/read-all');
  return data;
}
