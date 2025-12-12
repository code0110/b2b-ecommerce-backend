<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">
        Detalii comandă
        <span v-if="order">#{{ order.id }}</span>
      </h1>

      <RouterLink
        :to="{ name: 'account-orders' }"
        class="btn btn-outline-secondary btn-sm"
      >
        &laquo; Înapoi la comenzi
      </RouterLink>
    </div>

    <div v-if="loading" class="text-center py-5 text-muted">
      Se încarcă detaliile comenzii...
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-else-if="!order" class="alert alert-warning">
      Comanda nu a putut fi găsită.
    </div>

    <div v-else>
      <!-- Info generală -->
      <div class="row mb-3">
        <div class="col-md-4 mb-3">
          <div class="card h-100">
            <div class="card-header py-2">
              <strong>Informații comandă</strong>
            </div>
            <div class="card-body small">
              <p class="mb-1">
                <strong>Număr:</strong>
                {{ order.order_number || `CMD-${order.id}` }}
              </p>
              <p class="mb-1">
                <strong>Data:</strong>
                {{ formatDate(order.placed_at || order.created_at) }}
              </p>
              <p class="mb-1">
                <strong>Status:</strong>
                <span class="badge bg-secondary">
                  {{ order.status || 'necunoscut' }}
                </span>
              </p>
              <p class="mb-0">
                <strong>Plată:</strong>
                <span
                  class="badge"
                  :class="paymentBadgeClass(order.payment_status)"
                >
                  {{ order.payment_status || 'necunoscut' }}
                </span>
              </p>
            </div>
          </div>
        </div>

        <div class="col-md-4 mb-3">
          <div class="card h-100">
            <div class="card-header py-2">
              <strong>Client</strong>
            </div>
            <div class="card-body small">
              <p class="mb-1">
                <strong>Nume:</strong>
                {{ order.customer?.name || '-' }}
              </p>
              <p class="mb-1">
                <strong>Email:</strong>
                {{ order.customer?.email || '-' }}
              </p>
              <p class="mb-0">
                <strong>Telefon:</strong>
                {{ order.customer?.phone || '-' }}
              </p>
            </div>
          </div>
        </div>

        <div class="col-md-4 mb-3">
          <div class="card h-100">
            <div class="card-header py-2">
              <strong>Sumar plată</strong>
            </div>
            <div class="card-body small">
              <p class="mb-1">
                <strong>Subtotal:</strong>
                {{ formatMoney(order.subtotal || 0) }}
              </p>
              <p class="mb-1">
                <strong>Discount:</strong>
                - {{ formatMoney(order.discount_total || 0) }}
              </p>
              <p class="mb-1">
                <strong>TVA:</strong>
                {{ formatMoney(order.tax_total || 0) }}
              </p>
              <p class="mb-1">
                <strong>Transport:</strong>
                {{ formatMoney(order.shipping_total || 0) }}
              </p>
              <p class="mb-0 fw-bold">
                Total:
                {{ formatMoney(order.grand_total || 0) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Liniile comenzii -->
      <div class="card mb-3">
        <div class="card-header py-2">
          <strong>Produse</strong>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Produs</th>
                  <th>Cod</th>
                  <th class="text-center">Cantitate</th>
                  <th class="text-end">Preț unitar</th>
                  <th class="text-end">Total linie</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in order.items || []" :key="item.id">
                  <td>
                    {{ item.product?.name || item.product_name || '-' }}
                  </td>
                  <td>
                    {{ item.product?.code || item.product_code || '-' }}
                  </td>
                  <td class="text-center">
                    {{ item.quantity }}
                  </td>
                  <td class="text-end">
                    {{ formatMoney(item.unit_price || 0) }}
                  </td>
                  <td class="text-end">
                    {{ formatMoney(item.total || item.quantity * item.unit_price || 0) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Acțiuni -->
      <div class="d-flex justify-content-between align-items-center">
        <button
          type="button"
          class="btn btn-outline-primary btn-sm"
          @click="reorder"
          :disabled="reorderLoading"
        >
          <span v-if="reorderLoading">Se pregătește comanda...</span>
          <span v-else>Comandă din nou</span>
        </button>

        <!-- loc pentru buton "Descarcă factură" etc. -->
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { fetchMyOrder, reorderOrder } from '@/services/orders';

const route = useRoute();

const loading = ref(false);
const error = ref('');
const order = ref(null);
const reorderLoading = ref(false);

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

const loadOrder = async () => {
  loading.value = true;
  error.value = '';
  order.value = null;

  try {
    const id = route.params.id;
    const data = await fetchMyOrder(id);
    order.value = data;
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca detaliile comenzii.';
  } finally {
    loading.value = false;
  }
};

const reorder = async () => {
  if (!order.value) return;
  reorderLoading.value = true;
  try {
    await reorderOrder(order.value.id);
    // Deocamdată doar mesaj; ulterior poți redirecționa spre coș etc.
    alert(
      'Funcționalitatea de „Comandă din nou” poate recrea coșul pe baza acestei comenzi (vezi răspunsul API).'
    );
  } catch (e) {
    console.error(e);
    alert('Nu s-a putut pregăti comanda pentru re-comandă.');
  } finally {
    reorderLoading.value = false;
  }
};

onMounted(loadOrder);
</script>
