<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Dashboard administrare</h1>

      <button
        class="btn btn-sm btn-outline-secondary"
        type="button"
        @click="loadData"
        :disabled="loading"
      >
        <span v-if="loading" class="spinner-border spinner-border-sm me-1" />
        Reîncarcă
      </button>
    </div>

    <!-- Mesaj eroare -->
    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <!-- Carduri statistici principale -->
    <div class="row g-3 mb-3">
      <div
        v-for="card in cards"
        :key="card.key"
        class="col-md-3 col-sm-6"
      >
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <div class="small text-muted mb-1">{{ card.label }}</div>
            <div class="h4 mb-0">
              {{ formatNumber(card.value) }}
            </div>
            <div
              v-if="card.subLabel"
              class="small text-muted mt-1"
            >
              {{ card.subLabel }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top produse / top clienți -->
    <div class="row g-3 mb-3">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center py-2">
            <h2 class="h6 mb-0">Top produse (după valoare)</h2>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>Produs</th>
                  <th class="text-end">Cantitate</th>
                  <th class="text-end">Valoare</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!topProducts.length">
                  <td colspan="3" class="text-center text-muted py-3">
                    Nu există date pentru perioada selectată.
                  </td>
                </tr>
                <tr
                  v-for="item in topProducts"
                  :key="item.id || item.product_id"
                >
                  <td>
                    <div class="fw-semibold small">
                      {{ item.name || item.product_name }}
                    </div>
                    <div class="small text-muted">
                      Cod: {{ item.code || item.sku || '-' }}
                    </div>
                  </td>
                  <td class="text-end small">
                    {{ formatNumber(item.total_qty || item.qty || 0) }}
                  </td>
                  <td class="text-end small">
                    {{ formatMoney(item.total_value || item.value || 0) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center py-2">
            <h2 class="h6 mb-0">Top clienți (după valoare)</h2>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>Client</th>
                  <th class="text-end">Comenzi</th>
                  <th class="text-end">Valoare</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!topCustomers.length">
                  <td colspan="3" class="text-center text-muted py-3">
                    Nu există date pentru perioada selectată.
                  </td>
                </tr>
                <tr
                  v-for="item in topCustomers"
                  :key="item.id || item.customer_id"
                >
                  <td>
                    <div class="fw-semibold small">
                      {{ item.name || item.customer_name }}
                    </div>
                    <div class="small text-muted">
                      {{ item.type_label || item.type || '—' }}
                    </div>
                  </td>
                  <td class="text-end small">
                    {{ formatNumber(item.orders_count || item.order_count || 0) }}
                  </td>
                  <td class="text-end small">
                    {{ formatMoney(item.total_value || item.value || 0) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Comenzi recente -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center py-2">
        <h2 class="h6 mb-0">Comenzi recente</h2>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Client</th>
              <th>Tip</th>
              <th>Status</th>
              <th class="text-end">Total</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!recentOrders.length">
              <td colspan="6" class="text-center text-muted py-3">
                Nu există comenzi recente.
              </td>
            </tr>
            <tr
              v-for="order in recentOrders"
              :key="order.id"
            >
              <td class="small">#{{ order.number || order.id }}</td>
              <td class="small">
                <div class="fw-semibold">
                  {{ order.customer_name || '—' }}
                </div>
                <div class="text-muted">
                  {{ order.customer_email || '' }}
                </div>
              </td>
              <td class="small">
                {{ order.customer_type_label || order.customer_type || '—' }}
              </td>
              <td class="small">
                <span class="badge bg-light text-dark">
                  {{ order.status_label || order.status }}
                </span>
              </td>
              <td class="small text-end">
                {{ formatMoney(order.total || 0) }}
              </td>
              <td class="small">
                {{ formatDate(order.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { fetchDashboardSummary } from '@/services/admin/dashboard'

const loading = ref(false)
const error = ref('')

const cards = ref([])
const topProducts = ref([])
const topCustomers = ref([])
const recentOrders = ref([])

const formatNumber = (value) => {
  if (value == null) return 0
  return Number(value).toLocaleString('ro-RO')
}

const formatMoney = (value) => {
  if (value == null) return '0,00 RON'
  return `${Number(value).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })} RON`
}

const formatDate = (value) => {
  if (!value) return ''
  const date = new Date(value)
  return date.toLocaleString('ro-RO')
}

const buildCards = (payload) => {
  // Tolerant la diferite structuri de răspuns
  const src = payload || {}

  const rawCards = [
    {
      key: 'new_orders',
      label: 'Comenzi noi',
      value: src.new_orders ?? src.orders_new ?? 0
    },
    {
      key: 'in_progress_orders',
      label: 'Comenzi în derulare',
      value: src.in_progress_orders ?? src.orders_in_progress ?? 0
    },
    {
      key: 'customers',
      label: 'Clienți activi',
      value: src.customers_count ?? src.customers ?? 0
    },
    {
      key: 'revenue',
      label: 'Valoare comenzi (perioadă curentă)',
      value: src.revenue_total ?? src.orders_total ?? 0
    }
  ]

  cards.value = rawCards
}

const loadData = async () => {
  loading.value = true
  error.value = ''

  try {
    const data = await fetchDashboardSummary()

    buildCards(data)

    topProducts.value = data.top_products || data.topProducts || []
    topCustomers.value = data.top_customers || data.topCustomers || []
    recentOrders.value = data.recent_orders || data.recentOrders || []
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca datele pentru dashboard.'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>
