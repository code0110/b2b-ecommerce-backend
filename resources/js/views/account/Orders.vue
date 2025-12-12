<template>
  <div class="container py-4">
    <h1 class="h4 mb-3">Comenzile mele</h1>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>
    <div v-if="loading" class="alert alert-info">
      Se încarcă comenzile...
    </div>

    <!-- Filtre simple -->
    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-3" @submit.prevent="reloadOrders">
          <div class="col-md-4">
            <label class="form-label small">Căutare</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="Număr comandă, referință..."
            />
          </div>
          <div class="col-md-3">
            <label class="form-label small">Status</label>
            <select
              v-model="filters.status"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="new">Nouă</option>
              <option value="processing">În procesare</option>
              <option value="shipped">Livrată</option>
              <option value="cancelled">Anulată</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Status plată</label>
            <select
              v-model="filters.payment_status"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="pending">În așteptare</option>
              <option value="paid">Plătită</option>
              <option value="failed">Eroare plată</option>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
              Aplică filtre
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="!loading && !orders.length" class="text-muted">
      Nu ai comenzi înregistrate.
    </div>

    <div class="card" v-else>
      <div class="card-body table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Data</th>
              <th>Status</th>
              <th>Status plată</th>
              <th>Valoare</th>
              <th>Metodă plată</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="order in orders"
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
                <span
                  class="badge"
                  :class="paymentBadgeClass(order.payment_status)"
                >
                  {{ paymentStatusLabel(order.payment_status) }}
                </span>
              </td>
              <td>{{ formatPrice(order.total) }} RON</td>
              <td class="small">
                {{ order.payment_method_label || order.payment_method || '—' }}
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

      <!-- Paginare -->
      <div
        v-if="meta.last_page > 1"
        class="card-footer d-flex justify-content-between align-items-center"
      >
        <div class="text-muted small">
          Pagina {{ meta.current_page }} din {{ meta.last_page }} –
          {{ meta.total }} comenzi
        </div>
        <div class="btn-group">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            :disabled="meta.current_page === 1"
            @click="changePage(meta.current_page - 1)"
          >
            « Anterioară
          </button>
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            :disabled="meta.current_page === meta.last_page"
            @click="changePage(meta.current_page + 1)"
          >
            Următoarea »
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { fetchAccountOrders } from '@/services/account';

const loading = ref(false);
const error = ref('');
const orders = ref([]);
const meta = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

const filters = ref({
  search: '',
  status: '',
  payment_status: '',
});

const formatPrice = (value) => {
  if (value == null) return '-';
  const num = Number(value);
  if (Number.isNaN(num)) return String(value);
  return num.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const paymentStatusLabel = (status) => {
  switch (status) {
    case 'pending':
      return 'În așteptare';
    case 'paid':
      return 'Plătită';
    case 'failed':
      return 'Eroare plată';
    case 'cancelled':
      return 'Anulată';
    default:
      return status || '—';
  }
};

const paymentBadgeClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-warning text-dark';
    case 'paid':
      return 'bg-success';
    case 'failed':
      return 'bg-danger';
    case 'cancelled':
      return 'bg-secondary';
    default:
      return 'bg-light text-muted';
  }
};

const loadOrders = async (page = 1) => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      page,
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
      payment_status: filters.value.payment_status || undefined,
    };

    const { items, meta: m } = await fetchAccountOrders(params);
    orders.value = items;
    meta.value = m;
  } catch (e) {
    console.error('Account orders error', e);
    error.value =
      e.response?.data?.message ||
      'Nu s-au putut încărca comenzile.';
  } finally {
    loading.value = false;
  }
};

const reloadOrders = () => {
  loadOrders(1);
};

const changePage = (page) => {
  loadOrders(page);
};

onMounted(() => {
  loadOrders(1);
});
</script>
