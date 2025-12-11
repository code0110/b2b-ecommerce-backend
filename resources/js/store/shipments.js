import { defineStore } from 'pinia'

/**
 * Store demo pentru expedieri / AWB-uri.
 *
 * Într-un proiect real, aceste informații ar veni dintr-un sistem de logistică
 * sau direct din API-urile curierilor și din ERP.
 */

const demoShipments = [
  {
    id: 1,
    orderId: 1,
    orderNumber: 'BC-100001',
    customerName: 'Ionescu Andrei',
    courier: 'Fan Courier',
    awbNumber: 'FAN1234567890',
    status: 'delivered', // label_generated | picked_up | in_transit | out_for_delivery | delivered | cancelled
    createdAt: '2025-01-16T08:30:00',
    lastUpdate: '2025-01-18T15:30:00',
    estimatedDeliveryDate: '2025-01-18',
    trackingUrl: 'https://www.fancourier.ro/awb-tracking/?i=FAN1234567890',
    notes: 'Livrat la client. Semnătură capturată în sistemul curierului.'
  },
  {
    id: 2,
    orderId: 2,
    orderNumber: 'BC-200010',
    customerName: 'SC Demo Construct SRL',
    courier: 'Cargus',
    awbNumber: 'CGS987654321',
    status: 'in_transit',
    createdAt: '2025-03-02T09:10:00',
    lastUpdate: '2025-03-03T18:05:00',
    estimatedDeliveryDate: '2025-03-04',
    trackingUrl: 'https://www.cargus.ro/awb-tracking/CGS987654321',
    notes: 'Paletizat. Livrare la depozit.'
  },
  {
    id: 3,
    orderId: 3,
    orderNumber: 'BC-100002',
    customerName: 'Ionescu Andrei',
    courier: 'Sameday',
    awbNumber: 'SD123123123',
    status: 'cancelled',
    createdAt: '2025-02-11T10:00:00',
    lastUpdate: '2025-02-11T15:30:00',
    estimatedDeliveryDate: null,
    trackingUrl: 'https://sameday.ro/awb/SD123123123',
    notes: 'Comandă anulată, AWB stornat.'
  },
  {
    id: 4,
    orderId: 4,
    orderNumber: 'AG-300050',
    customerName: 'SC Demo Retail SRL',
    courier: 'Fan Courier',
    awbNumber: 'FAN555666777',
    status: 'out_for_delivery',
    createdAt: '2025-03-05T08:45:00',
    lastUpdate: '2025-03-06T09:15:00',
    estimatedDeliveryDate: '2025-03-06',
    trackingUrl: 'https://www.fancourier.ro/awb-tracking/?i=FAN555666777',
    notes: 'Livrare cu valoare ramburs (încasare la agent).'
  }
]

export const useShipmentsStore = defineStore('shipments', {
  state: () => ({
    shipments: demoShipments
  }),

  getters: {
    /**
     * Toate expedierile.
     */
    all: (state) => state.shipments,

    /**
     * Expedierile pentru un anumit ID de comandă.
     */
    forOrderId: (state) => (orderId) =>
      state.shipments.filter((s) => s.orderId === Number(orderId)),

    /**
     * Expedierile pentru un anumit număr de comandă.
     */
    forOrderNumber: (state) => (orderNumber) =>
      state.shipments.filter((s) => s.orderNumber === orderNumber)
  },

  actions: {
    /**
     * Actualizează statusul unei expedieri (demo).
     */
    updateStatus(id, newStatus) {
      const shipment = this.shipments.find((s) => s.id === Number(id))
      if (!shipment) return

      shipment.status = newStatus
      shipment.lastUpdate = new Date().toISOString()
    },

    /**
     * Adăugare expediere nouă (demo).
     */
    addShipment(payload) {
      const nextId = this.shipments.length
        ? Math.max(...this.shipments.map((s) => s.id)) + 1
        : 1

      const nowIso = new Date().toISOString()

      this.shipments.push({
        id: nextId,
        createdAt: nowIso,
        lastUpdate: nowIso,
        status: 'label_generated',
        ...payload
      })
    }
  }
})
