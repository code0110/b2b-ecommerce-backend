<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Comenzi</h1>
        <p class="text-muted small mb-0">
          Management complet al comenzilor B2B/B2C: filtrare, status, plată și detalii.
        </p>
      </div>
    </div>

    <!-- Filtre -->
    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
          <div class="col-sm-2">
            <label class="form-label form-label-sm">Nr. comandă</label>
            <input
              v-model="filters.order_number"
              type="text"
              class="form-control form-control-sm"
              placeholder="EX: ORD-2025-0001"
            >
          </div>

          <div class="col-sm-2">
            <label class="form-label form-label-sm">Client</label>
            <input
              v-model="filters.customer"
              type="text"
              class="form-control form-control-sm"
              placeholder="Nume / email"
            >
          </div>

          <div class="col-sm-2">
            <label class="form-label form-label-sm">Tip client</label>
            <select v-model="filters.type" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="b2c">B2C</option>
              <option value="b2b">B2B</option>
            </select>
          </div>

          <div class="col-sm-2">
            <label class="form-label form-label-sm">Status comandă</label>
            <select v-model="filters.status" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="pending">În așteptare</option>
              <option value="processing">În procesare</option>
              <option value="completed">Finalizată</option>
              <option value="cancelled">Anulată</option>
              <option value="awaiting_payment">Așteaptă plată</option>
              <option value="on_hold">On hold</option>
            </select>
          </div>

          <div class="col-sm-2">
            <label class="form-label form-label-sm">Status plată</label>
            <select v-model="filters.payment_status" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="pending">Neplătită</option>
              <option value="paid">Plătită</option>
              <option value="failed">Eșuată</option>
              <option value="refunded">Rambursată</option>
              <option value="partially_paid">Parțial plătită</option>
            </select>
          </div>

          <div class="col-sm-2">
            <label class="form-label form-label-sm">Din data</label>
            <input v-model="filters.from_date" type="date" class="form-control form-control-sm">
          </div>

          <div class="col-sm-2">
            <label class="form-label form-label-sm">Până la</label>
            <input v-model="filters.to_date" type="date" class="form-control form-control-sm">
          </div>

          <div class="col-sm-2">
            <label class="form-label form-label-sm">Blocaj credit</label>
            <select v-model="filters.credit_blocked" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="1">Blocate</option>
              <option value="0">Fără blocaj</option>
            </select>
          </div>

          <div class="col-sm-3 ms-auto d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-link btn-sm text-decoration-none"
              @click="resetFilters"
            >
              Reset
            </button>
            <button type="submit" class="btn btn-primary btn-sm">
              Aplică filtre
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Listă comenzi -->
    <div class="card">
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-4 text-muted">
          Se încarcă comenzile…
        </div>

        <div v-else-if="error" class="alert alert-danger m-3">
          {{ error }}
        </div>

        <div v-else>
          <div v-if="orders.length === 0" class="text-center py-4 text-muted">
            Nu există comenzi pentru filtrele selectate.
          </div>

          <div v-else class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width: 130px;">Nr. comandă</th>
                  <th>Client</th>
                  <th style="width: 90px;">Tip</th>
                  <th style="width: 130px;">Status</th>
                  <th style="width: 130px;">Status plată</th>
                  <th style="width: 110px;" class="text-end">Total</th>
                  <th style="width: 150px;">Data</th>
                  <th style="width: 50px;"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="order in orders"
                  :key="order.id"
                  :class="{ 'table-warning': order.credit_blocked }"
                >
                  <td>
                    <RouterLink
                      :to="{ name: 'admin-order-details', params: { id: order.id } }"
                      class="text-decoration-none fw-semibold"
                    >
                      {{ order.order_number }}
                    </RouterLink>
                    <div class="small text-muted">
                      ID: {{ order.id }}
                    </div>
                  </td>
                  <td>
                    <div class="fw-semibold">
                      {{ order.customer && order.customer.name ? order.customer.name : '—' }}
                    </div>
                    <div class="small text-muted">
                      {{ order.customer && order.customer.email ? order.customer.email : '' }}
                    </div>
                  </td>
                  <td>
                    <span
                      class="badge"
                      :class="order.type === 'b2b' ? 'bg-primary' : 'bg-secondary'"
                    >
                      {{ (order.type || '').toUpperCase() }}
                    </span>
                  </td>
                  <td>
                    <span :class="['badge', statusBadgeClass(order.status)]">
                      {{ statusLabel(order.status) }}
                    </span>
                  </td>
                  <td>
                    <span :class="['badge', paymentStatusBadgeClass(order.payment_status)]">
                      {{ paymentStatusLabel(order.payment_status) }}
                    </span>
                  </td>
                  <td class="text-end">
                    {{ formatMoney(order.grand_total) }} RON
                  </td>
                  <td>
                    <div class="small">
                      Plasată:
                      <strong>{{ order.placed_at || order.created_at }}</strong>
                    </div>
                  </td>
                  <td class="text-end">
                    <RouterLink
                      :to="{ name: 'admin-order-details', params: { id: order.id } }"
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
          <nav
            v-if="pagination.last_page > 1"
            class="mt-2 d-flex justify-content-between align-items-center px-3 pb-2 pt-2 border-top small"
          >
            <div>
              Pagina {{ pagination.current_page }} din {{ pagination.last_page }}
              · {{ pagination.total }} comenzi
            </div>
            <ul class="pagination pagination-sm mb-0">
              <li
                class="page-item"
                :class="{ disabled: pagination.current_page === 1 }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="changePage(pagination.current_page - 1)"
                >
                  «
                </button>
              </li>
              <li
                v-for="page in pagination.last_page"
                :key="page"
                class="page-item"
                :class="{ active: page === pagination.current_page }"
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
                :class="{ disabled: pagination.current_page === pagination.last_page }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="changePage(pagination.current_page + 1)"
                >
                  »
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchOrders } from '@/services/admin/orders';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const error = ref('');
const orders = ref([]);

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
});

const filters = reactive({
  order_number: route.query.order_number || '',
  customer: route.query.customer || '',
  type: route.query.type || '',
  status: route.query.status || '',
  payment_status: route.query.payment_status || '',
  from_date: route.query.from_date || '',
  to_date: route.query.to_date || '',
  credit_blocked: route.query.credit_blocked || '',
});

const loadOrders = async () => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      ...filters,
      page: pagination.current_page,
    };
    const data = await fetchOrders(params);

    orders.value = data.data || data.items || [];
    pagination.current_page = data.current_page ?? data.meta?.current_page ?? 1;
    pagination.last_page = data.last_page ?? data.meta?.last_page ?? 1;
    pagination.per_page = data.per_page ?? data.meta?.per_page ?? orders.value.length;
    pagination.total = data.total ?? data.meta?.total ?? orders.value.length;
  } catch (e) {
    console.error('Admin orders load error', e);
    error.value = 'Nu s-au putut încărca comenzile.';
  } finally {
    loading.value = false;
  }
};

const applyFilters = () => {
  pagination.current_page = 1;
  router.replace({
    name: 'admin-orders',
    query: {
      ...filters,
      page: 1,
    },
  });
  loadOrders();
};

const resetFilters = () => {
  Object.assign(filters, {
    order_number: '',
    customer: '',
    type: '',
    status: '',
    payment_status: '',
    from_date: '',
    to_date: '',
    credit_blocked: '',
  });
  applyFilters();
};

const changePage = (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) {
    return;
  }
  pagination.current_page = page;
  router.replace({
    name: 'admin-orders',
    query: {
      ...filters,
      page,
    },
  });
  loadOrders();
};

const statusLabel = (status) => {
  switch (status) {
    case 'pending':
      return 'În așteptare';
    case 'processing':
      return 'În procesare';
    case 'completed':
      return 'Finalizată';
    case 'cancelled':
      return 'Anulată';
    case 'awaiting_payment':
      return 'Așteaptă plată';
    case 'on_hold':
      return 'On hold';
    default:
      return status || '—';
  }
};

const statusBadgeClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-warning text-dark';
    case 'processing':
      return 'bg-info text-dark';
    case 'completed':
      return 'bg-success';
    case 'cancelled':
      return 'bg-danger';
    case 'awaiting_payment':
      return 'bg-secondary';
    case 'on_hold':
      return 'bg-dark';
    default:
      return 'bg-light text-dark';
  }
};

const paymentStatusLabel = (status) => {
  switch (status) {
    case 'pending':
      return 'Neplătită';
    case 'paid':
      return 'Plătită';
    case 'failed':
      return 'Eșuată';
    case 'refunded':
      return 'Rambursată';
    case 'partially_paid':
      return 'Parțial plătită';
    default:
      return status || '—';
  }
};

const paymentStatusBadgeClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-warning text-dark';
    case 'paid':
      return 'bg-success';
    case 'failed':
      return 'bg-danger';
    case 'refunded':
      return 'bg-info text-dark';
    case 'partially_paid':
      return 'bg-primary';
    default:
      return 'bg-light text-dark';
  }
};

const formatMoney = (value) => {
  const num = Number(value) || 0;
  return num.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

onMounted(() => {
  pagination.current_page = Number(route.query.page || 1);
  loadOrders();
});
</script>
