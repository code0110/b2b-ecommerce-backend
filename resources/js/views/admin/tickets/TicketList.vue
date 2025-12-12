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

    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Subiect</th>
              <th>Client</th>
              <th>Status</th>
              <th>Categorie</th>
              <th>Creat la</th>
              <th style="width: 120px;">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr v-if="!loading && !tickets.length">
              <td colspan="7" class="text-center text-muted py-3">
                Nu există tichete pentru filtrele selectate sau endpoint-ul nu este încă implementat.
              </td>
            </tr>
            <tr
              v-for="t in tickets"
              :key="t.id"
            >
              <td class="small">#{{ t.id }}</td>
              <td class="small">{{ t.subject }}</td>
              <td class="small">
                {{ t.customer_name || '—' }}
              </td>
              <td class="small">
                {{ statusLabel(t.status) }}
              </td>
              <td class="small">
                {{ t.category || '—' }}
              </td>
              <td class="small">
                {{ formatDate(t.created_at) }}
              </td>
              <td class="small">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  @click="openTicket(t)"
                >
                  Detalii
                </button>
              </td>
            </tr>
          </tbody>
        </table>
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
