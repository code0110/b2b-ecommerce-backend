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
                  <!-- Afișare preț unitar (base + final) -->
                  <div v-if="item.unit_base_price > item.unit_final_price" class="text-muted text-decoration-line-through me-1">
                     {{ item.unit_base_price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                  </div>
                  <div :class="{'text-danger fw-bold': item.unit_base_price > item.unit_final_price}">
                    {{ item.unit_final_price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                  </div>
                  <span class="text-muted" style="font-size: 0.85em;">x {{ item.quantity }}</span>
                </div>

                <!-- Total linie -->
                <div class="fw-semibold mb-1">
                  {{ item.line_final_total.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                </div>
                
                <!-- Promoții aplicate -->
                <div v-if="item.applied_promotions && item.applied_promotions.length" class="small text-success mb-2">
                  <div v-for="promo in item.applied_promotions" :key="promo.id">
                    <i class="bi bi-tag-fill me-1"></i> {{ promo.name }} (-{{ promo.discount_amount }} RON)
                  </div>
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
                <span>
                  {{ subtotal.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                </span>
              </div>
              <div v-if="discountTotal > 0" class="d-flex justify-content-between mb-2 text-success">
                <span>Reducere</span>
                <span>
                  -{{ discountTotal.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                </span>
              </div>
              <div class="d-flex justify-content-between mb-3 border-top pt-2">
                <span class="fw-bold">Total de plată</span>
                <span class="fw-bold">
                  {{ grandTotal.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
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
const summary = ref({
  subtotal: 0,
  discountTotal: 0,
  total: 0
});

const loadCart = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await getCart();
    cartRaw.value = data;

    // Use items directly from API as they are already enriched
    items.value = data.items ?? [];
    
    // Update summary from API response
    summary.value = {
      subtotal: data.subtotal ?? 0,
      discountTotal: data.discount_total ?? 0,
      total: data.total ?? 0
    };
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

const subtotal = computed(() => summary.value.subtotal);
const discountTotal = computed(() => summary.value.discountTotal);
const grandTotal = computed(() => summary.value.total);

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
