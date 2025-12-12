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
            <div class="col-md-4">
              <label class="form-label form-label-sm">Client (ID sau nume – simplu demo)</label>
              <input
                v-model="form.customer_reference"
                type="text"
                class="form-control form-control-sm"
                placeholder="ID client sau nume"
              >
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
                v-model="form.collected_at"
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
    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Data</th>
              <th>Client</th>
              <th>Tip</th>
              <th>Sumă</th>
              <th>Referință</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr v-if="!loading && !collections.length">
              <td colspan="5" class="text-center text-muted py-3">
                Nu există încasări sau endpoint-ul nu este încă implementat.
              </td>
            </tr>
            <tr
              v-for="c in collections"
              :key="c.id"
            >
              <td class="small">
                {{ formatDate(c.collected_at || c.created_at) }}
              </td>
              <td class="small">
                {{ c.customer_name || c.customer_reference || '—' }}
              </td>
              <td class="small">
                {{ typeLabel(c.type) }}
              </td>
              <td class="small">
                {{ formatMoney(c.amount || 0) }}
              </td>
              <td class="small">
                {{ c.reference || c.document_number || '—' }}
              </td>
            </tr>
          </tbody>
        </table>
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

const collections = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const formError = ref('')
const showForm = ref(false)

const form = ref({
  type: 'chs',
  customer_reference: '',
  amount: null,
  collected_at: '',
  reference: '',
  document_number: ''
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

const startCreate = () => {
  showForm.value = true
  formError.value = ''
  form.value = {
    type: 'chs',
    customer_reference: '',
    amount: null,
    collected_at: new Date().toISOString().slice(0, 10),
    reference: '',
    document_number: ''
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
