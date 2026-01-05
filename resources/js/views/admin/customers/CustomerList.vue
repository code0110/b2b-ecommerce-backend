<template>
  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1">Clienți</h1>
        <p class="text-muted small mb-0">Gestionează baza de date a clienților, statusurile și asignările acestora.</p>
      </div>
      <button class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" @click="openCreateModal">
        <i class="bi bi-person-plus-fill"></i>
        <span>Client Nou</span>
      </button>
    </div>

    <!-- Filters Card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-3 bg-white rounded">
        <form class="row g-3 align-items-end" @submit.prevent="applyFilters">
          <div class="col-md-4">
            <label class="form-label text-muted small fw-bold text-uppercase">Căutare</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input
                v-model="filters.search"
                type="text"
                class="form-control bg-light border-start-0 ps-0"
                placeholder="Nume, email, companie..."
              >
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label text-muted small fw-bold text-uppercase">Tip client</label>
            <select v-model="filters.type" class="form-select bg-light">
              <option value="">Toate tipurile</option>
              <option value="b2c">Persoană fizică (B2C)</option>
              <option value="b2b">Companie (B2B)</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label text-muted small fw-bold text-uppercase">Status</label>
            <select v-model="filters.is_active" class="form-select bg-light">
              <option value="">Toate statusurile</option>
              <option value="1">Activ</option>
              <option value="0">Inactiv</option>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill" :disabled="loading">
              <i class="bi bi-funnel-fill me-1"></i> Filtrează
            </button>
            <button type="button" class="btn btn-light border" @click="resetFilters" title="Resetează">
              <i class="bi bi-arrow-counterclockwise"></i>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !customers.length" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="alert alert-danger shadow-sm border-0 d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-triangle-fill"></i>
      {{ error }}
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm overflow-hidden" v-if="customers.length || !loading">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4 text-uppercase text-muted small fw-bold py-3">Client</th>
              <th class="text-uppercase text-muted small fw-bold py-3">Tip & Grup</th>
              <th class="text-uppercase text-muted small fw-bold py-3">Contact</th>
              <th class="text-uppercase text-muted small fw-bold py-3">Asignări</th>
              <th class="text-end text-uppercase text-muted small fw-bold py-3">Sold / Limită</th>
              <th class="text-end text-uppercase text-muted small fw-bold py-3 pe-4">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!loading && !customers.length">
              <td colspan="6" class="text-center text-muted py-5">
                <div class="d-flex flex-column align-items-center">
                  <i class="bi bi-inbox fs-1 mb-2 opacity-25"></i>
                  <p class="mb-0">Nu au fost găsiți clienți conform filtrelor selectate.</p>
                </div>
              </td>
            </tr>
            <tr v-for="c in customers" :key="c.id" class="cursor-pointer-row">
              <td class="ps-4">
                <div class="d-flex align-items-center">
                  <div class="avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3 shadow-sm"
                       :class="c.is_active ? 'bg-primary' : 'bg-secondary'"
                       style="width: 40px; height: 40px; min-width: 40px; font-size: 0.9rem;">
                    {{ getInitials(c) }}
                  </div>
                  <div>
                    <RouterLink :to="{ name: 'admin-customer-details', params: { id: c.id } }" 
                                class="fw-bold text-dark text-decoration-none hover-link text-truncate d-block" style="max-width: 200px;">
                      {{ c.name || c.company_name || c.full_name }}
                    </RouterLink>
                    <div class="small text-muted d-flex align-items-center gap-1">
                      <span class="badge rounded-pill" :class="c.is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'" style="font-size: 0.65rem;">
                        {{ c.is_active ? 'ACTIV' : 'INACTIV' }}
                      </span>
                      <span v-if="c.code" class="text-muted border-start ps-1 ms-1">{{ c.code }}</span>
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-flex flex-column gap-1">
                  <span class="badge w-fit" :class="c.type === 'b2b' || c.clientType === 'B2B' ? 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' : 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25'">
                    {{ (c.type || c.clientType || 'N/A').toUpperCase() }}
                  </span>
                  <span class="small text-muted" v-if="c.group || c.group_name">
                    <i class="bi bi-people me-1"></i>
                    {{ c.group?.name || c.group_name }}
                  </span>
                </div>
              </td>
              <td>
                <div class="d-flex flex-column small">
                  <div class="text-dark mb-1"><i class="bi bi-envelope text-muted me-2"></i>{{ c.email }}</div>
                  <div class="text-muted" v-if="c.phone"><i class="bi bi-telephone text-muted me-2"></i>{{ c.phone }}</div>
                </div>
              </td>
              <td>
                <div class="d-flex flex-column gap-1 small">
                  <div class="d-flex align-items-center text-muted" title="Agent">
                    <i class="bi bi-person-badge me-2 text-primary"></i>
                    <span :class="{'text-dark fw-medium': c.agent}">{{ c.agent ? formatUser(c.agent) : '—' }}</span>
                  </div>
                  <div class="d-flex align-items-center text-muted" title="Director">
                    <i class="bi bi-person-video me-2 text-info"></i>
                    <span :class="{'text-dark fw-medium': c.sales_director || c.salesDirector}">{{ c.sales_director || c.salesDirector ? formatUser(c.sales_director || c.salesDirector) : '—' }}</span>
                  </div>
                </div>
              </td>
              <td class="text-end">
                <div class="fw-bold text-dark">{{ formatMoney(c.balance || c.current_balance || 0) }}</div>
                <div class="small text-muted">Limită: {{ formatMoney(c.credit_limit || 0) }}</div>
              </td>
              <td class="text-end pe-4">
                <div class="d-flex gap-1 justify-content-end">
                  <RouterLink :to="{ name: 'admin-customer-details', params: { id: c.id } }" class="btn btn-sm btn-outline-secondary" title="Vezi Detalii">
                    <i class="bi bi-eye"></i>
                  </RouterLink>
                  <button class="btn btn-sm btn-outline-primary" @click="openEditModal(c)" title="Editează">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-info" @click="openAssignModal(c)" title="Asignează Agent/Director">
                    <i class="bi bi-person-gear"></i>
                  </button>
                  <button 
                    v-if="isAgentOrDirector"
                    class="btn btn-sm" 
                    :class="visitStore.activeVisit?.customer_id === c.id ? 'btn-success' : 'btn-outline-success'"
                    @click="handleStartVisit(c)" 
                    :disabled="visitStore.loading || (visitStore.activeVisit?.customer_id === c.id)"
                    :title="visitStore.activeVisit?.customer_id === c.id ? 'Vizită în curs' : 'Începe Vizită'"
                  >
                    <i class="bi" :class="visitStore.activeVisit?.customer_id === c.id ? 'bi-geo-alt-fill' : 'bi-geo-alt'"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" @click="handleDelete(c)" title="Șterge">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="meta && (meta.current_page && meta.last_page) && customers.length" class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
        <span class="text-muted small">
          Afișare {{ (meta.current_page - 1) * 20 + 1 }} - {{ Math.min(meta.current_page * 20, meta.total || 9999) }} din {{ meta.total || 'multe' }} rezultate
        </span>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: meta.current_page <= 1 }">
              <button class="page-link border-0" @click="changePage(meta.current_page - 1)">
                <i class="bi bi-chevron-left"></i>
              </button>
            </li>
            <li class="page-item active">
              <span class="page-link border-0 bg-primary">{{ meta.current_page }}</span>
            </li>
            <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page }">
              <button class="page-link border-0" @click="changePage(meta.current_page + 1)">
                <i class="bi bi-chevron-right"></i>
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Modals -->
    <CustomerFormModal 
      v-if="showFormModal" 
      :customer="editingCustomer"
      @close="closeFormModal"
      @save="handleSaveCustomer"
    />

    <!-- Modal asignări (Inline for now to keep existing logic working smoothly) -->
    <div v-if="assignModalOpen">
      <div class="modal-backdrop" @click="closeAssignModal"></div>
      <div class="modal-panel custom-modal-panel">
        <div class="card border-0 shadow-lg">
          <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Asignare Echipă Vânzări</h5>
            <button class="btn-close" @click="closeAssignModal"></button>
          </div>
          <div class="card-body">
            <div class="mb-4 p-3 bg-light rounded border">
              <div class="small text-muted text-uppercase fw-bold mb-1">Client Selectat</div>
              <div class="fw-bold fs-5">{{ selectedCustomer?.name }}</div>
              <div class="small text-muted">{{ selectedCustomer?.email }}</div>
            </div>
            
            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label fw-bold small">Agent Vânzări</label>
                <select class="form-select" v-model="assignAgentId">
                  <option :value="null">-- Nealocat --</option>
                  <option v-for="u in agents" :key="u.id" :value="u.id">
                    {{ formatUser(u) }}
                  </option>
                </select>
                <div class="form-text small">Gestionează comenzile și relația directă.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small">Director Vânzări</label>
                <select class="form-select" v-model="assignDirectorId">
                  <option :value="null">-- Nealocat --</option>
                  <option v-for="u in directors" :key="u.id" :value="u.id">
                    {{ formatUser(u) }}
                  </option>
                </select>
                <div class="form-text small">Supervizează și aprobă limitele de credit.</div>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white border-top py-3 d-flex justify-content-end gap-2">
            <button class="btn btn-light border" @click="closeAssignModal">Anulează</button>
            <button class="btn btn-success px-4" :disabled="savingAssign" @click="saveAssignments(selectedCustomer.id)">
              <span v-if="savingAssign" class="spinner-border spinner-border-sm me-2"></span>
              Salvează Asignările
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { fetchCustomers, createCustomer, updateCustomer, deleteCustomer } from '@/services/admin/customers'
import { fetchUsers } from '@/services/admin/users'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/store/auth'
import { useVisitStore } from '@/store/visit'
import CustomerFormModal from './CustomerFormModal.vue'

// State
const customers = ref([])
const meta = ref(null)
const loading = ref(false)
const error = ref('')
const toast = useToast()
const authStore = useAuthStore()
const visitStore = useVisitStore()

const agents = ref([])
const directors = ref([])

// Filters
const filters = ref({
  search: '',
  type: '',
  is_active: '',
  page: 1
})

// Modal State
const showFormModal = ref(false)
const editingCustomer = ref(null)

const assignModalOpen = ref(false)
const selectedCustomer = ref(null)
const assignAgentId = ref(null)
const assignDirectorId = ref(null)
const savingAssign = ref(false)

// Auto-assign director when agent is selected
watch(assignAgentId, (newAgentId) => {
  if (!newAgentId) return
  
  const agent = agents.value.find(a => a.id === newAgentId)
  if (agent && agent.director_id) {
    assignDirectorId.value = agent.director_id
  }
})

// Initials helper
const getInitials = (c) => {
    const name = c.name || c.company_name || c.full_name || 'Client';
    return name.substring(0, 2).toUpperCase();
}

// Formatters
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

// Data Loading
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
      last_page: resp.last_page ?? resp.meta?.last_page ?? 1,
      total: resp.total ?? resp.meta?.total ?? 0
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

// Actions
const applyFilters = () => {
  filters.value.page = 1
  loadCustomers()
}

const resetFilters = () => {
  filters.value = { search: '', type: '', is_active: '', page: 1 }
  loadCustomers()
}

const changePage = (page) => {
  filters.value.page = page
  loadCustomers()
}

// Create / Edit Logic
const openCreateModal = () => {
  editingCustomer.value = null
  showFormModal.value = true
}

const openEditModal = (customer) => {
  editingCustomer.value = customer
  showFormModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
  editingCustomer.value = null
}

const handleSaveCustomer = async (formData) => {
  try {
    if (formData.id) {
      await updateCustomer(formData.id, formData)
      toast.success('Client actualizat cu succes!')
    } else {
      await createCustomer(formData)
      toast.success('Client creat cu succes!')
    }
    closeFormModal()
    loadCustomers()
  } catch (e) {
    console.error(e)
    toast.error('Eroare la salvarea clientului.')
  }
}

const handleDelete = async (customer) => {
  if (!confirm(`Sigur dorești să ștergi clientul ${customer.name}? Această acțiune este ireversibilă.`)) return

  try {
    await deleteCustomer(customer.id)
    toast.success('Client șters cu succes.')
    loadCustomers()
  } catch (e) {
    console.error(e)
    toast.error('Nu s-a putut șterge clientul.')
  }
}

// Assign Logic
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
    toast.error('Eroare la salvarea asignărilor.')
  } finally {
    savingAssign.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadCustomers(), loadUsersByRole(), visitStore.checkActiveVisit()])
})

const isAgentOrDirector = computed(() => {
  return authStore.hasRole('sales_agent') || authStore.hasRole('sales_director')
})

import { useRouter } from 'vue-router'

const router = useRouter()

const handleStartVisit = async (customer) => {
  if (visitStore.activeVisit && visitStore.activeVisit.customer_id === customer.id) {
    // Already in visit with this customer, redirect to details
    router.push({ name: 'admin-customer-details', params: { id: customer.id } })
    return;
  }
  
  if (visitStore.hasActiveVisit) {
    if (!confirm('Aveți deja o vizită activă. Doriți să o încheiați pe cea curentă și să începeți una nouă?')) {
      return;
    }
    await visitStore.endVisit();
  }
  
  try {
    await visitStore.startVisit(customer.id);
    toast.success(`Vizită începută cu ${customer.name}`);
    router.push({ name: 'admin-customer-details', params: { id: customer.id } })
  } catch (e) {
    console.error(e);
    toast.error('Nu s-a putut începe vizita.');
  }
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(2px);
  z-index: 1040;
}

.modal-panel {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 600px;
  max-width: 95vw;
  z-index: 1050;
  animation: slideIn 0.3s ease-out;
}

.custom-modal-panel {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from { opacity: 0; transform: translate(-50%, -48%); }
    to { opacity: 1; transform: translate(-50%, -50%); }
}

.hover-link:hover {
  color: var(--bs-primary) !important;
  text-decoration: underline !important;
}

.w-fit {
  width: fit-content;
}

.cursor-pointer-row {
  transition: background-color 0.15s ease;
}

/* Fix table header alignment */
th {
    font-weight: 600;
    letter-spacing: 0.5px;
}
</style>