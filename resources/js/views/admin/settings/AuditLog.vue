<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h4 mb-0 fw-bold text-gray-800">Audit Log</h1>
      <span class="badge bg-primary rounded-pill">
        <i class="bi bi-clock-history me-1"></i>
        Istoric
      </span>
    </div>

    <div v-if="error" class="alert alert-danger py-2 shadow-sm border-0 mb-4">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ error }}
    </div>

    <!-- Filtre -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body bg-light rounded-3">
        <form @submit.prevent="applyFilters" class="row g-3">
          <div class="col-md-3">
            <label class="form-label small text-muted text-uppercase fw-bold">Căutare</label>
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search text-muted"></i>
              </span>
              <input
                v-model="filters.search"
                type="text"
                class="form-control border-start-0 ps-0"
                placeholder="Utilizator, acțiune, entitate..."
              >
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted text-uppercase fw-bold">Acțiune</label>
            <input
              v-model="filters.action"
              type="text"
              class="form-control"
              placeholder="Ex: login, update..."
            >
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted text-uppercase fw-bold">De la</label>
            <input
              v-model="filters.date_from"
              type="date"
              class="form-control"
            >
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted text-uppercase fw-bold">Până la</label>
            <input
              v-model="filters.date_to"
              type="date"
              class="form-control"
            >
          </div>
          <div class="col-md-3 d-flex align-items-end gap-2">
            <button
              type="submit"
              class="btn btn-primary w-100 fw-medium"
              :disabled="loading"
            >
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              Aplică
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="resetFilters"
              title="Resetează filtrele"
            >
              <i class="bi bi-arrow-counterclockwise"></i>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Grid Layout -->
    <div v-if="loading && !logs.length" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
      <p class="text-muted mt-2">Se încarcă istoricul...</p>
    </div>

    <div v-else-if="!logs.length" class="text-center py-5 text-muted bg-light rounded-3">
      <i class="bi bi-inbox fs-1 d-block mb-3"></i>
      Nu există înregistrări de audit pentru filtrele selectate.
    </div>

    <div v-else class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
      <div v-for="row in logs" :key="row.id" class="col">
        <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
          <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center">
              <div class="avatar-circle bg-light text-primary me-2">
                {{ (row.user_name || row.user?.name || '?').charAt(0).toUpperCase() }}
              </div>
              <div>
                <h6 class="mb-0 fw-bold text-dark text-truncate" style="max-width: 150px;">
                  {{ row.user_name || row.user?.name || 'Sistem' }}
                </h6>
                <small class="text-muted d-block" style="font-size: 0.75rem;">
                  {{ row.user_email || row.user?.email || '-' }}
                </small>
              </div>
            </div>
            <span class="badge bg-light text-secondary border">
              <i class="bi bi-calendar3 me-1"></i>
              {{ formatDate(row.created_at) }}
            </span>
          </div>
          
          <div class="card-body">
            <div class="mb-3">
              <span 
                class="badge rounded-pill mb-2"
                :class="getActionBadgeClass(row.action)"
              >
                {{ row.action }}
              </span>
              <div class="d-flex justify-content-between small text-muted border-bottom pb-2 mb-2">
                <span>Entitate:</span>
                <span class="fw-medium text-dark">{{ row.entity_type || '-' }}</span>
              </div>
              <div class="d-flex justify-content-between small text-muted">
                <span>ID Entitate:</span>
                <span class="font-monospace text-dark">{{ row.entity_id || '-' }}</span>
              </div>
            </div>

            <div class="bg-light p-2 rounded-2 border position-relative">
              <h6 class="text-xs text-uppercase text-muted fw-bold mb-1">Detalii</h6>
              <pre class="mb-0 small text-muted custom-scrollbar" style="max-height: 100px; overflow-y: auto; font-size: 0.7rem;">{{ shortDetails(row) }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Paginare -->
    <div
      v-if="meta && (meta.current_page && meta.last_page)"
      class="d-flex justify-content-between align-items-center mt-4 bg-white p-3 rounded shadow-sm"
    >
      <span class="text-muted small">
        Pagina <span class="fw-bold text-dark">{{ meta.current_page }}</span> din {{ meta.last_page }}
      </span>
      <div class="btn-group">
        <button
          type="button"
          class="btn btn-outline-primary btn-sm"
          :disabled="meta.current_page <= 1 || loading"
          @click="changePage(meta.current_page - 1)"
        >
          <i class="bi bi-chevron-left me-1"></i> Anterior
        </button>
        <button
          type="button"
          class="btn btn-outline-primary btn-sm"
          :disabled="meta.current_page >= meta.last_page || loading"
          @click="changePage(meta.current_page + 1)"
        >
          Următor <i class="bi bi-chevron-right ms-1"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { fetchAuditLogs } from '@/services/admin/auditLogs'

const logs = ref([])
const meta = ref(null)
const loading = ref(false)
const error = ref('')

const filters = ref({
  search: '',
  action: '',
  date_from: '',
  date_to: '',
  page: 1
})

const formatDate = (value) => {
  if (!value) return ''
  const d = new Date(value)
  return d.toLocaleString('ro-RO', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric', 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

const getActionBadgeClass = (action) => {
  if (!action) return 'bg-secondary'
  const act = action.toLowerCase()
  if (act.includes('create') || act.includes('add')) return 'bg-success'
  if (act.includes('update') || act.includes('edit')) return 'bg-warning text-dark'
  if (act.includes('delete') || act.includes('remove')) return 'bg-danger'
  if (act.includes('login')) return 'bg-info text-dark'
  return 'bg-primary'
}

const shortDetails = (row) => {
  const payload =
    row.details ||
    row.metadata ||
    row.changes ||
    row.payload ||
    {}

  try {
    if (typeof payload === 'string') {
      return payload.length > 400
        ? payload.slice(0, 400) + '...'
        : payload
    }

    const json = JSON.stringify(payload, null, 2)
    return json.length > 400 ? json.slice(0, 400) + '...' : json
  } catch (e) {
    return ''
  }
}

const loadLogs = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {
      search: filters.value.search || undefined,
      action: filters.value.action || undefined,
      date_from: filters.value.date_from || undefined,
      date_to: filters.value.date_to || undefined,
      page: filters.value.page || 1
    }

    const resp = await fetchAuditLogs(params)

    logs.value = resp.data || resp || []
    meta.value = resp.meta || null
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-a putut încărca audit log-ul.'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  filters.value.page = 1
  loadLogs()
}

const resetFilters = () => {
  filters.value = {
    search: '',
    action: '',
    date_from: '',
    date_to: '',
    page: 1
  }
  loadLogs()
}

const changePage = (page) => {
  filters.value.page = page
  loadLogs()
}

onMounted(loadLogs)
</script>

<style scoped>
.avatar-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 0.8rem;
}

.hover-shadow:hover {
  transform: translateY(-2px);
  box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}

.transition-all {
  transition: all 0.3s ease;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
  height: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 2px;
}

.text-xs {
  font-size: 0.7rem;
}
</style>
