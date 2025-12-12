<template>
  <div>
    <h1 class="h4 mb-3">Comenzile mele</h1>

    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-2 align-items-end" @submit.prevent="reload">
          <div class="col-md-3">
            <label class="form-label small">Număr / ID comandă</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="#, ID sau text"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label small">Status comandă</label>
            <select v-model="filters.status" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="pending">În așteptare</option>
              <option value="processing">În lucru</option>
              <option value="shipped">Expediată</option>
              <option value="completed">Finalizată</option>
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
              <option value="unpaid">Neplătită</option>
              <option value="pending">Plată în așteptare</option>
              <option value="paid">Plătită</option>
              <option value="failed">Eșuată</option>
            </select>
          </div>
          <div class="col-md-3 text-md-end mt-2 mt-md-0">
            <button type="submit" class="btn btn-primary btn-sm me-2">
              Aplică filtre
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="resetFilters"
            >
              Resetează
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5 text-muted">
      Se încarcă comenzile...
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-else>
      <div v-if="orders.data && orders.data.length" class="table-responsive">
        <table class="table table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Număr</th>
              <th>Data</th>
              <th>Status</th>
              <th>Plată</th>
              <th class="text-end">Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders.data" :key="order.id">
              <td>#{{ order.id }}</td>
              <td>{{ order.order_number || `CMD-${order.id}` }}</td>
              <td>
                {{ formatDate(order.placed_at || order.created_at) }}
              </td>
              <td>
                <span class="badge bg-secondary">
                  {{ order.status || 'necunoscut' }}
                </span>
              </td>
              <td>
                <span
                  class="badge"
                  :class="paymentBadgeClass(order.payment_status)"
                >
                  {{ order.payment_status || 'necunoscut' }}
                </span>
              </td>
              <td class="text-end">
                {{ formatMoney(order.grand_total || 0) }}
              </td>
              <td class="text-end">
                <RouterLink
                  :to="{
                    name: 'account-order-details',
                    params: { id: order.id }
                  }"
                  class="btn btn-link btn-sm text-decoration-none"
                >
                  Detalii
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-else
        class="alert alert-info mb-0"
      >
        Nu ai încă nicio comandă înregistrată.
      </div>

      <!-- Paginare simplă -->
      <nav
        v-if="orders.meta && orders.meta.last_page > 1"
        class="mt-3"
      >
        <ul class="pagination pagination-sm mb-0">
          <li
            class="page-item"
            :class="{ disabled: !orders.links.prev }"
          >
            <button class="page-link" @click="changePage(orders.meta.current_page - 1)">
              «
            </button>
          </li>

          <li
            v-for="page in pages"
            :key="page"
            class="page-item"
            :class="{ active: page === orders.meta.current_page }"
          >
            <button class="page-link" @click="changePage(page)">
              {{ page }}
            </button>
          </li>

          <li
            class="page-item"
            :class="{ disabled: !orders.links.next }"
          >
            <button class="page-link" @click="changePage(orders.meta.current_page + 1)">
              »
            </button>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { fetchMyOrders } from '@/services/orders';

const loading = ref(false);
const error = ref('');

const orders = ref({
  data: [],
  meta: null,
  links: {},
});

const filters = ref({
  search: '',
  status: '',
  payment_status: '',
  page: 1,
});

const pages = computed(() => {
  if (!orders.value.meta) return [];
  const total = orders.value.meta.last_page;
  return Array.from({ length: total }, (_, i) => i + 1);
});

const formatDate = (value) => {
  if (!value) return '-';
  return new Date(value).toLocaleString('ro-RO');
};

const formatMoney = (value) => {
  return (Number(value) || 0).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const paymentBadgeClass = (status) => {
  switch (status) {
    case 'paid':
      return 'bg-success';
    case 'pending':
      return 'bg-warning text-dark';
    case 'failed':
      return 'bg-danger';
    default:
      return 'bg-secondary';
  }
};

const loadOrders = async () => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      page: filters.value.page,
      status: filters.value.status || undefined,
      payment_status: filters.value.payment_status || undefined,
      search: filters.value.search || undefined,
    };

    const response = await fetchMyOrders(params);

    // Laravel paginator: data, meta, links
    orders.value = {
      data: response.data ?? response.data,
      meta: response.meta ?? null,
      links: response.links ?? {},
    };
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca comenzile.';
  } finally {
    loading.value = false;
  }
};

const reload = () => {
  filters.value.page = 1;
  loadOrders();
};

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    payment_status: '',
    page: 1,
  };
  loadOrders();
};

const changePage = (page) => {
  if (!orders.value.meta) return;
  if (page < 1 || page > orders.value.meta.last_page) return;
  filters.value.page = page;
  loadOrders();
};

onMounted(loadOrders);
</script>
