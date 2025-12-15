<template>
  <div class="container-fluid py-3" v-if="loaded">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">
          Comandă
          <span class="text-muted">#{{ order.order_number }}</span>
        </h1>
        <p class="text-muted small mb-0">
          Plasată {{ order.placed_at || order.created_at }}
          ·
          {{ customer ? customer.name : 'Client necunoscut' }}
          ({{ customer && customer.type ? customer.type.toUpperCase() : '—' }})
        </p>
      </div>
      <div class="d-flex gap-2">
        <RouterLink
          :to="{ name: 'admin-orders', query: $route.query }"
          class="btn btn-outline-secondary btn-sm"
        >
          ← Înapoi la listă
        </RouterLink>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-else class="row g-3">
      <!-- Stânga: status & client -->
      <div class="col-lg-4">
        <!-- Status comandă -->
        <div class="card mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <span class="small text-uppercase text-muted fw-semibold">Status comandă</span>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <div class="small text-muted mb-1">Status curent</div>
              <span :class="['badge', statusBadgeClass(order.status)]">
                {{ statusLabel(order.status) }}
              </span>
            </div>
            <div class="mb-3">
              <label class="form-label form-label-sm">Modifică status</label>
              <select
                v-model="statusForm.status"
                class="form-select form-select-sm mb-2"
              >
                <option value="pending">În așteptare</option>
                <option value="processing">În procesare</option>
                <option value="completed">Finalizată</option>
                <option value="cancelled">Anulată</option>
                <option value="awaiting_payment">Așteaptă plată</option>
                <option value="on_hold">On hold</option>
              </select>
              <input
                v-if="statusForm.status === 'cancelled'"
                v-model="statusForm.cancel_reason"
                type="text"
                class="form-control form-control-sm mb-2"
                placeholder="Motiv anulare (opțional)"
              >
              <button
                class="btn btn-primary btn-sm w-100"
                type="button"
                :disabled="statusLoading"
                @click="submitStatus"
              >
                <span
                  v-if="statusLoading"
                  class="spinner-border spinner-border-sm me-1"
                ></span>
                Salvează status
              </button>
            </div>
          </div>
        </div>

        <!-- Status plată -->
        <div class="card mb-3">
          <div class="card-header py-2">
            <span class="small text-uppercase text-muted fw-semibold">Status plată</span>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <div class="small text-muted mb-1">Status curent</div>
              <span :class="['badge', paymentStatusBadgeClass(order.payment_status)]">
                {{ paymentStatusLabel(order.payment_status) }}
              </span>
            </div>
            <div class="mb-3">
              <label class="form-label form-label-sm">Modifică status plată</label>
              <select
                v-model="paymentForm.payment_status"
                class="form-select form-select-sm mb-2"
              >
                <option value="pending">Neplătită</option>
                <option value="paid">Plătită</option>
                <option value="failed">Eșuată</option>
                <option value="refunded">Rambursată</option>
                <option value="partially_paid">Parțial plătită</option>
              </select>
              <input
                v-model="paymentForm.payment_method"
                type="text"
                class="form-control form-control-sm mb-2"
                placeholder="Metodă plată (card/OP/chs/bo/cec)"
              >
              <button
                class="btn btn-outline-primary btn-sm w-100"
                type="button"
                :disabled="paymentLoading"
                @click="submitPaymentStatus"
              >
                <span
                  v-if="paymentLoading"
                  class="spinner-border spinner-border-sm me-1"
                ></span>
                Salvează status plată
              </button>
            </div>
          </div>
        </div>

        <!-- Info client -->
        <div class="card">
          <div class="card-header py-2">
            <span class="small text-uppercase text-muted fw-semibold">Client</span>
          </div>
          <div class="card-body small">
            <div v-if="customer">
              <div class="fw-semibold mb-1">
                {{ customer.name }}
              </div>
              <div class="text-muted mb-1">
                {{ customer.email }} · {{ customer.phone || '—' }}
              </div>
              <div class="mb-1">
                Tip client:
                <span
                  class="badge"
                  :class="customer.type === 'b2b' ? 'bg-primary' : 'bg-secondary'"
                >
                  {{ customer.type ? customer.type.toUpperCase() : '' }}
                </span>
              </div>
              <div class="mb-1">
                Termen plată: <strong>{{ customer.payment_terms_days }} zile</strong>
              </div>
              <div>
                Credit:
                <strong>{{ formatMoney(customer.credit_limit) }} RON</strong>
                · Sold curent:
                <strong>{{ formatMoney(customer.current_balance) }} RON</strong>
              </div>
            </div>
            <div v-else class="text-muted">
              Client indisponibil (șters sau nealocat).
            </div>
          </div>
        </div>
      </div>

      <!-- Dreapta: rezumat, adrese și articole -->
      <div class="col-lg-8">
        <!-- Rezumat comandă -->
        <div class="card mb-3">
          <div class="card-body small">
            <div class="row">
              <div class="col-md-4 mb-2 mb-md-0">
                <div class="text-muted mb-1">Date comandă</div>
                <div>Nr. comandă: <strong>{{ order.order_number }}</strong></div>
                <div>Plasată: <strong>{{ order.placed_at || order.created_at }}</strong></div>
                <div v-if="order.cancelled_at">
                  Anulată: <strong>{{ order.cancelled_at }}</strong>
                </div>
                <div v-if="order.cancel_reason">
                  Motiv anulare: <strong>{{ order.cancel_reason }}</strong>
                </div>
              </div>
              <div class="col-md-5 mb-2 mb-md-0">
                <div class="text-muted mb-1">Adrese</div>
                <div class="d-flex gap-3">
                  <div class="flex-grow-1">
                    <div class="fw-semibold mb-1">Facturare</div>
                    <div v-if="billingAddress">
                      <div>{{ billingAddress.contact_name || (customer && customer.name) }}</div>
                      <div>{{ billingAddress.street }}</div>
                      <div>{{ billingAddress.postal_code }} {{ billingAddress.city }}</div>
                      <div>{{ billingAddress.county }}</div>
                      <div>{{ billingAddress.phone }}</div>
                    </div>
                    <div v-else class="text-muted">
                      —
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold mb-1">Livrare</div>
                    <div v-if="shippingAddress">
                      <div>{{ shippingAddress.contact_name || (customer && customer.name) }}</div>
                      <div>{{ shippingAddress.street }}</div>
                      <div>{{ shippingAddress.postal_code }} {{ shippingAddress.city }}</div>
                      <div>{{ shippingAddress.county }}</div>
                      <div>{{ shippingAddress.phone }}</div>
                    </div>
                    <div v-else class="text-muted">
                      —
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3 text-md-end">
                <div class="text-muted mb-1">Total comandă</div>
                <div class="fs-5 fw-semibold">
                  {{ formatMoney(order.grand_total) }} RON
                </div>
                <div class="small text-muted">
                  {{ order.total_items }} linii ·
                  TVA: {{ formatMoney(order.tax_total) }} RON
                </div>
                <div v-if="order.discount_total > 0" class="small text-success">
                  Discount: -{{ formatMoney(order.discount_total) }} RON
                </div>
                <div class="small">
                  Transport: {{ formatMoney(order.shipping_total) }} RON
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Articole comandă -->
        <div class="card">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <span class="small text-uppercase text-muted fw-semibold">
              Articole comandă ({{ items.length }})
            </span>
          </div>
          <div class="card-body p-0">
            <div v-if="items.length === 0" class="text-center py-4 text-muted small">
              Nu există linii de comandă.
            </div>
            <div v-else class="table-responsive">
              <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Produs</th>
                    <th style="width: 120px;">Cod</th>
                    <th style="width: 80px;" class="text-end">Cant.</th>
                    <th style="width: 110px;" class="text-end">Preț unitar</th>
                    <th style="width: 110px;" class="text-end">Discount</th>
                    <th style="width: 110px;" class="text-end">TVA</th>
                    <th style="width: 110px;" class="text-end">Total linie</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in items" :key="item.id">
                    <td>
                      <div class="fw-semibold">{{ item.product_name }}</div>
                    </td>
                    <td>
                      <span class="small text-muted">{{ item.sku }}</span>
                    </td>
                    <td class="text-end">
                      {{ item.quantity }}
                    </td>
                    <td class="text-end">
                      {{ formatMoney(item.unit_price) }}
                    </td>
                    <td class="text-end text-success">
                      {{ item.discount_amount > 0 ? '-' + formatMoney(item.discount_amount) : '—' }}
                    </td>
                    <td class="text-end">
                      {{ formatMoney(item.tax_amount) }}
                    </td>
                    <td class="text-end fw-semibold">
                      {{ formatMoney(item.total) }}
                    </td>
                  </tr>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <th colspan="6" class="text-end">Subtotal</th>
                    <th class="text-end">{{ formatMoney(order.subtotal) }}</th>
                  </tr>
                  <tr v-if="order.discount_total > 0">
                    <th colspan="6" class="text-end">Discount total</th>
                    <th class="text-end text-success">
                      -{{ formatMoney(order.discount_total) }}
                    </th>
                  </tr>
                  <tr>
                    <th colspan="6" class="text-end">TVA</th>
                    <th class="text-end">{{ formatMoney(order.tax_total) }}</th>
                  </tr>
                  <tr>
                    <th colspan="6" class="text-end">Transport</th>
                    <th class="text-end">{{ formatMoney(order.shipping_total) }}</th>
                  </tr>
                  <tr>
                    <th colspan="6" class="text-end">Total comandă</th>
                    <th class="text-end">{{ formatMoney(order.grand_total) }}</th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div v-else class="container-fluid py-3">
    <div class="text-center text-muted py-5">
      Se încarcă detaliile comenzii…
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import {
  fetchOrder,
  updateOrderStatus,
  updateOrderPaymentStatus,
} from '@/services/admin/orders';

const route = useRoute();

const loaded = ref(false);
const error = ref('');

const order = reactive({});
const customer = ref(null);
const billingAddress = ref(null);
const shippingAddress = ref(null);
const items = ref([]);

const statusForm = reactive({
  status: 'pending',
  cancel_reason: '',
});
const statusLoading = ref(false);

const paymentForm = reactive({
  payment_status: 'pending',
  payment_method: '',
});
const paymentLoading = ref(false);

const loadOrder = async () => {
  loaded.value = false;
  error.value = '';

  try {
    const id = route.params.id;
    const data = await fetchOrder(id);

    Object.assign(order, data.order || {});
    customer.value = data.customer || null;
    billingAddress.value = data.billing_address || null;
    shippingAddress.value = data.shipping_address || null;
    items.value = data.items || [];

    statusForm.status = order.status;
    statusForm.cancel_reason = order.cancel_reason || '';
    paymentForm.payment_status = order.payment_status;
    paymentForm.payment_method = order.payment_method || '';
  } catch (e) {
    console.error('Admin order details error', e);
    error.value = 'Nu s-au putut încărca detaliile comenzii.';
  } finally {
    loaded.value = true;
  }
};

const submitStatus = async () => {
  if (!order.id) return;
  statusLoading.value = true;
  error.value = '';

  try {
    const data = await updateOrderStatus(order.id, {
      status: statusForm.status,
      cancel_reason: statusForm.cancel_reason,
    });

    Object.assign(order, data.order || {});
  } catch (e) {
    console.error('Update status error', e);
    error.value = 'Nu s-a putut actualiza statusul comenzii.';
  } finally {
    statusLoading.value = false;
  }
};

const submitPaymentStatus = async () => {
  if (!order.id) return;
  paymentLoading.value = true;
  error.value = '';

  try {
    const data = await updateOrderPaymentStatus(order.id, {
      payment_status: paymentForm.payment_status,
      payment_method: paymentForm.payment_method,
    });

    Object.assign(order, data.order || {});
  } catch (e) {
    console.error('Update payment status error', e);
    error.value = 'Nu s-a putut actualiza statusul plății.';
  } finally {
    paymentLoading.value = false;
  }
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

onMounted(loadOrder);
</script>
