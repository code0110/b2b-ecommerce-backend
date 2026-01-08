<template>
  <div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
      <div>
        <h1 class="h3 mb-0 text-gray-800">Comenzi</h1>
        <p class="text-muted small mb-0">Gestionează comenzile și statusurile acestora</p>
      </div>
      <div class="d-flex gap-2 w-100 w-md-auto">
        <button class="btn btn-outline-secondary btn-sm w-100 w-md-auto" @click="loadOrders">
          <i class="bi bi-arrow-clockwise me-1"></i> Actualizare
        </button>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body bg-light rounded-3">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label small text-muted text-uppercase fw-bold">Căutare</label>
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0">
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
            <label class="form-label small text-muted text-uppercase fw-bold">Client</label>
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-person text-muted"></i>
              </span>
              <input
                v-model="filters.customer"
                type="text"
                class="form-control border-start-0 ps-0"
                placeholder="Nume sau email..."
                @input="debouncedSearch"
              />
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted text-uppercase fw-bold">Status Comandă</label>
            <select v-model="filters.status" class="form-select" @change="loadOrders">
              <option value="">Toate</option>
              <option value="pending">În așteptare</option>
              <option value="processing">În procesare</option>
              <option value="awaiting_payment">Așteaptă plată</option>
              <option value="on_hold">On hold</option>
              <option value="completed">Finalizată</option>
              <option value="cancelled">Anulată</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted text-uppercase fw-bold">Status Plată</label>
            <select v-model="filters.payment_status" class="form-select" @change="loadOrders">
              <option value="">Toate</option>
              <option value="pending">Neplătită</option>
              <option value="paid">Plătită</option>
              <option value="failed">Eșuată</option>
              <option value="refunded">Rambursată</option>
              <option value="partially_paid">Parțial plătită</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted text-uppercase fw-bold">Perioadă</label>
            <div class="input-group">
              <input
                v-model="filters.from_date"
                type="date"
                class="form-control px-2"
                @change="loadOrders"
                title="De la"
              />
              <span class="input-group-text bg-white border-start-0 border-end-0 px-1">-</span>
              <input
                v-model="filters.to_date"
                type="date"
                class="form-control px-2"
                @change="loadOrders"
                title="Până la"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Orders Grid -->
    <div v-if="loading && !orders.length" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
      <p class="text-muted mt-2">Se încarcă comenzile...</p>
    </div>

    <div v-else-if="orders.length === 0" class="text-center py-5 bg-light rounded-3">
      <div class="mb-3">
        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
      </div>
      <h5 class="text-muted">Nu au fost găsite comenzi</h5>
      <p class="text-muted small">Încearcă să modifici filtrele de căutare.</p>
    </div>

    <div v-else class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
      <div v-for="order in orders" :key="order.id" class="col">
        <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
          <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-primary font-monospace">#{{ order.id }}</span>
            <small class="text-muted">
              <i class="bi bi-calendar3 me-1"></i>
              {{ formatDate(order.created_at) }}
            </small>
          </div>
          
          <div class="card-body">
            <!-- Customer Info -->
            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
              <div class="avatar-circle me-3 bg-primary bg-opacity-10 text-primary fw-bold small d-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px; min-width: 40px;">
                {{ getInitials(order.customer?.name || 'Client') }}
              </div>
              <div class="overflow-hidden">
                <h6 class="mb-0 fw-bold text-dark text-truncate">{{ order.customer?.name || 'Client Necunoscut' }}</h6>
                <small class="text-muted d-block text-truncate">{{ order.customer?.email || '-' }}</small>
              </div>
            </div>

            <!-- Status & Total -->
            <div class="d-flex justify-content-between align-items-end mb-3">
              <div>
                <small class="text-muted d-block mb-1 text-uppercase fw-bold" style="font-size: 0.7rem;">Status</small>
                <div class="d-flex flex-column gap-1">
                    <span class="badge rounded-pill border" :class="getStatusBadgeClass(order.status)">
                      {{ getStatusLabel(order.status) }}
                    </span>
                    <span v-if="order.approval_status === 'pending'" class="badge bg-warning text-dark border border-warning">
                        Necesită Aprobare
                    </span>
                     <span v-if="order.approval_status === 'approved'" class="badge bg-success text-white border border-success">
                        Aprobat
                    </span>
                     <span v-if="order.approval_status === 'rejected'" class="badge bg-danger text-white border border-danger">
                        Respins
                    </span>
                </div>
              </div>
              <div class="text-end">
                <small class="text-muted d-block mb-1 text-uppercase fw-bold" style="font-size: 0.7rem;">Total</small>
                <span class="fs-5 fw-bold text-dark">{{ formatPrice(order.grand_total) }}</span>
              </div>
            </div>
          </div>

          <div class="card-footer bg-light border-top-0 py-3 d-flex justify-content-between align-items-center">
             <button class="btn btn-sm btn-outline-primary" @click="openDetails(order.id)">
               <i class="bi bi-eye me-1"></i> Detalii
             </button>

             <div class="dropdown">
                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Acțiuni
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                  <li v-if="order.approval_status === 'pending'">
                      <a class="dropdown-item text-success" href="#" @click.prevent="approveOrder(order)">
                          <i class="bi bi-check-circle me-2"></i> Aprobă
                      </a>
                  </li>
                  <li v-if="order.approval_status === 'pending'">
                      <a class="dropdown-item text-danger" href="#" @click.prevent="rejectOrder(order)">
                          <i class="bi bi-x-circle me-2"></i> Respinge
                      </a>
                  </li>
                  <li><hr class="dropdown-divider" v-if="order.approval_status === 'pending'"></li>
                  <li><h6 class="dropdown-header">Schimbă status</h6></li>
                  <li v-for="st in availableStatuses" :key="st.value">
                    <button 
                      class="dropdown-item d-flex justify-content-between align-items-center" 
                      @click="quickUpdateStatus(order.id, st.value)"
                      :class="{ 'active': order.status === st.value }"
                    >
                      {{ st.label }}
                      <i v-if="order.status === st.value" class="bi bi-check-lg"></i>
                    </button>
                  </li>
                </ul>
             </div>
          </div>
        </div>
      </div>
    </div>
      
    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="d-flex justify-content-between align-items-center mt-4 bg-white p-3 rounded shadow-sm">
      <div class="small text-muted d-none d-md-block">
        Afișare <span class="fw-bold text-dark">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span> - 
        <span class="fw-bold text-dark">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span> 
        din <span class="fw-bold text-dark">{{ pagination.total }}</span> comenzi
      </div>
      <nav class="ms-auto ms-md-0">
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
import { useRoute } from 'vue-router'

const orders = ref([])
const loading = ref(false)
const toast = useToast()
const route = useRoute()
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
  credit_blocked: '',
  approval_status: ''
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
  if (!name) return '?'
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
    toast.error('Nu s-au putut încărca comenzile')
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

const approveOrder = async (order) => {
    if (!confirm('Sunteți sigur că doriți să aprobați această comandă?')) return;
    try {
        await adminApi.post(`/orders/${order.id}/approve`);
        toast.success('Comanda a fost aprobată');
        loadOrders(pagination.value.current_page);
    } catch (e) {
        toast.error('Eroare la aprobare');
    }
}

const rejectOrder = async (order) => {
    if (!confirm('Sunteți sigur că doriți să respingeți această comandă?')) return;
    try {
        await adminApi.post(`/orders/${order.id}/reject`);
        toast.success('Comanda a fost respinsă');
        loadOrders(pagination.value.current_page);
    } catch (e) {
        toast.error('Eroare la respingere');
    }
}

onMounted(() => {
  if (route.query.approval_status) {
      filters.approval_status = route.query.approval_status
  }
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
    // Optimistic update
    const order = orders.value.find(o => o.id === orderId)
    if (order) order.status = status
  } catch (e) {
    toast.error('Eroare la actualizarea statusului')
    loadOrders(pagination.value.current_page) // Revert on error
  }
}
</script>

<style scoped>
.avatar-circle {
  transition: all 0.2s;
}

.hover-shadow:hover {
  transform: translateY(-2px);
  box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}

.transition-all {
  transition: all 0.3s ease;
}

.dropdown-item.active, .dropdown-item:active {
  background-color: #0d6efd;
  color: white;
}
</style>
