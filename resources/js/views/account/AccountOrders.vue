<template>
  <div class="container py-4">
    <!-- Header + context client -->
    <div class="mb-4">
      <h2 class="mb-1">Istoric comenzi</h2>
      <p class="text-muted mb-2">
        Secțiune demo de comenzi plasate de client (B2B/B2C) sau în numele lui de către agent/director.
      </p>

      <div class="alert alert-info mb-0" v-if="user">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="fw-semibold">
              Autentificat ca:
              <span class="badge bg-light text-dark ms-1">
                {{ user.name }} ({{ user.role || 'fără rol' }})
              </span>
            </div>
            <div class="small text-muted">
              Rolul determină ce clienți poți vedea și pentru cine poți plasa comenzi (client → agent → director → admin/operator).
            </div>
          </div>
          <div class="text-end small">
            <div v-if="isImpersonating">
              Client activ (impersonare):
              <strong>{{ frontCustomerName || '—' }}</strong>
              <span v-if="frontClientType" class="badge bg-warning text-dark ms-1">
                {{ frontClientType }}
              </span>
            </div>
            <div v-else>
              Client activ:
              <strong>{{ frontCustomerName || 'N/A' }}</strong>
              <span v-if="frontClientType" class="badge bg-primary ms-1">
                {{ frontClientType }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="alert alert-warning mt-3 mb-0" v-if="!frontClientType">
        <strong>Atenție:</strong> În acest moment nu există un client activ în front.
        În producție, istoricul comenzilor este disponibil doar pentru un client
        autentificat sau pentru un client selectat prin impersonare.
      </div>
    </div>

    <!-- Filtre & rezumat -->
    <div class="card shadow-sm mb-3 small">
      <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Cod comandă</label>
            <input
              type="text"
              class="form-control form-control-sm"
              placeholder="Caută după cod..."
              v-model="filters.code"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label">Status comandă</label>
            <select
              class="form-select form-select-sm"
              v-model="filters.status"
            >
              <option value="">Toate</option>
              <option value="in_procesare">În procesare</option>
              <option value="in_livrare">În livrare</option>
              <option value="livrata">Livrată</option>
              <option value="anulata">Anulată</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Status plată</label>
            <select
              class="form-select form-select-sm"
              v-model="filters.paymentStatus"
            >
              <option value="">Toate</option>
              <option value="neplatita">Neplătită</option>
              <option value="in_asteptare">Plată în așteptare</option>
              <option value="platita">Plătită</option>
              <option value="ramburs">Ramburs</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tip client</label>
            <select
              class="form-select form-select-sm"
              v-model="filters.clientType"
            >
              <option value="">Toate</option>
              <option value="B2B">B2B</option>
              <option value="B2C">B2C</option>
            </select>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center small text-muted">
        <div>
          Comenzi demo: <strong>{{ filteredOrders.length }}</strong> (filtrate local)
        </div>
        <div>
          Total demo comandat: 
          <strong>
            {{ totalFilteredAmount.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
            RON
          </strong>
        </div>
      </div>
    </div>

    <!-- Tabel comenzi -->
    <div class="card shadow-sm">
      <div class="card-header py-2">
        <strong class="small text-uppercase">Comenzile mele (demo)</strong>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 120px;">Cod</th>
                <th style="width: 120px;">Data</th>
                <th>Tip client</th>
                <th style="width: 150px;">Status comandă</th>
                <th style="width: 150px;">Status plată</th>
                <th style="width: 120px;" class="text-end">Total (RON)</th>
                <th style="width: 200px;">Mod plasare</th>
                <th style="width: 120px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in filteredOrders" :key="order.id">
                <td class="fw-semibold">{{ order.code }}</td>
                <td>{{ order.date }}</td>
                <td>
                  <span
                    class="badge"
                    :class="order.clientType === 'B2B' ? 'bg-primary' : 'bg-secondary'"
                  >
                    {{ order.clientType }}
                  </span>
                </td>
                <td>
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
                </td>
                <td>
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
                </td>
                <td class="text-end fw-semibold">
                  {{ order.total.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="small">
                  <div v-if="order.isImpersonated">
                    Plasată în numele clientului
                  </div>
                  <div v-else>
                    Plasată direct din cont
                  </div>
                  <div class="text-muted" v-if="order.agentName">
                    Agent: {{ order.agentName }}
                  </div>
                </td>
                <td class="text-end">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary me-2"
                    @click="repeatOrder(order)"
                  >
                    Comandă din nou
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    @click="goToOrderDetails(order)"
                  >
                    Detalii
                  </button>
                </td>
              </tr>
              <tr v-if="filteredOrders.length === 0">
                <td colspan="8" class="text-center text-muted py-4">
                  Nu există comenzi care să corespundă filtrelor curente (demo).
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer small text-muted">
        Aceasta este o listă statică. În implementarea reală, datele vin din API
        (ERP / platformă), iar acțiunea „Comandă din nou” ar reumple coșul cu
        produsele din comanda selectată.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const router = useRouter()
const authStore = useAuthStore()

const user = computed(() => authStore.user || null)
const isImpersonating = computed(() => !!authStore.impersonatedCustomer)

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

const filters = reactive({
  code: '',
  status: '',
  paymentStatus: '',
  clientType: ''
})

const orders = [
  {
    id: 1,
    code: 'CMD-1001',
    date: '2025-02-18',
    clientType: 'B2B',
    status: 'in_procesare',
    paymentStatus: 'in_asteptare',
    total: 24500.5,
    isImpersonated: true,
    agentName: 'Popescu Mihai'
  },
  {
    id: 2,
    code: 'CMD-1000',
    date: '2025-02-16',
    clientType: 'B2B',
    status: 'in_livrare',
    paymentStatus: 'platita',
    total: 12780.0,
    isImpersonated: false,
    agentName: null
  },
  {
    id: 3,
    code: 'CMD-0999',
    date: '2025-02-10',
    clientType: 'B2C',
    status: 'livrata',
    paymentStatus: 'ramburs',
    total: 520.99,
    isImpersonated: false,
    agentName: null
  },
  {
    id: 4,
    code: 'CMD-0998',
    date: '2025-02-08',
    clientType: 'B2B',
    status: 'anulata',
    paymentStatus: 'neplatita',
    total: 70340.75,
    isImpersonated: true,
    agentName: 'Ionescu Adrian'
  }
]

const filteredOrders = computed(() => {
  return orders.filter((o) => {
    const matchesCode =
      !filters.code || o.code.toLowerCase().includes(filters.code.toLowerCase())
    const matchesStatus = !filters.status || o.status === filters.status
    const matchesPayStatus =
      !filters.paymentStatus || o.paymentStatus === filters.paymentStatus
    const matchesClientType =
      !filters.clientType || o.clientType === filters.clientType
    return matchesCode && matchesStatus && matchesPayStatus && matchesClientType
  })
})

const totalFilteredAmount = computed(() => {
  return filteredOrders.value.reduce((sum, o) => sum + o.total, 0)
})

const repeatOrder = (order) => {
  window.alert(
    `Demo: comanda ${order.code} ar reumple coșul cu produsele originale în implementarea reală.`
  )
}

const goToOrderDetails = (order) => {
  // În lipsa unei rute exacte, folosim un path generic.
  router.push(`/account/orders/${order.id}`)
}
</script>
