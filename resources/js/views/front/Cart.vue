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
                  
                  <div class="input-group input-group-sm mt-2" style="width: 140px; margin-left: auto;">
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
              <RouterLink to="/checkout" class="btn btn-primary w-100 btn-sm mb-2">
                Mergi la checkout
              </RouterLink>
              <button class="btn btn-outline-primary w-100 btn-sm" @click="requestQuote" :disabled="quoting">
                <span v-if="quoting" class="spinner-border spinner-border-sm me-2"></span>
                Solicită Ofertă Personalizată
              </button>
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
import axios from 'axios';
import { useToast } from 'vue-toastification';

const router = useRouter();
const toast = useToast();

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
    await axios.post('/api/quotes/from-cart', { notes: 'Solicitare din coșul de cumpărături' });
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
