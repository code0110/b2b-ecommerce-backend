<template>
  <div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Comenzile mele</h1>
        <p class="small text-muted mb-0">
          Vezi istoricul comenzilor și plasează din nou comenzi frecvente.
        </p>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body small">
        <form class="row g-2" @submit.prevent="reload">
          <div class="col-md-3">
            <label class="form-label form-label-sm">Nr. comandă</label>
            <input
              v-model="filters.number"
              type="text"
              class="form-control form-control-sm"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Status</label>
            <select
              v-model="filters.status"
              class="form-select form-select-sm"
            >
              <option value="">Toate statusurile</option>
              <option value="new">Nouă</option>
              <option value="processing">În procesare</option>
              <option value="shipped">Expediată</option>
              <option value="completed">Finalizată</option>
              <option value="cancelled">Anulată</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">De la</label>
            <input
              v-model="filters.from"
              type="date"
              class="form-control form-control-sm"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Până la</label>
            <div class="input-group input-group-sm">
              <input
                v-model="filters.to"
                type="date"
                class="form-control form-control-sm"
              />
              <button class="btn btn-primary btn-sm" type="submit">
                Filtrează
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger small mb-3">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border spinner-border-sm" role="status" />
      <div class="small text-muted mt-2">
        Se încarcă comenzile...
      </div>
    </div>

    <div v-else>
      <div v-if="orders.length === 0" class="alert alert-info small">
        Nu ai comenzi pentru filtrarea selectată.
      </div>

      <div v-else class="card shadow-sm">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr class="small text-muted">
                <th>Nr. comandă</th>
                <th>Data</th>
                <th>Status</th>
                <th>Plată</th>
                <th class="text-end">Total</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in orders" :key="order.id">
                <td>{{ order.number }}</td>
                <td>{{ formatDate(order.created_at) }}</td>
                <td>
                  <span class="badge bg-light text-dark border">
                    {{ order.status_label || order.status }}
                  </span>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    {{ order.payment_status_label || order.payment_status }}
                  </span>
                </td>
                <td class="text-end">
                  {{ formatMoney(order.total) }}
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <RouterLink
                      :to="`/cont/comenzi/${order.id}`"
                      class="btn btn-outline-secondary btn-sm"
                    >
                      Detalii
                    </RouterLink>
                    <button
                      type="button"
                      class="btn btn-outline-primary btn-sm"
                      @click="handleReorder(order)"
                    >
                      Comandă din nou
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginare simplă -->
        <div
          v-if="meta.last_page > 1"
          class="card-footer py-2"
        >
          <nav class="d-flex justify-content-between align-items-center small">
            <span>
              Pagina {{ meta.current_page }} din {{ meta.last_page }}
            </span>
            <ul class="pagination pagination-sm mb-0">
              <li
                class="page-item"
                :class="{ disabled: meta.current_page <= 1 }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="changePage(meta.current_page - 1)"
                >
                  «
                </button>
              </li>
              <li
                class="page-item"
                v-for="page in pages"
                :key="page"
                :class="{ active: page === meta.current_page }"
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
                :class="{ disabled: meta.current_page >= meta.last_page }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="changePage(meta.current_page + 1)"
                >
                  »
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <div
      v-if="reorderMessage"
      class="alert alert-success alert-dismissible fade show small mt-3"
      role="alert"
    >
      {{ reorderMessage }}
      <button
        type="button"
        class="btn-close btn-close-white"
        aria-label="Close"
        @click="reorderMessage = ''"
      ></button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { fetchOrders, reorderOrder } from '@/services/account/orders';

const loading = ref(false);
const error = ref('');
const orders = ref([]);
const meta = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

const filters = ref({
  number: '',
  status: '',
  from: '',
  to: '',
});

const reorderMessage = ref('');

const pages = computed(() => {
  const last = meta.value.last_page || 1;
  const current = meta.value.current_page || 1;
  const items = [];
  const start = Math.max(1, current - 2);
  const end = Math.min(last, current + 2);
  for (let p = start; p <= end; p += 1) items.push(p);
  return items;
});

const formatMoney = (value) => {
  if (!value) value = 0;
  return Number(value).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const formatDate = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleDateString('ro-RO');
};

const loadOrders = async () => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      page: meta.value.current_page,
    };
    if (filters.value.number) params.number = filters.value.number;
    if (filters.value.status) params.status = filters.value.status;
    if (filters.value.from) params.from = filters.value.from;
    if (filters.value.to) params.to = filters.value.to;

    const data = await fetchOrders(params);
    const source = data.data ?? data;

    orders.value = source.data ?? source;
    meta.value = source.meta ?? data.meta ?? meta.value;
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut încărca lista de comenzi.';
  } finally {
    loading.value = false;
  }
};

const reload = () => {
  meta.value.current_page = 1;
  loadOrders();
};

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return;
  meta.value.current_page = page;
  loadOrders();
};

const handleReorder = async (order) => {
  try {
    await reorderOrder(order.id);
    reorderMessage.value =
      'Produsele din această comandă au fost adăugate în coș.';
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut re-plasa această comandă.';
  }
};

onMounted(loadOrders);
</script>
