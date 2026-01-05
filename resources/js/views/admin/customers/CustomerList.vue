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
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Activ</label>
            <select
              v-model="filters.is_active"
              class="form-select form-select-sm"
            >
              <option value="">Toți</option>
              <option value="1">Activi</option>
              <option value="0">Inactivi</option>
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
              <th>Agent</th>
              <th>Director</th>
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
              <td class="small">
                <span v-if="c.agent">
                  {{ formatUser(c.agent) }}
                </span>
                <span v-else class="text-muted">—</span>
              </td>
              <td class="small">
                <span v-if="c.sales_director || c.salesDirector">
                  {{ formatUser(c.sales_director || c.salesDirector) }}
                </span>
                <span v-else class="text-muted">—</span>
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
                <button
                  class="btn btn-sm btn-primary ms-1"
                  type="button"
                  @click="openAssignModal(c)"
                >
                  Asignări
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
    <!-- Modal asignări -->
    <div v-if="assignModalOpen">
      <div class="modal-backdrop" @click="closeAssignModal"></div>
      <div class="modal-panel">
        <div class="card m-0">
          <div class="card-header d-flex justify-content-between align-items-center py-2">
            <strong>Asignare Agent / Director</strong>
            <button class="btn btn-sm btn-outline-secondary" @click="closeAssignModal">Închide</button>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <div class="small text-muted">Client</div>
              <div class="fw-semibold">{{ selectedCustomer?.name }}</div>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label form-label-sm">Agent</label>
                <select class="form-select form-select-sm" v-model="assignAgentId">
                  <option :value="null">Nealocat</option>
                  <option v-for="u in agents" :key="u.id" :value="u.id">
                    {{ formatUser(u) }}
                  </option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label form-label-sm">Director</label>
                <select class="form-select form-select-sm" v-model="assignDirectorId">
                  <option :value="null">Nealocat</option>
                  <option v-for="u in directors" :key="u.id" :value="u.id">
                    {{ formatUser(u) }}
                  </option>
                </select>
              </div>
            </div>
            <div class="mt-3 d-flex justify-content-end gap-2">
              <button class="btn btn-sm btn-outline-secondary" @click="closeAssignModal">Anulează</button>
              <button class="btn btn-sm btn-success" :disabled="savingAssign" @click="saveAssignments(selectedCustomer.id)">
                <span v-if="savingAssign" class="spinner-border spinner-border-sm me-1"></span>
                Salvează
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { fetchCustomers } from '@/services/admin/customers'
import { fetchUsers } from '@/services/admin/users'
import { updateCustomer } from '@/services/admin/customers'
import { useToast } from 'vue-toastification'

const customers = ref([])
const meta = ref(null)
const loading = ref(false)
const error = ref('')
const toast = useToast()

const agents = ref([])
const directors = ref([])
const assignModalOpen = ref(false)
const selectedCustomer = ref(null)
const assignAgentId = ref(null)
const assignDirectorId = ref(null)
const savingAssign = ref(false)

const filters = ref({
  search: '',
  type: '',
  is_active: '',
  page: 1
})

const formatUser = (u) => {
  if (!u) return '-'
  const name = [u.first_name, u.last_name].filter(Boolean).join(' ')
  return name || u.email || `#${u.id}`
}

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
      q: filters.value.search || undefined,
      type: filters.value.type || undefined,
      is_active: filters.value.is_active !== '' ? filters.value.is_active : undefined,
      page: filters.value.page || 1
    }
    const resp = await fetchCustomers(params)
    customers.value = resp.data || resp || []
    meta.value = {
      current_page: resp.current_page ?? resp.meta?.current_page ?? 1,
      last_page: resp.last_page ?? resp.meta?.last_page ?? 1
    }
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca clienții.'
  } finally {
    loading.value = false
  }
}

const loadUsersByRole = async () => {
  try {
    const agentsResp = await fetchUsers({ role: 'sales_agent', per_page: 100 })
    const directorsResp = await fetchUsers({ role: 'sales_director', per_page: 100 })
    agents.value = agentsResp.data || agentsResp || []
    directors.value = directorsResp.data || directorsResp || []
  } catch (e) {
    console.error(e)
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
    is_active: '',
    page: 1
  }
  loadCustomers()
}

const changePage = (page) => {
  filters.value.page = page
  loadCustomers()
}

const openAssignModal = (customer) => {
  selectedCustomer.value = customer
  assignAgentId.value = customer?.agent?.id ?? null
  assignDirectorId.value = (customer?.sales_director?.id ?? customer?.salesDirector?.id) ?? null
  assignModalOpen.value = true
}

const closeAssignModal = () => {
  assignModalOpen.value = false
  selectedCustomer.value = null
  assignAgentId.value = null
  assignDirectorId.value = null
}

const saveAssignments = async (id) => {
  savingAssign.value = true
  try {
    const payload = {
      agent_user_id: assignAgentId.value != null ? Number(assignAgentId.value) : null,
      sales_director_user_id: assignDirectorId.value != null ? Number(assignDirectorId.value) : null
    }
    await updateCustomer(id, payload)
    await loadCustomers()
    closeAssignModal()
    toast.success('Asignările au fost salvate.')
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut salva asignările.'
    toast.error('Eroare la salvarea asignărilor.')
  } finally {
    savingAssign.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadCustomers(), loadUsersByRole()])
})
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.3);
}
.modal-panel {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 640px;
  max-width: 95vw;
  background: #fff;
  border-radius: .5rem;
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}
</style>
