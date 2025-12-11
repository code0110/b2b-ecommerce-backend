import { defineStore } from 'pinia'

/**
 * Store demo pentru încasări (CHS / BO / CEC / card / OP).
 */

const demoCollections = [
  {
    id: 1,
    customerId: 2,
    customerName: 'SC Demo Construct SRL',
    type: 'chs', // chs | bo | cec | card | op
    amount: 5000,
    currency: 'RON',
    collectedAt: '2025-02-10',
    collectedBy: 'Agent vânzări 1',
    documentType: 'invoice',
    documentNumber: 'INV-2025-0001',
    reference: 'Încasare parțială factură',
    notes: ''
  },
  {
    id: 2,
    customerId: 2,
    customerName: 'SC Demo Construct SRL',
    type: 'bo',
    amount: 15000,
    currency: 'RON',
    collectedAt: '2025-02-15',
    collectedBy: 'Agent vânzări 1',
    documentType: 'contract',
    documentNumber: 'CTR-2025-001',
    reference: 'Bilet la ordin pentru scadență 30 de zile',
    notes: ''
  },
  {
    id: 3,
    customerId: 1,
    customerName: 'Ionescu Andrei',
    type: 'card',
    amount: 1240.5,
    currency: 'RON',
    collectedAt: '2025-01-16',
    collectedBy: 'Online',
    documentType: 'invoice',
    documentNumber: 'INV-2025-0001',
    reference: 'Plată online comandă BC-100001',
    notes: ''
  },
  {
    id: 4,
    customerId: 1,
    customerName: 'Ionescu Andrei',
    type: 'op',
    amount: 320,
    currency: 'RON',
    collectedAt: '2025-02-12',
    collectedBy: 'Client',
    documentType: 'proforma',
    documentNumber: 'PROF-2025-0045',
    reference: 'Plată OP comandă BC-100002',
    notes: ''
  }
]

export const useCollectionsStore = defineStore('collections', {
  state: () => ({
    items: [...demoCollections]
  }),
  getters: {
    all: (state) => state.items
  },
  actions: {
    addCollection(payload) {
      const nextId = this.items.length ? Math.max(...this.items.map((c) => c.id)) + 1 : 1
      this.items.push({ ...payload, id: nextId })
    }
  }
})
