<!-- resources/js/views/admin/customer-groups/CustomerGroupList.vue -->
<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Grupuri de clienți</h1>
      <button class="btn btn-primary btn-sm" @click="resetForm">
        + Grup nou
      </button>
    </div>

    <div v-if="loading" class="alert alert-info small py-2">
      Se încarcă grupurile de clienți...
    </div>
    <div v-else-if="error" class="alert alert-danger small py-2">
      {{ error }}
    </div>

    <div class="row g-3">
      <div class="col-lg-7">
        <div class="card shadow-sm">
          <div class="card-header py-2">
            <span class="small text-uppercase text-muted">Listă grupuri</span>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Denumire</th>
                  <th>Tip</th>
                  <th class="text-end">Discount default</th>
                  <th class="text-end">Termen plată</th>
                  <th class="text-end">Limită credit</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!groups.length">
                  <td colspan="6" class="text-center small text-muted py-3">
                    Nu există încă grupuri de clienți.
                  </td>
                </tr>
                <tr
                  v-for="group in groups"
                  :key="group.id"
                  :class="{ 'table-active': form.id === group.id }"
                >
                  <td class="small">
                    <div class="fw-semibold">{{ group.name }}</div>
                  </td>
                  <td class="small text-muted">
                    {{ group.type === 'b2b' ? 'B2B' : 'B2C' }}
                  </td>
                  <td class="small text-end">
                    {{ Number(group.default_discount || 0).toFixed(2) }} %
                  </td>
                  <td class="small text-end">
                    {{ group.payment_terms_days || 0 }} zile
                  </td>
                  <td class="small text-end">
                    <span v-if="group.credit_limit != null">
                      {{ Number(group.credit_limit).toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                      RON
                    </span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td class="text-end">
                    <button
                      class="btn btn-link btn-sm text-decoration-none"
                      @click="editGroup(group)"
                    >
                      Editează
                    </button>
                    <button
                      class="btn btn-link btn-sm text-danger text-decoration-none"
                      @click="confirmDelete(group)"
                    >
                      Șterge
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card shadow-sm">
          <div class="card-header py-2">
            <span class="small text-uppercase text-muted">
              {{ form.id ? 'Editează grup' : 'Grup nou' }}
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
                <label class="form-label form-label-sm">Tip grup</label>
                <select
                  v-model="form.type"
                  class="form-select form-select-sm"
                  :class="{ 'is-invalid': validationErrors.type }"
                >
                  <option value="b2b">B2B</option>
                  <option value="b2c">B2C</option>
                </select>
                <div v-if="validationErrors.type" class="invalid-feedback">
                  {{ validationErrors.type[0] }}
                </div>
              </div>

              <div class="row">
                <div class="col-6 mb-2">
                  <label class="form-label form-label-sm">Discount default (%)</label>
                  <input
                    v-model.number="form.default_discount"
                    type="number"
                    min="0"
                    max="90"
                    step="0.01"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.default_discount }"
                  />
                  <div
                    v-if="validationErrors.default_discount"
                    class="invalid-feedback"
                  >
                    {{ validationErrors.default_discount[0] }}
                  </div>
                </div>
                <div class="col-6 mb-2">
                  <label class="form-label form-label-sm">Termen plată (zile)</label>
                  <input
                    v-model.number="form.payment_terms_days"
                    type="number"
                    min="0"
                    step="1"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.payment_terms_days }"
                  />
                  <div
                    v-if="validationErrors.payment_terms_days"
                    class="invalid-feedback"
                  >
                    {{ validationErrors.payment_terms_days[0] }}
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label form-label-sm">Limită credit (RON)</label>
                <input
                  v-model.number="form.credit_limit"
                  type="number"
                  min="0"
                  step="0.01"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': validationErrors.credit_limit }"
                />
                <div
                  v-if="validationErrors.credit_limit"
                  class="invalid-feedback"
                >
                  {{ validationErrors.credit_limit[0] }}
                </div>
              </div>

              <div
                v-if="generalError"
                class="alert alert-danger py-1 small mb-2"
              >
                {{ generalError }}
              </div>

              <div class="d-flex justify-content-between align-items-center">
                <button
                  type="submit"
                  class="btn btn-primary btn-sm"
                  :disabled="saving"
                >
                  <span
                    v-if="saving"
                    class="spinner-border spinner-border-sm me-1"
                  />
                  {{ form.id ? 'Salvează modificările' : 'Creează grup' }}
                </button>

                <button
                  v-if="form.id"
                  type="button"
                  class="btn btn-link btn-sm text-decoration-none"
                  @click="resetForm"
                >
                  Anulează editarea
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
  fetchCustomerGroups,
  createCustomerGroup,
  updateCustomerGroup,
  deleteCustomerGroup
} from '@/services/admin/customerGroups';

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const generalError = ref('');
const groups = ref([]);

const form = ref({
  id: null,
  name: '',
  type: 'b2b',
  default_discount: 0,
  payment_terms_days: 0,
  credit_limit: null
});

const validationErrors = ref({});

const loadGroups = async () => {
  loading.value = true;
  error.value = '';

  try {
    groups.value = await fetchCustomerGroups();
  } catch (e) {
    console.error('Customer groups load error', e);
    error.value = 'Nu s-au putut încărca grupurile de clienți.';
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  form.value = {
    id: null,
    name: '',
    type: 'b2b',
    default_discount: 0,
    payment_terms_days: 0,
    credit_limit: null
  };
  validationErrors.value = {};
  generalError.value = '';
};

const editGroup = group => {
  form.value = {
    id: group.id,
    name: group.name,
    type: group.type || 'b2b',
    default_discount: Number(group.default_discount || 0),
    payment_terms_days: Number(group.payment_terms_days || 0),
    credit_limit:
      group.credit_limit != null ? Number(group.credit_limit) : null
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
    type: form.value.type,
    default_discount: form.value.default_discount,
    payment_terms_days: form.value.payment_terms_days,
    credit_limit: form.value.credit_limit
  };

  try {
    if (form.value.id) {
      const updated = await updateCustomerGroup(form.value.id, payload);
      const idx = groups.value.findIndex(g => g.id === updated.id);
      if (idx !== -1) {
        groups.value[idx] = updated;
      }
    } else {
      const created = await createCustomerGroup(payload);
      groups.value.push(created);
      resetForm();
    }
  } catch (e) {
    console.error('Customer group save error', e);
    if (e.response && e.response.status === 422) {
      validationErrors.value = e.response.data.errors || {};
    } else {
      generalError.value =
        'A apărut o eroare la salvarea grupului de clienți.';
    }
  } finally {
    saving.value = false;
  }
};

const confirmDelete = async group => {
  if (!confirm(`Sigur vrei să ștergi grupul "${group.name}"?`)) {
    return;
  }

  try {
    await deleteCustomerGroup(group.id);
    groups.value = groups.value.filter(g => g.id !== group.id);
    if (form.value.id === group.id) {
      resetForm();
    }
  } catch (e) {
    console.error('Customer group delete error', e);
    alert('Nu s-a putut șterge grupul (vezi dacă are clienți asociați).');
  }
};

onMounted(loadGroups);
</script>
