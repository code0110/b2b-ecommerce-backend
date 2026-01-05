<template>
  <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-white border-bottom py-3">
          <h5 class="modal-title fw-bold text-primary">
            <i class="bi" :class="isEdit ? 'bi-pencil-square' : 'bi-person-plus'"></i>
            {{ isEdit ? 'Editare Client' : 'Client Nou' }}
          </h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>
        
        <div class="modal-body bg-light">
          <form @submit.prevent="handleSubmit" id="customerForm">
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body">
                <h6 class="text-uppercase text-muted fw-bold small mb-3">Tip Client</h6>
                <div class="d-flex gap-3">
                  <div class="form-check card-radio p-0 flex-fill">
                    <input 
                      class="form-check-input d-none" 
                      type="radio" 
                      name="type" 
                      id="type_b2c" 
                      value="b2c" 
                      v-model="form.type"
                    >
                    <label class="form-check-label card p-3 text-center cursor-pointer transition-all h-100" for="type_b2c">
                      <i class="bi bi-person fs-4 mb-2 d-block"></i>
                      <span class="fw-bold">Persoană Fizică (B2C)</span>
                    </label>
                  </div>
                  <div class="form-check card-radio p-0 flex-fill">
                    <input 
                      class="form-check-input d-none" 
                      type="radio" 
                      name="type" 
                      id="type_b2b" 
                      value="b2b" 
                      v-model="form.type"
                    >
                    <label class="form-check-label card p-3 text-center cursor-pointer transition-all h-100" for="type_b2b">
                      <i class="bi bi-building fs-4 mb-2 d-block"></i>
                      <span class="fw-bold">Companie (B2B)</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body">
                <h6 class="text-uppercase text-muted fw-bold small mb-3">Date Identificare</h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small">Prenume</label>
                    <input v-model="form.first_name" type="text" class="form-control" :required="form.type === 'b2c'">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small">Nume</label>
                    <input v-model="form.last_name" type="text" class="form-control" :required="form.type === 'b2c'">
                  </div>
                  
                  <div class="col-12" v-if="form.type === 'b2b'">
                    <label class="form-label small">Nume Companie (Denumire Scurtă)</label>
                    <input v-model="form.company_name" type="text" class="form-control" required>
                  </div>

                  <div class="col-12" v-if="form.type === 'b2b'">
                    <label class="form-label small">Denumire Legală Completă</label>
                    <input v-model="form.legal_name" type="text" class="form-control" placeholder="ex: SC NUME FIRMA SRL">
                  </div>

                  <div class="col-md-6">
                    <label class="form-label small">Email</label>
                    <input v-model="form.email" type="email" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small">Telefon</label>
                    <input v-model="form.phone" type="text" class="form-control">
                  </div>
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm mb-4" v-if="form.type === 'b2b'">
              <div class="card-body">
                <h6 class="text-uppercase text-muted fw-bold small mb-3">Date Fiscale & Bancare</h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small">CUI / CIF</label>
                    <input v-model="form.cui" type="text" class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small">Nr. Reg. Com.</label>
                    <input v-model="form.reg_com" type="text" class="form-control">
                  </div>
                  <div class="col-md-8">
                    <label class="form-label small">IBAN</label>
                    <input v-model="form.iban" type="text" class="form-control">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small">Monedă</label>
                    <select v-model="form.currency" class="form-select">
                      <option value="RON">RON</option>
                      <option value="EUR">EUR</option>
                      <option value="USD">USD</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body">
                <h6 class="text-uppercase text-muted fw-bold small mb-3">Date Comerciale</h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small">Termen Plată (zile)</label>
                    <input v-model.number="form.payment_terms_days" type="number" class="form-control" min="0">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small">Limită Credit</label>
                    <div class="input-group">
                      <input v-model.number="form.credit_limit" type="number" class="form-control" min="0" step="0.01">
                      <span class="input-group-text">{{ form.currency || 'RON' }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body">
                <h6 class="text-uppercase text-muted fw-bold small mb-3">Echipă Vânzări</h6>
                <div class="alert alert-info border-0 d-flex align-items-center mb-3">
                  <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                  <div class="small">
                    Selectarea unui <strong>Agent</strong> va asigna automat <strong>Directorul</strong> acestuia.
                  </div>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-bold">Agent Vânzări</label>
                    <select class="form-select" v-model="form.agent_user_id">
                      <option :value="null">-- Nealocat --</option>
                      <option v-for="u in agents" :key="u.id" :value="u.id">
                        {{ formatUser(u) }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-bold">Director Vânzări</label>
                    <select class="form-select" v-model="form.sales_director_user_id" :disabled="!!form.agent_user_id">
                      <option :value="null">-- Nealocat --</option>
                      <option v-for="u in directors" :key="u.id" :value="u.id">
                        {{ formatUser(u) }}
                      </option>
                    </select>
                  </div>
                  
                  <div class="col-12 mt-3">
                    <label class="form-label small fw-bold">Echipă Vânzări (Acces Secundar)</label>
                    <div class="border rounded p-2 bg-white" style="max-height: 150px; overflow-y: auto;">
                        <div v-if="agents.length === 0" class="text-muted small fst-italic p-1">Nu există agenți disponibili.</div>
                        <div v-for="agent in agents" :key="agent.id" class="form-check">
                            <input class="form-check-input" type="checkbox" :value="agent.id" v-model="form.team_members" :id="'team_agent_'+agent.id" :disabled="form.agent_user_id === agent.id">
                            <label class="form-check-label small" :for="'team_agent_'+agent.id">
                                {{ formatUser(agent) }}
                            </label>
                        </div>
                    </div>
                    <div class="form-text small">Agenți suplimentari care pot vizualiza și gestiona acest client.</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body">
                <h6 class="text-uppercase text-muted fw-bold small mb-3">Configurare</h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small">Grup Clienți</label>
                    <select v-model="form.group_id" class="form-select">
                      <option :value="null">Fără grup</option>
                      <option v-for="g in groups" :key="g.id" :value="g.id">
                        {{ g.name }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                      <input class="form-check-input" type="checkbox" id="isActive" v-model="form.is_active">
                      <label class="form-check-label" for="isActive">Cont Activ</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-if="error" class="alert alert-danger py-2 small">
              {{ error }}
            </div>
          </form>
        </div>

        <div class="modal-footer bg-white border-top py-3">
          <button type="button" class="btn btn-light border" @click="$emit('close')">Anulează</button>
          <button type="button" class="btn btn-primary px-4" @click="handleSubmit" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            {{ isEdit ? 'Salvează Modificările' : 'Creează Client' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { fetchCustomerGroups } from '@/services/admin/customerGroups'

const props = defineProps({
  customer: {
    type: Object,
    default: null
  },
  agents: {
    type: Array,
    default: () => []
  },
  directors: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'save'])

const isEdit = computed(() => !!props.customer)
const loading = ref(false)
const error = ref('')
const groups = ref([])

const form = ref({
  type: 'b2c',
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  company_name: '',
  legal_name: '',
  cui: '',
  reg_com: '',
  iban: '',
  currency: 'RON',
  payment_terms_days: 0,
  credit_limit: 0,
  agent_user_id: null,
  sales_director_user_id: null,
  team_members: [],
  group_id: null,
  is_active: true
})

// Auto-assign director when agent is selected
watch(() => form.value.agent_user_id, (newAgentId) => {
  if (!newAgentId) {
    // Optional: clear director if agent is removed? 
    // Usually yes, unless we want to keep the director assignment.
    // Let's keep it safe and not clear if we just deselect agent, 
    // but if we switch agents, we must sync.
    return
  }
  
  // Find agent in props
  const agent = props.agents.find(a => a.id === newAgentId)
  if (agent) {
    // Always sync director with agent's director (even if null)
    form.value.sales_director_user_id = agent.director_id || null
  }
  
  // Remove agent from team members if present (cannot be both primary and secondary)
  if (form.value.team_members.includes(newAgentId)) {
    form.value.team_members = form.value.team_members.filter(id => id !== newAgentId)
  }
})

onMounted(async () => {
  try {
    const groupsData = await fetchCustomerGroups()
    groups.value = groupsData || []
    
    if (isEdit.value) {
      // Populate form
      const c = props.customer
      form.value = {
        type: c.type || (c.clientType === 'B2B' ? 'b2b' : 'b2c'),
        first_name: c.first_name || '',
        last_name: c.last_name || '',
        email: c.email || '',
        phone: c.phone || '',
        company_name: c.name || c.company_name || '', // 'name' for B2B often holds company name
        legal_name: c.legal_name || '',
        cui: c.cif || c.cui || '',
        reg_com: c.reg_com || '',
        iban: c.iban || '',
        currency: c.currency || 'RON',
        payment_terms_days: c.payment_terms_days || 0,
        credit_limit: c.credit_limit || 0,
        agent_user_id: c.agent_user_id || c.agent?.id || null,
        sales_director_user_id: c.sales_director_user_id || c.sales_director?.id || c.salesDirector?.id || null,
        team_members: c.team_members ? c.team_members.map(u => u.id) : (c.teamMembers ? c.teamMembers.map(u => u.id) : []),
        group_id: c.group_id || c.group?.id || null,
        is_active: c.is_active ?? true
      }
      
      // If B2C, split name if first/last empty
      if (form.value.type === 'b2c' && !form.value.first_name && !form.value.last_name && c.name) {
         const parts = c.name.split(' ')
         form.value.first_name = parts[0]
         form.value.last_name = parts.slice(1).join(' ')
      }
    }
  } catch (e) {
    console.error('Error fetching groups', e)
  }
})

const formatUser = (u) => {
  if (!u) return '-'
  const name = [u.first_name, u.last_name].filter(Boolean).join(' ')
  return name || u.email || `#${u.id}`
}

const handleSubmit = async () => {
  loading.value = true
  error.value = ''
  
  try {
    // Basic validation
    if (form.value.type === 'b2c' && (!form.value.first_name || !form.value.last_name)) {
      throw new Error('Completați Nume și Prenume.')
    }
    if (form.value.type === 'b2b' && !form.value.company_name) {
      throw new Error('Completați Numele Companiei.')
    }
    
    const payload = {
      ...form.value,
      id: props.customer?.id,
      name: form.value.type === 'b2b' ? form.value.company_name : `${form.value.first_name} ${form.value.last_name}`.trim(),
      cif: form.value.cui, // Map CUI to CIF
    }

    emit('save', payload)
  } catch (e) {
    error.value = e.message || 'Eroare la salvare.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* Bootstrap modal override for Vue transition if needed, 
   but we are using d-block so it's always visible when mounted. 
   We rely on parent v-if for visibility. 
*/
.modal {
  background-color: rgba(0,0,0,0.5);
}
</style>
