<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Încasări (CHS / BO / CEC / OP)</h1>
      <button
        class="btn btn-sm btn-primary"
        type="button"
        @click="startCreate"
      >
        Adaugă încasare
      </button>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <!-- Form încasare -->
    <div v-if="showForm" class="card mb-3">
      <div class="card-header py-2 small">
        Înregistrare încasare nouă
      </div>
      <div class="card-body small">
        <form @submit.prevent="saveCollection">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label form-label-sm">Tip încasare</label>
              <select
                v-model="form.type"
                class="form-select form-select-sm"
              >
                <option value="chs">CHS – cash</option>
                <option value="bo">BO – bilet la ordin</option>
                <option value="cec">CEC</option>
                <option value="op">OP – ordin de plată</option>
              </select>
            </div>
            <div class="col-md-4 position-relative">
              <label class="form-label form-label-sm">Client</label>
              <div class="input-group input-group-sm">
                <input
                  type="text"
                  class="form-control"
                  placeholder="Caută client (nume, email...)"
                  v-model="customerSearch"
                  @input="searchCustomers"
                  @focus="showCustomerResults = true"
                >
                <button v-if="selectedCustomer" class="btn btn-outline-secondary" type="button" @click="clearCustomer">
                  <i class="bi bi-x"></i>
                </button>
              </div>
              
              <!-- Dropdown rezultate -->
              <div v-if="showCustomerResults && customerResults.length > 0" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1050; max-height: 200px; overflow-y: auto;">
                <button
                  v-for="cust in customerResults"
                  :key="cust.id"
                  type="button"
                  class="list-group-item list-group-item-action list-group-item-light small"
                  @click="selectCustomer(cust)"
                >
                  <strong>{{ cust.name }}</strong>
                  <div class="text-muted" style="font-size: 0.8em;">{{ cust.email }} | {{ cust.cif || 'Fără CIF' }}</div>
                </button>
              </div>
              <div v-if="showCustomerResults && customerSearch.length >= 2 && customerResults.length === 0 && !searchingCustomers" class="position-absolute w-100 p-2 bg-white border rounded shadow-sm text-muted small" style="z-index: 1050;">
                Nu s-au găsit rezultate.
              </div>
            </div>
            <div class="col-md-2">
              <label class="form-label form-label-sm">Sumă</label>
              <input
                v-model.number="form.amount"
                type="number"
                step="0.01"
                class="form-control form-control-sm"
                required
              >
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">Data încasării</label>
              <input
                v-model="form.payment_date"
                type="date"
                class="form-control form-control-sm"
              >
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-4">
              <label class="form-label form-label-sm">Referință comandă / factură</label>
              <input
                v-model="form.reference"
                type="text"
                class="form-control form-control-sm"
              >
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-sm">Număr document (BO/CEC/OP)</label>
              <input
                v-model="form.document_number"
                type="text"
                class="form-control form-control-sm"
              >
            </div>
            
            <div class="col-12" v-if="['bo', 'cec'].includes(form.type)">
                <div class="row g-3 p-2 bg-light border rounded mt-1 mb-2">
                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Serie</label>
                        <input v-model="form.series" type="text" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Număr</label>
                        <input v-model="form.number" type="text" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Bancă</label>
                        <input v-model="form.bank" type="text" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Scadență</label>
                        <input v-model="form.due_date" type="date" class="form-control form-control-sm">
                    </div>
                </div>
            </div>

            <div class="col-md-4 d-flex align-items-end">
              <button
                class="btn btn-sm btn-primary me-2"
                type="submit"
                :disabled="saving"
              >
                Salvează
              </button>
              <button
                class="btn btn-sm btn-outline-secondary"
                type="button"
                @click="cancelForm"
              >
                Anulează
              </button>
            </div>
          </div>

          <div v-if="formError" class="text-danger small mt-2">
            {{ formError }}
          </div>
        </form>
      </div>
    </div>

    <!-- Lista încasări -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <div v-else-if="!collections.length" class="text-center py-5">
      <div class="mb-3">
        <i class="bi bi-wallet2 text-muted opacity-25" style="font-size: 3rem;"></i>
      </div>
      <h5 class="text-muted">Nu există încasări</h5>
      <p class="text-muted small">Momentan nu sunt înregistrări de încasări.</p>
    </div>

    <div v-else class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
      <div v-for="c in collections" :key="c.id" class="col">
        <div class="card h-100 border shadow-sm hover-shadow transition-all">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
             <div>
                <span class="badge rounded-pill me-2" 
                   :class="{
                      'bg-success bg-opacity-10 text-success': c.type === 'chs',
                      'bg-primary bg-opacity-10 text-primary': c.type === 'bo',
                      'bg-info bg-opacity-10 text-info': c.type === 'cec',
                      'bg-secondary bg-opacity-10 text-secondary': c.type === 'op'
                   }">
                   {{ c.type ? c.type.toUpperCase() : 'N/A' }}
                </span>
                <span class="small text-muted">{{ formatDate(c.payment_date || c.created_at) }}</span>
             </div>
             <div class="fw-bold text-dark">
                {{ formatMoney(c.amount || 0) }}
             </div>
          </div>
          
          <div class="card-body">
             <div class="mb-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">CLIENT</small>
                <div v-if="c.customer">
                   <div class="fw-semibold text-dark text-truncate">{{ c.customer.name || c.customer.company_name }}</div>
                   <div class="small text-muted" v-if="c.customer.cif">{{ c.customer.cif }}</div>
                </div>
                <div v-else class="fw-semibold text-dark">
                   {{ c.customer_name || c.customer_reference || '—' }}
                </div>
             </div>

             <div>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">DETALII</small>
                <div v-if="['bo', 'cec'].includes(c.type)" class="small">
                   <div v-if="c.series || c.number" class="d-flex align-items-center mt-1">
                      <i class="bi bi-file-text me-2 text-muted"></i>
                      <span><strong>Doc:</strong> {{ c.series }} {{ c.number }}</span>
                   </div>
                   <div v-if="c.bank" class="d-flex align-items-center mt-1">
                      <i class="bi bi-bank me-2 text-muted"></i>
                      <span>{{ c.bank }}</span>
                   </div>
                   <div v-if="c.due_date" class="d-flex align-items-center mt-1 text-danger">
                      <i class="bi bi-calendar-event me-2"></i>
                      <span>Scadență: {{ formatDate(c.due_date) }}</span>
                   </div>
                   <div v-if="c.document_number" class="mt-1 text-muted ps-4">{{ c.document_number }}</div>
                </div>
                <div v-else class="small mt-1">
                   <div class="d-flex align-items-center">
                      <i class="bi bi-hash me-2 text-muted"></i>
                      {{ c.reference || c.document_number || 'Fără referință' }}
                   </div>
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
  fetchCollections,
  createCollection
} from '@/services/admin/collections'
import { adminApi } from '@/services/http'

const collections = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const formError = ref('')
const showForm = ref(false)

// Customer search state
const customerSearch = ref('')
const customerResults = ref([])
const showCustomerResults = ref(false)
const searchingCustomers = ref(false)
const selectedCustomer = ref(null)

const form = ref({
  type: 'chs',
  customer_id: null,
  amount: null,
  payment_date: new Date().toISOString().slice(0, 10),
  reference: '',
  document_number: '',
  series: '',
  number: '',
  bank: '',
  due_date: '',
  currency: 'RON',
  status: 'collected'
})

const formatDate = (val) => {
  if (!val) return ''
  const d = new Date(val)
  return d.toLocaleDateString('ro-RO')
}

const formatMoney = (val) => {
  if (val == null) return '0,00 RON'
  return `${Number(val).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })} RON`
}

const typeLabel = (t) => {
  switch (t) {
    case 'chs': return 'CHS – cash'
    case 'bo': return 'BO – bilet la ordin'
    case 'cec': return 'CEC'
    case 'op': return 'OP – ordin de plată'
    default: return t || '—'
  }
}

const loadCollections = async () => {
  loading.value = true
  error.value = ''
  try {
    const resp = await fetchCollections()
    collections.value = resp.data || resp || []
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca încasările (sau endpoint-ul nu există încă).'
  } finally {
    loading.value = false
  }
}

const searchCustomers = async () => {
  if (customerSearch.value.length < 2) {
    customerResults.value = []
    showCustomerResults.value = false
    return
  }
  
  searchingCustomers.value = true
  try {
    const { data } = await adminApi.get('/customers', {
      params: { q: customerSearch.value }
    })
    customerResults.value = data.data || []
    showCustomerResults.value = true
  } catch (e) {
    console.error(e)
  } finally {
    searchingCustomers.value = false
  }
}

const selectCustomer = (customer) => {
  selectedCustomer.value = customer
  form.value.customer_id = customer.id
  customerSearch.value = customer.name || customer.company_name || customer.email
  showCustomerResults.value = false
}

const clearCustomer = () => {
  selectedCustomer.value = null
  form.value.customer_id = null
  customerSearch.value = ''
  customerResults.value = []
}

const startCreate = () => {
  showForm.value = true
  formError.value = ''
  clearCustomer() // Reset customer selection
  form.value = {
    type: 'chs',
    customer_id: null,
    amount: null,
    payment_date: new Date().toISOString().slice(0, 10),
    reference: '',
    document_number: '',
    series: '',
    number: '',
    bank: '',
    due_date: '',
    currency: 'RON',
    status: 'collected'
  }
}

const cancelForm = () => {
  showForm.value = false
}

const saveCollection = async () => {
  formError.value = ''
  saving.value = true
  try {
    const payload = { ...form.value }
    await createCollection(payload)
    showForm.value = false
    await loadCollections()
  } catch (e) {
    console.error(e)
    formError.value =
      e?.response?.data?.message ||
      'Nu s-a putut salva încasarea (sau endpoint-ul nu există încă).'
  } finally {
    saving.value = false
  }
}

onMounted(loadCollections)
</script>
