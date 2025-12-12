<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Audit log</h1>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <!-- Filtre simple -->
    <form
      class="card mb-3"
      @submit.prevent="applyFilters"
    >
      <div class="card-body row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label form-label-sm">Căutare</label>
          <input
            v-model="filters.search"
            type="text"
            class="form-control form-control-sm"
            placeholder="utilizator, acțiune, entitate..."
          >
        </div>
        <div class="col-md-2">
          <label class="form-label form-label-sm">Acțiune</label>
          <input
            v-model="filters.action"
            type="text"
            class="form-control form-control-sm"
            placeholder="update_price, login..."
          >
        </div>
        <div class="col-md-2">
          <label class="form-label form-label-sm">De la</label>
          <input
            v-model="filters.date_from"
            type="date"
            class="form-control form-control-sm"
          >
        </div>
        <div class="col-md-2">
          <label class="form-label form-label-sm">Până la</label>
          <input
            v-model="filters.date_to"
            type="date"
            class="form-control form-control-sm"
          >
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button
            type="submit"
            class="btn btn-sm btn-primary"
            :disabled="loading"
          >
            Aplică filtre
          </button>
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            @click="resetFilters"
          >
            Reset
          </button>
        </div>
      </div>
    </form>

    <!-- Tabel log -->
    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Data</th>
              <th>Utilizator</th>
              <th>Acțiune</th>
              <th>Entitate</th>
              <th>ID entitate</th>
              <th>Detalii</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!logs.length && !loading">
              <td colspan="6" class="text-center text-muted py-3">
                Nu există înregistrări de audit pentru filtrele selectate.
              </td>
            </tr>
            <tr v-if="loading">
              <td colspan="6" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr
              v-for="row in logs"
              :key="row.id"
            >
              <td class="small">
                {{ formatDate(row.created_at) }}
              </td>
              <td class="small">
                <div class="fw-semibold">{{ row.user_name || row.user?.name }}</div>
                <div class="text-muted">{{ row.user_email || row.user?.email }}</div>
              </td>
              <td class="small">
                <span class="badge bg-light text-dark">
                  {{ row.action }}
                </span>
              </td>
              <td class="small">
                {{ row.entity_type || '-' }}
              </td>
              <td class="small">
                {{ row.entity_id || '-' }}
              </td>
              <td class="small">
                <pre class="mb-0 small text-muted" style="max-height: 80px; overflow: auto;">
{{ shortDetails(row) }}
                </pre>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginare simplă (dacă backend-ul trimite meta) -->
      <div
        v-if="meta && (meta.current_page && meta.last_page)"
        class="card-footer py-2 d-flex justify-content-between align-items-center small"
      >
        <div>
          Pagina {{ meta.current_page }} / {{ meta.last_page }}
        </div>
        <div class="btn-group btn-group-sm">
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="meta.current_page <= 1 || loading"
            @click="changePage(meta.current_page - 1)"
          >
            «
          </button>
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="meta.current_page >= meta.last_page || loading"
            @click="changePage(meta.current_page + 1)"
          >
            »
          </button>
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
  return d.toLocaleString('ro-RO')
}

const shortDetails = (row) => {
  // Afișează o versiune scurtă a before/after sau details
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
