import { defineStore } from 'pinia'

/**
 * Store demo pentru reguli de transport.
 * Într-un proiect real va fi sincronizat cu ERP / integrare curieri.
 */

const demoMethods = [
  {
    id: 1,
    name: 'Curier rapid',
    code: 'courier-express',
    type: 'courier', // courier | own-fleet | pickup
    active: true,
    description: 'Livrare în 24-48h prin curier rapid.',
    freeShippingThreshold: 300,
    allowedCustomerTypes: ['B2B', 'B2C'],
    weightRules: [
      { id: 11, minKg: 0, maxKg: 5, price: 25, currency: 'RON' },
      { id: 12, minKg: 5, maxKg: 20, price: 35, currency: 'RON' },
      { id: 13, minKg: 20, maxKg: null, price: 60, currency: 'RON' }
    ],
    valueRules: [
      { id: 21, minValue: 0, maxValue: 299.99, extraFee: 0, freeShipping: false },
      { id: 22, minValue: 300, maxValue: null, extraFee: 0, freeShipping: true }
    ],
    regionRules: [
      {
        id: 31,
        regionType: 'county', // country | region | county | locality
        regionCode: 'B',
        label: 'București',
        extraFee: 0
      },
      {
        id: 32,
        regionType: 'county',
        regionCode: 'MM',
        label: 'Zone greu accesibile (exemplu)',
        extraFee: 20
      }
    ]
  },
  {
    id: 2,
    name: 'Livrare flota proprie',
    code: 'own-fleet',
    type: 'own-fleet',
    active: true,
    description: 'Livrare cu flota proprie pentru comenzi voluminoase.',
    freeShippingThreshold: 2000,
    allowedCustomerTypes: ['B2B'],
    weightRules: [
      { id: 41, minKg: 0, maxKg: 500, price: 150, currency: 'RON' },
      { id: 42, minKg: 500, maxKg: null, price: 300, currency: 'RON' }
    ],
    valueRules: [
      { id: 51, minValue: 0, maxValue: 1999.99, extraFee: 0, freeShipping: false },
      { id: 52, minValue: 2000, maxValue: null, extraFee: 0, freeShipping: true }
    ],
    regionRules: [
      {
        id: 61,
        regionType: 'region',
        regionCode: 'RO-SUD',
        label: 'Sud / Sud-Est',
        extraFee: 0
      }
    ]
  },
  {
    id: 3,
    name: 'Ridicare din depozit',
    code: 'pickup-depot',
    type: 'pickup',
    active: true,
    description: 'Clientul ridică marfa direct din depozit / showroom.',
    freeShippingThreshold: null,
    allowedCustomerTypes: ['B2B', 'B2C'],
    weightRules: [],
    valueRules: [],
    regionRules: []
  }
]

export const useShippingStore = defineStore('shipping', {
  state: () => ({
    methods: [...demoMethods],
    lastMethodId: demoMethods.length
  }),
  getters: {
    all: (state) => state.methods,
    getById: (state) => (id) => state.methods.find((m) => m.id === Number(id))
  },
  actions: {
    saveMethod(payload) {
      if (payload.id) {
        const idx = this.methods.findIndex((m) => m.id === payload.id)
        if (idx !== -1) {
          this.methods[idx] = { ...this.methods[idx], ...payload }
        }
      } else {
        this.lastMethodId += 1
        const newMethod = { ...payload, id: this.lastMethodId }
        this.methods.push(newMethod)
      }
    }
  }
})
