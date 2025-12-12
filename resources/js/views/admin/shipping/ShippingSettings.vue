<!-- resources/js/views/admin/shipping/ShippingSettings.vue -->
<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Setări livrare</h1>
    </div>

    <div v-if="loading" class="alert alert-info small py-2">
      Se încarcă metodele de livrare...
    </div>
    <div v-else-if="error" class="alert alert-danger small py-2">
      {{ error }}
    </div>

    <div class="row g-3">
      <div class="col-lg-7">
        <div class="card shadow-sm">
          <div class="card-header py-2">
            <span class="small text-uppercase text-muted">Metode de livrare</span>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Denumire</th>
                  <th>Cod</th>
                  <th>Tip</th>
                  <th>Status</th>
                  <th class="text-end"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!methods.length">
                  <td colspan="5" class="text-center small text-muted py-3">
                    Nu există încă metode de livrare definite.
                  </td>
                </tr>
                <tr v-for="method in methods" :key="method.id">
                  <td class="small">
                    {{ method.name }}
                  </td>
                  <td class="small text-muted">
                    {{ method.code }}
                  </td>
                  <td class="small text-muted">
                    <span v-if="method.type === 'courier'">Curier</span>
                    <span v-else-if="method.type === 'own_fleet'">Flotă proprie</span>
                    <span v-else>Ridicare</span>
                  </td>
                  <td class="small">
                    <span
                      class="badge"
                      :class="method.is_active ? 'bg-success' : 'bg-secondary'"
                    >
                      {{ method.is_active ? 'Activă' : 'Inactivă' }}
                    </span>
                  </td>
                  <td class="text-end">
                    <button
                      class="btn btn-link btn-sm text-decoration-none"
                      @click="editMethod(method)"
                    >
                      Editează
                    </button>
                    <button
                      class="btn btn-link btn-sm text-danger text-decoration-none"
                      @click="confirmDelete(method)"
                    >
                      Șterge
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="card-footer small text-muted">
            Reguli detaliate de preț (shipping_rules) sunt încărcate în backend;
            aici administrezi metodele de bază (cod, tip, activ/inactiv).
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card shadow-sm">
          <div class="card-header py-2">
            <span class="small text-uppercase text-muted">
              {{ form.id ? 'Editează metodă' : 'Metodă nouă' }}
            </span>
          </div>
          <div class="card-body">
            <form @submit.prevent="submit">
              <div class="mb-2">
                <label class="form-label form-label-sm">Denumire</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': validationErrors.name }"
                />
                <div v-if="validationErrors.name" class="invalid-feedback">
                  {{ validationErrors.name[0] }}
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label form-label-sm">Cod intern</label>
                <input
                  v-model="form.code"
                  type="text"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': validationErrors.code }"
                />
                <div v-if="validationErrors.code" class="invalid-feedback">
                  {{ validationErrors.code[0] }}
                </div>
                <div class="form-text small">
                  Cod unic, folosit și în integrarea cu curierul.
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label form-label-sm">Tip</label>
                <select
                  v-model="form.type"
                  class="form-select form-select-sm"
                  :class="{ 'is-invalid': validationErrors.type }"
                >
                  <option value="courier">Curier</option>
                  <option value="own_fleet">Flotă proprie</option>
                  <option value="pickup">Ridicare din depozit</option>
                </select>
                <div v-if="validationErrors.type" class="invalid-feedback">
                  {{ validationErrors.type[0] }}
                </div>
              </div>

              <div class="mb-3">
                <div class="form-check form-switch">
                  <input
                    v-model="form.is_active"
                    class="form-check-input"
                    type="checkbox"
                    id="shipping-active"
                  />
                  <label class="form-check-label small" for="shipping-active">
                    Metodă activă
                  </label>
                </div>
              </div>

              <div
                v-if="generalError"
                class="alert alert-danger small py-1 mb-2"
              >
                {{ generalError }}
              </div>

              <div class="d-flex justify-content-between">
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  @click="resetForm"
                >
                  Reset
                </button>
                <button
                  type="submit"
                  class="btn btn-primary btn-sm"
                  :disabled="saving"
                >
                  <span
                    v-if="saving"
                    class="spinner-border spinner-border-sm me-1"
                  />
                  {{ form.id ? 'Salvează metoda' : 'Creează metoda' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import {
  fetchShippingMethods,
  createShippingMethod,
  updateShippingMethod,
  deleteShippingMethod
} from '@/services/admin/shipping';

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const generalError = ref('');
const methods = ref([]);

const form = ref({
  id: null,
  name: '',
  code: '',
  type: 'courier',
  is_active: true
});

const validationErrors = ref({});

const loadMethods = async () => {
  loading.value = true;
  error.value = '';

  try {
    methods.value = await fetchShippingMethods();
  } catch (e) {
    console.error('Shipping methods load error', e);
    error.value = 'Nu s-au putut încărca metodele de livrare.';
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  form.value = {
    id: null,
    name: '',
    code: '',
    type: 'courier',
    is_active: true
  };
  validationErrors.value = {};
  generalError.value = '';
};

const editMethod = method => {
  form.value = {
    id: method.id,
    name: method.name,
    code: method.code,
    type: method.type || 'courier',
    is_active: !!method.is_active
  };
  validationErrors.value = {};
  generalError.value = '';
};

const submit = async () => {
  saving.value = true;
  validationErrors.value = {};
  generalError.value = '';

  const payload = {
    name: form.value.name,
    code: form.value.code,
    type: form.value.type,
    is_active: form.value.is_active
  };

  try {
    if (form.value.id) {
      const updated = await updateShippingMethod(form.value.id, payload);
      const idx = methods.value.findIndex(m => m.id === updated.id);
      if (idx !== -1) {
        methods.value[idx] = updated;
      }
    } else {
      const created = await createShippingMethod(payload);
      methods.value.push(created);
      resetForm();
    }
  } catch (e) {
    console.error('Shipping method save error', e);
    if (e.response && e.response.status === 422) {
      validationErrors.value = e.response.data.errors || {};
    } else {
      generalError.value =
        'A apărut o eroare la salvarea metodei de livrare.';
    }
  } finally {
    saving.value = false;
  }
};

const confirmDelete = async method => {
  if (!confirm(`Sigur vrei să ștergi metoda "${method.name}"?`)) {
    return;
  }

  try {
    await deleteShippingMethod(method.id);
    methods.value = methods.value.filter(m => m.id !== method.id);
    if (form.value.id === method.id) {
      resetForm();
    }
  } catch (e) {
    console.error('Shipping method delete error', e);
    alert('Nu s-a putut șterge metoda de livrare.');
  }
};

onMounted(loadMethods);
</script>
