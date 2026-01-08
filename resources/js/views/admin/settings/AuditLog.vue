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
              <div class="small text-muted" style="font-size: 0.75rem;">
                <div v-if="row.action === 'updated' && getPayload(row).before">
                  <div v-for="(val, key) in getPayload(row).after" :key="key" class="text-truncate">
                    <span class="fw-bold">{{ formatFieldName(key) }}:</span>
                    <span class="text-danger text-decoration-line-through me-1">{{ formatValue(getPayload(row).before[key]) }}</span>
                    <i class="bi bi-arrow-right small text-muted mx-1"></i>
                    <span class="text-success">{{ formatValue(val) }}</span>
                  </div>
                </div>
                <div v-else-if="row.action === 'created'">
                  <div v-for="(val, key) in getPayload(row).after" :key="key" class="text-truncate">
                     <span class="fw-bold">{{ formatFieldName(key) }}:</span> {{ formatValue(val) }}
                  </div>
                </div>
                <div v-else-if="row.action === 'deleted'">
                  <span class="text-danger">Element șters</span>
                </div>
                <div v-else>
                   {{ getSummary(row) }}
                </div>
              </div>
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

    <!-- Details Modal -->
    <div class="modal fade" id="auditDetailsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-info-circle me-2"></i>Detalii Audit #{{ selectedLog?.id }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4" v-if="selectedLog">
            <div class="row g-4">
              <!-- Info Utilizator -->
              <div class="col-md-6">
                <h6 class="text-uppercase text-muted small fw-bold mb-3 border-bottom pb-2">Utilizator</h6>
                <div class="d-flex align-items-center mb-3">
                   <div class="avatar-circle bg-primary text-white me-3" style="width: 48px; height: 48px; font-size: 1.2rem;">
                    {{ (selectedLog.user_name || selectedLog.user?.name || '?').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <div class="fw-bold fs-5">{{ selectedLog.user_name || selectedLog.user?.name || 'Sistem' }}</div>
                    <div class="text-muted small">{{ selectedLog.user_email || selectedLog.user?.email || '-' }}</div>
                  </div>
                </div>
                <div class="small">
                  <div class="mb-1"><span class="text-muted">IP:</span> <span class="font-monospace">{{ selectedLog.meta?.ip || '-' }}</span></div>
                  <div class="mb-1"><span class="text-muted">Browser:</span> <span class="text-truncate d-inline-block align-bottom" style="max-width: 200px;">{{ selectedLog.meta?.user_agent || '-' }}</span></div>
                  <div><span class="text-muted">Data:</span> {{ formatDate(selectedLog.created_at) }}</div>
                </div>
              </div>

              <!-- Info Actiune -->
              <div class="col-md-6">
                <h6 class="text-uppercase text-muted small fw-bold mb-3 border-bottom pb-2">Acțiune</h6>
                <div class="mb-3">
                  <span class="badge rounded-pill fs-6 px-3 py-2" :class="getActionBadgeClass(selectedLog.action)">
                    {{ selectedLog.action.toUpperCase() }}
                  </span>
                </div>
                <div class="card bg-light border-0 p-3">
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Entitate:</span>
                    <span class="fw-bold">{{ selectedLog.entity_type }}</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-muted">ID:</span>
                    <span class="font-monospace">{{ selectedLog.entity_id }}</span>
                  </div>
                </div>
              </div>

              <!-- Modificari -->
              <div class="col-12">
                <h6 class="text-uppercase text-muted small fw-bold mb-3 border-bottom pb-2">Modificări</h6>
                
                <div v-if="selectedLog.action === 'updated' && getPayload(selectedLog).before" class="table-responsive border rounded">
                  <table class="table table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 30%">Câmp</th>
                        <th style="width: 35%">Valoare Veche</th>
                        <th style="width: 35%">Valoare Nouă</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(val, key) in getPayload(selectedLog).after" :key="key">
                        <td class="fw-medium">{{ formatFieldName(key) }}</td>
                        <td class="text-danger text-decoration-line-through bg-light">{{ formatValue(getPayload(selectedLog).before[key]) }}</td>
                        <td class="text-success fw-bold bg-light">{{ formatValue(val) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div v-else-if="selectedLog.action === 'created'" class="table-responsive border rounded">
                   <table class="table table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 40%">Câmp</th>
                        <th style="width: 60%">Valoare</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(val, key) in getPayload(selectedLog).after" :key="key">
                        <td class="fw-medium">{{ formatFieldName(key) }}</td>
                        <td>{{ formatValue(val) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div v-else class="bg-light p-3 rounded font-monospace small">
                  {{ getPayload(selectedLog) }}
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light border-top-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
          </div>
        </div>
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

const selectedLog = ref(null)
let modalInstance = null

const viewDetails = (row) => {
  selectedLog.value = row
  if (!modalInstance) {
    const el = document.getElementById('auditDetailsModal')
    if (el) modalInstance = new window.bootstrap.Modal(el)
  }
  modalInstance?.show()
}

const getPayload = (row) => {
  return row.changes || row.payload || row.details || row.metadata || {}
}

const getSummary = (row) => {
  const payload = getPayload(row)
  // Logic simplu de sumarizare
  if (row.action === 'updated' && payload.before && payload.after) {
    const keys = Object.keys(payload.after)
    return `S-au modificat câmpurile: ${keys.map(formatFieldName).join(', ')}`
  }
  if (row.action === 'login') return 'Autentificare în sistem'
  if (row.action === 'logout') return 'Deconectare din sistem'
  
  try {
    const str = JSON.stringify(payload)
    return str.length > 50 ? str.slice(0, 50) + '...' : str
  } catch (e) {
    return '...'
  }
}

const formatFieldName = (key) => {
  if (!key) return ''
  // Convert snake_case to Title Case
  return key
    .replace(/_/g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase())
}

const formatValue = (val) => {
  if (val === null || val === undefined) return 'null'
  if (val === true) return 'Da'
  if (val === false) return 'Nu'
  if (typeof val === 'object') {
    try {
      return JSON.stringify(val)
    } catch (e) {
      return '[Object]'
    }
  }
  return val
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
