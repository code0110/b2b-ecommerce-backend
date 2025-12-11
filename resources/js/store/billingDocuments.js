import { defineStore } from 'pinia'

/**
 * Store demo pentru documente financiare (facturi + proforme).
 * Într-un proiect real va fi alimentat din ERP / backend.
 */
const demoDocuments = [
  {
    id: 1,
    docNumber: 'INV-2025-0001',
    type: 'invoice', // invoice | proforma
    orderId: 1,
    customerUserId: 1,
    issueDate: '2025-01-16',
    dueDate: '2025-01-16',
    amountGross: 1240.5,
    currency: 'RON',
    status: 'paid', // paid | unpaid | overdue | cancelled
    paymentMethod: 'card',
    allowOnlinePayment: false,
    pdfUrl: null
  },
  {
    id: 2,
    docNumber: 'PROF-2025-0045',
    type: 'proforma',
    orderId: 2,
    customerUserId: 1,
    issueDate: '2025-02-01',
    dueDate: '2025-03-03',
    amountGross: 38250.0,
    currency: 'RON',
    status: 'unpaid',
    paymentMethod: 'op',
    allowOnlinePayment: true,
    pdfUrl: null
  },
  {
    id: 3,
    docNumber: 'INV-2025-0002',
    type: 'invoice',
    orderId: 3,
    customerUserId: 1,
    issueDate: '2025-02-11',
    dueDate: '2025-02-11',
    amountGross: 320.0,
    currency: 'RON',
    status: 'cancelled',
    paymentMethod: 'card',
    allowOnlinePayment: false,
    pdfUrl: null
  }
]

export const useBillingDocumentsStore = defineStore('billingDocuments', {
  state: () => ({
    documents: [...demoDocuments]
  }),
  getters: {
    all: (state) => state.documents,
    forUser: (state) => (userId) =>
      state.documents.filter((d) => d.customerUserId === Number(userId))
  },
  actions: {
    // loc pentru acțiuni viitoare (ex. refresh din API)
  }
})
