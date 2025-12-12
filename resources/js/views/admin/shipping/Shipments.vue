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

    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>AWB / număr</th>
              <th>Comandă</th>
              <th>Client</th>
              <th>Status</th>
              <th>Curier</th>
              <th>Creat la</th>
              <th style="width: 140px;">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr v-if="!loading && !shipments.length">
              <td colspan="7" class="text-center text-muted py-3">
                Nu există expediții pentru filtrele selectate.
              </td>
            </tr>
            <tr
              v-for="s in shipments"
              :key="s.id"
            >
              <td class="small">
                {{ s.awb_number || s.number || '-' }}
              </td>
              <td class="small">
                #{{ s.order_number || s.order_id }}
              </td>
              <td class="small">
                {{ s.customer_name || '—' }}
              </td>
              <td class="small">
                <select
                  v-model="s.status"
                  class="form-select form-select-sm"
                  style="min-width: 130px;"
                  @change="changeStatus(s)"
                >
                  <option value="pending">În pregătire</option>
                  <option value="shipped">Expediat</option>
                  <option value="delivered">Livrat</option>
                  <option value="canceled">Anulat</option>
                </select>
              </td>
              <td class="small">
                {{ s.carrier_name || '—' }}
              </td>
              <td class="small">
                {{ formatDate(s.created_at) }}
              </td>
              <td class="small">
                <button
                  class="btn btn-sm btn-outline-secondary"
                  type="button"
                  @click="viewShipment(s)"
                >
                  Detalii
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

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
