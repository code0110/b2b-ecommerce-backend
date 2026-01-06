<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Tichete suport</h1>
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
              placeholder="subiect, client..."
            >
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Status</label>
            <select
              v-model="filters.status"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="new">Nou</option>
              <option value="in_progress">În lucru</option>
              <option value="resolved">Rezolvat</option>
              <option value="closed">Închis</option>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button
              class="btn btn-sm btn-primary"
              type="submit"
              :disabled="loading"
            >
              Aplică
            </button>
            <button
              class="btn btn-sm btn-outline-secondary"
              type="button"
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

    <div v-else-if="!tickets.length" class="text-center py-5">
      <div class="mb-3">
        <i class="bi bi-ticket-perforated text-muted opacity-25" style="font-size: 3rem;"></i>
      </div>
      <h5 class="text-muted">Nu există tichete</h5>
      <p class="text-muted small">Nu s-au găsit tichete pentru filtrele selectate.</p>
    </div>

    <div v-else class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
      <div v-for="t in tickets" :key="t.id" class="col">
        <div class="card h-100 border shadow-sm hover-shadow transition-all">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
             <div>
                <span class="badge rounded-pill me-2" 
                   :class="{
                      'bg-danger bg-opacity-10 text-danger': t.status === 'new',
                      'bg-warning bg-opacity-10 text-warning': t.status === 'in_progress',
                      'bg-success bg-opacity-10 text-success': t.status === 'resolved',
                      'bg-secondary bg-opacity-10 text-secondary': t.status === 'closed'
                   }">
                   {{ statusLabel(t.status) }}
                </span>
                <small class="text-muted">#{{ t.id }}</small>
             </div>
             <small class="text-muted">{{ formatDate(t.created_at).split(',')[0] }}</small>
          </div>
          
          <div class="card-body">
             <h6 class="fw-bold text-dark mb-3">{{ t.subject }}</h6>
             
             <div class="mb-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">CLIENT</small>
                <div class="d-flex align-items-center mt-1">
                   <div class="avatar-circle-sm me-2 bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                      <i class="bi bi-person-fill small"></i>
                   </div>
                   <div class="fw-semibold text-dark text-truncate">{{ t.customer_name || '—' }}</div>
                </div>
             </div>

             <div>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">CATEGORIE</small>
                <div class="mt-1">
                   <span class="badge bg-light text-dark border">{{ t.category || 'General' }}</span>
                </div>
             </div>
          </div>

          <div class="card-footer bg-white py-3">
             <button
                type="button"
                class="btn btn-sm btn-outline-primary w-100"
                @click="openTicket(t)"
             >
                <i class="bi bi-eye me-1"></i> Vezi Detalii
             </button>
          </div>
        </div>
      </div>
    </div>

    <!-- modal detalii -->
    <div
      v-if="selected"
      class="modal-backdrop fade show"
    ></div>
    <div
      v-if="selected"
      class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
      style="z-index: 1050;"
    >
      <div class="card shadow" style="max-width: 700px; width: 100%;">
        <div class="card-header py-2 small d-flex justify-content-between align-items-center">
          <strong>Tichet #{{ selected.id }} – {{ selected.subject }}</strong>
          <button
            type="button"
            class="btn-close btn-sm"
            @click="selected = null"
          ></button>
        </div>
        <div class="card-body small">
          <p class="mb-1">
            <strong>Client:</strong> {{ selected.customer_name || '—' }}
          </p>
          <p class="mb-1">
            <strong>Status:</strong> {{ statusLabel(selected.status) }}
          </p>
          <p class="mb-1">
            <strong>Categorie:</strong> {{ selected.category || '—' }}
          </p>
          <hr class="my-2">
          <p class="mb-1"><strong>Mesaj inițial:</strong></p>
          <p class="text-muted">
            {{ selected.message || '—' }}
          </p>
        </div>
        <div class="card-footer py-2 small d-flex justify-content-end">
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
import { fetchTickets } from '@/services/admin/tickets'

const tickets = ref([])
const loading = ref(false)
const error = ref('')
const selected = ref(null)

const filters = ref({
  search: '',
  status: ''
})

const formatDate = (val) => {
  if (!val) return ''
  const d = new Date(val)
  return d.toLocaleString('ro-RO')
}

const statusLabel = (status) => {
  switch (status) {
    case 'new': return 'Nou'
    case 'in_progress': return 'În lucru'
    case 'resolved': return 'Rezolvat'
    case 'closed': return 'Închis'
    default: return status || '—'
  }
}

const loadTickets = async () => {
  loading.value = true
  error.value = ''
  try {
    const params = {
      search: filters.value.search || undefined,
      status: filters.value.status || undefined
    }
    const resp = await fetchTickets(params)
    tickets.value = resp.data || resp || []
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca tichetele (sau endpoint-ul nu există încă).'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  loadTickets()
}

const resetFilters = () => {
  filters.value = { search: '', status: '' }
  loadTickets()
}

const openTicket = (t) => {
  selected.value = t
}

onMounted(loadTickets)
</script>
