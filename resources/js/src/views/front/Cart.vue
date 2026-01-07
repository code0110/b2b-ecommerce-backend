<template>
  <div class="container py-4">
    <div class="row mb-3">
      <div class="col-md-8">
        <h1 class="h5 mb-1">Coș de cumpărături</h1>
        <p class="text-muted small mb-0">
          Revizuiește produsele, cantitățile și aplică vouchere de reducere.
        </p>
      </div>
      <div class="col-md-4 text-md-end small mt-2 mt-md-0" v-if="frontClientLabel">
        <div class="text-muted">
          Client activ: <strong>{{ frontClientLabel }}</strong>
        </div>
        <div class="text-muted" v-if="isImpersonating">
          (mod impersonare – {{ user?.name || 'agent/director' }})
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Col stânga: tabel produse -->
      <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong class="small text-uppercase">Produse în coș (demo)</strong>
            <span class="badge bg-light text-dark small">
              {{ items.length }} linii · {{ totalQuantity }} buc
            </span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light small text-uppercase text-muted">
                  <tr>
                    <th>Produs</th>
                    <th style="width: 90px;" class="text-center">Stoc</th>
                    <th style="width: 120px;" class="text-end">Preț unitar</th>
                    <th style="width: 120px;" class="text-center">Cantitate</th>
                    <th style="width: 120px;" class="text-end">Total linie</th>
                    <th style="width: 50px;"></th>
                  </tr>
                </thead>
                <tbody class="small">
                  <tr v-for="item in items" :key="item.id">
                    <td>
                      <div class="d-flex">
                        <div class="flex-shrink-0 me-2">
                          <div
                            class="bg-light border rounded"
                            style="width: 48px; height: 48px; background-size: cover; background-position: center;"
                            :style="item.imageUrl ? { backgroundImage: 'url(' + item.imageUrl + ')' } : {}"
                          ></div>
                        </div>
                        <div class="flex-grow-1">
                          <div class="fw-semibold">{{ item.name }}</div>
                          <div class="text-muted">
                            Cod: {{ item.code }}
                          </div>
                          <div class="text-muted" v-if="item.unitInfo">
                            UM: {{ item.unitInfo }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      <span
                        v-if="item.is_gift"
                        class="badge bg-info text-white"
                      >
                        <i class="bi bi-gift-fill me-1"></i> CADOU
                      </span>
                      <span
                        v-else
                        class="badge"
                        :class="{
                          'bg-success': item.stockStatus === 'in_stock',
                          'bg-warning text-dark': item.stockStatus === 'limited',
                          'bg-secondary': item.stockStatus === 'supplier'
                        }"
                      >
                        {{ stockStatusLabel(item.stockStatus) }}
                      </span>
                      <div class="text-muted" v-if="item.deliveryEstimate && !item.is_gift">
                        {{ item.deliveryEstimate }}
                      </div>
                    </td>
                    <td class="text-end">
                      <span v-if="item.is_gift" class="text-success fw-bold">GRATUIT</span>
                      <span v-else>{{ formatMoney(item.price) }}</span>
                    </td>
                    <td class="text-center" style="max-width: 120px;">
                      <input
                        type="number"
                        min="1"
                        class="form-control form-control-sm text-center"
                        v-model.number="item.quantity"
                        @change="updateQuantity(item, item.quantity)"
                        :disabled="item.is_gift"
                        :readonly="item.is_gift"
                      />
                    </td>
                    <td class="text-end fw-semibold">
                      <span v-if="item.is_gift" class="text-success">0,00 RON</span>
                      <span v-else>{{ formatMoney(lineTotal(item)) }}</span>
                    </td>
                    <td class="text-end">
                      <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        @click="removeItem(item.id)"
                        :disabled="item.is_gift"
                        :title="item.is_gift ? 'Produsul cadou se elimină automat la ștergerea produselor eligibile' : 'Elimină'"
                      >
                        ×
                      </button>
                    </td>
                  </tr>
                  <tr v-if="items.length === 0">
                    <td colspan="6">
                      <div class="text-center text-muted py-4">
                        Coșul este gol.
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer small text-muted">
            <strong>Notă demo:</strong>
            în varianta completă, această zonă ar fi conectată la un API de coș,
            cu recalcularea automată a promoțiilor și a disponibilității stocului
            în timp real (inclusiv pentru comenzi B2B mari).
          </div>
        </div>

        <!-- Voucher / note comandă -->
        <div class="card shadow-sm">
          <div class="card-body small">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Cupon discount / voucher</label>
                <div class="input-group input-group-sm">
                  <input
                    v-model="voucherCode"
                    type="text"
                    class="form-control"
                    placeholder="Cod voucher..."
                  />
                  <button
                    type="button"
                    class="btn btn-outline-primary"
                    @click="applyVoucher"
                  >
                    Aplică
                  </button>
                </div>
                <div class="form-text" v-if="appliedVoucher">
                  Cupon aplicat: <strong>{{ appliedVoucher }}</strong>
                  <button class="btn btn-link btn-sm text-danger p-0 ms-2" @click="removeVoucher">
                    (Șterge)
                  </button>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Notă pentru comandă (internă / livrare)</label>
                <textarea
                  v-model="orderNote"
                  class="form-control form-control-sm"
                  rows="3"
                  placeholder="Instrucțiuni pentru livrare, referință proiect, număr de comandă client..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Col dreapta: sumar coș -->
      <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Rezumat comandă</strong>
          </div>
          <div class="card-body small">
            <ul class="list-unstyled mb-2">
              <li class="d-flex justify-content-between mb-1">
                <span>Subtotal produse</span>
                <span>{{ formatMoney(subtotal) }}</span>
              </li>
              <li
                class="d-flex justify-content-between mb-1 text-success"
                v-if="discountAmount > 0"
              >
                <span>Discount</span>
                <span>-{{ formatMoney(discountAmount) }}</span>
              </li>
              <li class="d-flex justify-content-between mb-1">
                <span>Transport estimat</span>
                <span>
                  <span v-if="shippingCost === 0">0 RON (gratuit peste prag)</span>
                  <span v-else>{{ formatMoney(shippingCost) }}</span>
                </span>
              </li>
              <li class="d-flex justify-content-between border-top pt-2 mt-2 fw-semibold">
                <span>Total estimat</span>
                <span>{{ formatMoney(grandTotal) }}</span>
              </li>
            </ul>
            <p class="text-muted mb-1">
              Valorile sunt demonstrative. În producție, taxele, promoțiile
              și pragurile de transport gratuit ar fi calculate din configurația
              de prețuri și reguli de livrare.
            </p>
          </div>
          <div class="card-footer d-grid gap-2">
            <button
              type="button"
              class="btn btn-primary btn-sm"
              :disabled="items.length === 0"
              @click="goToCheckout"
            >
              Continuă către Checkout
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="goBackToCatalog"
            >
              Continuă cumpărăturile
            </button>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body small">
            <h2 class="h6 text-uppercase text-muted mb-2">Note B2B (demo)</h2>
            <p class="mb-1">
              Pentru clienții B2B, coșul poate afișa condiții comerciale (discount standard,
              limită de credit, termene de plată) și mesaje de tip „comanda va fi importată
              în ERP ca proformă normală”.
            </p>
            <p class="mb-0 text-muted">
              Acest template nu implementează logica de business, dar pregătește structura UI
              pentru integrarea cu ERP și regulile de credit / blocaj comenzi.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { useCartStore } from '@/store/cart'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const user = computed(() => authStore.user || null)
const isImpersonating = computed(() => !!authStore.impersonatedCustomer)
const frontClientType = computed(() => {
  if (authStore.impersonatedCustomer) {
    return authStore.impersonatedCustomer.clientType || null
  }
  return authStore.user?.clientType || null
})
const frontClientLabel = computed(() => {
  if (authStore.impersonatedCustomer) {
    return authStore.impersonatedCustomer.name || ''
  }
  return authStore.user?.name || ''
})

// Cart state from store
const items = computed(() => cartStore.lines)
const subtotal = computed(() => cartStore.subTotal)
const grandTotal = computed(() => cartStore.grandTotal)
const discountAmount = computed(() => cartStore.discountAmount)
const totalQuantity = computed(() => cartStore.itemCount)

// UI state
const voucherCode = ref('')
const appliedVoucher = computed(() => cartStore.couponCode)
const orderNote = ref('')
const shippingCost = ref(0) // TODO: Get from backend

onMounted(() => {
  cartStore.fetchCart()
})

const formatMoney = (val) => {
  return new Intl.NumberFormat('ro-RO', {
    style: 'currency',
    currency: 'RON'
  }).format(val)
}

const lineTotal = (item) => {
  return (item.price * item.quantity)
}

const stockStatusLabel = (status) => {
  const map = {
    'in_stock': 'În stoc',
    'limited': 'Stoc limitat',
    'supplier': 'Stoc furnizor',
    'out_of_stock': 'Indisponibil'
  }
  return map[status] || status
}

const removeItem = async (id) => {
  if (!confirm('Eliminați acest produs din coș?')) return
  await cartStore.removeLine(id)
}

const updateQuantity = async (item, newQty) => {
  if (newQty < 1) return
  await cartStore.updateQty(item.id, newQty)
}

const applyVoucher = async () => {
  if (!voucherCode.value) return
  try {
    await cartStore.applyCoupon(voucherCode.value)
    voucherCode.value = '' // Clear input on success
  } catch (e) {
    alert(e.response?.data?.message || 'Eroare la aplicarea cuponului')
  }
}

const removeVoucher = async () => {
  if (!confirm('Ștergeți cuponul?')) return
  await cartStore.removeCoupon()
}

const goToCheckout = () => {
  router.push({ name: 'checkout' })
}

const goBackToCatalog = () => {
  router.push({ name: 'catalog' })
}
</script>
