<template>
  <div class="container py-4" v-if="!loading && order">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">
        Comanda #{{ order.number || order.id }}
      </h1>
      <RouterLink
        :to="{ name: 'account-orders' }"
        class="btn btn-outline-secondary btn-sm"
      >
        Înapoi la comenzi
      </RouterLink>
    </div>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <!-- Info principală -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h2 class="h6 mb-2">Detalii comandă</h2>
            <p class="small mb-1">
              Data: <strong>{{ order.date || order.created_at }}</strong>
            </p>
            <p class="small mb-1">
              Status:
              <span class="badge bg-secondary">
                {{ order.status_label || order.status }}
              </span>
            </p>
            <p class="small mb-1">
              Status plată:
              <span
                class="badge"
                :class="paymentBadgeClass(order.payment_status)"
              >
                {{ paymentStatusLabel(order.payment_status) }}
              </span>
            </p>
            <p class="small mb-1">
              Metodă plată:
              <strong>
                {{ order.payment_method_label || order.payment_method || '—' }}
              </strong>
            </p>
            <p class="small mb-0">
              Total:
              <strong>{{ formatPrice(order.total) }} RON</strong>
            </p>
          </div>
        </div>
      </div>

      <!-- Adrese -->
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h2 class="h6 mb-2">Adresă facturare</h2>
            <p class="small mb-0" v-if="order.billing_address">
              {{ order.billing_address.company_name }}<br />
              {{ order.billing_address.name }}<br />
              {{ order.billing_address.street }}<br />
              {{ order.billing_address.city }},
              {{ order.billing_address.county }}<br />
              {{ order.billing_address.zip }}<br />
              {{ order.billing_address.country }}
            </p>
            <p v-else class="small text-muted mb-0">
              Nu este setată adresă de facturare.
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h2 class="h6 mb-2">Adresă livrare</h2>
            <p class="small mb-0" v-if="order.shipping_address">
              {{ order.shipping_address.company_name }}<br />
              {{ order.shipping_address.name }}<br />
              {{ order.shipping_address.street }}<br />
              {{ order.shipping_address.city }},
              {{ order.shipping_address.county }}<br />
              {{ order.shipping_address.zip }}<br />
              {{ order.shipping_address.country }}
            </p>
            <p v-else class="small text-muted mb-0">
              Nu este setată adresă de livrare.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Linii comandă -->
    <div class="card mb-4">
      <div class="card-body table-responsive">
        <h2 class="h6 mb-3">Produse comandate</h2>
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Produs</th>
              <th>Cod</th>
              <th>Cantitate</th>
              <th>Preț unitar</th>
              <th>Discount</th>
              <th>Total linie</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="line in order.lines || []"
              :key="line.id"
            >
              <td>
                <div class="fw-semibold small">
                  {{ line.product_name }}
                </div>
                <div class="small text-muted">
                  {{ line.product_variant || '' }}
                </div>
              </td>
              <td class="small">
                {{ line.product_code }}
              </td>
              <td>{{ line.qty }}</td>
              <td>{{ formatPrice(line.unit_price) }} RON</td>
              <td>
                <span v-if="line.discount_amount || line.discount_percent" class="small">
                  <span v-if="line.discount_percent">
                    -{{ line.discount_percent }}%
                  </span>
                  <span v-if="line.discount_amount">
                    ({{ formatPrice(line.discount_amount) }} RON)
                  </span>
                </span>
                <span v-else class="small text-muted">—</span>
              </td>
              <td>{{ formatPrice(line.line_total) }} RON</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Totaluri -->
    <div class="card">
      <div class="card-body">
        <h2 class="h6 mb-3">Total comandă</h2>
        <div class="row">
          <div class="col-md-6">
            <table class="table table-sm mb-0">
              <tbody>
                <tr>
                  <th class="small">Subtotal</th>
                  <td class="text-end small">
                    {{ formatPrice(order.subtotal) }} RON
                  </td>
                </tr>
                <tr>
                  <th class="small">Discount</th>
                  <td class="text-end small">
                    -{{ formatPrice(order.discount_total) }} RON
                  </td>
                </tr>
                <tr>
                  <th class="small">Transport</th>
                  <td class="text-end small">
                    {{ formatPrice(order.shipping_total) }} RON
                  </td>
                </tr>
                <tr>
                  <th class="small">TVA</th>
                  <td class="text-end small">
                    {{ formatPrice(order.vat_total) }} RON
                  </td>
                </tr>
                <tr>
                  <th class="small">Total</th>
                  <td class="text-end small fw-semibold">
                    {{ formatPrice(order.total) }} RON
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <!-- acțiuni: comandă din nou, descarcă factură etc. -->
            <button type="button" class="btn btn-outline-primary btn-sm me-2">
              Comandă din nou
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm">
              Descarcă factură (PDF)
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading / 404 -->
  <div v-else-if="loading" class="container py-4">
    <div class="alert alert-info">
      Se încarcă detaliile comenzii...
    </div>
  </div>
  <div v-else class="container py-4">
    <div class="alert alert-warning">
      Comanda nu a fost găsită.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { fetchAccountOrder } from '@/services/account';

const route = useRoute();

const loading = ref(false);
const error = ref('');
const order = ref(null);

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

const loadOrder = async () => {
  loading.value = true;
  error.value = '';
  order.value = null;

  const id = route.params.id;

  try {
    const data = await fetchAccountOrder(id);
    order.value = data.order ?? data;
  } catch (e) {
    console.error('Account order error', e);
    if (e.response?.status === 404) {
      error.value = 'Comanda nu a fost găsită.';
    } else {
      error.value =
        e.response?.data?.message ||
        'Nu s-au putut încărca detaliile comenzii.';
    }
  } finally {
    loading.value = false;
  }
};

watch(
  () => route.params.id,
  () => {
    loadOrder();
  },
);

onMounted(() => {
  loadOrder();
});
</script>
