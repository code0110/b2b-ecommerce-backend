<template>
  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <button
          type="button"
          class="btn btn-link text-decoration-none ps-0"
          @click="goBack"
        >
          ← Înapoi la lista de clienți
        </button>
        <h4 class="mb-0 mt-2">
          Fișă client
          <span v-if="customer" class="text-muted small">
            • {{ customer.name }}
          </span>
        </h4>
        <p class="text-muted small mb-0">
          Detalii client, structură comercială și ierarhie agent → director → admin.
        </p>
      </div>
      <div class="text-end">
        <button
          v-if="customer"
          type="button"
          class="btn btn-primary btn-sm me-2"
          @click="startEdit"
        >
          Editează date client
        </button>
        <button
          v-if="canImpersonateCustomer"
          type="button"
          class="btn btn-outline-primary btn-sm"
          @click="impersonateAsCustomer"
        >
          Plasează comandă ca acest client
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <div v-else-if="!customer" class="alert alert-warning">
      Clientul nu a fost găsit.
    </div>

    <div v-else class="row">
      <!-- Col stânga: date generale + multi-user -->
      <div class="col-xl-8 mb-3">
        <div class="card shadow-sm mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0">
                {{ customer.name }}
                <span class="badge bg-secondary ms-2">
                  {{ customer.clientType || 'Nespecificat' }}
                </span>
              </h5>
              <small class="text-muted">
                {{ customer.customerCode || 'Fără cod intern' }}
              </small>
            </div>
            <div class="text-end small">
              <div>
                <span class="text-muted">Status:</span>
                <span
                  :class="[
                    'badge',
                    customer.status === 'blocked' ? 'bg-danger' : 'bg-success'
                  ]"
                >
                  {{ customer.status === 'blocked' ? 'Blocat' : 'Activ' }}
                </span>
              </div>
              <div class="mt-1">
                <span class="text-muted">Grup:</span>
                <strong>{{ customer.group || 'Nespecificat' }}</strong>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="small text-muted">Tip client</div>
                <div class="fw-semibold">
                  {{
                    customer.clientType === 'B2B'
                      ? 'Client B2B (companie)'
                      : customer.clientType === 'B2C'
                        ? 'Client B2C (persoană fizică)'
                        : 'Nespecificat'
                  }}
                </div>
              </div>
              <div class="col-md-6">
                <div class="small text-muted">Email</div>
                <div class="fw-semibold">
                  <a
                    v-if="customer.email"
                    :href="`mailto:${customer.email}`"
                    class="text-decoration-none"
                  >
                    {{ customer.email }}
                  </a>
                  <span v-else class="text-muted">-</span>
                </div>
              </div>
              <div class="col-md-6">
                <div class="small text-muted">Telefon</div>
                <div class="fw-semibold">
                  <a
                    v-if="customer.phone"
                    :href="`tel:${customer.phone}`"
                    class="text-decoration-none"
                  >
                    {{ customer.phone }}
                  </a>
                  <span v-else class="text-muted">-</span>
                </div>
              </div>
              <div class="col-md-6" v-if="customer.legalName">
                <div class="small text-muted">Denumire fiscală</div>
                <div class="fw-semibold">
                  {{ customer.legalName }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Adrese -->
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Adrese</strong>
          </div>
          <div class="card-body small">
             <div v-if="customer.workPoints && customer.workPoints.length">
                <div v-for="addr in customer.workPoints" :key="addr.id" class="mb-2 pb-2 border-bottom">
                   <strong>{{ addr.address }}</strong>
                   <span v-if="addr.city">, {{ addr.city }}</span>
                   <span v-if="addr.county">, {{ addr.county }}</span>
                   <div class="text-muted">{{ addr.type }}</div>
                </div>
             </div>
             <div v-else class="text-muted">Nu există adrese definite.</div>
          </div>
        </div>

        <!-- Users -->
        <div v-if="customer.clientType === 'B2B'" class="card shadow-sm mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Utilizatori asociați</strong>
          </div>
          <div class="card-body small">
             <div v-if="customer.companyUsers && customer.companyUsers.length">
                <table class="table table-sm">
                   <thead><tr><th>Nume</th><th>Email</th></tr></thead>
                   <tbody>
                      <tr v-for="u in customer.companyUsers" :key="u.id">
                         <td>{{ u.name }}</td>
                         <td>{{ u.email }}</td>
                      </tr>
                   </tbody>
                </table>
             </div>
             <div v-else class="text-muted">Nu există utilizatori asociați.</div>
          </div>
        </div>
      </div>

      <!-- Col dreapta -->
      <div class="col-xl-4 mb-3">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Structură comercială</strong>
          </div>
          <div class="card-body small">
            <dl class="row mb-0">
              <dt class="col-5">Tip client</dt>
              <dd class="col-7">{{ customer.clientType || 'Nespecificat' }}</dd>

              <dt class="col-5">Agent</dt>
              <dd class="col-7">{{ customer.assignedAgent || 'Nealocat' }}</dd>

              <dt class="col-5">Director</dt>
              <dd class="col-7">{{ customer.assignedDirector || 'Nealocat' }}</dd>

              <dt class="col-5">Termen plată</dt>
              <dd class="col-7">
                {{ customer.paymentTermDays ? customer.paymentTermDays + ' zile' : 'La livrare' }}
              </dd>

              <dt class="col-5">Limită credit</dt>
              <dd class="col-7">
                {{ customer.creditLimit ? customer.creditLimit.toLocaleString('ro-RO') + ' ' + customer.currency : 'Fără limită' }}
              </dd>

              <dt class="col-5">Sold curent</dt>
              <dd class="col-7">
                <span :class="customer.currentBalance > 0 ? 'text-danger' : 'text-success'">
                  {{ customer.currentBalance.toLocaleString('ro-RO') }} {{ customer.currency }}
                </span>
              </dd>
            </dl>
          </div>
        </div>

        <!-- Credit Info -->
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Credit & Balanță</strong>
          </div>
          <div class="card-body small">
            <dl class="row mb-0">
               <dt class="col-6">Limită Credit</dt>
               <dd class="col-6 text-end">{{ customer.creditLimit }} {{ customer.currency }}</dd>
               
               <dt class="col-6">Balanță Curentă</dt>
               <dd class="col-6 text-end">{{ customer.currentBalance }} {{ customer.currency }}</dd>
               
               <dt class="col-6">Credit Disponibil</dt>
               <dd class="col-6 text-end">
                 {{ (customer.creditLimit - customer.currentBalance).toLocaleString('ro-RO') }} {{ customer.currency }}
               </dd>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="isEditing" class="modal-backdrop fade show"></div>
    <div v-if="isEditing" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Editare Client</h5>
            <button type="button" class="btn-close" @click="isEditing = false"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveCustomer">
              <h6 class="mb-3">Date Generale</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Nume Afișat</label>
                  <input v-model="editForm.name" type="text" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Tip Client</label>
                  <select v-model="editForm.clientType" class="form-select">
                    <option value="B2B">Persoană Juridică (B2B)</option>
                    <option value="B2C">Persoană Fizică (B2C)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input v-model="editForm.email" type="email" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Telefon</label>
                  <input v-model="editForm.phone" type="text" class="form-control">
                </div>
              </div>

              <h6 class="mb-3">Date Fiscale (Opțional)</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Denumire Fiscală</label>
                  <input v-model="editForm.legalName" type="text" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">CUI / CIF</label>
                  <input v-model="editForm.cif" type="text" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Nr. Reg. Com.</label>
                  <input v-model="editForm.regCom" type="text" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">IBAN</label>
                  <input v-model="editForm.iban" type="text" class="form-control">
                </div>
              </div>

              <h6 class="mb-3">Comercial & Financiar</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Grup Clienți</label>
                  <select v-model="editForm.groupId" class="form-select">
                    <option :value="null">-- Fără grup --</option>
                    <option v-for="g in customerGroups" :key="g.id" :value="g.id">
                      {{ g.name }}
                    </option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Monedă</label>
                  <select v-model="editForm.currency" class="form-select">
                    <option value="RON">RON</option>
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Limită Credit</label>
                  <div class="input-group">
                    <input v-model.number="editForm.creditLimit" type="number" step="0.01" class="form-control">
                    <span class="input-group-text">{{ editForm.currency }}</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Sold Curent (Atenție!)</label>
                  <div class="input-group">
                    <input v-model.number="editForm.currentBalance" type="number" step="0.01" class="form-control">
                    <span class="input-group-text">{{ editForm.currency }}</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Termen de Plată (zile)</label>
                  <input v-model.number="editForm.paymentTermDays" type="number" class="form-control">
                </div>
              </div>
              
              <h6 class="mb-3">Status & Asignare</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-3">
                  <label class="form-label">Status</label>
                  <select v-model="editForm.status" class="form-select">
                    <option value="active">Activ</option>
                    <option value="blocked">Blocat</option>
                  </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                  <div class="form-check mb-2">
                    <input v-model="editForm.isPartner" class="form-check-input" type="checkbox" id="isPartnerCheck">
                    <label class="form-check-label" for="isPartnerCheck">
                      Este Partener
                    </label>
                  </div>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Agent</label>
                  <select v-model="editForm.assignedAgentId" class="form-select">
                    <option :value="null">-- Fără agent --</option>
                    <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                      {{ agent.first_name }} {{ agent.last_name }}
                    </option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Director</label>
                  <select v-model="editForm.assignedDirectorId" class="form-select">
                    <option :value="null">-- Fără director --</option>
                    <option v-for="director in directors" :key="director.id" :value="director.id">
                      {{ director.first_name }} {{ director.last_name }}
                    </option>
                  </select>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="isEditing = false">Anulează</button>
            <button type="button" class="btn btn-primary" @click="saveCustomer">Salvează</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCustomersStore } from '@/store/customers'
import { useAuthStore } from '@/store/auth'
import { fetchUsers } from '@/services/admin/users'

const route = useRoute()
const router = useRouter()
const customersStore = useCustomersStore()
const authStore = useAuthStore()

const loading = ref(true)
const customer = ref(null)
const isEditing = ref(false)
const agents = ref([])
const directors = ref([])

const editForm = reactive({
  name: '',
  email: '',
  phone: '',
  isPartner: false,
  creditLimit: 0,
  currentBalance: 0,
  paymentTermDays: 0,
  status: 'active',
  assignedAgentId: null,
  assignedDirectorId: null
})

const goBack = () => {
  router.push({ name: 'admin-customers' })
}

onMounted(async () => {
  const id = route.params.id
  loading.value = true
  try {
    customer.value = await customersStore.fetchCustomer(id)
  } catch (error) {
    console.error('Failed to fetch customer:', error)
  } finally {
    loading.value = false
  }
})

const startEdit = async () => {
  if (!customer.value) return
  editForm.name = customer.value.name
  editForm.email = customer.value.email || ''
  editForm.phone = customer.value.phone || ''
  editForm.clientType = customer.value.clientType || 'B2C'
  editForm.legalName = customer.value.legalName || ''
  editForm.cif = customer.value.cif || ''
  editForm.regCom = customer.value.regCom || ''
  editForm.iban = customer.value.iban || ''
  editForm.groupId = customer.value.groupId || null
  editForm.currency = customer.value.currency || 'RON'
  editForm.creditLimit = customer.value.creditLimit || 0
  editForm.currentBalance = customer.value.currentBalance || 0
  editForm.paymentTermDays = customer.value.paymentTermDays || 0
  editForm.status = customer.value.status
  editForm.isPartner = !!customer.value.isPartner
  editForm.assignedAgentId = customer.value.assignedAgentId || null
  editForm.assignedDirectorId = customer.value.assignedDirectorId || null
  
  isEditing.value = true
  
  // Load agents, directors, and groups if not loaded
  try {
    const promises = []
    if (agents.value.length === 0) promises.push(fetchUsers({ role: 'sales_agent', per_page: 100 }))
    if (directors.value.length === 0) promises.push(fetchUsers({ role: 'sales_director', per_page: 100 }))
    if (customerGroups.value.length === 0) promises.push(fetchCustomerGroups())

    if (promises.length > 0) {
      const results = await Promise.all(promises)
      
      // We need to map results back to variables, but order depends on what was missing.
      // Safer to just fetch individually if empty
      if (agents.value.length === 0) agents.value = (await fetchUsers({ role: 'sales_agent', per_page: 100 })).data || []
      if (directors.value.length === 0) directors.value = (await fetchUsers({ role: 'sales_director', per_page: 100 })).data || []
      if (customerGroups.value.length === 0) {
        const groups = await fetchCustomerGroups()
        customerGroups.value = Array.isArray(groups) ? groups : (groups.data || [])
      }
    }
  } catch (e) {
    console.error('Failed to load dependency data', e)
  }
}

const saveCustomer = async () => {
  if (!customer.value) return
  try {
    await customersStore.updateCustomer(customer.value.id, editForm)
    customer.value = await customersStore.fetchCustomer(customer.value.id) // Refresh data
    isEditing.value = false
  } catch (error) {
    alert('Eroare la salvare: ' + error.message)
  }
}

const canImpersonateCustomer = computed(() => {
  if (!authStore.user || !customer.value) return false

  const userRoles = (authStore.user.roles || []).map(r => r.slug || r.code || '')
  const c = customer.value

  if (c.clientType !== 'B2B' && c.clientType !== 'B2C') {
    return false
  }

  if (userRoles.some(r => ['admin', 'operator'].includes(r))) {
    return true
  }

  if (userRoles.includes('sales_agent')) {
    return !!c.assignedAgent && c.assignedAgent === authStore.user.name
  }

  if (userRoles.includes('sales_director')) {
    return !!c.assignedDirector && c.assignedDirector === authStore.user.name
  }

  return false
})

const impersonateAsCustomer = () => {
  if (!canImpersonateCustomer.value || !customer.value) return

  if (authStore.startImpersonation) {
    authStore.startImpersonation({
      id: customer.value.id,
      name: customer.value.name,
    })
  } else {
    localStorage.setItem('impersonated_client_id', customer.value.id)
    localStorage.setItem('impersonated_client_name', customer.value.name)
    window.location.href = '/'
  }
}
</script>

<style scoped>
.modal-backdrop {
  z-index: 1040;
}
.modal {
  z-index: 1050;
}
</style>
