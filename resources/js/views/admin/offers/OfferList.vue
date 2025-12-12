<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Oferte / cereri ofertă</h1>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <!-- Filtre -->
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
            placeholder="client, cod, număr ofertă..."
          >
        </div>
        <div class="col-md-2">
          <label class="form-label form-label-sm">Status</label>
          <select
            v-model="filters.status"
            class="form-select form-select-sm"
          >
            <option value="">Toate</option>
            <option value="new">Nouă</option>
            <option value="in_review">În analiză</option>
            <option value="approved">Aprobată</option>
            <option value="rejected">Respinsă</option>
            <option value="converted">Transformată în comandă</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label form-label-sm">Tip client</label>
          <select
            v-model="filters.customer_type"
            class="form-select form-select-sm"
          >
            <option value="">Toate</option>
            <option value="b2b">B2B</option>
            <option value="b2c">B2C</option>
          </select>
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

    <!-- Tabel oferte -->
    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Client</th>
              <th>Tip</th>
              <th>Valoare estimată</th>
              <th>Status</th>
              <th>Creată la</th>
              <th style="width: 150px;">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr v-if="!loading && !offers.length">
              <td colspan="7" class="text-center text-muted py-3">
                Nu există oferte pentru filtrele selectate.
              </td>
            </tr>
            <tr
              v-for="offer in offers"
              :key="offer.id"
            >
              <td class="small">#{{ offer.number || offer.id }}</td>
              <td class="small">
                <div class="fw-semibold">
                  {{ offer.customer_name || '—' }}
                </div>
                <div class="text-muted">
                  {{ offer.customer_email || '' }}
                </div>
              </td>
              <td class="small">
                {{ offer.customer_type_label || offer.customer_type || '—' }}
              </td>
              <td class="small text-end">
                {{ formatMoney(offer.estimated_total || offer.total || 0) }}
              </td>
              <td class="small">
                <span
                  class="badge bg-light text-dark"
                  :class="statusBadgeClass(offer.status)"
                >
                  {{ statusLabel(offer.status) }}
                </span>
              </td>
              <td class="small">
                {{ formatDate(offer.created_at) }}
              </td>
              <td class="small">
                <div class="btn-group btn-group-sm">
                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="openDetails(offer)"
                  >
                    Detalii
                  </button>
                  <button
                    v-if="canConvert(offer)"
                    type="button"
                    class="btn btn-outline-success"
                    @click="convert(offer)"
                  >
                    Comandă
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginare dacă există meta -->
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

    <!-- Modal detalii ofertă – simplu, text -->
    <div
      v-if="selectedOffer"
      class="modal-backdrop fade show"
    ></div>
    <div
      v-if="selectedOffer"
      class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
      style="z-index: 1050;"
    >
      <div class="card shadow" style="max-width: 600px; width: 100%;">
        <div class="card-header py-2 d-flex justify-content-between align-items-center small">
          <strong>Detalii ofertă #{{ selectedOffer.number || selectedOffer.id }}</strong>
          <button
            type="button"
            class="btn-close btn-sm"
            @click="selectedOffer = null"
          ></button>
        </div>
        <div class="card-body small">
          <p class="mb-1">
            <strong>Client:</strong>
            {{ selectedOffer.customer_name || '—' }}
            <span class="text-muted">
              ({{ selectedOffer.customer_email || '' }})
            </span>
          </p>
          <p class="mb-1">
            <strong>Tip client:</strong>
            {{ selectedOffer.customer_type_label || selectedOffer.customer_type || '—' }}
          </p>
          <p class="mb-1">
            <strong>Status:</strong>
            {{ statusLabel(selectedOffer.status) }}
          </p>
          <p class="mb-2">
            <strong>Valoare estimată:</strong>
            {{ formatMoney(selectedOffer.estimated_total || selectedOffer.total || 0) }}
          </p>

          <p class="mb-1">
            <strong>Observații client:</strong>
          </p>
          <p class="text-muted">
            {{ selectedOffer.notes || selectedOffer.customer_note || '—' }}
          </p>

          <p class="mb-1">
            <strong>Conținut ofertă (brut):</strong>
          </p>
          <pre class="small bg-light p-2 rounded" style="max-height: 200px; overflow: auto;">
{{ rawPayload(selectedOffer) }}
          </pre>
        </div>
        <div class="card-footer py-2 d-flex justify-content-between align-items-center small">
          <div>
            Creată la: {{ formatDate(selectedOffer.created_at) }}
          </div>
          <div class="btn-group btn-group-sm">
            <button
              type="button"
              class="btn btn-secondary"
              @click="selectedOffer = null"
            >
              Închide
            </button>
            <button
              v-if="canConvert(selectedOffer)"
              type="button"
              class="btn btn-success"
              @click="convert(selectedOffer)"
            >
              Transformă în comandă
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  fetchOffers,
  convertOfferToOrder
} from '@/services/admin/offers'

const offers = ref([])
const meta = ref(null)
const loading = ref(false)
const error = ref('')

const selectedOffer = ref(null)

const filters = ref({
  search: '',
  status: '',
  customer_type: '',
  date_from: '',
  date_to: '',
  page: 1
})

const formatDate = (value) => {
  if (!value) return ''
  const d = new Date(value)
  return d.toLocaleString('ro-RO')
}

const formatMoney = (value) => {
  if (value == null) return '0,00 RON'
  return `${Number(value).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })} RON`
}

const statusLabel = (status) => {
  switch (status) {
    case 'new':
      return 'Nouă'
    case 'in_review':
      return 'În analiză'
    case 'approved':
      return 'Aprobată'
    case 'rejected':
      return 'Respinsă'
    case 'converted':
      return 'Transformată în comandă'
    default:
      return status || '—'
  }
}

const statusBadgeClass = (status) => {
  switch (status) {
    case 'new':
      return 'border border-primary text-primary'
    case 'in_review':
      return 'border border-warning text-warning'
    case 'approved':
      return 'border border-success text-success'
    case 'rejected':
      return 'border border-danger text-danger'
    case 'converted':
      return 'border border-secondary text-secondary'
    default:
      return ''
  }
}

const rawPayload = (offer) => {
  const payload =
    offer.payload ||
    offer.details ||
    offer.items ||
    {}

  try {
    if (typeof payload === 'string') return payload
    return JSON.stringify(payload, null, 2)
  } catch (e) {
    return ''
  }
}

const canConvert = (offer) => {
  return (
    offer.status === 'approved' ||
    offer.status === 'new' ||
    offer.status === 'in_review'
  )
}

const loadOffers = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
      customer_type: filters.value.customer_type || undefined,
      date_from: filters.value.date_from || undefined,
      date_to: filters.value.date_to || undefined,
      page: filters.value.page || 1
    }

    const resp = await fetchOffers(params)

    offers.value = resp.data || resp || []
    meta.value = resp.meta || null
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca ofertele.'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  filters.value.page = 1
  loadOffers()
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    customer_type: '',
    date_from: '',
    date_to: '',
    page: 1
  }
  loadOffers()
}

const changePage = (page) => {
  filters.value.page = page
  loadOffers()
}

const openDetails = (offer) => {
  selectedOffer.value = offer
}

const convert = async (offer) => {
  if (!canConvert(offer)) return
  if (!confirm('Transformi această ofertă în comandă?')) return

  try {
    await convertOfferToOrder(offer.id)
    await loadOffers()
    if (selectedOffer.value?.id === offer.id) {
      selectedOffer.value = null
    }
    alert('Oferta a fost transformată în comandă.')
  } catch (e) {
    console.error(e)
    alert('Nu s-a putut transforma oferta în comandă.')
  }
}

onMounted(loadOffers)
</script>
