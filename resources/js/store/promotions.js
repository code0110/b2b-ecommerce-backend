import { defineStore } from 'pinia'

const demoPromotions = [
  {
    id: 1,
    name: 'Promoție gips-carton -10%',
    slug: 'promotie-gips-carton-10',
    shortDescription: 'Reducere 10% la toate plăcile de gips-carton standard.',
    longDescription:
      '<p>Campanie dedicată proiectelor de amenajări interioare. Reducerea se aplică pentru clienți B2B și B2C la plăcile de gips-carton standard, în limita stocului disponibil.</p>',
    type: 'discount_percent', // discount_percent / discount_fixed / x_get_y / bundle
    images: {
      list: '',
      header: '',
      mobile: ''
    },
    startDate: '2025-01-01',
    endDate: '2025-03-31',
    status: 'active', // active / inactive / upcoming
    packageType: 'iterative', // exclusive / iterative
    bonusType: 'valoare', // gratuitate / valoare
    clientTypes: ['B2B', 'B2C'],
    loggedIn: 'any', // any / logged / guest
    customerGroups: ['Distribuitori', 'Clienți VIP'],
    customers: [],
    categories: ['Plăci gips-carton'],
    brands: ['DemoBrand'],
    productList: ['PGC-12.5'],
    trigger: {
      minQtyPerProduct: 10,
      minCartValue: null,
      notes: 'Se aplică pentru minim 10 bucăți per produs eligibil.'
    },
    benefit: {
      discountPercent: 10,
      discountValue: null,
      freeProductCode: null,
      specialPriceCode: null
    }
  },
  {
    id: 2,
    name: 'Pachet profile + accesorii',
    slug: 'pachet-profile-accesorii',
    shortDescription: 'Pachet bundle pentru sisteme de profile metalice și accesorii.',
    longDescription:
      '<p>La achiziția a minim 100 ml de profile metalice, primești 5% discount la accesoriile compatibile.</p>',
    type: 'bundle',
    images: {
      list: '',
      header: '',
      mobile: ''
    },
    startDate: '2025-04-01',
    endDate: '2025-06-30',
    status: 'upcoming',
    packageType: 'exclusive',
    bonusType: 'valoare',
    clientTypes: ['B2B'],
    loggedIn: 'logged',
    customerGroups: ['Distribuitori'],
    customers: [],
    categories: ['Profile metalice'],
    brands: ['SteelBrand'],
    productList: ['UW-50'],
    trigger: {
      minQtyPerProduct: null,
      minCartValue: 5000,
      notes: 'Valoare minimă coș pentru trigger: 5.000 RON.'
    },
    benefit: {
      discountPercent: 5,
      discountValue: null,
      freeProductCode: null,
      specialPriceCode: null
    }
  }
]

export const usePromotionsStore = defineStore('promotions', {
  state: () => ({
    promotions: [...demoPromotions],
    lastId: demoPromotions.length
  }),
  getters: {
    all: (state) => state.promotions,
    activePromotions: (state) => state.promotions.filter((p) => p.status === 'active'),
    upcomingPromotions: (state) => state.promotions.filter((p) => p.status === 'upcoming'),
    getBySlug: (state) => (slug) => state.promotions.find((p) => p.slug === slug)
  },
  actions: {
    savePromotion(payload) {
      if (payload.id) {
        const index = this.promotions.findIndex((p) => p.id === payload.id)
        if (index !== -1) {
          this.promotions[index] = { ...this.promotions[index], ...payload }
        }
      } else {
        this.lastId += 1
        const created = { ...payload, id: this.lastId }
        this.promotions.push(created)
      }
    }
  }
})
