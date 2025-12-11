import { defineStore } from 'pinia'
import { useProductsStore } from '@/store/products'

/**
 * Store demo pentru coșul de cumpărături.
 * Într-un proiect real, coșul poate fi sincronizat cu backend / ERP.
 */

const initialItems = [
  {
    id: 1,
    productId: 1,
    name: 'Placă gips-carton 12.5mm',
    code: 'PGC-12.5',
    qty: 20,
    unit: 'buc',
    unitPrice: 25.5,
    currency: 'RON',
    stockStatus: 'in_stock',
    deliveryEstimate: '24-48h',
    weightKgPerUnit: 9.5
  },
  {
    id: 2,
    productId: 2,
    name: 'Profil metalic UW 50',
    code: 'UW-50',
    qty: 30,
    unit: 'buc',
    unitPrice: 17.5,
    currency: 'RON',
    stockStatus: 'low_stock',
    deliveryEstimate: '2-4 zile',
    weightKgPerUnit: 2.1
  }
]

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [...initialItems],
    lastId: initialItems.length,
    couponCode: '',
    couponInfo: ''
  }),
  getters: {
    lines: (state) => state.items,
    itemCount: (state) => state.items.reduce((sum, line) => sum + Number(line.qty || 0), 0),
    subTotal: (state) =>
      state.items.reduce(
        (sum, line) => sum + Number(line.qty || 0) * Number(line.unitPrice || 0),
        0
      ),
    totalWeightKg: (state) =>
      state.items.reduce(
        (sum, line) => sum + Number(line.qty || 0) * Number(line.weightKgPerUnit || 0),
        0
      )
  },
  actions: {
    addItem(productId, qty = 1) {
      const productsStore = useProductsStore()
      const product = productsStore.getById(productId)
      if (!product) return

      const existing = this.items.find((l) => l.productId === productId)
      if (existing) {
        existing.qty += qty
        return
      }

      this.lastId += 1
      this.items.push({
        id: this.lastId,
        productId: product.id,
        name: product.name,
        code: product.internalCode,
        qty,
        unit: 'buc',
        unitPrice: product.overridePrice ?? product.listPrice,
        currency: 'RON',
        stockStatus: product.stockStatus,
        deliveryEstimate: '24-72h',
        weightKgPerUnit: product.attributes?.greutateKg ?? 1
      })
    },
    updateQty(lineId, qty) {
      const line = this.items.find((l) => l.id === lineId)
      if (!line) return
      const val = Number(qty) || 0
      line.qty = val < 0 ? 0 : val
    },
    removeLine(lineId) {
      this.items = this.items.filter((l) => l.id !== lineId)
    },
    clear() {
      this.items = []
    },
    applyCoupon(code) {
      this.couponCode = code
      if (!code) {
        this.couponInfo = ''
        return
      }
      // Doar mesaj template – în realitate se validează cu backend.
      this.couponInfo =
        'Template: cuponul "' +
        code +
        '" a fost trimis spre validare. În implementarea reală se va verifica eligibilitatea promoției.'
    }
  }
})
