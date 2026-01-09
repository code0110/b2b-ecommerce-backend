<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Derogări</h1>
        <p class="text-muted small mb-0">
          Gestionare cereri de derogare și istoric aprobări.
        </p>
      </div>
      <div>
        <button class="btn btn-outline-secondary btn-sm" @click="reload" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="bi bi-arrow-clockwise me-1"></i>
          Actualizează
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3">
      <li class="nav-item">
        <a 
          class="nav-link" 
          :class="{ active: activeTab === 'pending' }" 
          href="#" 
          @click.prevent="switchTab('pending')"
        >
          De Aprobat
          <span v-if="pendingCount > 0" class="badge bg-danger ms-1">{{ pendingCount }}</span>
        </a>
      </li>
      <li class="nav-item">
        <a 
          class="nav-link" 
          :class="{ active: activeTab === 'approved' }" 
          href="#" 
          @click.prevent="switchTab('approved')"
        >
          Istoric Aprobate
        </a>
      </li>
      <li class="nav-item">
        <a 
          class="nav-link" 
          :class="{ active: activeTab === 'rejected' }" 
          href="#" 
          @click.prevent="switchTab('rejected')"
        >
          Istoric Respinse
        </a>
      </li>
    </ul>

    <div class="card">
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-4 text-muted">
          Se încarcă lista...
        </div>

        <div v-else-if="error" class="alert alert-danger m-3">
          {{ error }}
        </div>

        <div v-else>
          <div v-if="orders.length === 0" class="text-center py-4 text-muted">
            <span v-if="activeTab === 'pending'">Nu există cereri de derogare în așteptare.</span>
            <span v-else>Nu există comenzi în această listă.</span>
          </div>

          <div v-else class="vstack gap-2 px-3 py-2">
            <div
              v-for="order in orders"
              :key="order.id"
              class="border rounded p-2"
            >
              <div class="d-flex justify-content-between align-items-start">
                <div class="me-3">
                  <div class="small text-muted">#{{ order.id }}</div>
                  <div class="fw-semibold">{{ order.order_number || '-' }}</div>
                  <div class="text-muted small">
                    {{ order.customer?.name || '—' }} · {{ order.customer?.cif || order.customer?.email || '' }}
                  </div>
                </div>
                <div class="text-end">
                  <div class="small text-muted">Total</div>
                  <div class="fw-semibold">{{ formatMoney(order.grand_total) }} RON</div>
                </div>
              </div>
              <div class="d-flex justify-content-between mt-2 align-items-center">
                <div class="small text-muted">
                  {{ order.created_at ? new Date(order.created_at).toLocaleDateString('ro-RO') : '-' }}
                </div>
                <div>
                  <span v-if="order.approval_status === 'approved'" class="badge bg-success">Aprobată</span>
                  <span v-else-if="order.approval_status === 'rejected'" class="badge bg-danger">Respinsă</span>
                  <span v-else-if="order.approval_status === 'pending'" class="badge bg-warning text-dark">Necesită Aprobare</span>
                  <span v-else class="badge bg-secondary">{{ order.approval_status || 'N/A' }}</span>
                </div>
              </div>
              <div class="mt-2 d-flex justify-content-between align-items-center">
                <div v-if="order.approval_status === 'pending'" class="d-flex gap-2">
                  <button 
                    class="btn btn-sm btn-outline-success"
                    :disabled="processingId === order.id"
                    @click="approve(order)"
                  >
                    <span v-if="processingId === order.id" class="spinner-border spinner-border-sm me-1"></span>
                    Aprobă
                  </button>
                  <button 
                    class="btn btn-sm btn-outline-danger"
                    :disabled="processingId === order.id"
                    @click="reject(order)"
                  >
                    <span v-if="processingId === order.id" class="spinner-border spinner-border-sm me-1"></span>
                    Respinge
                  </button>
                </div>
                <RouterLink 
                  :to="{ name: 'account-order-details', params: { id: order.id } }"
                  class="btn btn-sm btn-outline-secondary"
                >
                  Detalii
                </RouterLink>
              </div>
            </div>
          </div>

          <nav
            v-if="pagination.last_page > 1"
            class="mt-2 d-flex justify-content-between align-items-center px-3 pb-2 pt-2 border-top small"
          >
            <div>
              Pagina {{ pagination.current_page }} din {{ pagination.last_page }}
              · {{ pagination.total }} rezultate
            </div>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button class="page-link" type="button" @click="changePage(pagination.current_page - 1)">
                  «
                </button>
              </li>
              <li
                v-for="page in pagination.last_page"
                :key="page"
                class="page-item"
                :class="{ active: page === pagination.current_page }"
              >
                <button class="page-link" type="button" @click="changePage(page)">
                  {{ page }}
                </button>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <button class="page-link" type="button" @click="changePage(pagination.current_page + 1)">
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
import { onMounted, reactive, ref, watch } from 'vue';
import { adminApi } from '@/services/http';

const loading = ref(false);
const error = ref('');
const orders = ref([]);
const activeTab = ref('pending'); // Default to pending actions
const pendingCount = ref(0);
const processingId = ref(null);

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
});

const switchTab = (tab) => {
  activeTab.value = tab;
  pagination.current_page = 1;
  loadOrders();
};

const loadOrders = async () => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await adminApi.get('/orders', {
      params: {
        approval_status: activeTab.value,
        page: pagination.current_page,
        per_page: pagination.per_page,
      },
    });
    orders.value = data.data || [];
    pagination.current_page = data.current_page || 1;
    pagination.last_page = data.last_page || 1;
    pagination.per_page = data.per_page || 20;
    pagination.total = data.total || 0;
  } catch (e) {
    console.error('Orders load error', e);
    error.value = e.response?.data?.message || 'Nu s-au putut încărca comenzile.';
  } finally {
    loading.value = false;
  }
};

const checkPendingCount = async () => {
  try {
    const { data } = await adminApi.get('/orders', {
      params: { approval_status: 'pending', per_page: 1 }
    });
    pendingCount.value = data.total || 0;
  } catch (e) {
    // Silent fail
  }
};

const changePage = (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return;
  pagination.current_page = page;
  loadOrders();
};

const reload = () => {
  loadOrders();
  checkPendingCount();
};

const formatMoney = (value) => {
  const num = Number(value) || 0;
  return num.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const approve = async (order) => {
  if (!order || order.approval_status !== 'pending') return;
  if (!confirm('Confirmați aprobarea acestei comenzi?')) return;
  processingId.value = order.id;
  try {
    await adminApi.post(`/orders/${order.id}/approve`);
    await reload();
  } catch (e) {
    alert('Eroare la aprobare: ' + (e.response?.data?.message || e.message));
  } finally {
    processingId.value = null;
  }
};

const reject = async (order) => {
  if (!order || order.approval_status !== 'pending') return;
  if (!confirm('Confirmați respingerea? Comanda va fi anulată.')) return;
  processingId.value = order.id;
  try {
    await adminApi.post(`/orders/${order.id}/reject`);
    await reload();
  } catch (e) {
    alert('Eroare la respingere: ' + (e.response?.data?.message || e.message));
  } finally {
    processingId.value = null;
  }
};

onMounted(() => {
  loadOrders();
  checkPendingCount();
});
</script>
