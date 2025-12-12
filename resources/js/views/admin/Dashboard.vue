<script setup>
import { ref, onMounted } from 'vue';
import { fetchAdminDashboard } from '@/services/admin/dashboard';

const loading = ref(false);
const error = ref('');

// statistici principale – poți extinde după nevoie
const ordersInProgress = ref(0);
const topCustomers = ref([]);
const topProducts = ref([]);

const loadDashboard = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchAdminDashboard();

    // mapăm cheile din răspuns
    ordersInProgress.value = data.orders_in_progress ?? 0;
    topCustomers.value = data.top_customers ?? [];
    topProducts.value = data.top_products ?? [];
  } catch (e) {
    console.error('Admin dashboard error', e);
    error.value = 'Nu s-au putut încărca datele de pe dashboard.';
  } finally {
    loading.value = false;
  }
};

onMounted(loadDashboard);
</script>

<template>
  <div class="container-fluid py-4">
    <h1 class="h3 mb-4">Dashboard administrare</h1>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-if="loading" class="alert alert-info">
      Se încarcă datele de pe dashboard...
    </div>

    <div v-if="!loading">
      <!-- Row cu widget-uri -->
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card shadow-sm">
            <div class="card-body">
              <h6 class="text-muted mb-2">Comenzi în derulare</h6>
              <div class="h3 mb-0">
                {{ ordersInProgress }}
              </div>
            </div>
          </div>
        </div>

        <!-- poți adăuga alte widget-uri (credit, notificări etc.) aici -->
      </div>

      <div class="row">
        <!-- Top clienți -->
        <div class="col-lg-6 mb-4">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-3">Top clienți</h5>
              <div v-if="!topCustomers.length" class="text-muted">
                Nu există clienți în top încă.
              </div>
              <div v-else class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th>Client</th>
                      <th>Email</th>
                      <th>Sold curent</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="customer in topCustomers" :key="customer.id">
                      <td>{{ customer.name }}</td>
                      <td>{{ customer.email }}</td>
                      <td>
                        {{ customer.current_balance }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Top produse -->
        <div class="col-lg-6 mb-4">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-3">Top produse</h5>
              <div v-if="!topProducts.length" class="text-muted">
                Nu există produse în top încă.
              </div>
              <div v-else class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th>Produs</th>
                      <th>Cod intern</th>
                      <th>Stoc</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="product in topProducts" :key="product.id">
                      <td>{{ product.name }}</td>
                      <td>{{ product.internal_code }}</td>
                      <td>{{ product.stock_qty }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- /row -->
    </div>
  </div>
</template>
