<template>
  <div class="container py-4">
    <div class="row mb-3">
      <div class="col-md-8">
        <h1 class="h5 mb-1">Coș de cumpărături (demo)</h1>
        <p class="text-muted small mb-0">
          Aceasta este o pagină de coș de cumpărături orientată B2B/B2C. Datele sunt demo,
          dar structura acoperă: produse, cantități, stoc, termene de livrare, voucher
          și sumar de comandă. În implementarea reală, ar fi legată de un store global
          de coș și de regulile de promoții / discounturi.
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
                        class="badge"
                        :class="{
                          'bg-success': item.stockStatus === 'in_stock',
                          'bg-warning text-dark': item.stockStatus === 'limited',
                          'bg-secondary': item.stockStatus === 'supplier'
                        }"
                      >
                        {{ stockStatusLabel(item.stockStatus) }}
                      </span>
                      <div class="text-muted" v-if="item.deliveryEstimate">
                        {{ item.deliveryEstimate }}
                      </div>
                    </td>
                    <td class="text-end">
                      {{ formatMoney(item.price) }}
                    </td>
                    <td class="text-center" style="max-width: 120px;">
                      <input
                        type="number"
                        min="1"
                        class="form-control form-control-sm text-center"
                        v-model.number="item.quantity"
                      />
                    </td>
                    <td class="text-end fw-semibold">
                      {{ formatMoney(lineTotal(item)) }}
                    </td>
                    <td class="text-end">
                      <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        @click="removeItem(item.id)"
                      >
                        ×
                      </button>
                    </td>
                  </tr>
                  <tr v-if="items.length === 0">
                    <td colspan="6">
                      <div class="text-center text-muted py-4">
                        Coșul este gol în acest template demo.
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
                <label class="form-label">Cupon discount / voucher (demo)</label>
                <div class="input-group input-group-sm">
                  <input
                    v-model="voucherCode"
                    type="text"
                    class="form-control"
                    placeholder="DEMO10, BLACKFRIDAY, CONTRACT-XYZ..."
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
                  Cupon aplicat: <strong>{{ appliedVoucher }}</strong> – reducere simulată
                  de {{ formatMoney(discountAmount) }}.
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Notă pentru comandă (internă / livrare)</label>
                <textarea
                  v-model="orderNote"
                  class="form-control form-control-sm"
                  rows="3"
                  placeholder="Instrucțiuni pentru livrare, referință proiect, număr de comandă client... (demo)"
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
            <strong class="small text-uppercase">Rezumat comandă (demo)</strong>
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
                <span>Discount (voucher demo)</span>
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
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const router = useRouter()
const authStore = useAuthStore()

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

const items = ref([
  {
    id: 1,
    name: 'Produs demo 1 – exemplu B2B',
    code: 'PRD-DEMO-001',
    imageUrl: '',
    price: 250,
    quantity: 2,
    stockStatus: 'in_stock',
    deliveryEstimate: 'Livrare 24–48h',
    unitInfo: 'buc / cutie (10 buc)'
  },
  {
    id: 2,
    name: 'Produs demo 2 – palet',
    code: 'PRD-DEMO-002',
    imageUrl: '',
    price: 150,
    quantity: 3,
    stockStatus: 'limited',
    deliveryEstimate: 'Stoc limitat – confirmare agent',
    unitInfo: 'palet (50 cutii)'
  },
  {
    id: 3,
    name: 'Produs demo 3 – la comandă',
    code: 'PRD-DEMO-003',
    imageUrl: '',
    price: 100,
    quantity: 1,
    stockStatus: 'supplier',
    deliveryEstimate: 'La comandă – 7–10 zile',
    unitInfo: 'buc'
  }
])

const voucherCode = ref('')
const appliedVoucher = ref('')
const orderNote = ref('')
const discountPercent = ref(0)

const totalQuantity = computed(() =>
  items.value.reduce((sum, item) => sum + (item.quantity || 0), 0)
)

const lineTotal = (item) => {
  const qty = item.quantity || 0
  return item.price * qty
}

const subtotal = computed(() =>
  items.value.reduce((sum, item) => sum + lineTotal(item), 0)
)

const discountAmount = computed(() => {
  if (discountPercent.value <= 0) return 0
  return Math.round(subtotal.value * discountPercent.value) / 100
})

const shippingCost = computed(() => {
  if (subtotal.value === 0) return 0
  if (subtotal.value >= 500) {
    return 0
  }
  return frontClientType.value === 'B2B' ? 20 : 25
})

const grandTotal = computed(() => {
  return subtotal.value - discountAmount.value + shippingCost.value
})

const stockStatusLabel = (status) => {
  switch (status) {
    case 'in_stock':
      return 'În stoc'
    case 'limited':
      return 'Stoc limitat'
    case 'supplier':
      return 'În stoc furnizor / la comandă'
    default:
      return status
  }
}

const formatMoney = (value) => {
  const number = Number(value || 0)
  return number.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }) + ' RON'
}

const removeItem = (id) => {
  items.value = items.value.filter((item) => item.id !== id)
}

const applyVoucher = () => {
  const code = voucherCode.value.trim().toUpperCase()
  if (!code) {
    window.alert('Introdu un cod de voucher (demo).')
    return
  }

  if (code === 'DEMO10') {
    discountPercent.value = 10
    appliedVoucher.value = 'DEMO10 – reducere 10% demo'
  } else if (code === 'DEMO20') {
    discountPercent.value = 20
    appliedVoucher.value = 'DEMO20 – reducere 20% demo'
  } else {
    discountPercent.value = 0
    appliedVoucher.value = ''
    window.alert(
      'Codul de voucher introdus nu este recunoscut în acest template demo. În producție, validarea s-ar face în backend.'
    )
    return
  }

  window.alert(
    'Voucher aplicat în mod demonstrativ. Într-un sistem real, reducerea ar fi validată și salvată în coșul din backend.'
  )
}

const goToCheckout = () => {
  if (items.value.length === 0) {
    window.alert('Coșul este gol în acest template demo.')
    return
  }
  router.push({ name: 'checkout' })
}

const goBackToCatalog = () => {
  router.push({ name: 'home' })
}
</script>
