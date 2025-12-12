<template>
  <div class="container py-4">
    <h1 class="h4 mb-3">Coș de cumpărături</h1>

    <div v-if="loading" class="text-muted">
      Se încarcă coșul...
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-else>
      <div v-if="items.length === 0" class="alert alert-info">
        Coșul este gol.
      </div>

      <div v-else class="row">
        <div class="col-md-8">
          <div
            v-for="item in items"
            :key="item.id"
            class="card mb-2"
          >
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold">
                  {{ item.product?.name || 'Produs' }}
                </div>
                <div class="small text-muted">
                  Cod: {{ item.product?.internal_code || item.product?.sku || '-' }}
                </div>
              </div>
              <div class="text-end">
                <div class="small mb-1">
                  {{ item.unit_price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                  ×
                  {{ item.quantity }}
                </div>
                <div class="fw-semibold mb-1">
                  {{ item.total.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                </div>
                <button
                  class="btn btn-outline-danger btn-sm"
                  type="button"
                  @click="remove(item.id)"
                >
                  Șterge
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card">
            <div class="card-body">
              <h2 class="h6">Sumar coș</h2>
              <div class="d-flex justify-content-between mb-2">
                <span>Subtotal</span>
                <span class="fw-semibold">
                  {{ subtotal.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                </span>
              </div>
              <RouterLink to="/checkout" class="btn btn-primary w-100 btn-sm">
                Mergi la checkout
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { getCart, removeCartItem } from '@/services/cart';

const loading = ref(false);
const error = ref('');
const cartRaw = ref(null);
const items = ref([]);

const loadCart = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await getCart();
    cartRaw.value = data;

    const rawItems = data.items ?? [];
    items.value = rawItems.map(i => ({
      ...i,
      unit_price: Number(i.unit_price ?? 0),
      total: Number(i.total ?? 0),
    }));
  } catch (e) {
    console.error('Cart load error', e);
    if (e.response?.data?.message) {
      error.value = e.response.data.message;
    } else {
      error.value = 'Nu s-a putut încărca coșul de cumpărături.';
    }
  } finally {
    loading.value = false;
  }
};

const subtotal = computed(() =>
  items.value.reduce((sum, i) => sum + (i.total || 0), 0)
);

const remove = async (id) => {
  try {
    await removeCartItem(id);
    await loadCart();
  } catch (e) {
    console.error('Remove item error', e);
    error.value = 'Nu s-a putut șterge articolul din coș.';
  }
};

onMounted(loadCart);
</script>
