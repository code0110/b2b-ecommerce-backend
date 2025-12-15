<template>
  <div class="container py-4">
    <div class="mb-3">
      <RouterLink
        to="/cont/comenzi"
        class="btn btn-outline-secondary btn-sm"
      >
        ← Înapoi la comenzi
      </RouterLink>
    </div>

    <div v-if="error" class="alert alert-danger small mb-3">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border spinner-border-sm" role="status" />
      <div class="small text-muted mt-2">Se încarcă detaliile comenzii...</div>
    </div>

    <div v-else-if="order" class="row g-3">
      <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
          <div class="card-body small">
            <div class="d-flex justify-content-between">
              <div>
                <h1 class="h5 mb-1">
                  Comanda #{{ order.number }}
                </h1>
                <div class="text-muted">
                  Plasată la {{ formatDateTime(order.created_at) }}
                </div>
              </div>
              <div class="text-end">
                <div>
                  <span class="badge bg-light text-dark border">
                    {{ order.status_label || order.status }}
                  </span>
                </div>
                <div class="mt-1">
                  <span class="badge bg-light text-dark border">
                    Plată: {{ order.payment_status_label || order.payment_status }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Linii comandă -->
        <div class="card shadow-sm">
          <div class="card-body small">
            <h2 class="h6 mb-3">Produse comandate</h2>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr class="text-muted">
                    <th>Produs</th>
                    <th>Cod</th>
                    <th class="text-end">Cantitate</th>
                    <th class="text-end">Preț unitar</th>
                    <th class="text-end">Total linie</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="line in order.lines" :key="line.id">
                    <td>
                      <RouterLink
                        v-if="line.product_slug"
                        :to="`/produs/${line.product_slug}`"
                        class="text-decoration-none"
                      >
                        {{ line.product_name }}
                      </RouterLink>
                      <span v-else>{{ line.product_name }}</span>
                    </td>
                    <td>{{ line.product_code }}</td>
                    <td class="text-end">
                      {{ line.quantity }}
                      <span v-if="line.unit" class="text-muted">
                        {{ line.unit }}
                      </span>
                    </td>
                    <td class="text-end">
                      {{ formatMoney(line.unit_price) }}
                    </td>
                    <td class="text-end">
                      {{ formatMoney(line.line_total) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="d-flex justify-content-between mt-3">
              <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                @click="handleReorder"
              >
                Comandă din nou
              </button>
              <div class="text-end">
                <div>
                  Subtotal:
                  <strong>{{ formatMoney(order.subtotal) }}</strong>
                </div>
                <div v-if="order.discount_total && order.discount_total > 0">
                  Discount:
                  <strong>-{{ formatMoney(order.discount_total) }}</strong>
                </div>
                <div>
                  Transport:
                  <strong>{{ formatMoney(order.shipping_total) }}</strong>
                </div>
                <div class="h6 mt-1 mb-0">
                  Total:
                  <strong>{{ formatMoney(order.total) }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Facturi asociate -->
        <div v-if="order.invoices && order.invoices.length" class="card shadow-sm mt-3">
          <div class="card-body small">
            <h2 class="h6 mb-2">Documente asociate</h2>
            <ul class="list-unstyled mb-0">
              <li
                v-for="inv in order.invoices"
                :key="inv.id"
                class="d-flex justify-content-between align-items-center mb-1"
              >
                <div>
                  {{ inv.number }} – {{ formatDate(inv.issue_date) }}
                  <span class="text-muted">
                    ({{ formatMoney(inv.total) }})
                  </span>
                </div>
                <div>
                  <a
                    v-if="inv.pdf_url"
                    :href="inv.pdf_url"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-outline-secondary btn-sm"
                  >
                    Descarcă PDF
                  </a>
                </div>
              </li>
            </ul>
          </div>
        </div>

      </div>

      <!-- Col dreapta: adrese & info plată/livrare -->
      <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
          <div class="card-body small">
            <h2 class="h6 mb-2">Livrare</h2>
            <p class="mb-1">
              <strong>Metodă:</strong>
              {{ order.shipping_method_label || order.shipping_method }}
            </p>
            <p class="mb-1">
              <strong>Adresă livrare:</strong><br />
              {{ formatAddress(order.shipping_address) }}
            </p>
            <p class="mb-0" v-if="order.tracking_number">
              <strong>AWB:</strong> {{ order.tracking_number }}
            </p>
          </div>
        </div>

        <div class="card shadow-sm mb-3">
          <div class="card-body small">
            <h2 class="h6 mb-2">Facturare & plată</h2>
            <p class="mb-1">
              <strong>Metodă plată:</strong>
              {{ order.payment_method_label || order.payment_method }}
            </p>
            <p class="mb-1">
              <strong>Adresă facturare:</strong><br />
              {{ formatAddress(order.billing_address) }}
            </p>
            <p class="mb-0" v-if="order.due_date">
              <strong>Scadență:</strong> {{ formatDate(order.due_date) }}
            </p>
          </div>
        </div>

        <div v-if="order.customer_note" class="card shadow-sm">
          <div class="card-body small">
            <h2 class="h6 mb-2">Observații client</h2>
            <p class="mb-0">
              {{ order.customer_note }}
            </p>
          </div>
        </div>

      </div>
    </div>

    <div v-else-if="!loading" class="alert alert-warning small">
      Comanda nu a fost găsită.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { fetchOrder, reorderOrder } from '@/services/account/orders';

const route = useRoute();

const order = ref(null);
const loading = ref(false);
const error = ref('');

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

const formatDateTime = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleString('ro-RO');
};

const formatAddress = (addr) => {
  if (!addr) return '-';
  const parts = [
    addr.name,
    addr.company_name,
    addr.street,
    addr.city,
    addr.county,
    addr.zip,
    addr.country,
  ].filter(Boolean);
  return parts.join(', ');
};

const loadOrder = async () => {
  loading.value = true;
  error.value = '';

  try {
    const id = route.params.id;
    const data = await fetchOrder(id);
    order.value = data.order ?? data;
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut încărca detaliile comenzii.';
  } finally {
    loading.value = false;
  }
};

const handleReorder = async () => {
  if (!order.value) return;
  try {
    await reorderOrder(order.value.id);
    alert(
      'Produsele din această comandă au fost adăugate în coș.'
    );
  } catch (e) {
    console.error(e);
    alert('Nu am putut re-plasa comanda.');
  }
};

onMounted(loadOrder);
</script>
