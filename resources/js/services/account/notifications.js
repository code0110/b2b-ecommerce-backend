// resources/js/services/account/notifications.js
import  api  from '../http';

export const fetchNotifications = async (params = {}) => {
  const { data } = await api.get('/notifications', { params });
  return data;
};

export const markNotificationRead = async (id) => {
  const { data } = await api.post(`/notifications/${id}/read`);
  return data;
};

export const markAllNotificationsRead = async () => {
  const { data } = await api.post('/notifications/read-all');
  return data;
};

export const fetchUnreadCount = async () => {
  const { data } = await api.get('/notifications/unread-count');
  return data;
};
