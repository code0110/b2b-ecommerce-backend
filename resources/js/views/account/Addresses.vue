<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Adrese de facturare și livrare</h1>
        <p class="small text-muted mb-0">
          Gestionează adrese multiple pentru comenzi recurente și proiecte.
        </p>
      </div>
      <button
        type="button"
        class="btn btn-primary btn-sm"
        @click="startCreate('shipping')"
      >
        Adresă nouă
      </button>
    </div>

    <div v-if="error" class="alert alert-danger small mb-3">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border spinner-border-sm" role="status" />
      <div class="small text-muted mt-2">Se încarcă adresele...</div>
    </div>

    <div v-else class="row g-3">
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-body small">
            <h2 class="h6 mb-3">Adrese de livrare</h2>

            <div v-if="shippingAddresses.length === 0" class="text-muted">
              Nu există adrese de livrare definite.
            </div>

            <div
              v-for="addr in shippingAddresses"
              :key="addr.id"
              class="border rounded p-2 mb-2"
            >
              <div class="d-flex justify-content-between">
                <div>
                  <div class="fw-semibold">
                    {{ addr.name || 'Adresă livrare' }}
                    <span
                      v-if="addr.is_default"
                      class="badge bg-primary ms-1"
                    >
                      Implicită
                    </span>
                  </div>
                  <div>{{ addr.company_name }}</div>
                  <div>{{ addr.street }}</div>
                  <div>
                    {{ addr.zip }}
                    {{ addr.city }},
                    {{ addr.county }}
                  </div>
                  <div>{{ addr.country }}</div>
                </div>
                <div class="text-end">
                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm mb-1"
                    @click="startEdit(addr)"
                  >
                    Editează
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm mb-1"
                    @click="confirmDelete(addr)"
                  >
                    Șterge
                  </button>
                  <button
                    v-if="!addr.is_default"
                    type="button"
                    class="btn btn-link btn-sm p-0 d-block"
                    @click="makeDefault(addr)"
                  >
                    Setează implicită
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-body small">
            <h2 class="h6 mb-3">Adrese de facturare</h2>

            <div v-if="billingAddresses.length === 0" class="text-muted">
              Nu există adrese de facturare definite.
            </div>

            <div
              v-for="addr in billingAddresses"
              :key="addr.id"
              class="border rounded p-2 mb-2"
            >
              <div class="d-flex justify-content-between">
                <div>
                  <div class="fw-semibold">
                    {{ addr.name || 'Adresă facturare' }}
                    <span
                      v-if="addr.is_default"
                      class="badge bg-primary ms-1"
                    >
                      Implicită
                    </span>
                  </div>
                  <div>{{ addr.company_name }}</div>
                  <div>{{ addr.street }}</div>
                  <div>
                    {{ addr.zip }}
                    {{ addr.city }},
                    {{ addr.county }}
                  </div>
                  <div>{{ addr.country }}</div>
                </div>
                <div class="text-end">
                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm mb-1"
                    @click="startEdit(addr)"
                  >
                    Editează
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm mb-1"
                    @click="confirmDelete(addr)"
                  >
                    Șterge
                  </button>
                  <button
                    v-if="!addr.is_default"
                    type="button"
                    class="btn btn-link btn-sm p-0 d-block"
                    @click="makeDefault(addr)"
                  >
                    Setează implicită
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Formular add/edit -->
      <div
        class="col-12"
        v-if="editingAddress"
      >
        <div class="card shadow-sm">
          <div class="card-body small">
            <div class="d-flex justify-content-between mb-2">
              <h2 class="h6 mb-0">
                {{ editingAddress.id ? 'Editează adresă' : 'Adresă nouă' }}
              </h2>
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                @click="cancelEdit"
              >
                Renunță
              </button>
            </div>

            <form class="row g-2" @submit.prevent="submitAddress">
              <div class="col-md-6">
                <label class="form-label form-label-sm">
                  Tip adresă
                </label>
                <select
                  v-model="editingAddress.type"
                  class="form-select form-select-sm"
                  required
                >
                  <option value="shipping">Livrare</option>
                  <option value="billing">Facturare</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label form-label-sm">
                  Nume / Etichetă
                </label>
                <input
                  v-model="editingAddress.name"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="ex: Sediu, Depozit, Sucursala X"
                />
              </div>
              <div class="col-md-6">
                <label class="form-label form-label-sm">
                  Denumire companie
                </label>
                <input
                  v-model="editingAddress.company_name"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-md-6">
                <label class="form-label form-label-sm">
                  Stradă și nr. *
                </label>
                <input
                  v-model="editingAddress.street"
                  type="text"
                  class="form-control form-control-sm"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label form-label-sm">
                  Localitate *
                </label>
                <input
                  v-model="editingAddress.city"
                  type="text"
                  class="form-control form-control-sm"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label form-label-sm">
                  Județ *
                </label>
                <input
                  v-model="editingAddress.county"
                  type="text"
                  class="form-control form-control-sm"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label form-label-sm">
                  Cod poștal
                </label>
                <input
                  v-model="editingAddress.zip"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label form-label-sm">
                  Țară *
                </label>
                <input
                  v-model="editingAddress.country"
                  type="text"
                  class="form-control form-control-sm"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label form-label-sm">
                  Telefon
                </label>
                <input
                  v-model="editingAddress.phone"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                  <input
                    v-model="editingAddress.is_default"
                    class="form-check-input"
                    type="checkbox"
                    id="isDefault"
                  />
                  <label
                    class="form-check-label"
                    for="isDefault"
                  >
                    Setează implicită
                  </label>
                </div>
              </div>

              <div class="col-12 d-flex justify-content-end">
                <button
                  type="submit"
                  class="btn btn-primary btn-sm"
                  :disabled="saving"
                >
                  <span
                    v-if="saving"
                    class="spinner-border spinner-border-sm me-1"
                  ></span>
                  Salvează
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
import { ref, onMounted, computed } from 'vue';
import {
  fetchAddresses,
  createAddress,
  updateAddress,
  deleteAddress,
  setDefaultAddress,
} from '@/services/account/addresses';

const loading = ref(false);
const saving = ref(false);
const error = ref('');

const addresses = ref([]);
const editingAddress = ref(null);

const shippingAddresses = computed(() =>
  addresses.value.filter((a) => a.type === 'shipping')
);
const billingAddresses = computed(() =>
  addresses.value.filter((a) => a.type === 'billing')
);

const loadAddresses = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchAddresses();
    addresses.value = data.data ?? data;
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut încărca adresele.';
  } finally {
    loading.value = false;
  }
};

const startCreate = (type = 'shipping') => {
  editingAddress.value = {
    id: null,
    type,
    name: '',
    company_name: '',
    street: '',
    city: '',
    county: '',
    zip: '',
    country: 'România',
    phone: '',
    is_default: false,
  };
};

const startEdit = (addr) => {
  editingAddress.value = { ...addr };
};

const cancelEdit = () => {
  editingAddress.value = null;
};

const submitAddress = async () => {
  if (!editingAddress.value) return;

  saving.value = true;
  error.value = '';

  try {
    const payload = { ...editingAddress.value };

    if (payload.id) {
      await updateAddress(payload.id, payload);
    } else {
      await createAddress(payload);
    }

    editingAddress.value = null;
    await loadAddresses();
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut salva adresa.';
  } finally {
    saving.value = false;
  }
};

const confirmDelete = async (addr) => {
  if (!window.confirm('Sigur dorești să ștergi această adresă?')) return;

  try {
    await deleteAddress(addr.id);
    await loadAddresses();
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut șterge adresa.';
  }
};

const makeDefault = async (addr) => {
  try {
    await setDefaultAddress(addr.id);
    await loadAddresses();
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut seta adresa implicită.';
  }
};

onMounted(loadAddresses);
</script>
