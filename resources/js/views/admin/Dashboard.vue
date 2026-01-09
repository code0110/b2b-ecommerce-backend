<template>
  <div class="container-fluid pb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
      <div>
        <div class="text-muted small">Dashboard</div>
        <h1 class="h4 mb-0 fw-bold text-dark">Ecommerce</h1>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-light border d-flex align-items-center gap-2" type="button" @click="loadData" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm" />
          <i v-else class="bi bi-arrow-repeat"></i>
          Reîncarcă
        </button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div class="row g-4 mb-4">
      <div class="col-12 col-xl-8">
        <div class="vx-card h-100 overflow-hidden">
          <div class="card-body p-4 position-relative">
            <div class="row align-items-center g-3">
              <div class="col-lg-7">
                <div class="text-muted small mb-1">Bun venit!</div>
                <h2 class="h4 fw-bold mb-2">Rezumat rapid pentru azi</h2>
                <div class="text-muted mb-3">
                  Vezi evoluția comenzilor și a veniturilor din ultimele 7 zile.
                </div>
                <div class="d-flex flex-wrap gap-2">
                  <RouterLink :to="{ name: 'admin-orders' }" class="btn btn-primary btn-sm px-3">
                    Vezi comenzi
                  </RouterLink>
                  <RouterLink :to="{ name: 'admin-promotions' }" class="btn btn-outline-primary btn-sm px-3">
                    Promoții
                  </RouterLink>
                </div>
              </div>
              <div class="col-lg-5">
                <div class="vx-kpi-grid">
                  <div v-for="card in kpiCards" :key="card.key" class="vx-kpi">
                    <div class="d-flex align-items-center justify-content-between">
                      <div>
                        <div class="text-muted small fw-semibold text-uppercase">{{ card.label }}</div>
                        <div class="h4 fw-bold mb-0 text-dark">{{ card.format(card.value) }}</div>
                      </div>
                      <div class="vx-kpi-icon" :class="card.iconClass">
                        <i class="bi" :class="card.icon"></i>
                      </div>
                    </div>
                    <div v-if="card.subLabel" class="small text-muted mt-2">{{ card.subLabel }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-4">
        <div class="vx-card h-100">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="text-muted small">Ultimele 7 zile</div>
                <div class="h5 fw-bold mb-0">Comenzi</div>
              </div>
              <span class="badge bg-primary-subtle text-primary">Live</span>
            </div>
            <div class="mt-3" style="height: 220px;">
              <Line :data="ordersChartData" :options="sparklineOptions" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-12 col-xl-8">
        <div class="vx-card h-100">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <div class="text-muted small">Ultimele 7 zile</div>
                <div class="h5 fw-bold mb-0">Revenue report</div>
              </div>
              <div class="text-end">
                <div class="text-muted small">Total</div>
                <div class="fw-bold text-dark">{{ formatMoney(revenueTotal7d) }}</div>
              </div>
            </div>
            <div style="height: 320px;">
              <Bar :data="revenueChartData" :options="barOptions" />
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-4">
        <div class="vx-card h-100">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">Structură</div>
                <div class="h5 fw-bold mb-0">Tip clienți</div>
              </div>
              <RouterLink :to="{ name: 'admin-customers' }" class="btn btn-sm btn-light border">
                Clienți
              </RouterLink>
            </div>
            <div class="mt-3" style="height: 240px;">
              <Doughnut :data="customerTypeChartData" :options="doughnutOptions" />
            </div>
            <div class="mt-3 d-flex flex-wrap gap-2">
              <span class="badge bg-primary-subtle text-primary">B2B: {{ formatNumber(customerTypeCounts.b2b) }}</span>
              <span class="badge bg-success-subtle text-success">B2C: {{ formatNumber(customerTypeCounts.b2c) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-12 col-xl-6">
        <div class="vx-card h-100">
          <div class="card-body p-0">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
              <div class="h6 mb-0 fw-bold">Top produse</div>
              <RouterLink :to="{ name: 'admin-products' }" class="btn btn-sm btn-light border">Produse</RouterLink>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                  <tr>
                    <th class="ps-4 py-3 border-0">Produs</th>
                    <th class="text-end py-3 border-0">Cantitate</th>
                    <th class="text-end pe-4 py-3 border-0">Valoare</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!topProducts.length">
                    <td colspan="3" class="text-center text-muted py-4">Nu există date.</td>
                  </tr>
                  <tr v-for="item in topProducts" :key="item.id || item.product_id">
                    <td class="ps-4">
                      <div class="fw-semibold text-dark text-truncate" style="max-width: 360px;">
                        {{ item.name || item.product_name }}
                      </div>
                      <div class="small text-muted font-monospace">{{ item.code || item.sku || '-' }}</div>
                    </td>
                    <td class="text-end">{{ formatNumber(item.total_qty || item.qty || 0) }}</td>
                    <td class="text-end pe-4 fw-semibold text-dark">{{ formatMoney(item.total_value || item.value || 0) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-6">
        <div class="vx-card h-100">
          <div class="card-body p-0">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
              <div class="h6 mb-0 fw-bold">Top clienți</div>
              <RouterLink :to="{ name: 'admin-customers' }" class="btn btn-sm btn-light border">Clienți</RouterLink>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                  <tr>
                    <th class="ps-4 py-3 border-0">Client</th>
                    <th class="text-end py-3 border-0">Comenzi</th>
                    <th class="text-end pe-4 py-3 border-0">Valoare</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!topCustomers.length">
                    <td colspan="3" class="text-center text-muted py-4">Nu există date.</td>
                  </tr>
                  <tr v-for="item in topCustomers" :key="item.id || item.customer_id">
                    <td class="ps-4">
                      <div class="fw-semibold text-dark">{{ item.name || item.customer_name }}</div>
                      <div class="small">
                        <span class="badge" :class="(item.type_label === 'B2B' || item.type === 'b2b') ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success'">
                          {{ (item.type_label || item.type || 'N/A').toUpperCase() }}
                        </span>
                      </div>
                    </td>
                    <td class="text-end">{{ formatNumber(item.orders_count || item.order_count || 0) }}</td>
                    <td class="text-end pe-4 fw-semibold text-dark">{{ formatMoney(item.total_value || item.value || 0) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="vx-card">
      <div class="card-body p-0">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
          <div class="h6 mb-0 fw-bold">Comenzi recente</div>
          <RouterLink :to="{ name: 'admin-orders' }" class="btn btn-sm btn-primary">
            Vezi toate
          </RouterLink>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
              <tr>
                <th class="ps-4 py-3 border-0">#</th>
                <th class="py-3 border-0">Client</th>
                <th class="py-3 border-0">Tip</th>
                <th class="py-3 border-0">Status</th>
                <th class="text-end py-3 border-0">Total</th>
                <th class="text-end pe-4 py-3 border-0">Data</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!recentOrders.length">
                <td colspan="6" class="text-center text-muted py-4">Nu există comenzi recente.</td>
              </tr>
              <tr v-for="order in recentOrders" :key="order.id">
                <td class="ps-4 fw-semibold">
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
                <td class="text-end fw-semibold text-dark">{{ formatMoney(order.total || 0) }}</td>
                <td class="text-end pe-4 text-muted small">{{ formatDate(order.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { fetchDashboardSummary } from '@/services/admin/dashboard'
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  LineElement,
  PointElement,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'
import { Bar, Doughnut, Line } from 'vue-chartjs'

const loading = ref(false)
const error = ref('')

const cards = ref([])
const topProducts = ref([])
const topCustomers = ref([])
const recentOrders = ref([])

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Tooltip, Legend)

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

const normalizeDayKey = (d) => {
  const date = new Date(d)
  if (Number.isNaN(date.getTime())) return null
  const yyyy = date.getFullYear()
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

const lastNDays = (n) => {
  const days = []
  const base = new Date()
  base.setHours(0, 0, 0, 0)
  for (let i = n - 1; i >= 0; i--) {
    const d = new Date(base)
    d.setDate(base.getDate() - i)
    days.push(d)
  }
  return days
}

const buildCards = (payload) => {
  // Tolerant la diferite structuri de răspuns
  const src = payload || {}

  const rawCards = [
    {
      key: 'new_orders',
      label: 'Comenzi noi',
      value: src.new_orders ?? src.orders_new ?? 0,
      icon: 'bi-bag-check',
      iconClass: 'text-primary bg-primary bg-opacity-10'
    },
    {
      key: 'in_progress_orders',
      label: 'Comenzi în derulare',
      value: src.in_progress_orders ?? src.orders_in_progress ?? 0,
      icon: 'bi-arrow-repeat',
      iconClass: 'text-info bg-info bg-opacity-10'
    },
    {
      key: 'customers',
      label: 'Clienți activi',
      value: src.customers_count ?? src.customers ?? 0,
      icon: 'bi-people',
      iconClass: 'text-success bg-success bg-opacity-10'
    },
    {
      key: 'revenue',
      label: 'Valoare comenzi (perioadă curentă)',
      value: src.revenue_total ?? src.orders_total ?? 0,
      icon: 'bi-cash-stack',
      iconClass: 'text-warning bg-warning bg-opacity-10',
      format: 'money'
    }
  ]

  cards.value = rawCards
}

const kpiCards = computed(() => {
  return (cards.value || []).map((c) => {
    const format = c.format === 'money' ? formatMoney : formatNumber
    return { ...c, format }
  })
})

const groupedOrders7d = computed(() => {
  const days = lastNDays(7)
  const seed = {}
  for (const d of days) {
    const k = normalizeDayKey(d)
    seed[k] = { revenue: 0, count: 0 }
  }

  for (const o of recentOrders.value || []) {
    const key = normalizeDayKey(o.created_at || o.createdAt)
    if (!key || !seed[key]) continue
    seed[key].count += 1
    seed[key].revenue += Number(o.total || o.total_value || 0) || 0
  }

  return days.map((d) => {
    const key = normalizeDayKey(d)
    const label = d.toLocaleDateString('ro-RO', { day: '2-digit', month: 'short' })
    return {
      key,
      label,
      revenue: seed[key]?.revenue ?? 0,
      count: seed[key]?.count ?? 0
    }
  })
})

const revenueTotal7d = computed(() => {
  return groupedOrders7d.value.reduce((acc, x) => acc + (x.revenue || 0), 0)
})

const revenueChartData = computed(() => {
  return {
    labels: groupedOrders7d.value.map((x) => x.label),
    datasets: [
      {
        label: 'Revenue',
        data: groupedOrders7d.value.map((x) => x.revenue),
        backgroundColor: 'rgba(115, 103, 240, 0.85)',
        borderRadius: 10,
        barThickness: 18
      }
    ]
  }
})

const ordersChartData = computed(() => {
  return {
    labels: groupedOrders7d.value.map((x) => x.label),
    datasets: [
      {
        label: 'Comenzi',
        data: groupedOrders7d.value.map((x) => x.count),
        borderColor: 'rgba(115, 103, 240, 1)',
        backgroundColor: 'rgba(115, 103, 240, 0.12)',
        tension: 0.35,
        fill: true,
        pointRadius: 0,
        borderWidth: 2
      }
    ]
  }
})

const customerTypeCounts = computed(() => {
  let b2b = 0
  let b2c = 0
  for (const o of recentOrders.value || []) {
    const t = String(o.customer_type_label || o.customer_type || '').toLowerCase()
    if (t.includes('b2b')) b2b += 1
    else if (t.includes('b2c')) b2c += 1
  }
  return { b2b, b2c }
})

const customerTypeChartData = computed(() => {
  return {
    labels: ['B2B', 'B2C'],
    datasets: [
      {
        data: [customerTypeCounts.value.b2b, customerTypeCounts.value.b2c],
        backgroundColor: ['rgba(115, 103, 240, 0.9)', 'rgba(40, 199, 111, 0.9)'],
        borderColor: 'rgba(255, 255, 255, 1)',
        borderWidth: 2,
        hoverOffset: 6
      }
    ]
  }
})

const sparklineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { display: false },
    y: { display: false }
  }
}

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: 'rgba(47, 43, 61, 0.6)', font: { size: 11 } }
    },
    y: {
      grid: { color: 'rgba(47, 43, 61, 0.06)' },
      ticks: {
        color: 'rgba(47, 43, 61, 0.6)',
        font: { size: 11 },
        callback: (v) => {
          const n = Number(v) || 0
          return n >= 1000 ? `${Math.round(n / 1000)}k` : `${n}`
        }
      }
    }
  }
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: { boxWidth: 10, usePointStyle: true }
    }
  },
  cutout: '70%'
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

<style scoped>
.vx-card {
  background: var(--vx-card-bg, #fff);
  border-radius: var(--vx-card-radius, 0.6rem);
  box-shadow: var(--vx-card-shadow, 0 6px 24px rgba(47, 43, 61, 0.08));
  border: 0;
}

.vx-kpi-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.vx-kpi {
  border-radius: 0.75rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(47, 43, 61, 0.06);
}

.vx-kpi-icon {
  width: 42px;
  height: 42px;
  border-radius: 0.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.hover-primary:hover {
  color: var(--bs-primary) !important;
}

@media (max-width: 991.98px) {
  .vx-kpi-grid {
    grid-template-columns: 1fr;
  }
}
</style>
