import { defineStore } from 'pinia'

/**
 * Store demo pentru fluxul de ofertare & negociere B2B/B2C.
 * În implementarea reală, ofertele vin din backend / ERP / CRM,
 * iar statusurile și conversiile în comenzi sunt gestionate acolo.
 */

const demoOffers = [
  {
    id: 1,
    code: 'OFF-0001',
    customerName: 'SC Demo Construct SRL',
    customerEmail: 'mihai.popescu@democonstruct.ro',
    customerType: 'B2B',
    createdAt: '2025-01-20T10:15:00Z',
    updatedAt: '2025-01-21T08:05:00Z',
    status: 'in_review', // requested / in_review / approved / rejected / converted
    assignedAgent: 'Ionescu Agent',
    approvedBy: 'Director Vânzări',
    relatedOrderCode: null,
    currency: 'RON',
    totalList: 3200,
    totalProposed: 2950,
    lines: [
      {
        id: 1,
        productId: 1,
        productName: 'Placă gips-carton 12.5mm',
        productCode: 'PGC-12.5',
        requestedQty: 120,
        unit: 'buc',
        listPrice: 25.5,
        proposedPrice: 23.5
      },
      {
        id: 2,
        productId: 2,
        productName: 'Profil metalic UW 50',
        productCode: 'UW-50',
        requestedQty: 40,
        unit: 'buc',
        listPrice: 18.0,
        proposedPrice: 17.0
      }
    ],
    notesFromCustomer: 'Proiect nou, dorim condiții speciale pentru volum.',
    internalNotes: 'Client bun platnic, recomandăm acordarea discountului propus.'
  },
  {
    id: 2,
    code: 'OFF-0002',
    customerName: 'Popescu Ion',
    customerEmail: 'ion.popescu@example.com',
    customerType: 'B2C',
    createdAt: '2025-02-05T14:30:00Z',
    updatedAt: '2025-02-05T14:30:00Z',
    status: 'requested',
    assignedAgent: null,
    approvedBy: null,
    relatedOrderCode: null,
    currency: 'RON',
    totalList: 850,
    totalProposed: null,
    lines: [
      {
        id: 1,
        productId: 1,
        productName: 'Placă gips-carton 12.5mm',
        productCode: 'PGC-12.5',
        requestedQty: 30,
        unit: 'buc',
        listPrice: 25.5,
        proposedPrice: null
      }
    ],
    notesFromCustomer: 'Renovare apartament, caut cel mai bun preț.',
    internalNotes: ''
  }
]

export const useOffersStore = defineStore('offers', {
  state: () => ({
    offers: [...demoOffers],
    lastId: demoOffers.length
  }),
  getters: {
    all: (state) => state.offers,
    byId: (state) => (id) => state.offers.find((o) => o.id === Number(id)) || null,
    byCustomerEmail: (state) => (email) =>
      state.offers.filter((o) => o.customerEmail && o.customerEmail.toLowerCase() === String(email || '').toLowerCase())
  },
  actions: {
    createOfferRequest(payload) {
      this.lastId += 1
      const now = new Date().toISOString()
      const newOffer = {
        id: this.lastId,
        code: 'OFF-' + String(this.lastId).padStart(4, '0'),
        status: 'requested',
        createdAt: now,
        updatedAt: now,
        assignedAgent: null,
        approvedBy: null,
        relatedOrderCode: null,
        currency: 'RON',
        totalList: payload.totalList || null,
        totalProposed: null,
        lines: payload.lines || [],
        notesFromCustomer: payload.notesFromCustomer || '',
        internalNotes: '',
        customerName: payload.customerName || '',
        customerEmail: payload.customerEmail || '',
        customerType: payload.customerType || 'B2B'
      }
      this.offers.push(newOffer)
      return newOffer
    },
    updateStatus(id, status) {
      const offer = this.offers.find((o) => o.id === Number(id))
      if (!offer) return
      offer.status = status
      offer.updatedAt = new Date().toISOString()
    },
    assignAgent(id, agentName) {
      const offer = this.offers.find((o) => o.id === Number(id))
      if (!offer) return
      offer.assignedAgent = agentName
      offer.updatedAt = new Date().toISOString()
    },
    markConvertedToOrder(id, orderCode) {
      const offer = this.offers.find((o) => o.id === Number(id))
      if (!offer) return
      offer.status = 'converted'
      offer.relatedOrderCode = orderCode
      offer.updatedAt = new Date().toISOString()
    }
  }
})
