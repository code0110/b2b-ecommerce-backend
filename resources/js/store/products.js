import { defineStore } from 'pinia'

const demoProducts = [
  {
    id: 1,
    name: 'Placă gips-carton 12.5mm',
    internalCode: 'PGC-12.5',
    mainCategory: 'Plăci gips-carton',
    categories: ['Plăci gips-carton'],
    brand: 'DemoBrand',
    stockStatus: 'in_stock', // in_stock / out_of_stock / low_stock / on_order
    stockQty: 240,
    listPrice: 25.5,
    prp: 27.0,
    vat: 19,
    overridePrice: null,
    erpId: 'ERP-1001',
    barcode: '1234567890123',
    isPublished: true,
    isPromoted: true,
    sortOrder: 10,
    shortDescription: 'Placă gips-carton standard 12.5mm.',
    longDescription: '<p>Placă gips-carton pentru pereți și plafoane.</p>',
    attributes: {
      material: 'Gips-carton',
      grosime: '12.5mm',
      dimensiune: '1200x2600',
      culoare: 'alb'
    },
    variationType: 'simple',
    variants: [],
    documents: [],
    relatedProducts: [],
    complementaryProducts: [],
    erpSyncStatus: 'synced'
  },
  {
    id: 2,
    name: 'Profil metalic UW 50',
    internalCode: 'UW-50',
    mainCategory: 'Profile metalice',
    categories: ['Profile metalice'],
    brand: 'SteelBrand',
    stockStatus: 'low_stock',
    stockQty: 15,
    listPrice: 18.0,
    prp: 19.5,
    vat: 19,
    overridePrice: 17.5,
    erpId: 'ERP-2002',
    barcode: '9876543210987',
    isPublished: false,
    isPromoted: false,
    sortOrder: 20,
    shortDescription: 'Profil UW 50 pentru structuri pereți.',
    longDescription: '<p>Profil metalic pentru pereți interiori.</p>',
    attributes: {
      material: 'Oțel',
      grosime: '0.6mm',
      dimensiune: '4m',
      culoare: 'zincat'
    },
    variationType: 'simple',
    variants: [],
    documents: [],
    relatedProducts: [],
    complementaryProducts: [],
    erpSyncStatus: 'pending'
  }
]

export const useProductsStore = defineStore('products', {
  state: () => ({
    products: [...demoProducts],
    lastId: demoProducts.length
  }),
  getters: {
    all: (state) => state.products,
    getById: (state) => (id) => state.products.find(p => p.id === Number(id))
  },
  actions: {
    saveProduct(payload) {
      if (payload.id) {
        const index = this.products.findIndex(p => p.id === payload.id)
        if (index !== -1) {
          this.products[index] = { ...this.products[index], ...payload }
        }
      } else {
        this.lastId += 1
        const newProd = { ...payload, id: this.lastId }
        this.products.push(newProd)
      }
    },
    togglePublish(id) {
      const p = this.getById(id)
      if (p) p.isPublished = !p.isPublished
    }
  }
})
