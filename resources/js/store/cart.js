import { defineStore } from 'pinia'
import api from '@/services/http'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    cartId: null,
    subtotal: 0,
    total: 0,
    discountTotal: 0,
    couponCode: '',
    couponInfo: '',
    loading: false
  }),

  getters: {
    lines: (state) => state.items,
    itemCount: (state) => state.items.reduce((sum, line) => sum + Number(line.quantity || 0), 0),
    subTotal: (state) => state.subtotal,
    grandTotal: (state) => state.total,
    discountAmount: (state) => state.discountTotal
  },

  actions: {
    async fetchCart() {
      this.loading = true
      try {
        const { data } = await api.get('/cart')
        this.setCartData(data)
      } catch (e) {
        console.error('Fetch cart error', e)
        this.items = []
        this.subtotal = 0
        this.total = 0
      } finally {
        this.loading = false
      }
    },

    setCartData(data) {
      this.cartId = data.id
      this.items = (data.items || []).map(item => ({
        ...item,
        name: item.product_name || item.product?.name,
        code: item.product_code || item.product?.internal_code,
        price: parseFloat(item.unit_price || 0),
        total: parseFloat(item.line_total || 0),
        // Keep other fields
        id: item.id,
        quantity: item.quantity,
        unit: item.unit
      }))
      this.subtotal = parseFloat(data.subtotal || 0)
      this.total = parseFloat(data.total || 0)
      this.discountTotal = parseFloat(data.discount_total || 0)
      
      // Update coupon info if available in response
      if (data.applied_coupon) {
        this.couponCode = data.applied_coupon.code
        this.couponInfo = `Reducere: ${data.applied_coupon.discount_amount} RON`
      } else {
        this.couponCode = ''
        this.couponInfo = ''
      }
    },

    async addItem(productId, qty = 1, productVariantId = null, unit = null) {
      try {
        const payload = {
          product_id: productId,
          quantity: qty
        }
        if (productVariantId) {
          payload.product_variant_id = productVariantId
        }
        if (unit) {
          payload.unit = unit
        }

        const { data } = await api.post('/cart/items', payload)
        this.setCartData(data)
        return true
      } catch (e) {
        console.error('Add item error', e)
        return false
      }
    },

    async updateQty(lineId, qty) {
      if (qty <= 0) return this.removeLine(lineId)
      
      try {
        const { data } = await api.put(`/cart/items/${lineId}`, {
          quantity: qty
        })
        this.setCartData(data)
      } catch (e) {
        console.error('Update qty error', e)
      }
    },

    async removeLine(lineId) {
      try {
        const { data } = await api.delete(`/cart/items/${lineId}`)
        this.setCartData(data)
      } catch (e) {
        console.error('Remove line error', e)
      }
    },

    async applyCoupon(code) {
      this.couponCode = code
      // TODO: Implement backend coupon application if available
      console.log('Coupon application to be implemented', code)
    }
  }
})
