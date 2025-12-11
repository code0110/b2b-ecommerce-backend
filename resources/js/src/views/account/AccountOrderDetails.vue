<template>
  <div class="container py-4">
    <div class="mb-3 d-flex justify-content-between align-items-center">
      <button
        type="button"
        class="btn btn-link text-decoration-none ps-0"
        @click="goBack"
      >
        ← Înapoi la istoricul de comenzi
      </button>
      <div class="small text-muted" v-if="frontCustomerName">
        Client activ:
        <strong>{{ frontCustomerName }}</strong>
        <span v-if="frontClientType" class="badge bg-primary ms-1">
          {{ frontClientType }}
        </span>
      </div>
    </div>

    <div v-if="!order" class="alert alert-warning">
      Comanda nu a fost găsită în setul de date demo.
    </div>

    <div v-else class="row g-3">
      <!-- Col stânga: detalii comandă -->
      <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">Comanda {{ order.code }}</h4>
              <div class="text-muted small">
                Plasată în data de {{ order.date }}
              </div>
            </div>
            <div class="text-end small">
              <div class="mb-1">
                <span
                  class="badge"
                  :class="{
                    'bg-warning text-dark': order.status === 'in_procesare',
                    'bg-info text-dark': order.status === 'in_livrare',
                    'bg-success': order.status === 'livrata',
                    'bg-secondary': order.status === 'anulata'
                  }"
                >
                  {{
                    order.status === 'in_procesare'
                      ? 'În procesare'
                      : order.status === 'in_livrare'
                        ? 'În livrare'
                        : order.status === 'livrata'
                          ? 'Livrată'
                          : 'Anulată'
                  }}
                </span>
              </div>
              <div>
                Status plată:
                <span
                  class="badge"
                  :class="{
                    'bg-danger': order.paymentStatus === 'neplatita',
                    'bg-warning text-dark': order.paymentStatus === 'in_asteptare',
                    'bg-success': order.paymentStatus === 'platita',
                    'bg-info text-dark': order.paymentStatus === 'ramburs'
                  }"
                >
                  {{
                    order.paymentStatus === 'neplatita'
                      ? 'Neplătită'
                      : order.paymentStatus === 'in_asteptare'
                        ? 'Plată în așteptare'
                        : order.paymentStatus === 'platita'
                          ? 'Plătită'
                          : 'Ramburs'
                  }}
                </span>
              </div>
            </div>
          </div>
          <div class="card-body small">
            <h6 class="text-uppercase text-muted mb-2">Produse comandate (demo)</h6>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Produs</th>
                    <th style="width: 100px;" class="text-end">Cantitate</th>
                    <th style="width: 140px;" class="text-end">Preț unit. (RON)</th>
                    <th style="width: 140px;" class="text-end">Total linie (RON)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(line, idx) in order.lines" :key="idx">
                    <td>
                      <div class="fw-semibold">{{ line.productName }}</div>
                      <div class="text-muted">
                        Cod: {{ line.internalCode }} • {{ line.brand }} • {{ line.category }}
                      </div>
                    </td>
                    <td class="text-end">{{ line.quantity }}</td>
                    <td class="text-end">
                      {{ line.unitPrice.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                    </td>
                    <td class="text-end">
                      {{ (line.unitPrice * line.quantity).toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <hr />

            <div class="row">
              <div class="col-md-6">
                <h6 class="text-uppercase text-muted mb-2">Rezumat valori</h6>
                <dl class="row mb-0">
                  <dt class="col-6">Valoare produse</dt>
                  <dd class="col-6 text-end">
                    {{ order.subtotal.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                    RON
                  </dd>
                  <dt class="col-6">Discount promoțional</dt>
                  <dd class="col-6 text-end text-success">
                    - {{ order.discount.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                    RON
                  </dd>
                  <dt class="col-6">Transport</dt>
                  <dd class="col-6 text-end">
                    {{ order.shipping.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                    RON
                  </dd>
                  <dt class="col-6">Total comandă</dt>
                  <dd class="col-6 text-end fw-semibold">
                    {{ order.total.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                    RON
                  </dd>
                </dl>
              </div>
              <div class="col-md-6">
                <h6 class="text-uppercase text-muted mb-2">Flux retur (RMA) – demo</h6>
                <p class="mb-2">
                  Într-o implementare reală, aici ar apărea butoane pentru a iniția
                  un retur pe produs sau pe întreaga comandă, cu urmărirea statusului
                  (cerere, aprobat, în curs, finalizat).
                </p>
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  @click="startDemoReturn"
                >
                  Inițiază retur demo
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Col dreapta: adrese, livrare, plată -->
      <div class="col-lg-4">
        <div class="card shadow-sm mb-3 small">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Adresă livrare</strong>
          </div>
          <div class="card-body">
            <div>{{ order.shippingAddress.line1 }}</div>
            <div>{{ order.shippingAddress.city }}, {{ order.shippingAddress.county }}</div>
          </div>
        </div>

        <div class="card shadow-sm mb-3 small">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Adresă facturare</strong>
          </div>
          <div class="card-body">
            <div>{{ order.billingAddress.line1 }}</div>
            <div>{{ order.billingAddress.city }}, {{ order.billingAddress.county }}</div>
          </div>
        </div>

        <div class="card shadow-sm mb-3 small">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Livrare & plată</strong>
          </div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-5">Livrare</dt>
              <dd class="col-7">
                {{ order.shippingMethodLabel }}
              </dd>

              <dt class="col-5">Plată</dt>
              <dd class="col-7">
                {{ order.paymentMethodLabel }}
              </dd>

              <dt class="col-5">Tip client</dt>
              <dd class="col-7">
                <span
                  class="badge"
                  :class="order.clientType === 'B2B' ? 'bg-primary' : 'bg-secondary'"
                >
                  {{ order.clientType }}
                </span>
              </dd>

              <dt class="col-5">Mod plasare</dt>
              <dd class="col-7">
                <span v-if="order.isImpersonated">
                  Comandă plasată în numele clientului (impersonare).
                </span>
                <span v-else>
                  Comandă plasată direct din contul clientului.
                </span>
              </dd>
            </dl>
          </div>
        </div>

        <div class="card shadow-sm small">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Acțiuni rapide</strong>
          </div>
          <div class="card-body">
            <button
              type="button"
              class="btn btn-sm btn-outline-primary w-100 mb-2"
              @click="repeatOrder(order)"
            >
              Comandă din nou
            </button>
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary w-100"
              @click="downloadInvoice"
            >
              Descarcă factura (demo)
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const frontClientType = computed(() => {
  if (authStore.impersonatedCustomer?.clientType) {
    return authStore.impersonatedCustomer.clientType
  }
  if (authStore.user?.role === 'b2b') return 'B2B'
  if (authStore.user?.role === 'b2c') return 'B2C'
  return null
})

const frontCustomerName = computed(() => {
  if (authStore.impersonatedCustomer?.name) {
    return authStore.impersonatedCustomer.name
  }
  if (authStore.user && (authStore.user.role === 'b2b' || authStore.user.role === 'b2c')) {
    return authStore.user.name
  }
  return null
})

const demoOrders = [
  {
    id: 1,
    code: 'CMD-1001',
    date: '2025-02-18',
    clientType: 'B2B',
    status: 'in_procesare',
    paymentStatus: 'in_asteptare',
    subtotal: 26000.5,
    discount: 1500.0,
    shipping: 0,
    total: 24500.5,
    isImpersonated: true,
    shippingMethodLabel: 'Livrare cu flotă proprie',
    paymentMethodLabel: 'Condiții contractuale B2B',
    shippingAddress: {
      line1: 'Str. Fabricii nr. 10',
      city: 'București',
      county: 'Ilfov'
    },
    billingAddress: {
      line1: 'Str. Depozitelor nr. 5',
      city: 'București',
      county: 'Ilfov'
    },
    lines: [
      {
        productName: 'Ciment Portland 40kg',
        internalCode: 'PRD-001',
        brand: 'BrandX',
        category: 'Materiale de construcții',
        quantity: 200,
        unitPrice: 45.0
      },
      {
        productName: 'Aditiv beton',
        internalCode: 'PRD-002',
        brand: 'BrandY',
        category: 'Materiale de construcții',
        quantity: 50,
        unitPrice: 30.5
      }
    ]
  },
  {
    id: 3,
    code: 'CMD-0999',
    date: '2025-02-10',
    clientType: 'B2C',
    status: 'livrata',
    paymentStatus: 'ramburs',
    subtotal: 520.99,
    discount: 0,
    shipping: 0,
    total: 520.99,
    isImpersonated: false,
    shippingMethodLabel: 'Curier rapid',
    paymentMethodLabel: 'Ramburs la livrare',
    shippingAddress: {
      line1: 'Str. Exemplu nr. 3',
      city: 'Cluj-Napoca',
      county: 'Cluj'
    },
    billingAddress: {
      line1: 'Str. Exemplu nr. 3',
      city: 'Cluj-Napoca',
      county: 'Cluj'
    },
    lines: [
      {
        productName: 'Set scule mână',
        internalCode: 'PRD-020',
        brand: 'BrandTool',
        category: 'Unelte',
        quantity: 1,
        unitPrice: 520.99
      }
    ]
  }
]

const orderId = computed(() => Number(route.params.id))
const order = computed(() => demoOrders.find((o) => o.id === orderId.value) || null)

const goBack = () => {
  router.push('/account/orders')
}

const repeatOrder = (order) => {
  window.alert(
    `Demo: comanda ${order.code} ar reumple coșul cu produsele originale în implementarea reală.`
  )
}

const downloadInvoice = () => {
  window.alert('Demo: aici s-ar descărca PDF-ul facturii asociate acestei comenzi.')
}

const startDemoReturn = () => {
  window.alert(
    'Demo: aici s-ar porni un flux de retur (RMA) la nivel de produs sau de comandă.'
  )
}
</script>
