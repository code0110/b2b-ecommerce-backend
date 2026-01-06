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

    <!-- Data Grid -->
    <div v-if="customers.length || !loading">
      <div v-if="!loading && !customers.length" class="text-center py-5 text-muted">
        <div class="d-flex flex-column align-items-center">
          <i class="bi bi-inbox fs-1 mb-2 opacity-25"></i>
          <p class="mb-0">Nu au fost găsiți clienți conform filtrelor selectate.</p>
        </div>
      </div>

      <div v-else class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-4">
        <div v-for="c in customers" :key="c.id" class="col">
          <div class="card h-100 border shadow-sm customer-card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2 overflow-hidden">
                <span class="badge rounded-pill" :class="c.is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                    {{ c.is_active ? 'ACTIV' : 'INACTIV' }}
                </span>
                <span v-if="c.code" class="text-muted small border-start ps-2 text-truncate">
                    {{ c.code }}
                </span>
              </div>
              <div class="dropdown">
                  <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                      <li>
                          <RouterLink :to="{ name: 'admin-customer-details', params: { id: c.id } }" class="dropdown-item">
                              <i class="bi bi-eye me-2 text-primary"></i> Detalii
                          </RouterLink>
                      </li>
                      <li>
                          <button class="dropdown-item" @click="openEditModal(c)">
                              <i class="bi bi-pencil me-2 text-info"></i> Editează
                          </button>
                      </li>
                      <li>
                          <button class="dropdown-item" @click="openAssignModal(c)">
                              <i class="bi bi-person-gear me-2 text-warning"></i> Asignează
                          </button>
                      </li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                          <button class="dropdown-item text-danger" @click="handleDelete(c)">
                              <i class="bi bi-trash me-2"></i> Șterge
                          </button>
                      </li>
                  </ul>
              </div>
            </div>
            
            <div class="card-body">
              <!-- Identity -->
              <div class="d-flex align-items-center mb-3">
                  <div class="avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3 shadow-sm flex-shrink-0"
                       :class="c.is_active ? 'bg-primary' : 'bg-secondary'"
                       style="width: 48px; height: 48px; font-size: 1rem;">
                    {{ getInitials(c) }}
                  </div>
                  <div class="overflow-hidden">
                    <RouterLink :to="{ name: 'admin-customer-details', params: { id: c.id } }" 
                                class="fw-bold text-dark text-decoration-none hover-link text-truncate d-block">
                      {{ c.name || c.company_name || c.full_name }}
                    </RouterLink>
                    <div class="d-flex gap-1 mt-1 flex-wrap">
                        <span class="badge" :class="c.type === 'b2b' || c.clientType === 'B2B' ? 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' : 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25'">
                            {{ (c.type || c.clientType || 'N/A').toUpperCase() }}
                        </span>
                        <span class="badge bg-light text-muted border" v-if="c.group || c.group_name">
                            <i class="bi bi-people me-1"></i>
                            {{ c.group?.name || c.group_name }}
                        </span>
                    </div>
                  </div>
              </div>

              <!-- Contact Info -->
              <div class="bg-light rounded p-2 mb-3 small">
                  <div class="d-flex align-items-center mb-1 text-truncate">
                      <i class="bi bi-envelope text-muted me-2" style="width: 16px;"></i>
                      <span class="text-dark">{{ c.email }}</span>
                  </div>
                  <div class="d-flex align-items-center text-truncate" v-if="c.phone">
                      <i class="bi bi-telephone text-muted me-2" style="width: 16px;"></i>
                      <span class="text-dark">{{ c.phone }}</span>
                  </div>
              </div>

              <!-- Assignments -->
              <div class="row g-2 mb-3 small">
                  <div class="col-6">
                      <div class="text-muted mb-1" style="font-size: 0.75rem;">AGENT</div>
                      <div class="d-flex align-items-center text-truncate" :title="c.agent ? formatUser(c.agent) : 'Nealocat'">
                          <i class="bi bi-person-badge me-1 text-primary"></i>
                          <span class="text-truncate">{{ c.agent ? formatUser(c.agent) : '—' }}</span>
                      </div>
                  </div>
                  <div class="col-6 border-start ps-3">
                      <div class="text-muted mb-1" style="font-size: 0.75rem;">DIRECTOR</div>
                      <div class="d-flex align-items-center text-truncate" :title="c.sales_director || c.salesDirector ? formatUser(c.sales_director || c.salesDirector) : 'Nealocat'">
                          <i class="bi bi-person-video me-1 text-info"></i>
                          <span class="text-truncate">{{ c.sales_director || c.salesDirector ? formatUser(c.sales_director || c.salesDirector) : '—' }}</span>
                      </div>
                  </div>
              </div>

              <!-- Financials -->
              <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                  <div>
                      <div class="text-muted small" style="font-size: 0.75rem;">SOLD CURENT</div>
                      <div class="fw-bold text-dark">{{ formatMoney(c.balance || c.current_balance || 0) }}</div>
                  </div>
                  <div class="text-end">
                      <div class="text-muted small" style="font-size: 0.75rem;">LIMITĂ CREDIT</div>
                      <div class="text-dark">{{ formatMoney(c.credit_limit || 0) }}</div>
                  </div>
              </div>
            </div>
            
            <div class="card-footer bg-white py-2" v-if="isAgentOrDirector">
                 <button 
                    class="btn btn-sm w-100 d-flex align-items-center justify-content-center gap-2" 
                    :class="visitStore.activeVisit?.customer_id === c.id ? 'btn-success text-white' : 'btn-outline-success'"
                    @click="handleStartVisit(c)" 
                    :disabled="visitStore.loading || (visitStore.activeVisit?.customer_id === c.id)"
                  >
                    <i class="bi" :class="visitStore.activeVisit?.customer_id === c.id ? 'bi-geo-alt-fill' : 'bi-geo-alt'"></i>
                    <span>{{ visitStore.activeVisit?.customer_id === c.id ? 'Vizită în curs' : 'Începe Vizită' }}</span>
                  </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Pagination -->
      <div v-if="meta && (meta.current_page && meta.last_page) && customers.length" class="d-flex justify-content-between align-items-center mt-3 bg-white p-3 rounded shadow-sm border">
        <span class="text-muted small d-none d-md-inline">
          Afișare {{ (meta.current_page - 1) * 20 + 1 }} - {{ Math.min(meta.current_page * 20, meta.total || 9999) }} din {{ meta.total || 'multe' }} rezultate
        </span>
        <span class="text-muted small d-md-none">
            Pagina {{ meta.current_page }} din {{ meta.last_page }}
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

    <!-- Modal Asignare Rapidă (Standard Bootstrap) -->
    <div v-if="assignModalOpen" class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-white border-bottom py-3">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-person-badge-fill me-2"></i>
              Asignare Echipă
            </h5>
            <button type="button" class="btn-close" @click="closeAssignModal"></button>
          </div>
          
          <div class="modal-body bg-light">
            <div class="card border-0 shadow-sm">
              <div class="card-body">
                <p class="mb-4 text-muted small">
                  Selectează agentul și directorul pentru clientul 
                  <strong class="text-dark">{{ selectedCustomer?.name }}</strong>.
                </p>

                <div class="alert alert-info border-0 d-flex align-items-center mb-3">
                   <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                   <div class="small">
                     Selectarea unui <strong>Agent</strong> va asigna automat <strong>Directorul</strong> acestuia.
                   </div>
                </div>

                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label fw-bold small">Agent Vânzări</label>
                    <select class="form-select" v-model="assignAgentId">
                      <option :value="null">-- Nealocat --</option>
                      <option v-for="u in agents" :key="u.id" :value="u.id">
                        {{ formatUser(u) }}
                      </option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label fw-bold small">Director Vânzări</label>
                    <select class="form-select" v-model="assignDirectorId" :disabled="!!assignAgentId">
                      <option :value="null">-- Nealocat --</option>
                      <option v-for="u in directors" :key="u.id" :value="u.id">
                        {{ formatUser(u) }}
                      </option>
                    </select>
                    <div class="form-text small mt-2" v-if="assignAgentId">Determinat automat de agentul selectat.</div>
                    <div class="form-text small mt-2" v-else>Supervizează și aprobă limitele de credit.</div>
                  </div>
                  
                  <div class="col-12">
                    <label class="form-label fw-bold small">Echipă Vânzări (Secundar)</label>
                    <div class="border rounded p-2 bg-white" style="max-height: 120px; overflow-y: auto;">
                        <div v-if="agents.length === 0" class="text-muted small fst-italic p-1">Nu există agenți disponibili.</div>
                        <div v-for="agent in agents" :key="agent.id" class="form-check">
                            <input class="form-check-input" type="checkbox" :value="agent.id" v-model="assignTeamMembers" :id="'assign_team_'+agent.id" :disabled="assignAgentId === agent.id">
                            <label class="form-check-label small" :for="'assign_team_'+agent.id">
                                {{ formatUser(agent) }}
                            </label>
                        </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-white border-top py-3">
            <button type="button" class="btn btn-light border" @click="closeAssignModal">Anulează</button>
            <button type="button" class="btn btn-primary px-4" @click="saveAssignments(selectedCustomer.id)" :disabled="savingAssign">
              <span v-if="savingAssign" class="spinner-border spinner-border-sm me-2"></span>
              Salvează
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
const assignTeamMembers = ref([])
const savingAssign = ref(false)

// Auto-assign director when agent is selected
watch(assignAgentId, (newAgentId) => {
  if (!newAgentId) return
  
  const agent = agents.value.find(a => a.id === newAgentId)
  if (agent) {
    assignDirectorId.value = agent.director_id || null
  }
  
  // Remove agent from team members if present
  if (assignTeamMembers.value.includes(newAgentId)) {
    assignTeamMembers.value = assignTeamMembers.value.filter(id => id !== newAgentId)
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
  assignTeamMembers.value = customer.teamMembers ? customer.teamMembers.map(u => u.id) : (customer.team_members ? customer.team_members.map(u => u.id) : [])
  assignModalOpen.value = true
}

const closeAssignModal = () => {
  assignModalOpen.value = false
  selectedCustomer.value = null
  assignAgentId.value = null
  assignDirectorId.value = null
  assignTeamMembers.value = []
}

const saveAssignments = async (id) => {
  savingAssign.value = true
  try {
    const payload = {
      agent_user_id: assignAgentId.value != null ? Number(assignAgentId.value) : null,
      sales_director_user_id: assignDirectorId.value != null ? Number(assignDirectorId.value) : null,
      team_members: assignTeamMembers.value
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
const isAgent = computed(() => authStore.hasRole('sales_agent'))
const isDirector = computed(() => authStore.hasRole('sales_director') && !authStore.hasRole('admin'))

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
/* Bootstrap modal override for Vue transition if needed */
.modal {
  background-color: rgba(0,0,0,0.5);
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