<template>
  <div class="modal-backdrop" @click="$emit('close')"></div>
  <div class="modal-panel custom-modal-panel">
    <div class="card h-100 border-0 shadow-lg">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-primary">
          <i class="bi" :class="isEdit ? 'bi-pencil-square' : 'bi-person-plus'"></i>
          {{ isEdit ? 'Editare Client' : 'Client Nou' }}
        </h5>
        <button class="btn-close" @click="$emit('close')"></button>
      </div>
      
      <div class="card-body overflow-auto custom-scrollbar">
        <form @submit.prevent="handleSubmit">
          <!-- Tip Client -->
          <div class="mb-4">
            <label class="form-label small fw-bold text-muted text-uppercase">Tip Client</label>
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
                <label class="form-check-label card p-3 text-center cursor-pointer transition-all" for="type_b2c">
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
                <label class="form-check-label card p-3 text-center cursor-pointer transition-all" for="type_b2b">
                  <i class="bi bi-building fs-4 mb-2 d-block"></i>
                  <span class="fw-bold">Companie (B2B)</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Date Identificare -->
          <div class="mb-4">
            <label class="form-label small fw-bold text-muted text-uppercase">Date Identificare</label>
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

          <!-- Date Fiscale (Doar B2B) -->
          <div class="mb-4" v-if="form.type === 'b2b'">
            <label class="form-label small fw-bold text-muted text-uppercase">Date Fiscale & Bancare</label>
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

          <!-- Date Comerciale -->
          <div class="mb-4">
            <label class="form-label small fw-bold text-muted text-uppercase">Date Comerciale</label>
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

          <!-- Asignare Echipă -->
          <div class="mb-4 p-3 bg-light rounded border">
            <label class="form-label small fw-bold text-muted text-uppercase mb-3">Echipă Vânzări</label>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Agent Vânzări</label>
                <select class="form-select" v-model="form.agent_user_id">
                  <option :value="null">-- Nealocat --</option>
                  <option v-for="u in agents" :key="u.id" :value="u.id">
                    {{ formatUser(u) }}
                  </option>
                </select>
                <div class="form-text small">Selectarea agentului va completa automat directorul.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Director Vânzări</label>
                <select class="form-select" v-model="form.sales_director_user_id">
                  <option :value="null">-- Nealocat --</option>
                  <option v-for="u in directors" :key="u.id" :value="u.id">
                    {{ formatUser(u) }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Configurare -->
          <div class="mb-4">
            <label class="form-label small fw-bold text-muted text-uppercase">Configurare</label>
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
          
          <div v-if="error" class="alert alert-danger py-2 small">
            {{ error }}
          </div>

        </form>
      </div>

      <div class="card-footer bg-white border-top py-3 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-light border" @click="$emit('close')">Anulează</button>
        <button type="button" class="btn btn-primary px-4" @click="handleSubmit" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          {{ isEdit ? 'Salvează Modificările' : 'Creează Client' }}
        </button>
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
  group_id: null,
  is_active: true
})

// Auto-assign director when agent is selected
watch(() => form.value.agent_user_id, (newAgentId) => {
  if (!newAgentId) return
  
  // Find agent in props
  const agent = props.agents.find(a => a.id === newAgentId)
  if (agent && agent.director_id) {
    form.value.sales_director_user_id = agent.director_id
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
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(2px);
  z-index: 1050;
}

.modal-panel {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  z-index: 1051;
  display: flex;
  flex-direction: column;
}

.custom-modal-panel {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from { opacity: 0; transform: translate(-50%, -48%); }
    to { opacity: 1; transform: translate(-50%, -50%); }
}

.card-radio input:checked + label {
  border-color: var(--bs-primary);
  background-color: rgba(var(--bs-primary-rgb), 0.05);
  color: var(--bs-primary);
}

.card-radio label:hover {
  border-color: var(--bs-primary);
}

.cursor-pointer {
  cursor: pointer;
}

.transition-all {
  transition: all 0.2s ease;
}

/* Custom scrollbar for modal body */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #aaa;
}
</style>
