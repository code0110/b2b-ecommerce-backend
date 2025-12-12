<!-- resources/js/views/admin/customers/CustomerList.vue -->
<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Clienți</h1>
    </div>

    <form class="card mb-3 shadow-sm" @submit.prevent="applyFilters">
      <div class="card-body row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label form-label-sm">Căutare</label>
          <input
            v-model="filters.q"
            type="text"
            class="form-control form-control-sm"
            placeholder="nume, email, CIF, telefon..."
          />
        </div>
        <div class="col-md-3">
          <label class="form-label form-label-sm">Tip client</label>
          <select
            v-model="filters.type"
            class="form-select form-select-sm"
          >
            <option value="">Toate</option>
            <option value="b2b">B2B</option>
            <option value="b2c">B2C</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label form-label-sm d-block">&nbsp;</label>
          <button
            type="submit"
            class="btn btn-primary btn-sm me-2"
            :disabled="loading"
          >
            Aplică filtre
          </button>
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            @click="resetFilters"
          >
            Reset
          </button>
        </div>
      </div>
    </form>

    <div v-if="loading" class="alert alert-info small py-2">
      Se încarcă lista de clienți...
    </div>
    <div v-else-if="error" class="alert alert-danger small py-2">
      {{ error }}
    </div>

    <div class="card shadow-sm">
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th>Client</th>
              <th>Tip</th>
              <th>Grup</th>
              <th>Email</th>
              <th>Telefon</th>
              <th class="text-end">Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!customers.length">
              <td colspan="7" class="text-center small text-muted py-3">
                Nu au fost găsiți clienți pentru filtrele curente.
              </td>
            </tr>
            <tr v-for="customer in customers" :key="customer.id">
              <td class="small">
                <div class="fw-semibold">{{ customer.name }}</div>
                <div v-if="customer.legal_name" class="text-muted">
                  {{ customer.legal_name }}
                </div>
              </td>
              <td class="small text-muted">
                {{ customer.type === 'b2b' ? 'B2B' : 'B2C' }}
              </td>
              <td class="small text-muted">
                {{ customer.group?.name || '-' }}
              </td>
              <td class="small">
                {{ customer.email || '-' }}
              </td>
              <td class="small">
                {{ customer.phone || '-' }}
              </td>
              <td class="small text-end">
                <span
                  class="badge"
                  :class="customer.is_active ? 'bg-success' : 'bg-secondary'"
                >
                  {{ customer.is_active ? 'Activ' : 'Blocat' }}
                </span>
                <span
                  v-if="customer.is_partner"
                  class="badge bg-info ms-1"
                >
                  Partener
                </span>
              </td>
              <td class="text-end">
                <button
                  class="btn btn-link btn-sm text-decoration-none"
                  @click="goToDetails(customer.id)"
                >
                  Fișă client
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="pagination.last_page > 1"
        class="card-footer py-2 d-flex justify-content-between align-items-center small"
      >
        <div>
          Pagina {{ pagination.current_page }} din {{ pagination.last_page }}
        </div>
        <div class="btn-group btn-group-sm">
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="pagination.current_page <= 1 || loading"
            @click="changePage(pagination.current_page - 1)"
          >
            « Înapoi
          </button>
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="pagination.current_page >= pagination.last_page || loading"
            @click="changePage(pagination.current_page + 1)"
          >
            Înainte »
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { fetchCustomers } from '@/services/admin/customers';

const router = useRouter();

const loading = ref(false);
const error = ref('');
const customers = ref([]);

const filters = reactive({
  q: '',
  type: ''
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
});

const loadCustomers = async (page = 1) => {
  loading.value = true;
  error.value = '';

  try {
    const response = await fetchCustomers({
      page,
      q: filters.q || undefined,
      type: filters.type || undefined
    });

    customers.value = response.data || [];
    const meta = response.meta || {};

    pagination.value = {
      current_page: meta.current_page || 1,
      last_page: meta.last_page || 1,
      total: meta.total || customers.value.length
    };
  } catch (e) {
    console.error('Customers load error', e);
    error.value = 'Nu s-a putut încărca lista de clienți.';
  } finally {
    loading.value = false;
  }
};

const applyFilters = () => {
  loadCustomers(1);
};

const resetFilters = () => {
  filters.q = '';
  filters.type = '';
  loadCustomers(1);
};

const changePage = page => {
  if (page < 1 || page > pagination.value.last_page) return;
  loadCustomers(page);
};

const goToDetails = id => {
  router.push({
    name: 'admin-customer-details',
    params: { id }
  });
};

onMounted(() => loadCustomers(1));
</script>
