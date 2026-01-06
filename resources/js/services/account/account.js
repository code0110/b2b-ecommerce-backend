// resources/js/services/account/account.js
import  api  from '@/services/http';

// /api/account/dashboard
export const fetchAccountOverview = async () => {
  const { data } = await api.get('/account/dashboard');
  return data;
};

// /api/notifications/unread-count
export const fetchUnreadNotificationsCount = async () => {
  const { data } = await api.get('/notifications/unread-count');
  return data;
};
