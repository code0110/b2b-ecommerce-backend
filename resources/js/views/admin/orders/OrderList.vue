<template>
  <div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0 text-gray-800">Comenzi</h1>
        <p class="text-muted small mb-0">Gestionează comenzile și statusurile acestora</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" @click="loadOrders">
          <i class="bi bi-arrow-clockwise me-1"></i> Actualizare
        </button>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-hash text-muted"></i>
              </span>
              <input
                v-model="filters.order_number"
                type="text"
                class="form-control border-start-0 ps-0"
                placeholder="Nr. comandă / referință"
                @input="debouncedSearch"
              />
            </div>
          </div>
          <div class="col-md-3">
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-person text-muted"></i>
              </span>
              <input
                v-model="filters.customer"
                type="text"
                class="form-control border-start-0 ps-0"
                placeholder="Client (nume/email)"
                @input="debouncedSearch"
              />
            </div>
          </div>
          <div class="col-md-2">
            <select v-model="filters.status" class="form-select" @change="loadOrders">
              <option value="">Status comandă</option>
              <option value="pending">În așteptare</option>
              <option value="processing">În procesare</option>
              <option value="awaiting_payment">Așteaptă plată</option>
              <option value="on_hold">On hold</option>
              <option value="completed">Finalizată</option>
              <option value="cancelled">Anulată</option>
            </select>
          </div>
          <div class="col-md-2">
            <select v-model="filters.payment_status" class="form-select" @change="loadOrders">
              <option value="">Status plată</option>
              <option value="pending">Neplătită</option>
              <option value="paid">Plătită</option>
              <option value="failed">Eșuată</option>
              <option value="refunded">Rambursată</option>
              <option value="partially_paid">Parțial plătită</option>
            </select>
          </div>
          <div class="col-md-2">
            <div class="input-group">
              <span class="input-group-text bg-light">Din</span>
              <input
                v-model="filters.from_date"
                type="date"
                class="form-control"
                @change="loadOrders"
              />
              <span class="input-group-text bg-light">-</span>
              <input
                v-model="filters.to_date"
                type="date"
                class="form-control"
                @change="loadOrders"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Orders Table Card -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0 orders-card-body">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Se încarcă...</span>
          </div>
        </div>

        <div v-else-if="orders.length === 0" class="text-center py-5">
          <div class="mb-3">
            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
          </div>
          <h5 class="text-muted">Nu au fost găsite comenzi</h5>
          <p class="text-muted small">Încearcă să modifici filtrele de căutare.</p>
        </div>

        <div v-else class="table-responsive orders-table">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 py-3 text-muted small text-uppercase fw-bold border-0" style="width: 100px;">ID</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Client</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Data</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Status</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0 text-end">Total</th>
                <th class="pe-4 py-3 text-muted small text-uppercase fw-bold border-0 text-end" style="width: 100px;">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in orders" :key="order.id">
                <td class="ps-4 fw-semibold">#{{ order.id }}</td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar-circle me-3 bg-primary bg-opacity-10 text-primary fw-bold small d-flex align-items-center justify-content-center rounded-circle" style="width: 35px; height: 35px;">
                      {{ getInitials(order.customer?.name || 'Client') }}
                    </div>
                    <div>
                      <div class="fw-medium text-dark">{{ order.customer?.name || 'Client Necunoscut' }}</div>
                      <div class="small text-muted">{{ order.customer?.email || '-' }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="d-flex align-items-center text-muted">
                    <i class="bi bi-calendar3 me-2 small"></i>
                    {{ formatDate(order.created_at) }}
                  </div>
                </td>
                <td>
                  <span class="badge rounded-pill border" :class="getStatusBadgeClass(order.status)">
                    {{ getStatusLabel(order.status) }}
                  </span>
                </td>
                <td class="text-end fw-bold text-dark">
                  {{ formatPrice(order.grand_total) }}
                </td>
                <td class="pe-4 text-end">
                  <div class="btn-group">
                    <button class="btn btn-sm btn-light border btn-action" title="Vezi detalii" @click="openDetails(order.id)">
                    <i class="bi bi-eye"></i>
                    </button>
                    <div class="dropdown d-inline-block">
                      <button class="btn btn-sm btn-light border btn-action dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Schimbă status">
                        <i class="bi bi-arrow-repeat"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="dropdown-header">Setează status</li>
                        <li v-for="st in availableStatuses" :key="st.value">
                          <button class="dropdown-item" @click="quickUpdateStatus(order.id, st.value)" :class="{ active: order.status === st.value }">
                            {{ st.label }}
                          </button>
                        </li>
                      </ul>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="card-footer bg-white border-top py-3">
        <div class="d-flex justify-content-between align-items-center">
          <div class="small text-muted">
            Afișare {{ (pagination.current_page - 1) * pagination.per_page + 1 }} - 
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
            din {{ pagination.total }} comenzi
          </div>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button class="page-link border-0" @click="changePage(pagination.current_page - 1)">
                  <i class="bi bi-chevron-left"></i>
                </button>
              </li>
              <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: page === pagination.current_page }">
                <button class="page-link border-0" @click="changePage(page)">{{ page }}</button>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <button class="page-link border-0" @click="changePage(pagination.current_page + 1)">
                  <i class="bi bi-chevron-right"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
  <OrderDetailsModal
    :show="showDetails"
    :orderId="selectedOrderId"
    @close="showDetails = false"
    @update="loadOrders(pagination.current_page)"
  />
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue'
import { adminApi } from '@/services/http'
import OrderDetailsModal from './OrderDetailsModal.vue'
import { useToast } from 'vue-toastification'

const orders = ref([])
const loading = ref(false)
const toast = useToast()
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

const filters = reactive({
  order_number: '',
  customer: '',
  type: '',
  status: '',
  payment_status: '',
  from_date: '',
  to_date: '',
  credit_blocked: ''
})

// Helper functions for UI
const getStatusBadgeClass = (status) => {
  switch(status) {
    case 'pending': return 'bg-warning bg-opacity-10 text-warning border-warning border-opacity-25'
    case 'processing': return 'bg-info bg-opacity-10 text-info border-info border-opacity-25'
    case 'awaiting_payment': return 'bg-primary bg-opacity-10 text-primary border-primary border-opacity-25'
    case 'on_hold': return 'bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25'
    case 'completed': return 'bg-success bg-opacity-10 text-success border-success border-opacity-25'
    case 'cancelled': return 'bg-danger bg-opacity-10 text-danger border-danger border-opacity-25'
    default: return 'bg-light text-dark border-secondary border-opacity-25'
  }
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'În așteptare',
    processing: 'În procesare',
    awaiting_payment: 'Așteaptă plată',
    on_hold: 'On hold',
    completed: 'Finalizată',
    cancelled: 'Anulată'
  }
  return labels[status] || status
}

const getInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatPrice = (value) => {
  return new Intl.NumberFormat('ro-RO', {
    style: 'currency',
    currency: 'RON'
  }).format(value)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ro-RO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const debounce = (fn, wait) => {
  let t
  return (...args) => {
    clearTimeout(t)
    t = setTimeout(() => fn(...args), wait)
  }
}

// Pagination logic
const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const delta = 2
  const left = current - delta
  const right = current + delta + 1
  const range = []
  
  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= left && i < right)) {
      range.push(i)
    }
  }
  return range
})

const loadOrders = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      ...filters
    }
    
    // Clean empty filters
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null) {
        delete params[key]
      }
    })

    const response = await adminApi.get('/orders', { params })
    orders.value = response.data.data
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      per_page: response.data.per_page,
      total: response.data.total
    }
  } catch (error) {
    console.error('Error loading orders:', error)
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(() => {
  loadOrders(1)
}, 300)

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadOrders(page)
  }
}

onMounted(() => {
  loadOrders()
})

const showDetails = ref(false)
const selectedOrderId = ref(null)

const openDetails = (id) => {
  selectedOrderId.value = id
  showDetails.value = true
}

const availableStatuses = [
  { value: 'pending', label: 'În așteptare' },
  { value: 'processing', label: 'În procesare' },
  { value: 'awaiting_payment', label: 'Așteaptă plată' },
  { value: 'on_hold', label: 'On hold' },
  { value: 'completed', label: 'Finalizată' },
  { value: 'cancelled', label: 'Anulată' },
]

const quickUpdateStatus = async (orderId, status) => {
  try {
    await adminApi.post(`/orders/${orderId}/status`, { status })
    toast.success('Status actualizat')
    loadOrders(pagination.value.current_page)
  } catch (e) {
    toast.error('Eroare la actualizarea statusului')
  }
}
</script>

<style scoped>
.avatar-circle {
  font-size: 0.8rem;
  transition: all 0.2s;
}

.table-hover tbody tr:hover .avatar-circle {
  transform: scale(1.1);
}

.btn-action {
  transition: all 0.2s;
}

.btn-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.orders-card-body {
  min-height: 60vh;
  display: flex;
  flex-direction: column;
}

.orders-table {
  min-height: 40vh;
}
</style>
