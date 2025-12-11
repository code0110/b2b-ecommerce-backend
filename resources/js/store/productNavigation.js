import { defineStore } from 'pinia'
import { useProductsStore } from './products'

/**
 * Store pentru navigare în catalog:
 * - produse recent vizualizate
 * - listă produse pentru comparare
 *
 * În implementarea reală poți persista aceste informații în localStorage sau backend.
 */
export const useProductNavigationStore = defineStore('productNavigation', {
  state: () => ({
    recentlyViewedIds: [],
    compareIds: []
  }),
  getters: {
    recentlyViewedProducts: (state) => {
      const productsStore = useProductsStore()
      return state.recentlyViewedIds
        .map((id) => productsStore.getById(id))
        .filter(Boolean)
    },
    compareProducts: (state) => {
      const productsStore = useProductsStore()
      return state.compareIds
        .map((id) => productsStore.getById(id))
        .filter(Boolean)
    }
  },
  actions: {
    addRecentlyViewed(id) {
      const numericId = Number(id)
      if (!numericId) return

      // Scoatem dacă există deja, apoi adăugăm la început
      this.recentlyViewedIds = this.recentlyViewedIds.filter((existingId) => existingId !== numericId)
      this.recentlyViewedIds.unshift(numericId)

      // Limităm lista la ultimele 10 produse
      if (this.recentlyViewedIds.length > 10) {
        this.recentlyViewedIds = this.recentlyViewedIds.slice(0, 10)
      }
    },
    toggleCompare(id) {
      const numericId = Number(id)
      if (!numericId) return

      if (this.compareIds.includes(numericId)) {
        this.compareIds = this.compareIds.filter((existingId) => existingId !== numericId)
      } else {
        // Limităm compararea la max. 4 produse
        if (this.compareIds.length >= 4) {
          this.compareIds.shift()
        }
        this.compareIds.push(numericId)
      }
    },
    removeFromCompare(id) {
      const numericId = Number(id)
      this.compareIds = this.compareIds.filter((existingId) => existingId !== numericId)
    },
    clearCompare() {
      this.compareIds = []
    }
  }
})
