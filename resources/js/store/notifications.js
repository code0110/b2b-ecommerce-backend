import { defineStore } from 'pinia'
import { fetchNotifications, fetchUnreadCount, markNotificationRead, markAllNotificationsRead } from '@/services/notifications'

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false
  }),
  
  getters: {
    all: (state) => state.notifications,
    // Helper mostly for compatibility if components ask for specific audiences, 
    // but usually backend filters this.
    customerNotifications: (state) => state.notifications,
  },

  actions: {
    async fetchNotifications() {
      this.loading = true;
      try {
        // Fetch latest 20 notifications, for example
        const data = await fetchNotifications({ limit: 20 });
        this.notifications = data.data || data; // handle paginated or list response
      } catch (e) {
        console.error('Failed to fetch notifications', e);
      } finally {
        this.loading = false;
      }
    },

    async fetchUnreadCount() {
      try {
        const res = await fetchUnreadCount();
        this.unreadCount = res.unread !== undefined ? res.unread : res;
      } catch (e) {
        console.error('Failed to fetch unread count', e);
      }
    },

    async fetchAdminUnreadCount() {
      return this.fetchUnreadCount();
    },

    async markRead(id) {
      try {
        await markNotificationRead(id);
        const n = this.notifications.find(x => x.id === id);
        if (n) {
          n.read_at = new Date().toISOString();
        }
        if (this.unreadCount > 0) this.unreadCount--;
      } catch (e) {
        console.error('Failed to mark read', e);
      }
    },

    async markAllRead() {
      try {
        await markAllNotificationsRead();
        this.notifications.forEach(n => n.read_at = new Date().toISOString());
        this.unreadCount = 0;
      } catch (e) {
        console.error('Failed to mark all read', e);
      }
    }
  }
})
