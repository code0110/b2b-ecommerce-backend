<!-- resources/js/views/admin/Dashboard.vue -->
<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Dashboard admin</h1>
        <div class="text-muted small">
          Overview comenzi, clienți și produse
        </div>
      </div>
    </div>

    <div v-if="loading" class="alert alert-info py-2 small">
      Se încarcă datele de dashboard...
    </div>
    <div v-else-if="error" class="alert alert-danger py-2 small">
      {{ error }}
    </div>

    <div v-if="!loading" class="row g-3 mb-3">
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase mb-1">Comenzi în derulare</div>
            <div class="display-6 mb-0">
              {{ dashboard.orders_in_progress ?? 0 }}
            </div>
            <div class="small text-muted mt-1">
              Status: nouă / în pregătire / livrare
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase mb-1">Clienți B2B</div>
            <div class="display-6 mb-0">
              {{ dashboard.b2b_customers_count ?? 0 }}
            </div>
            <div class="small text-muted mt-1">
              Conturi companie active
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="text-muted small text-uppercase mb-1">Clienți B2C</div>
            <div class="display-6 mb-0">
              {{ dashboard.b2c_customers_count ?? 0 }}
            </div>
            <div class="small text-muted mt-1">
              Utilizatori retail înregistrați
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!loading" class="row g-3">
      <!-- Top clienți -->
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <span class="small text-uppercase text-muted">Top clienți</span>
            <span class="badge bg-light text-dark small">
              {{ topCustomers.length }}
            </span>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Client</th>
                  <th>Tip</th>
                  <th class="text-end">Total comandat</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!topCustomers.length">
                  <td colspan="3" class="text-center small text-muted py-3">
                    Nu există încă date de top clienți.
                  </td>
                </tr>
                <tr v-for="customer in topCustomers" :key="customer.id">
                  <td>
                    <div class="fw-semibold small">
                      {{ customer.name }}
                    </div>
                    <div class="text-muted small" v-if="customer.legal_name">
                      {{ customer.legal_name }}
                    </div>
                  </td>
                  <td class="small text-muted">
                    {{ customer.type === 'b2b' ? 'B2B' : 'B2C' }}
                  </td>
                  <td class="text-end small">
                    <span v-if="customer.total_order_value != null">
                      {{ Number(customer.total_order_value).toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                      RON
                    </span>
                    <span v-else class="text-muted">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Top produse -->
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <span class="small text-uppercase text-muted">Top produse</span>
            <span class="badge bg-light text-dark small">
              {{ topProducts.length }}
            </span>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Produs</th>
                  <th>Cod</th>
                  <th class="text-end">Cantitate</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!topProducts.length">
                  <td colspan="3" class="text-center small text-muted py-3">
                    Nu există încă date de top produse.
                  </td>
                </tr>
                <tr v-for="product in topProducts" :key="product.id">
                  <td class="small">
                    {{ product.name }}
                  </td>
                  <td class="small text-muted">
                    {{ product.internal_code || '-' }}
                  </td>
                  <td class="text-end small">
                    <span v-if="product.total_quantity != null">
                      {{ product.total_quantity }}
                    </span>
                    <span v-else class="text-muted">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { fetchAdminDashboard } from '@/services/admin/dashboard';

const loading = ref(false);
const error = ref('');
const dashboard = ref({
  orders_in_progress: 0,
  b2b_customers_count: 0,
  b2c_customers_count: 0,
  top_customers: [],
  top_products: []
});

const topCustomers = computed(() => dashboard.value.top_customers || []);
const topProducts = computed(() => dashboard.value.top_products || []);

const loadDashboard = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchAdminDashboard();
    dashboard.value = {
      ...dashboard.value,
      ...data
    };
  } catch (e) {
    console.error('Dashboard load error', e);
    error.value = 'Nu s-au putut încărca datele de dashboard.';
  } finally {
    loading.value = false;
  }
};

onMounted(loadDashboard);
</script>
