<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Grupuri de clienți</h1>
      <button
        class="btn btn-sm btn-primary"
        type="button"
        @click="startCreate"
      >
        Adaugă grup
      </button>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div class="card mb-3">
      <div class="card-header py-2 small">
        {{ currentGroup?.id ? 'Editează grup' : 'Grup nou' }}
      </div>
      <div class="card-body">
        <form @submit.prevent="saveGroup">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label form-label-sm">Denumire grup</label>
              <input
                v-model="form.name"
                type="text"
                class="form-control form-control-sm"
                required
              >
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">Tip</label>
              <select
                v-model="form.type"
                class="form-select form-select-sm"
              >
                <option value="b2b">B2B</option>
                <option value="b2c">B2C</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label form-label-sm">Discount default (%)</label>
              <input
                v-model.number="form.default_discount_percent"
                type="number"
                min="0"
                max="100"
                step="0.01"
                class="form-control form-control-sm"
              >
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">Termen de plată (zile)</label>
              <input
                v-model.number="form.default_payment_terms_days"
                type="number"
                min="0"
                class="form-control form-control-sm"
              >
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-3">
              <label class="form-label form-label-sm">Limită credit (RON)</label>
              <input
                v-model.number="form.default_credit_limit"
                type="number"
                step="0.01"
                class="form-control form-control-sm"
              >
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <div class="form-check">
                <input
                  v-model="form.is_active"
                  class="form-check-input"
                  type="checkbox"
                  id="group-active"
                >
                <label class="form-check-label small" for="group-active">
                  Activ
                </label>
              </div>
            </div>
          </div>

          <div class="mt-3 d-flex justify-content-between">
            <div>
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
                @click="resetForm"
              >
                Reset
              </button>
            </div>
            <div class="small text-muted" v-if="currentGroup?.id">
              ID: {{ currentGroup.id }}
            </div>
          </div>

          <div v-if="formError" class="text-danger small mt-2">
            {{ formError }}
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Denumire</th>
              <th>Tip</th>
              <th>Discount</th>
              <th>Termen plată</th>
              <th>Limită credit</th>
              <th>Status</th>
              <th style="width: 140px;">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr v-if="!loading && !groups.length">
              <td colspan="7" class="text-center text-muted py-3">
                Nu există grupuri definite.
              </td>
            </tr>
            <tr
              v-for="g in groups"
              :key="g.id"
            >
              <td class="small">
                {{ g.name }}
              </td>
              <td class="small">
                {{ g.type?.toUpperCase?.() || g.type }}
              </td>
              <td class="small">
                {{ g.default_discount_percent ?? 0 }} %
              </td>
              <td class="small">
                {{ g.default_payment_terms_days ?? '—' }} zile
              </td>
              <td class="small">
                {{ formatMoney(g.default_credit_limit || 0) }}
              </td>
              <td class="small">
                <span
                  class="badge"
                  :class="g.is_active ? 'bg-success' : 'bg-secondary'"
                >
                  {{ g.is_active ? 'Activ' : 'Inactiv' }}
                </span>
              </td>
              <td class="small">
                <div class="btn-group btn-group-sm">
                  <button
                    class="btn btn-outline-secondary"
                    type="button"
                    @click="editGroup(g)"
                  >
                    Editează
                  </button>
                  <button
                    class="btn btn-outline-danger"
                    type="button"
                    @click="removeGroup(g)"
                  >
                    Șterge
                  </button>
                </div>
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
