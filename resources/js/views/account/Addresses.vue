<template>
  <div class="container py-4">
    <h1 class="h4 mb-3">Adrese & date livrare / facturare</h1>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>
    <div v-if="loading" class="alert alert-info">
      Se încarcă adresele...
    </div>

    <div class="mb-3">
      <button
        type="button"
        class="btn btn-primary btn-sm"
        @click="startCreate('shipping')"
      >
        Adaugă adresă de livrare
      </button>
      <button
        type="button"
        class="btn btn-outline-primary btn-sm ms-2"
        @click="startCreate('billing')"
      >
        Adaugă adresă de facturare
      </button>
    </div>

    <div class="row g-3">
      <!-- Adrese livrare -->
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-body">
            <h2 class="h6 mb-3">Adrese de livrare</h2>
            <div
              v-if="shippingAddresses.length === 0"
              class="text-muted small"
            >
              Nu ai adrese de livrare configurate.
            </div>
            <div
              v-for="address in shippingAddresses"
              :key="address.id"
              class="border rounded p-2 mb-2 d-flex justify-content-between align-items-start"
            >
              <div class="small">
                <div class="fw-semibold">
                  {{ address.label || 'Adresă livrare' }}
                  <span
                    v-if="address.is_default"
                    class="badge bg-success ms-1"
                  >
                    Implicită
                  </span>
                </div>
                <div v-if="address.company_name">
                  {{ address.company_name }}
                </div>
                <div v-if="address.name">
                  {{ address.name }}
                </div>
                <div>
                  {{ address.street }}
                </div>
                <div>
                  {{ address.city }}, {{ address.county }}
                </div>
                <div>
                  {{ address.zip }}, {{ address.country }}
                </div>
              </div>
              <div class="ms-2">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary mb-1"
                  @click="startEdit(address)"
                >
                  Editează
                </button>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-danger"
                  @click="handleDelete(address)"
                >
                  Șterge
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Adrese facturare -->
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-body">
            <h2 class="h6 mb-3">Adrese de facturare</h2>
            <div
              v-if="billingAddresses.length === 0"
              class="text-muted small"
            >
              Nu ai adrese de facturare configurate.
            </div>
            <div
              v-for="address in billingAddresses"
              :key="address.id"
              class="border rounded p-2 mb-2 d-flex justify-content-between align-items-start"
            >
              <div class="small">
                <div class="fw-semibold">
                  {{ address.label || 'Adresă facturare' }}
                  <span
                    v-if="address.is_default"
                    class="badge bg-success ms-1"
                  >
                    Implicită
                  </span>
                </div>
                <div v-if="address.company_name">
                  {{ address.company_name }}
                </div>
                <div v-if="address.name">
                  {{ address.name }}
                </div>
                <div>
                  {{ address.street }}
                </div>
                <div>
                  {{ address.city }}, {{ address.county }}
                </div>
                <div>
                  {{ address.zip }}, {{ address.country }}
                </div>
              </div>
              <div class="ms-2">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary mb-1"
                  @click="startEdit(address)"
                >
                  Editează
                </button>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-danger"
                  @click="handleDelete(address)"
                >
                  Șterge
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Formular adăugare / editare -->
    <div v-if="showForm" class="card mt-4">
      <div class="card-body">
        <h2 class="h6 mb-3">
          {{ form.id ? 'Editează adresă' : 'Adaugă adresă' }}
          <span class="text-muted small">
            ({{ form.type === 'shipping' ? 'Livrare' : 'Facturare' }})
          </span>
        </h2>

        <div v-if="formError" class="alert alert-danger">
          {{ formError }}
        </div>

        <form class="row g-3" @submit.prevent="submitForm">
          <div class="col-md-4">
            <label class="form-label small">Etichetă</label>
            <input
              v-model="form.label"
              type="text"
              class="form-control form-control-sm"
              placeholder="Ex: Sediu central, Depozit, etc."
            />
          </div>
          <div class="col-md-4">
            <label class="form-label small">Nume / Persoană contact</label>
            <input
              v-model="form.name"
              type="text"
              class="form-control form-control-sm"
              required
            />
          </div>
          <div class="col-md-4">
            <label class="form-label small">Denumire firmă</label>
            <input
              v-model="form.company_name"
              type="text"
              class="form-control form-control-sm"
            />
          </div>

          <div class="col-md-6">
            <label class="form-label small">Stradă, număr, bloc etc.</label>
            <input
              v-model="form.street"
              type="text"
              class="form-control form-control-sm"
              required
            />
          </div>
          <div class="col-md-3">
            <label class="form-label small">Oraș</label>
            <input
              v-model="form.city"
              type="text"
              class="form-control form-control-sm"
              required
            />
          </div>
          <div class="col-md-3">
            <label class="form-label small">Județ</label>
            <input
              v-model="form.county"
              type="text"
              class="form-control form-control-sm"
              required
            />
          </div>

          <div class="col-md-3">
            <label class="form-label small">Cod poștal</label>
            <input
              v-model="form.zip"
              type="text"
              class="form-control form-control-sm"
              required
            />
          </div>
          <div class="col-md-3">
            <label class="form-label small">Țară</label>
            <input
              v-model="form.country"
              type="text"
              class="form-control form-control-sm"
              required
            />
          </div>
          <div class="col-md-3">
            <label class="form-label small">Telefon</label>
            <input
              v-model="form.phone"
              type="text"
              class="form-control form-control-sm"
            />
          </div>
          <div class="col-md-3 d-flex align-items-center">
            <div class="form-check mt-3">
              <input
                v-model="form.is_default"
                class="form-check-input"
                type="checkbox"
                id="address-default"
              />
              <label class="form-check-label small" for="address-default">
                Setează ca adresă implicită
              </label>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2 mt-3">
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="cancelForm"
            >
              Anulează
            </button>
            <button
              type="submit"
              class="btn btn-primary btn-sm"
              :disabled="formSubmitting"
            >
              <span v-if="!formSubmitting">
                {{ form.id ? 'Salvează modificările' : 'Adaugă adresă' }}
              </span>
              <span v-else>Se salvează...</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
  fetchAccountAddresses,
  createAccountAddress,
  updateAccountAddress,
  deleteAccountAddress,
} from '@/services/account';

const loading = ref(false);
const error = ref('');

const addresses = ref([]);

const showForm = ref(false);
const form = ref({
  id: null,
  type: 'shipping', // shipping | billing
  label: '',
  name: '',
  company_name: '',
  street: '',
  city: '',
  county: '',
  zip: '',
  country: '',
  phone: '',
  is_default: false,
});

const formError = ref('');
const formSubmitting = ref(false);

const shippingAddresses = computed(() =>
  addresses.value.filter((a) => a.type === 'shipping'),
);
const billingAddresses = computed(() =>
  addresses.value.filter((a) => a.type === 'billing'),
);

const loadAddresses = async () => {
  loading.value = true;
  error.value = '';
  addresses.value = [];

  try {
    const data = await fetchAccountAddresses();

    if (Array.isArray(data)) {
      addresses.value = data;
    } else if (Array.isArray(data.addresses)) {
      addresses.value = data.addresses;
    } else if (Array.isArray(data.data)) {
      addresses.value = data.data;
    }
  } catch (e) {
    console.error('Account addresses error', e);
    error.value =
      e.response?.data?.message ||
      'Nu s-au putut încărca adresele.';
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  form.value = {
    id: null,
    type: 'shipping',
    label: '',
    name: '',
    company_name: '',
    street: '',
    city: '',
    county: '',
    zip: '',
    country: '',
    phone: '',
    is_default: false,
  };
  formError.value = '';
};

const startCreate = (type) => {
  resetForm();
  form.value.type = type;
  showForm.value = true;
};

const startEdit = (address) => {
  form.value = {
    id: address.id,
    type: address.type || 'shipping',
    label: address.label || '',
    name: address.name || '',
    company_name: address.company_name || '',
    street: address.street || '',
    city: address.city || '',
    county: address.county || '',
    zip: address.zip || '',
    country: address.country || '',
    phone: address.phone || '',
    is_default: !!address.is_default,
  };
  formError.value = '';
  showForm.value = true;
};

const cancelForm = () => {
  showForm.value = false;
  resetForm();
};

const submitForm = async () => {
  formError.value = '';
  formSubmitting.value = true;

  try {
    const payload = { ...form.value };

    if (payload.id) {
      await updateAccountAddress(payload.id, payload);
    } else {
      await createAccountAddress(payload);
    }

    showForm.value = false;
    resetForm();
    await loadAddresses();
  } catch (e) {
    console.error('Save address error', e);
    if (e.response?.data?.errors) {
      formError.value = Object.values(e.response.data.errors)
        .flat()
        .join(' ');
    } else {
      formError.value =
        e.response?.data?.message ||
        'Salvarea adresei a eșuat. Verifică datele și încearcă din nou.';
    }
  } finally {
    formSubmitting.value = false;
  }
};

const handleDelete = async (address) => {
  if (!confirm('Sigur vrei să ștergi această adresă?')) return;

  try {
    await deleteAccountAddress(address.id);
    await loadAddresses();
  } catch (e) {
    console.error('Delete address error', e);
    alert('Ștergerea adresei a eșuat.');
  }
};

onMounted(() => {
  loadAddresses();
});
</script>
