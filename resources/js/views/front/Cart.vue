<template>
  <div>
    <div class="dd-page-header py-3 mb-3">
      <div class="container">
        <h1 class="h4 mb-1">Coș de cumpărături</h1>
        <p class="text-muted small mb-0">
          Revizuiește cantitățile și finalizează comanda.
        </p>
      </div>
    </div>

    <div class="container pb-4">
      <div v-if="loading" class="text-muted small py-3">
        Se încarcă coșul...
      </div>

      <div v-else-if="error" class="alert alert-danger">
        {{ error }}
      </div>

      <div v-else>
        <div v-if="items.length === 0" class="alert alert-info">
          Coșul este gol.
          <RouterLink :to="{ name: 'products-list' }" class="alert-link ms-1">
            Vezi produsele
          </RouterLink>
        </div>

        <div v-else class="row g-3">
          <div class="col-lg-8">
            <div
              v-for="item in items"
              :key="item.id"
              class="card mb-2"
            >
              <div class="card-body">
                <div class="row g-3 align-items-center">
                  <div class="col-3 col-sm-2">
                    <div class="ratio ratio-1x1 bg-light rounded">
                      <img
                        v-if="item.product?.main_image_url || item.product?.image_url"
                        :src="item.product?.main_image_url || item.product?.image_url"
                        :alt="item.product?.name || 'Produs'"
                        class="w-100 h-100 object-fit-contain p-2"
                        loading="lazy"
                      >
                      <div v-else class="d-flex align-items-center justify-content-center text-muted small w-100 h-100">
                        N/A
                      </div>
                    </div>
                  </div>

                  <div class="col-9 col-sm-6">
                    <div class="fw-semibold">
                      <RouterLink
                        v-if="item.product?.slug"
                        :to="`/produs/${item.product.slug}`"
                        class="text-decoration-none text-dark"
                      >
                        {{ item.product?.name || 'Produs' }}
                      </RouterLink>
                      <span v-else>
                        {{ item.product?.name || 'Produs' }}
                      </span>
                    </div>
                    <div class="small text-muted">
                      Cod: {{ item.product_variant?.sku || item.product?.internal_code || item.product?.sku || '-' }}
                    </div>
                    <div v-if="item.applied_promotions && item.applied_promotions.length" class="small text-danger mt-2">
                    <div v-for="promo in item.applied_promotions" :key="promo.id">
                      <i class="bi bi-tag-fill me-1"></i>{{ promo.name }} (-{{ promo.discount_amount }} RON)
                    </div>
                  </div>
                  </div>

                  <div class="col-12 col-sm-4 text-sm-end">
                    <div class="d-flex flex-column align-items-sm-end">
                      <div class="small">
                        <div v-if="item.unit_base_price > item.unit_final_price" class="text-muted text-decoration-line-through">
                          {{ formatPriceGlobal(item.unit_base_price, item.product?.vat_rate, item.product?.vat_included) }}
                        </div>
                        <div :class="item.unit_base_price > item.unit_final_price ? 'text-danger fw-bold' : 'fw-semibold'">
                          {{ formatPriceGlobal(item.unit_final_price, item.product?.vat_rate, item.product?.vat_included) }}
                        </div>
                      </div>

                      <div class="input-group input-group-sm mt-2" style="width: 160px;">
                        <button
                          class="btn btn-outline-secondary"
                          type="button"
                          @click="updateQuantity(item, item.quantity - 1)"
                          :disabled="item.quantity <= 1 || updating === item.id"
                        >
                          <i class="bi bi-dash"></i>
                        </button>
                        <input
                          type="number"
                          class="form-control text-center"
                          :value="item.quantity"
                          @change="updateQuantity(item, $event.target.value)"
                          min="1"
                          :disabled="updating === item.id"
                        >
                        <button
                          class="btn btn-outline-secondary"
                          type="button"
                          @click="updateQuantity(item, item.quantity + 1)"
                          :disabled="updating === item.id"
                        >
                          <i class="bi bi-plus"></i>
                        </button>
                      </div>

                      <div class="fw-semibold mt-2">
                        {{ formatPriceGlobal(item.line_final_total, item.product?.vat_rate, item.product?.vat_included) }}
                      </div>

                      <button
                        class="btn btn-outline-danger btn-sm mt-2"
                        type="button"
                        @click="remove(item.id)"
                      >
                        Șterge
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="card">
              <div class="card-body">
                <h2 class="h6 fw-bold mb-3">Sumar coș</h2>
                <div class="d-flex justify-content-between mb-2 small">
                  <span>Subtotal</span>
                  <span>
                    {{ new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(displayedSubtotal) }}
                  </span>
                </div>
                <div v-if="discountTotal > 0" class="d-flex justify-content-between mb-2 text-danger small">
                  <span>Reducere</span>
                  <span>
                    -{{ formatPriceGlobal(discountTotal, 19, true) }}
                  </span>
                </div>
                <div class="d-flex justify-content-between mb-3 border-top pt-2">
                  <span class="fw-bold">Total de plată</span>
                  <span class="fw-bold">
                    {{ formatPriceGlobal(grandTotal, 19, true) }}
                  </span>
                </div>
                <RouterLink :to="{ name: 'checkout' }" class="btn btn-orange w-100 btn-sm mb-2">
                  Mergi la checkout
                </RouterLink>
                <button class="btn btn-outline-secondary w-100 btn-sm" @click="requestQuote" :disabled="quoting">
                  <span v-if="quoting" class="spinner-border spinner-border-sm me-2"></span>
                  Solicită ofertă personalizată
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { getCart, removeCartItem, updateCartItem } from '@/services/cart';
import api from '@/services/http';
import { useToast } from 'vue-toastification';
import { usePrice } from '@/composables/usePrice';
import { useAuthStore } from '@/store/auth';

const router = useRouter();
const toast = useToast();
const { formatPrice: formatPriceGlobal, calculatePrice, showVat } = usePrice();
const authStore = useAuthStore();

const showNumericStock = computed(() => {
    const roles = (authStore.user?.roles || []).map(r => r.slug || r.code);
    return roles.includes('sales_agent') || roles.includes('sales_director') || roles.includes('admin');
});



const loading = ref(false);
const error = ref('');
const quoting = ref(false);
const cartRaw = ref(null);
const items = ref([]);
const updating = ref(null);
const summary = ref({
  subtotal: 0,
  discountTotal: 0,
  total: 0
});

const loadCart = async () => {
  // loading.value = true; // Don't show full loader on refresh to keep UI stable
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

const updateQuantity = async (item, newQuantity) => {
  const qty = parseInt(newQuantity);
  if (isNaN(qty) || qty < 1) return;
  
  updating.value = item.id;
  try {
    await updateCartItem(item.id, qty);
    await loadCart();
  } catch (e) {
    console.error('Update quantity error', e);
    error.value = 'Nu s-a putut actualiza cantitatea.';
  } finally {
    updating.value = null;
  }
};

const subtotal = computed(() => summary.value.subtotal);
const discountTotal = computed(() => summary.value.discountTotal);
const grandTotal = computed(() => summary.value.total);

const displayedSubtotal = computed(() => {
  if (!items.value || !items.value.length) return 0;
  return items.value.reduce((acc, item) => {
    const rate = item.product?.vat_rate ?? 19;
    const included = item.product?.vat_included ?? false;
    // line_final_total is the raw line total from backend
    return acc + calculatePrice(item.line_final_total, rate, included);
  }, 0);
});

const remove = async (id) => {
  try {
    await removeCartItem(id);
    await loadCart();
  } catch (e) {
    console.error('Remove item error', e);
    error.value = 'Nu s-a putut șterge articolul din coș.';
  }
};

const requestQuote = async () => {
  if (!confirm('Dorești să soliciți o ofertă personalizată pentru produsele din coș?')) return;
  
  quoting.value = true;
  try {
    await api.post('/quotes/from-cart', { notes: 'Solicitare din coșul de cumpărături' });
    toast.success('Cererea de ofertă a fost trimisă cu succes!');
    router.push({ name: 'account-offers' });
  } catch (e) {
    console.error(e);
    toast.error('Eroare la trimiterea cererii.');
  } finally {
    quoting.value = false;
  }
};

onMounted(loadCart);
</script>
