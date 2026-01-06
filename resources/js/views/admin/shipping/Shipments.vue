<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Expediții / AWB-uri</h1>
    </div>

    <div class="card mb-3">
      <div class="card-body py-2">
        <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
          <div class="col-md-4">
            <label class="form-label form-label-sm">Căutare</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="AWB, client, comandă..."
            >
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Status</label>
            <select
              v-model="filters.status"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="pending">În pregătire</option>
              <option value="shipped">Expediat</option>
              <option value="delivered">Livrat</option>
              <option value="canceled">Anulat</option>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button
              type="submit"
              class="btn btn-sm btn-primary"
              :disabled="loading"
            >
              Aplică
            </button>
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <div v-else-if="!shipments.length" class="text-center py-5">
      <div class="mb-3">
        <i class="bi bi-box-seam text-muted opacity-25" style="font-size: 3rem;"></i>
      </div>
      <h5 class="text-muted">Nu există expediții</h5>
      <p class="text-muted small">Nu s-au găsit expediții pentru filtrele selectate.</p>
    </div>

    <div v-else class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 mb-4">
      <div v-for="s in shipments" :key="s.id" class="col">
        <div class="card h-100 border shadow-sm hover-shadow transition-all">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
             <div>
                <span class="fw-bold text-primary">
                   <i class="bi bi-upc-scan me-1"></i>
                   {{ s.awb_number || s.number || '-' }}
                </span>
             </div>
             <span class="badge rounded-pill" 
                :class="{
                   'bg-warning bg-opacity-10 text-warning': s.status === 'pending',
                   'bg-info bg-opacity-10 text-info': s.status === 'shipped',
                   'bg-success bg-opacity-10 text-success': s.status === 'delivered',
                   'bg-danger bg-opacity-10 text-danger': s.status === 'canceled'
                }">
                {{ 
                   s.status === 'pending' ? 'În pregătire' : 
                   (s.status === 'shipped' ? 'Expediat' : 
                   (s.status === 'delivered' ? 'Livrat' : 
                   (s.status === 'canceled' ? 'Anulat' : s.status))) 
                }}
             </span>
          </div>
          
          <div class="card-body">
             <div class="row g-2 mb-3">
                <div class="col-6">
                   <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">COMANDĂ</small>
                   <div class="fw-semibold text-dark">#{{ s.order_number || s.order_id }}</div>
                </div>
                <div class="col-6">
                   <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">CURIER</small>
                   <div class="text-dark">{{ s.carrier_name || '—' }}</div>
                </div>
             </div>

             <div class="mb-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">CLIENT</small>
                <div class="fw-semibold text-dark text-truncate">{{ s.customer_name || '—' }}</div>
             </div>

             <div>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">DATA CREĂRII</small>
                <div class="d-flex align-items-center small text-dark mt-1">
                   <i class="bi bi-calendar3 me-2 text-muted"></i>
                   {{ formatDate(s.created_at) }}
                </div>
             </div>
          </div>

          <div class="card-footer bg-white py-2">
             <div class="d-flex gap-2">
                <div class="flex-grow-1">
                   <select
                      v-model="s.status"
                      class="form-select form-select-sm"
                      @change="changeStatus(s)"
                   >
                      <option value="pending">În pregătire</option>
                      <option value="shipped">Expediat</option>
                      <option value="delivered">Livrat</option>
                      <option value="canceled">Anulat</option>
                   </select>
                </div>
                <button
                   class="btn btn-sm btn-outline-primary"
                   type="button"
                   @click="viewShipment(s)"
                >
                   <i class="bi bi-eye"></i>
                </button>
             </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div
      v-if="meta && (meta.current_page && meta.last_page) && meta.last_page > 1"
      class="d-flex justify-content-center mt-4"
    >
      <nav aria-label="Page navigation">
         <ul class="pagination pagination-sm shadow-sm">
             <li class="page-item" :class="{ disabled: meta.current_page <= 1 || loading }">
                <button class="page-link border-0" @click="changePage(meta.current_page - 1)">
                   <i class="bi bi-chevron-left"></i>
                </button>
             </li>
             <li class="page-item disabled">
                <span class="page-link border-0 text-muted bg-transparent">
                   Pagina {{ meta.current_page }} din {{ meta.last_page }}
                </span>
             </li>
             <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page || loading }">
                <button class="page-link border-0" @click="changePage(meta.current_page + 1)">
                   <i class="bi bi-chevron-right"></i>
                </button>
             </li>
         </ul>
      </nav>
    </div>

    <!-- Modal simplu detalii -->
    <div
      v-if="selected"
      class="modal-backdrop fade show"
    ></div>
    <div
      v-if="selected"
      class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
      style="z-index: 1050;"
    >
      <div class="card shadow" style="max-width: 600px; width: 100%;">
        <div class="card-header py-2 small d-flex justify-content-between align-items-center">
          <strong>Detalii expediție #{{ selected.awb_number || selected.id }}</strong>
          <button
            type="button"
            class="btn-close btn-sm"
            @click="selected = null"
          ></button>
        </div>
        <div class="card-body small">
          <p class="mb-1">
            <strong>Comandă:</strong> #{{ selected.order_number || selected.order_id }}
          </p>
          <p class="mb-1">
            <strong>Client:</strong> {{ selected.customer_name || '—' }}
          </p>
          <p class="mb-1">
            <strong>Curier:</strong> {{ selected.carrier_name || '—' }}
          </p>
          <p class="mb-1">
            <strong>Status:</strong> {{ selected.status }}
          </p>
          <p class="mb-1">
            <strong>Tracking URL:</strong>
            <a
              v-if="selected.tracking_url"
              :href="selected.tracking_url"
              target="_blank"
              rel="noopener noreferrer"
            >
              {{ selected.tracking_url }}
            </a>
            <span v-else>—</span>
          </p>
        </div>
        <div class="card-footer py-2 small d-flex justify-content-between">
          <span>Creat la: {{ formatDate(selected.created_at) }}</span>
          <button
            class="btn btn-sm btn-secondary"
            type="button"
            @click="selected = null"
          >
            Închide
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  fetchShipments,
  updateShipmentStatus
} from '@/services/admin/shipments'

const shipments = ref([])
const meta = ref(null)
const loading = ref(false)
const error = ref('')

const selected = ref(null)

const filters = ref({
  search: '',
  status: '',
  page: 1
})

const formatDate = (val) => {
  if (!val) return ''
  const d = new Date(val)
  return d.toLocaleString('ro-RO')
}

const loadShipments = async () => {
  loading.value = true
  error.value = ''
  try {
    const params = {
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
      page: filters.value.page || 1
    }
    const resp = await fetchShipments(params)
    shipments.value = resp.data || resp || []
    meta.value = resp.meta || null
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca expedițiile.'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  filters.value.page = 1
  loadShipments()
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    page: 1
  }
  loadShipments()
}

const changePage = (page) => {
  filters.value.page = page
  loadShipments()
}

const viewShipment = (s) => {
  selected.value = s
}

const changeStatus = async (s) => {
  try {
    await updateShipmentStatus(s.id, { status: s.status })
  } catch (e) {
    console.error(e)
    alert('Nu s-a putut actualiza statusul expediției.')
    loadShipments()
  }
}

onMounted(loadShipments)
</script>
