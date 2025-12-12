<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Clienți</h1>
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
              placeholder="nume, email, firmă..."
            >
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Tip client</label>
            <select
              v-model="filters.type"
              class="form-select form-select-sm"
            >
              <option value="">Toți</option>
              <option value="b2c">B2C</option>
              <option value="b2b">B2B</option>
              <option value="agent">Agent</option>
              <option value="director">Director</option>
              <option value="operator">Operator</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Status</label>
            <select
              v-model="filters.status"
              class="form-select form-select-sm"
            >
              <option value="">Toți</option>
              <option value="active">Activ</option>
              <option value="blocked">Blocat</option>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-2">
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
              <th>Client</th>
              <th>Tip</th>
              <th>Grup</th>
              <th>Email</th>
              <th class="text-end">Sold</th>
              <th class="text-end">Limită credit</th>
              <th style="width: 120px;">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr v-if="!loading && !customers.length">
              <td colspan="7" class="text-center text-muted py-3">
                Nu există clienți pentru filtrele selectate.
              </td>
            </tr>
            <tr
              v-for="c in customers"
              :key="c.id"
            >
              <td class="small">
                <RouterLink
                  class="fw-semibold text-decoration-none"
                  :to="{ name: 'admin-customer-details', params: { id: c.id } }"
                >
                  {{ c.name || c.company_name || c.full_name }}
                </RouterLink>
              </td>
              <td class="small">
                {{ c.type_label || c.type || '—' }}
              </td>
              <td class="small">
                {{ c.group?.name || c.group_name || '—' }}
              </td>
              <td class="small">
                {{ c.email }}
              </td>
              <td class="small text-end">
                {{ formatMoney(c.balance || c.current_balance || 0) }}
              </td>
              <td class="small text-end">
                {{ formatMoney(c.credit_limit || 0) }}
              </td>
              <td class="small">
                <RouterLink
                  class="btn btn-sm btn-outline-secondary"
                  :to="{ name: 'admin-customer-details', params: { id: c.id } }"
                >
                  Detalii
                </RouterLink>
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { fetchCustomers } from '@/services/admin/customers'

const customers = ref([])
const meta = ref(null)
const loading = ref(false)
const error = ref('')

const filters = ref({
  search: '',
  type: '',
  status: '',
  page: 1
})

const formatMoney = (val) => {
  if (val == null) return '0,00 RON'
  return `${Number(val).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })} RON`
}

const loadCustomers = async () => {
  loading.value = true
  error.value = ''
  try {
    const params = {
      search: filters.value.search || undefined,
      type: filters.value.type || undefined,
      status: filters.value.status || undefined,
      page: filters.value.page || 1
    }
    const resp = await fetchCustomers(params)
    customers.value = resp.data || resp || []
    meta.value = resp.meta || null
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca clienții.'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  filters.value.page = 1
  loadCustomers()
}

const resetFilters = () => {
  filters.value = {
    search: '',
    type: '',
    status: '',
    page: 1
  }
  loadCustomers()
}

const changePage = (page) => {
  filters.value.page = page
  loadCustomers()
}

onMounted(loadCustomers)
</script>
