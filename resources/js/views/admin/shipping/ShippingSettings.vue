<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Setări livrare & transport</h1>
      <button
        class="btn btn-sm btn-primary"
        type="button"
        @click="addRule"
      >
        Adaugă regulă
      </button>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Metodă / curier</th>
              <th>Tip</th>
              <th>Regulă</th>
              <th class="text-end">Cost</th>
              <th>Activ</th>
              <th style="width: 140px;">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr v-if="!loading && rules.length === 0">
              <td colspan="6" class="text-center text-muted py-3">
                Nu există reguli de livrare configurate.
              </td>
            </tr>
            <tr
              v-for="rule in rules"
              :key="rule.id || rule._localId"
            >
              <td class="small">
                <input
                  v-model="rule.name"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Curier / tip livrare"
                >
              </td>
              <td class="small">
                <select
                  v-model="rule.type"
                  class="form-select form-select-sm"
                >
                  <option value="by_value">Pe valoare comandă</option>
                  <option value="by_weight">Pe greutate</option>
                  <option value="flat">Cost fix</option>
                </select>
              </td>
              <td class="small">
                <div v-if="rule.type === 'by_value'">
                  Min:
                  <input
                    v-model.number="rule.min_value"
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm d-inline-block"
                    style="width: 90px;"
                  >
                  Max:
                  <input
                    v-model.number="rule.max_value"
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm d-inline-block"
                    style="width: 90px;"
                  >
                </div>
                <div v-else-if="rule.type === 'by_weight'">
                  Kg de la:
                  <input
                    v-model.number="rule.min_weight"
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm d-inline-block"
                    style="width: 90px;"
                  >
                  până la:
                  <input
                    v-model.number="rule.max_weight"
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm d-inline-block"
                    style="width: 90px;"
                  >
                </div>
                <div v-else>
                  — cost fix —
                </div>
              </td>
              <td class="small text-end">
                <input
                  v-model.number="rule.cost"
                  type="number"
                  step="0.01"
                  class="form-control form-control-sm"
                  style="max-width: 120px; margin-left: auto;"
                >
              </td>
              <td class="small text-center">
                <input
                  v-model="rule.is_active"
                  type="checkbox"
                  class="form-check-input"
                >
              </td>
              <td class="small">
                <div class="btn-group btn-group-sm">
                  <button
                    class="btn btn-outline-primary"
                    type="button"
                    @click="saveRule(rule)"
                    :disabled="saving"
                  >
                    Salvează
                  </button>
                  <button
                    class="btn btn-outline-danger"
                    type="button"
                    @click="removeRule(rule)"
                    :disabled="saving"
                  >
                    Șterge
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="saveError" class="card-footer py-2 text-danger small">
        {{ saveError }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  fetchShippingConfig,
  createShippingConfig,
  updateShippingConfig,
  deleteShippingConfig
} from '@/services/admin/shipping'

const rules = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const saveError = ref('')

const loadConfig = async () => {
  loading.value = true
  error.value = ''
  try {
    const resp = await fetchShippingConfig()
    rules.value = resp.data || resp || []
  } catch (e) {
    // console.error(e)
    error.value = 'Nu s-au putut încărca regulile de transport.'
  } finally {
    loading.value = false
  }
}

const addRule = () => {
  rules.value.push({
    _localId: `local-${Date.now()}-${Math.random()}`,
    name: '',
    type: 'by_value',
    min_value: 0,
    max_value: null,
    min_weight: null,
    max_weight: null,
    cost: 0,
    is_active: true
  })
}

const saveRule = async (rule) => {
  saveError.value = ''
  saving.value = true
  try {
    const payload = { ...rule }
    delete payload._localId

    if (rule.id) {
      await updateShippingConfig(rule.id, payload)
    } else {
      const created = await createShippingConfig(payload)
      // reîncarcăm tot pentru a avea id real
      await loadConfig()
      return
    }
  } catch (e) {
    // console.error(e)
    saveError.value =
      e?.response?.data?.message ||
      'Nu s-a putut salva regula de transport.'
  } finally {
    saving.value = false
  }
}

const removeRule = async (rule) => {
  if (!rule.id) {
    rules.value = rules.value.filter((r) => r !== rule)
    return
  }
  if (!confirm(`Ștergi regula "${rule.name || ''}"?`)) return
  try {
    await deleteShippingConfig(rule.id)
    await loadConfig()
  } catch (e) {
    // console.error(e)
    alert('Nu s-a putut șterge regula.')
  }
}

onMounted(loadConfig)
</script>
