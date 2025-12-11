import { defineStore } from 'pinia'

/**
 * Store demo pentru comenzi. Într-un proiect real va fi conectat la API / ERP.
 */
const demoOrders = [
  {
    id: 1,
    number: 'BC-100001',
    customerUserId: 1,
    channel: 'online',
    isB2B: false,
    createdAt: '2025-01-15T10:15:00',
    status: 'delivered', // new | processing | shipped | delivered | cancelled
    paymentStatus: 'paid', // pending | paid | failed | refunded
    paymentMethod: 'card',
    shippingMethod: 'Curier rapid',
    totalGross: 1240.5,
    currency: 'RON',
    dueDate: null,
    creditUsed: false,
    billingAddress: 'Ionescu Andrei\nStr. Exemplu nr. 20, Cluj-Napoca',
    shippingAddress: 'Ionescu Andrei\nStr. Exemplu nr. 21, Cluj-Napoca',
    notes: '',
    lines: [
      {
        lineId: 11,
        productName: 'Placă gips-carton 12.5mm',
        productCode: 'PGC-12.5',
        qty: 20,
        unit: 'buc',
        unitPrice: 45.0,
        lineTotal: 900.0,
        canReturn: true,
        rmaStatus: 'none' // none | requested | approved | rejected
      },
      {
        lineId: 12,
        productName: 'Profil metalic UW 50',
        productCode: 'UW-50',
        qty: 30,
        unit: 'ml',
        unitPrice: 9.5,
        lineTotal: 285.0,
        canReturn: true,
        rmaStatus: 'none'
      },
      {
        lineId: 13,
        productName: 'Șuruburi gips-carton 25mm',
        productCode: 'SUR-25',
        qty: 1,
        unit: 'cutie',
        unitPrice: 55.5,
        lineTotal: 55.5,
        canReturn: false,
        rmaStatus: 'none'
      }
    ]
  },
  {
    id: 2,
    number: 'BB-200045',
    customerUserId: 1,
    channel: 'agent',
    isB2B: true,
    createdAt: '2025-02-01T09:00:00',
    status: 'processing',
    paymentStatus: 'pending',
    paymentMethod: 'op',
    shippingMethod: 'Livrare flota proprie',
    totalGross: 38250,
    currency: 'RON',
    dueDate: '2025-03-03',
    creditUsed: true,
    billingAddress: 'SC Demo Construct SRL\nStr. Exemplu nr. 1, București',
    shippingAddress: 'Depozit principal\nStr. Depozitelor nr. 10, București',
    notes: 'Comandă înregistrată de agent. Plată la 30 de zile.',
    lines: [
      {
        lineId: 21,
        productName: 'Placă gips-carton 12.5mm',
        productCode: 'PGC-12.5',
        qty: 500,
        unit: 'buc',
        unitPrice: 40.0,
        lineTotal: 20000.0,
        canReturn: true,
        rmaStatus: 'none'
      },
      {
        lineId: 22,
        productName: 'Profil metalic UW 50',
        productCode: 'UW-50',
        qty: 1000,
        unit: 'ml',
        unitPrice: 8.5,
        lineTotal: 8500.0,
        canReturn: true,
        rmaStatus: 'none'
      },
      {
        lineId: 23,
        productName: 'Vată minerală 50mm',
        productCode: 'VATA-50',
        qty: 100,
        unit: 'pachet',
        unitPrice: 97.5,
        lineTotal: 9750.0,
        canReturn: true,
        rmaStatus: 'none'
      }
    ]
  },
  {
    id: 3,
    number: 'BC-100002',
    customerUserId: 1,
    channel: 'online',
    isB2B: false,
    createdAt: '2025-02-10T14:30:00',
    status: 'cancelled',
    paymentStatus: 'refunded',
    paymentMethod: 'card',
    shippingMethod: 'Curier standard',
    totalGross: 320.0,
    currency: 'RON',
    dueDate: null,
    creditUsed: false,
    billingAddress: 'Ionescu Andrei\nStr. Exemplu nr. 20, Cluj-Napoca',
    shippingAddress: 'Ionescu Andrei\nStr. Exemplu nr. 21, Cluj-Napoca',
    notes: 'Comanda anulată la cererea clientului.',
    lines: [
      {
        lineId: 31,
        productName: 'Glet finisaj interior 25kg',
        productCode: 'GLET-25',
        qty: 10,
        unit: 'sac',
        unitPrice: 32.0,
        lineTotal: 320.0,
        canReturn: false,
        rmaStatus: 'none'
      }
    ]
  }
]

export const useOrdersStore = defineStore('orders', {
  state: () => ({
    orders: [...demoOrders]
  }),
  getters: {
    all: (state) => state.orders,
    getById: (state) => (id) => state.orders.find((o) => o.id === Number(id)),
    forUser: (state) => (userId) =>
      state.orders.filter((o) => o.customerUserId === Number(userId))
  },
  actions: {
    // Template pentru acțiunea "comandă din nou"
    reorder(orderId) {
      const order = this.orders.find((o) => o.id === Number(orderId))
      if (!order) return null
      // În implementarea reală:
      // - se trimite request către backend pentru a genera un nou coș
      // - se ține cont de stocuri, prețuri actualizate, promoții active etc.
      return {
        orderId: order.id,
        lineCount: order.lines.length,
        totalGross: order.totalGross
      }
    }
  }
})
