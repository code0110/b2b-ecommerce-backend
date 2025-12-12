<template>
  <div class="account-orders py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Comenzile mele</h1>
        <p class="text-muted small mb-0">
          Vezi istoricul comenzilor plasate în platformă.
        </p>
      </div>
    </div>

    <!-- Filtre simple -->
    <div class="card mb-3">
      <div class="card-body py-2">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="small text-muted mb-1">Status comandă</label>
            <select
              v-model="filters.status"
              class="form-select form-select-sm"
              @change="reload"
            >
              <option value="">Toate</option>
              <option value="pending">În așteptare</option>
              <option value="processing">În procesare</option>
              <option value="completed">Finalizată</option>
              <option value="cancelled">Anulată</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="small text-muted mb-1">Status plată</label>
            <select
              v-model="filters.payment_status"
              class="form-select form-select-sm"
              @change="reload"
            >
              <option value="">Toate</option>
              <option value="pending">În așteptare</option>
              <option value="paid">Plătită</option>
              <option value="failed">Eșuată</option>
              <option value="cancelled">Anulată</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="small text-muted mb-1">Nr. comandă / referință</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="Caută în număr / referință"
              @keyup.enter="reload"
            />
          </div>
          <div class="col-md-3 d-flex justify-content-md-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="reload"
            >
              Aplică filtre
            </button>
            <button
              v-if="hasActiveFilters"
              type="button"
              class="btn btn-link btn-sm text-decoration-none"
              @click="resetFilters"
            >
              Resetează
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Conținut -->
    <div v-if="loading" class="text-muted small py-3">
      Se încarcă comenzile...
    </div>
    <div v-else-if="error" class="alert alert-danger small py-2">
      {{ error }}
    </div>

    <div v-else>
      <div v-if="orders.data.length" class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr class="small text-muted">
              <th>Nr. comandă</th>
              <th>Data</th>
              <th>Status</th>
              <th>Status plată</th>
              <th class="text-end">Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="order in orders.data"
              :key="order.id"
              class="small"
            >
              <td>
                {{ displayOrderNumber(order) }}
              </td>
              <td>
                {{ formatDate(order.created_at) }}
              </td>
              <td>
                <span class="badge bg-light text-dark">
                  {{ order.status || 'nedefinit' }}
                </span>
              </td>
              <td>
                <span
                  class="badge"
                  :class="paymentBadgeClass(order.payment_status)"
                >
                  {{ order.payment_status || 'nedefinit' }}
                </span>
              </td>
              <td class="text-end">
                {{ formatMoney(order.total || order.grand_total || 0) }} RON
              </td>
              <td class="text-end">
                <RouterLink
                  :to="{
                    name: 'account-order-details',
                    params: { id: order.id }
                  }"
                  class="btn btn-outline-primary btn-sm"
                >
                  Detalii
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="alert alert-light border small">
        Nu ai încă nicio comandă înregistrată.
      </div>

      <!-- Pagination -->
      <nav
        v-if="orders.meta && orders.meta.last_page > 1"
        class="mt-3"
      >
        <ul class="pagination pagination-sm mb-0">
          <li
            class="page-item"
            :class="{ disabled: orders.meta.current_page === 1 }"
          >
            <button
              class="page-link"
              type="button"
              @click="changePage(orders.meta.current_page - 1)"
            >
              «
            </button>
          </li>

          <li
            v-for="page in pages"
            :key="page"
            class="page-item"
            :class="{ active: page === orders.meta.current_page }"
          >
            <button
              class="page-link"
              type="button"
              @click="changePage(page)"
            >
              {{ page }}
            </button>
          </li>

          <li
            class="page-item"
            :class="{ disabled: orders.meta.current_page === orders.meta.last_page }"
          >
            <button
              class="page-link"
              type="button"
              @click="changePage(orders.meta.current_page + 1)"
            >
              »
            </button>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAuthStore } from '@/store/auth';
import { fetchAccountOrders } from '@/services/account';

const authStore = useAuthStore();

const loading = ref(false);
const error = ref('');

const orders = reactive({
  data: [],
  meta: null,
});

const filters = reactive({
  status: '',
  payment_status: '',
  search: '',
  page: 1,
});

const hasActiveFilters = computed(() => {
  return !!(filters.status || filters.payment_status || filters.search);
});

const pages = computed(() => {
  if (!orders.meta) return [];
  const total = orders.meta.last_page;
  const current = orders.meta.current_page;
  const arr = [];
  const start = Math.max(1, current - 2);
  const end = Math.min(total, current + 2);

  for (let i = start; i <= end; i += 1) {
    arr.push(i);
  }

  return arr;
});

const formatDate = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleString('ro-RO');
};

const formatMoney = (value) => {
  return (Number(value) || 0).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const displayOrderNumber = (order) => {
  return order.number || order.order_number || `#${order.id}`;
};

const paymentBadgeClass = (status) => {
  switch (status) {
    case 'paid':
      return 'bg-success';
    case 'failed':
      return 'bg-danger';
    case 'pending':
      return 'bg-warning text-dark';
    default:
      return 'bg-light text-dark';
  }
};

const loadOrders = async () => {
  const userId = authStore.user?.id;
  if (!userId) {
    error.value = 'Nu există un utilizator autentificat.';
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const data = await fetchAccountOrders(userId, {
      status: filters.status || undefined,
      payment_status: filters.payment_status || undefined,
      page: filters.page,
      // poți extinde cu search când implementezi filtrul în backend
    });

    orders.data = data.data || [];
    orders.meta = data.meta || null;
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca comenzile.';
  } finally {
    loading.value = false;
  }
};

const reload = () => {
  filters.page = 1;
  loadOrders();
};

const resetFilters = () => {
  filters.status = '';
  filters.payment_status = '';
  filters.search = '';
  filters.page = 1;
  loadOrders();
};

const changePage = (page) => {
  if (!orders.meta) return;
  if (page < 1 || page > orders.meta.last_page) return;
  filters.page = page;
  loadOrders();
};

onMounted(loadOrders);
</script>

<style scoped>
.account-orders {
  max-width: 1000px;
}
</style>
