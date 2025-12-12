<!-- resources/js/views/admin/customers/CustomerDetails.vue -->
<template>
  <div class="container-fluid py-3" v-if="loaded">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Fișă client</h1>
        <div class="text-muted small">
          ID #{{ customerId }} ·
          <span v-if="customer?.type === 'b2b'">Client B2B</span>
          <span v-else>Client B2C</span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="alert alert-info small py-2">
      Se încarcă datele clientului...
    </div>
    <div v-else-if="error" class="alert alert-danger small py-2">
      {{ error }}
    </div>

    <div v-if="!loading && customer" class="row g-3">
      <!-- Date generale -->
      <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <span class="small text-uppercase text-muted">Date generale</span>
          </div>
          <div class="card-body">
            <form @submit.prevent="submit">
              <div class="row">
                <div class="col-md-6 mb-2">
                  <label class="form-label form-label-sm">Tip client</label>
                  <select
                    v-model="form.type"
                    class="form-select form-select-sm"
                    :class="{ 'is-invalid': validationErrors.type }"
                  >
                    <option value="b2c">B2C</option>
                    <option value="b2b">B2B</option>
                  </select>
                  <div v-if="validationErrors.type" class="invalid-feedback">
                    {{ validationErrors.type[0] }}
                  </div>
                </div>
                <div class="col-md-6 mb-2">
                  <label class="form-label form-label-sm">Grup client</label>
                  <select
                    v-model="form.group_id"
                    class="form-select form-select-sm"
                    :class="{ 'is-invalid': validationErrors.group_id }"
                  >
                    <option :value="null">Fără grup</option>
                    <option
                      v-for="group in groups"
                      :key="group.id"
                      :value="group.id"
                    >
                      {{ group.name }} ({{ group.type.toUpperCase() }})
                    </option>
                  </select>
                  <div v-if="validationErrors.group_id" class="invalid-feedback">
                    {{ validationErrors.group_id[0] }}
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-2">
                  <label class="form-label form-label-sm">Nume afișat</label>
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
                <div class="col-md-6 mb-2">
                  <label class="form-label form-label-sm">Denumire legală (firmă)</label>
                  <input
                    v-model="form.legal_name"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.legal_name }"
                  />
                  <div
                    v-if="validationErrors.legal_name"
                    class="invalid-feedback"
                  >
                    {{ validationErrors.legal_name[0] }}
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">CIF</label>
                  <input
                    v-model="form.cif"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.cif }"
                  />
                  <div v-if="validationErrors.cif" class="invalid-feedback">
                    {{ validationErrors.cif[0] }}
                  </div>
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">Nr. Reg. Com.</label>
                  <input
                    v-model="form.reg_com"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.reg_com }"
                  />
                  <div v-if="validationErrors.reg_com" class="invalid-feedback">
                    {{ validationErrors.reg_com[0] }}
                  </div>
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">IBAN</label>
                  <input
                    v-model="form.iban"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.iban }"
                  />
                  <div v-if="validationErrors.iban" class="invalid-feedback">
                    {{ validationErrors.iban[0] }}
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-2">
                  <label class="form-label form-label-sm">Email</label>
                  <input
                    v-model="form.email"
                    type="email"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.email }"
                  />
                  <div v-if="validationErrors.email" class="invalid-feedback">
                    {{ validationErrors.email[0] }}
                  </div>
                </div>
                <div class="col-md-6 mb-2">
                  <label class="form-label form-label-sm">Telefon</label>
                  <input
                    v-model="form.phone"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.phone }"
                  />
                  <div v-if="validationErrors.phone" class="invalid-feedback">
                    {{ validationErrors.phone[0] }}
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4 mb-2">
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
                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">Limită credit</label>
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
                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">Monedă</label>
                  <input
                    v-model="form.currency"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.currency }"
                  />
                  <div v-if="validationErrors.currency" class="invalid-feedback">
                    {{ validationErrors.currency[0] }}
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <div class="form-check form-switch">
                    <input
                      v-model="form.is_active"
                      class="form-check-input"
                      type="checkbox"
                      id="customer-active"
                    />
                    <label class="form-check-label small" for="customer-active">
                      Client activ
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check form-switch">
                    <input
                      v-model="form.is_partner"
                      class="form-check-input"
                      type="checkbox"
                      id="customer-partner"
                    />
                    <label class="form-check-label small" for="customer-partner">
                      Partener
                    </label>
                  </div>
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
                  Salvează
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Zone info suplimentare: adrese + utilizatori -->
      <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <span class="small text-uppercase text-muted">Adrese</span>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush small">
              <li
                v-if="!customer.addresses || !customer.addresses.length"
                class="list-group-item text-muted"
              >
                Fără adrese definite (se vor gestiona din contul clientului).
              </li>
              <li
                v-for="address in customer.addresses || []"
                :key="address.id"
                class="list-group-item"
              >
                <div class="fw-semibold">{{ address.label || 'Adresă' }}</div>
                <div>{{ address.street }}</div>
                <div class="text-muted">
                  {{ address.city }}, {{ address.county }},
                  {{ address.postal_code }}
                </div>
              </li>
            </ul>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-header py-2">
            <span class="small text-uppercase text-muted">Utilizatori asociați</span>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush small">
              <li
                v-if="!customer.users || !customer.users.length"
                class="list-group-item text-muted"
              >
                Nu există utilizatori asociați (cont B2C sau urmează invitații B2B).
              </li>
              <li
                v-for="user in customer.users || []"
                :key="user.id"
                class="list-group-item"
              >
                <div class="fw-semibold">{{ user.name }}</div>
                <div class="text-muted">{{ user.email }}</div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="container-fluid py-3">
    <div class="alert alert-info small">Se pregătește fișa clientului...</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { fetchCustomer, updateCustomer } from '@/services/admin/customers';
import { fetchCustomerGroups } from '@/services/admin/customerGroups';

const route = useRoute();
const customerId = route.params.id;

const loading = ref(false);
const loaded = ref(false);
const saving = ref(false);
const error = ref('');
const generalError = ref('');
const validationErrors = ref({});

const customer = ref(null);
const groups = ref([]);

const form = ref({
  type: 'b2c',
  name: '',
  legal_name: '',
  cif: '',
  reg_com: '',
  iban: '',
  email: '',
  phone: '',
  group_id: null,
  payment_terms_days: 0,
  credit_limit: null,
  current_balance: null,
  currency: 'RON',
  is_active: true,
  is_partner: false
});

const loadData = async () => {
  loading.value = true;
  error.value = '';

  try {
    groups.value = await fetchCustomerGroups();
    const data = await fetchCustomer(customerId);
    customer.value = data;

    form.value = {
      type: data.type || 'b2c',
      name: data.name || '',
      legal_name: data.legal_name || '',
      cif: data.cif || '',
      reg_com: data.reg_com || '',
      iban: data.iban || '',
      email: data.email || '',
      phone: data.phone || '',
      group_id: data.group_id || null,
      payment_terms_days: data.payment_terms_days || 0,
      credit_limit:
        data.credit_limit != null ? Number(data.credit_limit) : null,
      current_balance:
        data.current_balance != null ? Number(data.current_balance) : null,
      currency: data.currency || 'RON',
      is_active: !!data.is_active,
      is_partner: !!data.is_partner
    };
  } catch (e) {
    console.error('Customer load error', e);
    error.value = 'Nu s-au putut încărca datele clientului.';
  } finally {
    loading.value = false;
    loaded.value = true;
  }
};

const submit = async () => {
  saving.value = true;
  validationErrors.value = {};
  generalError.value = '';

  const payload = {
    ...form.value,
    group_id: form.value.group_id || null
  };

  try {
    const updated = await updateCustomer(customerId, payload);
    customer.value = updated;
  } catch (e) {
    console.error('Customer save error', e);
    if (e.response && e.response.status === 422) {
      validationErrors.value = e.response.data.errors || {};
    } else {
      generalError.value = 'A apărut o eroare la salvarea clientului.';
    }
  } finally {
    saving.value = false;
  }
};

onMounted(loadData);
</script>
