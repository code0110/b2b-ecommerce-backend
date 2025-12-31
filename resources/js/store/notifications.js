import { defineStore } from 'pinia'
import { fetchUnreadCount } from '@/services/admin/notifications'

// Keep only customer demo notifications for now
const demoNotifications = [
  {
    id: 1,
    audience: 'customer',
    type: 'order_status',
    title: 'Comanda #1001 a fost expediată',
    message: 'Comanda ta cu numărul 1001 a fost predată curierului. Poți urmări statusul în contul tău.',
    createdAt: '2025-01-10T10:30:00',
    read: false,
    route: {
      name: 'account-order-details',
      params: { id: '1001' }
    }
  },
  {
    id: 2,
    audience: 'customer',
    type: 'promotion',
    title: 'Nouă promoție pentru contul tău',
    message: 'Ai acces la o promoție dedicată clienților B2B pentru gips-carton. Verifică secțiunea Promoții.',
    createdAt: '2025-01-08T09:15:00',
    read: false,
    route: {
      name: 'promotions'
    }
  },
  {
    id: 3,
    audience: 'customer',
    type: 'credit',
    title: 'Atenționare sold restant',
    message: 'Ai un sold restant care se apropie de limita de credit. Verifică documentele și plățile.',
    createdAt: '2025-01-05T16:45:00',
    read: true,
    route: {
      name: 'account-documents'
    }
  }
]

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [...demoNotifications],
    realAdminUnreadCount: 0
  }),
  getters: {
    all: (state) => state.notifications,
    customerNotifications: (state) =>
      state.notifications.filter((n) => n.audience === 'customer'),
    
    // For admin, we use the real count from state
    adminNotifications: (state) => [],
    
    customerUnreadCount: (state) =>
      state.notifications.filter((n) => n.audience === 'customer' && !n.read).length,
      
    adminUnreadCount: (state) => state.realAdminUnreadCount
  },
  actions: {
    async fetchAdminUnreadCount() {
      try {
        const res = await fetchUnreadCount()
        // Ensure we handle both object response { unread: 5 } or direct number if API changes
        this.realAdminUnreadCount = res.unread !== undefined ? res.unread : res
      } catch (e) {
        console.error('Failed to fetch admin notifications count', e)
      }
    },
    
    markAsRead(id) {
      // Logic for demo customer notifications
      const n = this.notifications.find((x) => x.id === id)
      if (n) n.read = true
    }
  }
})
