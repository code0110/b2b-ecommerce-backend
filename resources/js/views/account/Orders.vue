<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Comenzile Mele</h1>
        <p class="text-muted small mb-0">
          Istoricul comenzilor dumneavoastră.
        </p>
      </div>
    </div>

    <!-- Filtre -->
    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
          <div class="col-sm-3">
            <label class="form-label form-label-sm">Nr. comandă</label>
            <input
              v-model="filters.order_number"
              type="text"
              class="form-control form-control-sm"
              placeholder="EX: ORD-2025-0001"
            >
          </div>

          <div class="col-sm-3">
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

          <div class="col-sm-3">
            <label class="form-label form-label-sm">Perioadă (Din - Până)</label>
            <div class="input-group input-group-sm">
                 <input v-model="filters.from_date" type="date" class="form-control form-control-sm">
                 <span class="input-group-text">-</span>
                 <input v-model="filters.to_date" type="date" class="form-control form-control-sm">
            </div>
          </div>

          <div class="col-sm-3 d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-link btn-sm text-decoration-none"
              @click="resetFilters"
            >
              Reset
            </button>
            <button type="submit" class="btn btn-orange btn-sm">
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
            Nu ați plasat nicio comandă care să corespundă filtrelor.
          </div>

          <div v-else class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width: 150px;">Nr. comandă</th>
                  <th style="width: 130px;">Status</th>
                  <th style="width: 130px;">Status plată</th>
                  <th style="width: 110px;" class="text-end">Total</th>
                  <th style="width: 150px;">Data</th>
                  <th style="width: 100px;"></th>
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
                      :to="{ name: 'account-order-details', params: { id: order.id } }"
                      class="text-decoration-none fw-semibold"
                    >
                      {{ order.order_number }}
                    </RouterLink>
                    <div class="small text-muted">
                      ID: {{ order.id }}
                    </div>
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
                      {{ order.created_at ? new Date(order.created_at).toLocaleDateString('ro-RO') : '-' }}
                    </div>
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
import { useAuthStore } from '@/store/auth';
import { useTrackingStore } from '@/store/tracking';
import { useToast } from 'vue-toastification';
import { fetchOrders } from '@/services/account/orders';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const trackingStore = useTrackingStore();
const toast = useToast();

const loading = ref(false);
const error = ref('');
const orders = ref([]);

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

const filters = reactive({
  order_number: route.query.order_number || '',
  status: route.query.status || '',
  from_date: route.query.from_date || '',
  to_date: route.query.to_date || '',
});

const loadOrders = async () => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      ...filters,
      page: pagination.current_page,
    };
    
    // Curățăm filtrele goale
    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === null) {
            delete params[key];
        }
    });

    const data = await fetchOrders(params);
    
    orders.value = data.data || [];
    pagination.current_page = data.current_page || 1;
    pagination.last_page = data.last_page || 1;
    pagination.per_page = data.per_page || 10;
    pagination.total = data.total || 0;

  } catch (e) {
    console.error('Orders load error', e);
    error.value = 'Nu s-au putut încărca comenzile.';
  } finally {
    loading.value = false;
  }
};

const applyFilters = () => {
  pagination.current_page = 1;
  router.replace({
    name: 'account-orders',
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
    status: '',
    from_date: '',
    to_date: '',
  });
  applyFilters();
};

const changePage = (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) {
    return;
  }
  pagination.current_page = page;
  router.replace({
    name: 'account-orders',
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
      return 'bg-orange text-white';
    case 'processing':
      return 'bg-dd-blue text-white';
    case 'completed':
      return 'bg-success text-white';
    case 'cancelled':
      return 'bg-danger text-white';
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
      return 'bg-orange text-white';
    case 'paid':
      return 'bg-success text-white';
    case 'failed':
      return 'bg-danger text-white';
    case 'refunded':
      return 'bg-dd-blue text-white';
    case 'partially_paid':
      return 'bg-dd-blue text-white';
    default:
      return 'bg-light text-dark';
  }
};

const formatMoney = (value) => {
  const num = Number(value) || 0;
  return num.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

onMounted(async () => {
  // Verificare shift agent (dacă e cazul)
  if (['sales_agent', 'sales_director'].includes(authStore.role)) {
      if (!trackingStore.isShiftActive) {
          // Nu forțăm checkStatus aici dacă a fost deja făcut în layout, 
          // dar pentru siguranță e ok.
          await trackingStore.checkStatus();
          // NOTĂ: Dacă agentul vrea să vadă comenzile PROPRII (ca și client), 
          // nu ar trebui să fie restricționat de shift. 
          // Dar dacă vede comenzile clienților (impersonate), e ok.
      }
  }

  pagination.current_page = Number(route.query.page || 1);
  loadOrders();
});
</script>
