import { defineStore } from 'pinia'

const demoGroups = [
  {
    id: 1,
    name: 'Distribuitori',
    type: 'B2B',
    defaultDiscount: 10,
    defaultPaymentTermDays: 30,
    defaultCreditLimit: 100000,
    promotions: [1],
    segmentationNotes: 'Clienți en-gros, acoperire regională.'
  },
  {
    id: 2,
    name: 'Retaileri',
    type: 'B2B',
    defaultDiscount: 7,
    defaultPaymentTermDays: 14,
    defaultCreditLimit: 50000,
    promotions: [],
    segmentationNotes: 'Magazine specializate materiale construcții.'
  },
  {
    id: 3,
    name: 'Clienți VIP',
    type: 'B2C',
    defaultDiscount: 5,
    defaultPaymentTermDays: 0,
    defaultCreditLimit: 0,
    promotions: [],
    segmentationNotes: 'Clienți persoane fizice cu istoric semnificativ de comenzi.'
  }
]

export const useCustomerGroupsStore = defineStore('customerGroups', {
  state: () => ({
    groups: [...demoGroups],
    lastId: demoGroups.length
  }),
  getters: {
    all: (state) => state.groups
  },
  actions: {
    saveGroup(payload) {
      if (payload.id) {
        const index = this.groups.findIndex((g) => g.id === payload.id)
        if (index !== -1) {
          this.groups[index] = { ...this.groups[index], ...payload }
        }
      } else {
        this.lastId += 1
        const ng = { ...payload, id: this.lastId }
        this.groups.push(ng)
      }
    }
  }
})
