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
        </p>
      </div>
      <div class="d-flex gap-2">
        <RouterLink
          :to="{ name: 'account-orders', query: $route.query }"
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
            <div v-if="order.status === 'cancelled' && order.cancel_reason" class="mt-2">
               <div class="small text-muted mb-1">Motiv anulare</div>
               <div class="small">{{ order.cancel_reason }}</div>
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
            <div v-if="order.payment_method">
               <div class="small text-muted mb-1">Metodă plată</div>
               <div class="small">{{ order.payment_method }}</div>
            </div>
          </div>
        </div>

        <!-- Info client (Only if relevant for the user to see their own info, otherwise redundant but okay) -->
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
                      <div class="fw-semibold">{{ item.product_name || (item.product ? item.product.name : 'Produs necunoscut') }}</div>
                    </td>
                    <td>
                      <span class="small text-muted">{{ item.sku || (item.product ? item.product.sku : '') }}</span>
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
                    <th class="text-end text-danger">
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
import { fetchOrder } from '@/services/account/orders';

const route = useRoute();

const loaded = ref(false);
const error = ref('');

const order = reactive({});
const customer = ref(null);
const billingAddress = ref(null);
const shippingAddress = ref(null);
const items = ref([]);

const loadOrder = async () => {
  loaded.value = false;
  error.value = '';

  try {
    const id = route.params.id;
    const data = await fetchOrder(id);

    // The backend returns the order object directly (from AccountOrderController::show)
    // with relations (items, billingAddress, shippingAddress, etc.) already loaded.
    
    Object.assign(order, data);
    
    items.value = data.items || [];
    
    // Handle both snake_case (typical JSON) and camelCase (Laravel relations)
    billingAddress.value = data.billing_address || data.billingAddress || null;
    shippingAddress.value = data.shipping_address || data.shippingAddress || null;
    
    // For customer info
    customer.value = data.customer || null; 

  } catch (e) {
    console.error('Order details error', e);
    error.value = 'Nu s-au putut încărca detaliile comenzii.';
  } finally {
    loaded.value = true;
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

onMounted(loadOrder);
</script>
