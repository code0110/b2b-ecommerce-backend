<template>
  <div class="container py-4">
    <h1 class="h4 mb-3">Dashboard client</h1>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>
    <div v-if="loading" class="alert alert-info">
      Se încarcă datele contului...
    </div>

    <div v-if="dashboard">
      <!-- Panou de sus: credit, sold, statistici rapide -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-body">
              <h2 class="h6 mb-2">Status cont</h2>
              <p class="small mb-1">
                Tip client:
                <strong>
                  {{ dashboard.customer_type_label || dashboard.customer_type || '—' }}
                </strong>
              </p>
              <p v-if="dashboard.company_name" class="small mb-1">
                Firmă: <strong>{{ dashboard.company_name }}</strong>
              </p>
              <p v-if="dashboard.contact_name" class="small mb-1">
                Persoană contact: <strong>{{ dashboard.contact_name }}</strong>
              </p>
              <p v-if="dashboard.segment" class="small mb-0 text-muted">
                Segment: {{ dashboard.segment }}
              </p>
            </div>
          </div>
        </div>

        <div class="col-md-4" v-if="dashboard.credit_info">
          <div class="card h-100">
            <div class="card-body">
              <h2 class="h6 mb-2">Credit & sold</h2>
              <p class="small mb-1">
                Sold curent:
                <strong>
                  {{ formatPrice(dashboard.credit_info.current_balance) }} RON
                </strong>
              </p>
              <p class="small mb-1">
                Sold restant:
                <strong>
                  {{ formatPrice(dashboard.credit_info.overdue_balance) }} RON
                </strong>
              </p>
              <p class="small mb-1">
                Limită credit:
                <strong>
                  {{ formatPrice(dashboard.credit_info.credit_limit) }} RON
                </strong>
              </p>
              <p class="small mb-0 text-muted" v-if="dashboard.credit_info.payment_terms">
                Termen de plată:
                {{ dashboard.credit_info.payment_terms }} zile
              </p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-body">
              <h2 class="h6 mb-2">Activitate recentă</h2>
              <p class="small mb-1">
                Comenzi în derulare:
                <strong>{{ dashboard.open_orders_count ?? 0 }}</strong>
              </p>
              <p class="small mb-1">
                Comenzi în ultimele 30 zile:
                <strong>{{ dashboard.last_30_days_orders_count ?? 0 }}</strong>
              </p>
              <p class="small mb-0 text-muted">
                Valoare comandată (30 zile):
                <strong>
                  {{ formatPrice(dashboard.last_30_days_orders_total) }} RON
                </strong>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Comenzi în derulare -->
      <div class="card mb-4" v-if="(dashboard.open_orders || []).length">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 mb-0">Comenzi în derulare</h2>
            <RouterLink
              :to="{ name: 'account-orders' }"
              class="btn btn-link btn-sm text-decoration-none"
            >
              Vezi toate comenzile →
            </RouterLink>
          </div>

          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Data</th>
                  <th>Status</th>
                  <th>Valoare</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="order in dashboard.open_orders"
                  :key="order.id"
                >
                  <td>{{ order.number || order.id }}</td>
                  <td>{{ order.date || order.created_at }}</td>
                  <td>
                    <span class="badge bg-secondary">
                      {{ order.status_label || order.status }}
                    </span>
                  </td>
                  <td>
                    {{ formatPrice(order.total) }} RON
                  </td>
                  <td class="text-end">
                    <RouterLink
                      :to="{ name: 'account-order-details', params: { id: order.id } }"
                      class="btn btn-outline-secondary btn-sm"
                    >
                      Detalii
                    </RouterLink>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Produse frecvent comandate -->
      <div class="card mb-4" v-if="(dashboard.frequent_products || []).length">
        <div class="card-body">
          <h2 class="h6 mb-3">Produse frecvent comandate</h2>
          <div class="row g-3">
            <div
              v-for="product in dashboard.frequent_products"
              :key="product.id"
              class="col-md-3 col-sm-6"
            >
              <div class="card h-100">
                <div class="card-body d-flex flex-column">
                  <div class="small text-muted mb-1">
                    {{ product.category?.name || product.category || '—' }}
                  </div>
                  <h3 class="h6 mb-1">{{ product.name }}</h3>
                  <div class="small text-muted mb-2">
                    {{ product.internal_code || product.code }}
                  </div>
                  <div class="mt-auto">
                    <div class="fw-semibold mb-1">
                      {{ formatPrice(product.last_price ?? product.list_price) }} RON
                    </div>
                    <RouterLink
                      v-if="product.slug"
                      :to="`/produs/${product.slug}`"
                      class="btn btn-outline-primary btn-sm"
                    >
                      Detalii produs
                    </RouterLink>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Promoții / recomandări personalizate -->
      <div class="card" v-if="(dashboard.personalized_promotions || []).length">
        <div class="card-body">
          <h2 class="h6 mb-3">Promoții recomandate pentru tine</h2>
          <div class="row g-3">
            <div
              v-for="promo in dashboard.personalized_promotions"
              :key="promo.id"
              class="col-md-4"
            >
              <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                  <span class="badge bg-danger mb-2">Promoție</span>
                  <h3 class="h6 mb-1">
                    {{ promo.name || promo.title }}
                  </h3>
                  <p class="small text-muted mb-2">
                    {{ promo.short_description || promo.teaser }}
                  </p>
                  <p class="small mb-2" v-if="promo.start_at || promo.end_at">
                    {{ promo.start_at }} – {{ promo.end_at || 'nelimitat' }}
                  </p>
                  <div class="mt-auto">
                    <RouterLink
                      v-if="promo.slug"
                      :to="`/promotii/${promo.slug}`"
                      class="btn btn-outline-primary btn-sm"
                    >
                      Vezi promoția
                    </RouterLink>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dacă nu avem dashboard deloc și nici loading -->
    <div v-if="!loading && !dashboard && !error" class="alert alert-warning">
      Nu s-au putut încărca datele contului.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { fetchAccountDashboard } from '@/services/account';

const loading = ref(false);
const error = ref('');
const dashboard = ref(null);

const formatPrice = (value) => {
  if (value == null) return '-';
  const num = Number(value);
  if (Number.isNaN(num)) return String(value);
  return num.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const loadDashboard = async () => {
  loading.value = true;
  error.value = '';
  dashboard.value = null;

  try {
    const data = await fetchAccountDashboard();
    dashboard.value = data;
  } catch (e) {
    console.error('Account dashboard error', e);
    error.value =
      e.response?.data?.message ||
      'Nu s-au putut încărca datele dashboard-ului.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadDashboard();
});
</script>
