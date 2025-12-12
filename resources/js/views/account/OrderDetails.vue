<template>
  <div class="account-order-details py-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div>
        <h1 class="h5 mb-1">
          Comanda {{ displayOrderNumber(order) }}
        </h1>
        <p class="text-muted small mb-0">
          Plasată la {{ formatDate(order.created_at) }}
        </p>
      </div>
      <div>
        <RouterLink
          :to="{ name: 'account-orders' }"
          class="btn btn-outline-secondary btn-sm"
        >
          Înapoi la comenzi
        </RouterLink>
      </div>
    </div>

    <div v-if="loading" class="text-muted small py-3">
      Se încarcă detaliile comenzii...
    </div>
    <div v-else-if="error" class="alert alert-danger small py-2">
      {{ error }}
    </div>
    <div v-else-if="!order || !order.id" class="alert alert-light border small">
      Comanda nu a fost găsită.
    </div>
    <div v-else class="row g-3">
      <!-- Col stânga: info comandă -->
      <div class="col-lg-8">
        <div class="card mb-3">
          <div class="card-header small fw-semibold">
            Produse comandate
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm mb-0 align-middle">
                <thead class="small text-muted">
                  <tr>
                    <th>Produs</th>
                    <th class="text-center">Cantitate</th>
                    <th class="text-end">Preț unitar</th>
                    <th class="text-end">Total linie</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="item in order.items || []"
                    :key="item.id"
                    class="small"
                  >
                    <td>
                      <div class="fw-semibold">
                        {{ item.product?.name || item.name }}
                      </div>
                      <div class="text-muted">
                        {{ item.product?.code || item.sku || '' }}
                      </div>
                    </td>
                    <td class="text-center">
                      {{ item.quantity }}
                    </td>
                    <td class="text-end">
                      {{ formatMoney(item.unit_price || item.price || 0) }} RON
                    </td>
                    <td class="text-end">
                      {{ formatMoney(item.line_total || item.total || 0) }} RON
                    </td>
                  </tr>

                  <tr v-if="!(order.items || []).length">
                    <td colspan="4" class="text-center text-muted small py-3">
                      Nu există linii de comandă înregistrate.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Informații suplimentare (opțional) -->
        <div class="row g-3">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header small fw-semibold">
                Facturi
              </div>
              <div class="card-body small">
                <div v-if="order.invoices && order.invoices.length">
                  <div
                    v-for="inv in order.invoices"
                    :key="inv.id"
                    class="d-flex justify-content-between align-items-center mb-1"
                  >
                    <div>
                      <div class="fw-semibold">
                        {{ inv.number || `Factura #${inv.id}` }}
                      </div>
                      <div class="text-muted">
                        {{ formatDate(inv.issue_date || inv.created_at) }}
                      </div>
                    </div>
                    <div class="text-end">
                      <div>{{ formatMoney(inv.total || 0) }} RON</div>
                    </div>
                  </div>
                </div>
                <div v-else class="text-muted">
                  Nu există facturi generate pentru această comandă.
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card">
              <div class="card-header small fw-semibold">
                Livrări / AWB
              </div>
              <div class="card-body small">
                <div v-if="order.shipments && order.shipments.length">
                  <div
                    v-for="sh in order.shipments"
                    :key="sh.id"
                    class="mb-1"
                  >
                    <div class="fw-semibold">
                      {{ sh.tracking_number || `AWB #${sh.id}` }}
                    </div>
                    <div class="text-muted">
                      Curier: {{ sh.courier_name || 'Nespecificat' }}<br />
                      Status: {{ sh.status || 'nedefinit' }}
                    </div>
                  </div>
                </div>
                <div v-else class="text-muted">
                  Nu există încă livrări înregistrate pentru această comandă.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Col dreapta: sumar comandă -->
      <div class="col-lg-4">
        <div class="card mb-3">
          <div class="card-header small fw-semibold">
            Sumar comandă
          </div>
          <div class="card-body small">
            <div class="d-flex justify-content-between mb-1">
              <span>Subtotal produse</span>
              <span>{{ formatMoney(order.subtotal || 0) }} RON</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span>Transport</span>
              <span>{{ formatMoney(order.shipping_total || 0) }} RON</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span>Discount</span>
              <span>-{{ formatMoney(order.discount_total || 0) }} RON</span>
            </div>
            <hr />
            <div class="d-flex justify-content-between fw-semibold mb-1">
              <span>Total</span>
              <span>{{ formatMoney(order.total || order.grand_total || 0) }} RON</span>
            </div>
            <div class="text-muted">
              Metodă plată:
              {{ order.payment_method || 'Nespecificată' }}
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header small fw-semibold">
            Statusuri
          </div>
          <div class="card-body small">
            <div class="mb-2">
              <div class="text-muted mb-1">Status comandă</div>
              <span class="badge bg-light text-dark">
                {{ order.status || 'nedefinit' }}
              </span>
            </div>
            <div class="mb-2">
              <div class="text-muted mb-1">Status plată</div>
              <span
                class="badge"
                :class="paymentBadgeClass(order.payment_status)"
              >
                {{ order.payment_status || 'nedefinit' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/store/auth';
import { fetchAccountOrderDetails } from '@/services/account';

const route = useRoute();
const authStore = useAuthStore();

const loading = ref(false);
const error = ref('');
const order = ref(null);

const formatDate = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleString('ro-RO');
};

const formatMoney = (value) =>
  (Number(value) || 0).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

const displayOrderNumber = (o) => {
  if (!o) return '';
  return o.number || o.order_number || `#${o.id}`;
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

const loadOrder = async () => {
  const userId = authStore.user?.id;
  const id = route.params.id;

  if (!userId) {
    error.value = 'Nu există un utilizator autentificat.';
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const data = await fetchAccountOrderDetails(userId, id);
    order.value = data;
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca detaliile comenzii.';
  } finally {
    loading.value = false;
  }
};

onMounted(loadOrder);
</script>

<style scoped>
.account-order-details {
  max-width: 1100px;
}
</style>
