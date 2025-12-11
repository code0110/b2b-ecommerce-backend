import { defineStore } from 'pinia'

/**
 * Store simplu pentru notificări demo (front + admin).
 * În implementarea reală, acest store ar fi alimentat din backend.
 */

const demoNotifications = [
  {
    id: 1,
    audience: 'customer', // customer / admin
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
  },
  {
    id: 4,
    audience: 'admin',
    type: 'order_status',
    title: 'Comandă nouă de la client B2B',
    message: 'Clientul Demo SRL a plasat o comandă cu valoare 12.500 RON. Verifică blocajele de credit și condițiile comerciale.',
    createdAt: '2025-01-11T08:05:00',
    read: false,
    route: {
      name: 'admin-dashboard'
    }
  },
  {
    id: 5,
    audience: 'admin',
    type: 'credit_block',
    title: 'Blocaj credit pentru client',
    message: 'Clientul Demo SRL a depășit limita de credit. Comenzile noi sunt blocate până la regularizarea soldului.',
    createdAt: '2025-01-09T14:20:00',
    read: false,
    route: {
      name: 'admin-customers'
    }
  },
  {
    id: 6,
    audience: 'admin',
    type: 'promotion_expiring',
    title: 'Promoție care expiră în curând',
    message: 'Campania „Promoție gips-carton -10%” expiră în 3 zile. Ia în calcul prelungirea sau o nouă campanie.',
    createdAt: '2025-01-07T11:00:00',
    read: true,
    route: {
      name: 'admin-promotions'
    }
  }
]

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [...demoNotifications]
  }),
  getters: {
    all: (state) => state.notifications,
    customerNotifications: (state) =>
      state.notifications.filter((n) => n.audience === 'customer'),
    adminNotifications: (state) =>
      state.notifications.filter((n) => n.audience === 'admin'),
    customerUnreadCount: (state) =>
      state.notifications.filter((n) => n.audience === 'customer' && !n.read).length,
    adminUnreadCount: (state) =>
      state.notifications.filter((n) => n.audience === 'admin' && !n.read).length
  },
  actions: {
    markAsRead(id) {
      const n = this.notifications.find((x) => x.id === id)
      if (n) {
        n.read = true
      }
    },
    markAllAsReadFor(audience) {
      this.notifications = this.notifications.map((n) =>
        n.audience === audience ? { ...n, read: true } : n
      )
    }
  }
})
