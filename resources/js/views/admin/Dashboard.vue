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
    <div class="row g-4 mb-4">
      <div v-for="card in cards" :key="card.key" class="col-md-3 col-sm-6">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body d-flex align-items-center">
            <div class="rounded-circle p-3 me-3" :class="getCardIconBg(card.key)">
               <i class="bi fs-3" :class="getCardIcon(card.key)"></i>
            </div>
            <div>
              <div class="text-muted small text-uppercase fw-semibold mb-1">{{ card.label }}</div>
              <div class="h3 fw-bold mb-0 text-dark">{{ formatNumber(card.value) }}</div>
              <div v-if="card.subLabel" class="small text-muted mt-1">
                {{ card.subLabel }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top produse / top clienți -->
    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-header bg-white border-bottom py-3">
            <h5 class="card-title mb-0 fw-bold text-dark">
              <i class="bi bi-box-seam me-2 text-primary"></i>Top Produse
            </h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                  <tr>
                    <th class="ps-3 py-3 border-0">Produs</th>
                    <th class="text-end py-3 border-0">Cantitate</th>
                    <th class="text-end pe-3 py-3 border-0">Valoare</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!topProducts.length">
                    <td colspan="3" class="text-center text-muted py-4">
                      <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-inbox fs-2 mb-2 text-secondary opacity-50"></i>
                        Nu există date pentru perioada selectată.
                      </div>
                    </td>
                  </tr>
                  <tr v-for="item in topProducts" :key="item.id || item.product_id">
                    <td class="ps-3">
                      <div class="d-flex align-items-center">
                        <div class="bg-light rounded p-1 me-2 border d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                          <i class="bi bi-image text-muted"></i>
                        </div>
                        <div>
                          <div class="fw-bold text-dark text-truncate" style="max-width: 200px;" :title="item.name || item.product_name">
                            {{ item.name || item.product_name }}
                          </div>
                          <div class="small text-muted font-monospace">
                            {{ item.code || item.sku || '-' }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="text-end fw-medium">{{ formatNumber(item.total_qty || item.qty || 0) }}</td>
                    <td class="text-end pe-3 fw-bold text-primary">{{ formatMoney(item.total_value || item.value || 0) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-header bg-white border-bottom py-3">
            <h5 class="card-title mb-0 fw-bold text-dark">
              <i class="bi bi-people me-2 text-info"></i>Top Clienți
            </h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                  <tr>
                    <th class="ps-3 py-3 border-0">Client</th>
                    <th class="text-end py-3 border-0">Comenzi</th>
                    <th class="text-end pe-3 py-3 border-0">Valoare</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!topCustomers.length">
                    <td colspan="3" class="text-center text-muted py-4">
                      <div class="d-flex flex-column align-items-center">
                         <i class="bi bi-person-x fs-2 mb-2 text-secondary opacity-50"></i>
                         Nu există date.
                      </div>
                    </td>
                  </tr>
                  <tr v-for="item in topCustomers" :key="item.id || item.customer_id">
                    <td class="ps-3">
                      <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 35px; height: 35px; font-size: 0.8rem;">
                          {{ (item.name || item.customer_name || 'C').substring(0,2).toUpperCase() }}
                        </div>
                        <div>
                          <div class="fw-bold text-dark">{{ item.name || item.customer_name }}</div>
                          <div class="small">
                             <span class="badge" :class="(item.type_label === 'B2B' || item.type === 'b2b') ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success'">
                               {{ (item.type_label || item.type || 'N/A').toUpperCase() }}
                             </span>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="text-end fw-medium">{{ formatNumber(item.orders_count || item.order_count || 0) }}</td>
                    <td class="text-end pe-3 fw-bold text-primary">{{ formatMoney(item.total_value || item.value || 0) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Comenzi recente -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold text-dark">
          <i class="bi bi-clock-history me-2 text-warning"></i>Comenzi Recente
        </h5>
        <RouterLink :to="{ name: 'admin-orders' }" class="btn btn-sm btn-outline-primary">
          Vezi toate
        </RouterLink>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-3 py-3 border-0">#</th>
                <th class="py-3 border-0">Client</th>
                <th class="py-3 border-0">Tip</th>
                <th class="py-3 border-0">Status</th>
                <th class="text-end py-3 border-0">Total</th>
                <th class="text-end pe-3 py-3 border-0">Data</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!recentOrders.length">
                <td colspan="6" class="text-center text-muted py-4">
                  Nu există comenzi recente.
                </td>
              </tr>
              <tr v-for="order in recentOrders" :key="order.id">
                <td class="ps-3 fw-bold text-secondary">
                  <RouterLink :to="{ name: 'admin-order-details', params: { id: order.id } }" class="text-decoration-none text-dark hover-primary">
                    #{{ order.number || order.id }}
                  </RouterLink>
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ order.customer_name || '—' }}</div>
                  <div class="small text-muted">{{ order.customer_email || '' }}</div>
                </td>
                <td>
                  <span class="badge" :class="(order.customer_type_label === 'B2B' || order.customer_type === 'b2b') ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success'">
                    {{ (order.customer_type_label || order.customer_type || '—').toUpperCase() }}
                  </span>
                </td>
                <td>
                  <span class="badge rounded-pill border" :class="getStatusBadgeClass(order.status)">
                    {{ order.status_label || order.status }}
                  </span>
                </td>
                <td class="text-end fw-bold">{{ formatMoney(order.total || 0) }}</td>
                <td class="text-end pe-3 text-muted small">{{ formatDate(order.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
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
    // console.error(e)
    error.value = 'Nu s-au putut încărca datele pentru dashboard.'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
const getCardIcon = (key) => {
  switch(key) {
    case 'sales_today': return 'bi-cash-coin';
    case 'orders_today': return 'bi-basket';
    case 'customers_new': return 'bi-person-plus';
    case 'pending_orders': return 'bi-hourglass-split';
    default: return 'bi-activity';
  }
}

const getCardIconBg = (key) => {
  switch(key) {
    case 'sales_today': return 'bg-success bg-opacity-10 text-success';
    case 'orders_today': return 'bg-primary bg-opacity-10 text-primary';
    case 'customers_new': return 'bg-info bg-opacity-10 text-info';
    case 'pending_orders': return 'bg-warning bg-opacity-10 text-warning';
    default: return 'bg-secondary bg-opacity-10 text-secondary';
  }
}

const getStatusBadgeClass = (status) => {
  switch(status) {
    case 'pending': return 'bg-warning bg-opacity-10 text-warning border-warning border-opacity-25';
    case 'processing': return 'bg-info bg-opacity-10 text-info border-info border-opacity-25';
    case 'completed': return 'bg-success bg-opacity-10 text-success border-success border-opacity-25';
    case 'cancelled': return 'bg-danger bg-opacity-10 text-danger border-danger border-opacity-25';
    default: return 'bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25';
  }
}
</script>
