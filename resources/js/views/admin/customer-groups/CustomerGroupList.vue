<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1 text-gray-800">Grupuri de clienți</h1>
        <p class="text-muted small mb-0">Gestionează politicile comerciale pentru grupurile de clienți.</p>
      </div>
      <button
        class="btn btn-primary shadow-sm"
        type="button"
        @click="startCreate"
      >
        <i class="bi bi-plus-lg me-1"></i> Adaugă grup
      </button>
    </div>

    <div v-if="error" class="alert alert-danger shadow-sm border-0 mb-4">
      <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ error }}
    </div>

    <div class="row g-4">
      <!-- Form Section -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 1rem; z-index: 1020;">
          <div class="card-header bg-white py-3 border-bottom-0">
            <h5 class="fw-bold mb-0 text-primary">
              <i class="bi bi-gear-fill me-2"></i>
              {{ currentGroup?.id ? 'Editează grup' : 'Grup nou' }}
            </h5>
          </div>
          <div class="card-body pt-0">
            <form @submit.prevent="saveGroup">
              <div class="mb-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Denumire grup</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="form-control"
                  placeholder="Ex: Distribuitori Gold"
                  required
                >
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Tip Client</label>
                <div class="d-flex gap-2">
                   <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="type" id="type_b2b" value="b2b" v-model="form.type">
                      <label class="form-check-label small" for="type_b2b">B2B</label>
                   </div>
                   <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="type" id="type_b2c" value="b2c" v-model="form.type">
                      <label class="form-check-label small" for="type_b2c">B2C</label>
                   </div>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="form-label small fw-bold text-muted text-uppercase">Discount (%)</label>
                  <div class="input-group">
                    <input
                      v-model.number="form.default_discount_percent"
                      type="number"
                      min="0"
                      max="100"
                      step="0.01"
                      class="form-control"
                    >
                    <span class="input-group-text bg-light text-muted">%</span>
                  </div>
                </div>
                <div class="col-6">
                  <label class="form-label small fw-bold text-muted text-uppercase">Termen (zile)</label>
                   <div class="input-group">
                    <input
                      v-model.number="form.default_payment_terms_days"
                      type="number"
                      min="0"
                      class="form-control"
                    >
                    <span class="input-group-text bg-light text-muted">Zile</span>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Limită credit (RON)</label>
                <div class="input-group">
                  <input
                    v-model.number="form.default_credit_limit"
                    type="number"
                    step="0.01"
                    class="form-control"
                  >
                  <span class="input-group-text bg-light text-muted">RON</span>
                </div>
              </div>
              
              <div class="mb-4">
                 <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="group-active" v-model="form.is_active">
                    <label class="form-check-label small fw-bold" for="group-active">Grup Activ</label>
                 </div>
              </div>

              <div class="d-flex justify-content-between align-items-center">
                <button
                  class="btn btn-light text-muted border"
                  type="button"
                  @click="resetForm"
                >
                  Anulează
                </button>
                <button
                  class="btn btn-primary px-4"
                  type="submit"
                  :disabled="saving"
                >
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                  {{ currentGroup?.id ? 'Actualizează' : 'Salvează' }}
                </button>
              </div>

              <div v-if="formError" class="alert alert-danger small mt-3 mb-0">
                {{ formError }}
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- List Section -->
      <div class="col-lg-8">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Se încarcă...</span>
          </div>
        </div>
        
        <div v-else-if="!groups.length" class="text-center py-5">
          <div class="mb-3">
            <i class="bi bi-collection text-muted opacity-25" style="font-size: 3rem;"></i>
          </div>
          <h5 class="text-muted">Nu există grupuri definite</h5>
          <p class="text-muted small">Creează primul grup folosind formularul.</p>
        </div>

        <div v-else class="row row-cols-1 row-cols-md-2 g-3">
          <div v-for="g in groups" :key="g.id" class="col">
             <div class="card h-100 border shadow-sm group-card hover-shadow transition-all" :class="{'border-primary': currentGroup?.id === g.id}">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                   <h6 class="fw-bold mb-0 text-dark">{{ g.name }}</h6>
                   <span class="badge rounded-pill" :class="g.is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary'">
                      {{ g.is_active ? 'Activ' : 'Inactiv' }}
                   </span>
                </div>
                <div class="card-body">
                   <div class="row g-2 mb-3">
                      <div class="col-6">
                         <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">TIP</small>
                         <div>
                            <span v-if="g.type === 'b2b'" class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">B2B</span>
                            <span v-else class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">B2C</span>
                         </div>
                      </div>
                      <div class="col-6">
                         <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">DISCOUNT</small>
                         <div class="fw-bold text-primary">{{ g.default_discount_percent ?? 0 }}%</div>
                      </div>
                   </div>
                   
                   <div class="mb-2">
                      <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">TERMEN DE PLATĂ</small>
                      <div class="d-flex align-items-center small text-dark">
                         <i class="bi bi-calendar-check me-2 text-muted"></i>
                         {{ g.default_payment_terms_days ?? 0 }} zile
                      </div>
                   </div>
                   
                   <div>
                      <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">LIMITĂ CREDIT</small>
                      <div class="d-flex align-items-center small text-dark">
                         <i class="bi bi-wallet2 me-2 text-muted"></i>
                         {{ formatMoney(g.default_credit_limit || 0) }}
                      </div>
                   </div>
                </div>
                <div class="card-footer bg-white py-2 d-flex justify-content-end gap-2">
                  <button
                    class="btn btn-sm btn-outline-primary"
                    type="button"
                    @click="editGroup(g)"
                  >
                    <i class="bi bi-pencil me-1"></i> Editează
                  </button>
                  <button
                    class="btn btn-sm btn-outline-danger"
                    type="button"
                    @click="removeGroup(g)"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
             </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  fetchCustomerGroups,
  createCustomerGroup,
  updateCustomerGroup,
  deleteCustomerGroup
} from '@/services/admin/customerGroups'

const groups = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const formError = ref('')

const currentGroup = ref(null)

const form = ref({
  name: '',
  type: 'b2b',
  default_discount_percent: 0,
  default_payment_terms_days: 0,
  default_credit_limit: 0,
  is_active: true
})

const formatMoney = (val) => {
  if (val == null) return '0,00 RON'
  return `${Number(val).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })} RON`
}

const loadGroups = async () => {
  loading.value = true
  error.value = ''
  try {
    const resp = await fetchCustomerGroups({ per_page: 1000 })
    groups.value = resp.data || resp || []
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca grupurile de clienți.'
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  currentGroup.value = null
  form.value = {
    name: '',
    type: 'b2b',
    default_discount_percent: 0,
    default_payment_terms_days: 0,
    default_credit_limit: 0,
    is_active: true
  }
  formError.value = ''
}

const startCreate = () => {
  resetForm()
}

const editGroup = (g) => {
  currentGroup.value = g
  form.value = {
    name: g.name || '',
    type: g.type || 'b2b',
    default_discount_percent: g.default_discount_percent ?? 0,
    default_payment_terms_days: g.default_payment_terms_days ?? 0,
    default_credit_limit: g.default_credit_limit ?? 0,
    is_active: g.is_active ?? true
  }
  formError.value = ''
}

const saveGroup = async () => {
  formError.value = ''
  saving.value = true
  try {
    const payload = { ...form.value }
    if (currentGroup.value?.id) {
      await updateCustomerGroup(currentGroup.value.id, payload)
    } else {
      await createCustomerGroup(payload)
    }
    await loadGroups()
    resetForm()
  } catch (e) {
    console.error(e)
    formError.value =
      e?.response?.data?.message ||
      'Nu s-a putut salva grupul de clienți.'
  } finally {
    saving.value = false
  }
}

const removeGroup = async (g) => {
  if (!confirm(`Ștergi grupul "${g.name}"?`)) return
  try {
    await deleteCustomerGroup(g.id)
    await loadGroups()
  } catch (e) {
    console.error(e)
    alert('Nu s-a putut șterge grupul. Verifică dacă nu are clienți alocați.')
  }
}

onMounted(loadGroups)
</script>
