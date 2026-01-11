<template>
  <div class="card border-0 shadow-sm overflow-hidden h-100 dd-product-card">
    <div class="row g-0 h-100">
      <!-- Left: Image & Badges -->
      <div class="col-md-3 bg-white position-relative border-end">
         <div class="position-absolute top-0 start-0 m-2 z-2 d-flex flex-column gap-1">
            <span v-if="hasDiscount" class="badge bg-danger rounded-1 shadow-sm">
              -{{ discountPercent }}%
            </span>
            <span v-if="product.is_new" class="badge bg-success rounded-1 shadow-sm">
              NOU
            </span>
            <span v-if="product.is_promo && !hasDiscount" class="badge bg-warning text-dark rounded-1 shadow-sm">
              PROMO
            </span>
         </div>

         <!-- Actions overlay on image for list view? Or maybe distinct buttons? -->
         <!-- Let's put wishlist/compare top right of image area -->
         <div class="position-absolute top-0 end-0 m-2 z-2 d-flex flex-column gap-2">
            <WishlistButton :product="product" :round="true" custom-class="shadow-sm bg-white text-secondary" />
            <CompareButton :product="product" :round="true" custom-class="shadow-sm bg-white text-secondary" />
         </div>

         <RouterLink :to="productLink" class="d-block h-100 w-100">
            <div class="ratio ratio-4x3 h-100">
                <img 
                  v-if="mainImage" 
                  :src="mainImage" 
                  :alt="product.name" 
                  class="object-fit-contain w-100 h-100 p-2 transition-transform" 
                  loading="lazy"
                >
                <div v-else class="d-flex align-items-center justify-content-center text-muted small w-100 h-100 bg-light">
                  <i class="bi bi-image fs-1 opacity-25"></i>
                </div>
             </div>
         </RouterLink>
      </div>

      <!-- Middle: Content -->
      <div class="col-md-6 border-end">
        <div class="card-body h-100 d-flex flex-column">
           <div class="mb-2">
              <div class="small text-muted mb-1">{{ product.main_category_name || 'Catalog' }}</div>
              <h3 class="h5 mb-1 fw-bold">
                  <RouterLink :to="productLink" class="text-decoration-none text-dark hover-orange">
                      {{ product.name }}
                  </RouterLink>
              </h3>
              <div class="small text-muted">Cod: {{ product.internal_code || product.sku || '-' }}</div>
           </div>

           <!-- Rating -->
           <div class="mb-2" v-if="rating > 0">
              <i v-for="n in 5" :key="n" class="bi" :class="n <= rating ? 'bi-star-fill text-warning' : 'bi-star text-muted'"></i>
              <span class="text-muted small ms-1">({{ rating }})</span>
           </div>

           <p class="card-text text-muted small mb-3 flex-grow-1 line-clamp-2">
              {{ product.short_description || product.description }}
           </p>

           <!-- Stock -->
           <div class="mt-auto">
              <template v-if="showNumericStock">
                 <small v-if="product.stock_qty > 0" class="text-success fw-bold d-flex align-items-center gap-1">
                   <i class="bi bi-check-circle-fill"></i> Stoc: {{ displayStock }}
                 </small>
                 <small v-else class="text-danger fw-bold d-flex align-items-center gap-1">
                   <i class="bi bi-x-circle-fill"></i> Stoc: 0
                 </small>
              </template>
              <template v-else>
                  <small v-if="isStockAvailable" class="text-success fw-bold d-flex align-items-center gap-1">
                    <i class="bi bi-check-circle-fill"></i> În stoc
                  </small>
                  <small v-else-if="product.stock_status && product.stock_status.toLowerCase().includes('furnizor')" class="text-info fw-bold d-flex align-items-center gap-1">
                     <i class="bi bi-truck"></i> Stoc furnizor
                  </small>
                  <small v-else-if="product.stock_status" class="text-warning fw-bold d-flex align-items-center gap-1">
                     <i class="bi bi-info-circle-fill"></i> {{ product.stock_status }}
                  </small>
                  <small v-else class="text-danger fw-bold d-flex align-items-center gap-1">
                    <i class="bi bi-x-circle-fill"></i> Stoc epuizat
                  </small>
              </template>
           </div>
        </div>
      </div>

      <!-- Right: Price & Action -->
      <div class="col-md-3 bg-light">
          <div class="card-body h-100 d-flex flex-column justify-content-center">
              <div class="price-container mb-3 text-center text-md-end">
                  <div v-if="hasDiscount">
                      <div class="text-decoration-line-through text-muted small">{{ formatPriceGlobal(listPrice, product.vat_rate, product.vat_included) }}</div>
                      <div class="text-danger fw-bold fs-4">{{ formatPriceGlobal(price, product.vat_rate, product.vat_included) }}</div>
                  </div>
                  <div v-else>
                       <div class="fw-bold fs-4 text-dark">{{ formatPriceGlobal(price, product.vat_rate, product.vat_included) }}</div>
                  </div>
                  <div class="text-muted small">{{ showVat ? 'TVA inclus' : '+TVA' }}</div>
              </div>

              <div class="d-flex flex-column gap-2">
                  <div class="input-group input-group-sm justify-content-center justify-content-md-end">
                      <button class="btn btn-outline-secondary" type="button" @click="decrementQty" :disabled="qty <= minQty || loading">
                        <i class="bi bi-dash"></i>
                      </button>
                      <input type="number" class="form-control text-center" style="max-width: 60px;" v-model.number="qty" :min="minQty" :step="orderStep" readonly>
                      <button class="btn btn-outline-secondary" type="button" @click="incrementQty" :disabled="loading">
                        <i class="bi bi-plus"></i>
                      </button>
                   </div>

                  <button 
                    class="btn btn-orange d-flex align-items-center justify-content-center gap-2" 
                    :disabled="loading || (!isStockAvailable && !product.allow_backorder)" 
                    @click.prevent="handleAddToCart"
                  >
                      <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                      <i v-else class="bi bi-cart-plus"></i>
                      Adaugă în coș
                  </button>
              </div>
          </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { RouterLink } from 'vue-router';
import WishlistButton from '@/components/common/WishlistButton.vue';
import CompareButton from '@/components/common/CompareButton.vue';
import { useCartStore } from '@/store/cart';
import { useToast } from 'vue-toastification';
import { useAuthStore } from '@/store/auth';
import { usePrice } from '@/composables/usePrice';

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
});

const cartStore = useCartStore();
const authStore = useAuthStore();
const toast = useToast();
const { formatPrice: formatPriceGlobal, showVat } = usePrice();
const loading = ref(false);
const qty = ref(props.product.order_quantity_step && props.product.order_quantity_step > 0 ? parseFloat(props.product.order_quantity_step) : 1);

const orderStep = computed(() => {
  return props.product.order_quantity_step && props.product.order_quantity_step > 0 
    ? parseFloat(props.product.order_quantity_step) 
    : 1;
});

const minQty = computed(() => {
  return props.product.min_order_quantity && props.product.min_order_quantity > 0
    ? parseFloat(props.product.min_order_quantity)
    : orderStep.value;
});

const showNumericStock = computed(() => {
  const roles = (authStore.user?.roles || []).map(r => (r.slug || r.code || '').toLowerCase());
  return roles.some(r => ['admin', 'sales_agent', 'sales_director', 'operator', 'manager'].includes(r));
});

const displayStock = computed(() => {
  const stock = parseFloat(props.product.stock_qty || 0);
  if (stock <= 0) return 0;
  
  if (props.product.order_quantity_step && props.product.order_quantity_step > 0) {
      const packs = Math.floor(stock / parseFloat(props.product.order_quantity_step));
      const unit = props.product.packaging_unit || 'cutii';
      return `${packs} ${unit} (${stock} ${props.product.unit_of_measure || ''})`;
  }
  
  return `${stock} ${props.product.unit_of_measure || 'buc.'}`;
});

// Computed (Shared logic ideally extracted to a composable, but okay for now)
const productLink = computed(() => `/produs/${props.product.slug}`);

const mainImage = computed(() => {
  return props.product.main_image_url || props.product.image_url || null;
});

const listPrice = computed(() => {
  return props.product.list_price || props.product.rrp_price || 0;
});

const price = computed(() => {
  return props.product.price_override || props.product.price || props.product.list_price || 0;
});

const hasDiscount = computed(() => {
  if (props.product.hasDiscount) return true;
  if (listPrice.value > price.value) return true;
  return false;
});

const discountPercent = computed(() => {
  if (props.product.discountPercent) return props.product.discountPercent;
  if (hasDiscount.value && listPrice.value > 0) {
    return Math.round(((listPrice.value - price.value) / listPrice.value) * 100);
  }
  return 0;
});

const rating = computed(() => {
  return props.product.average_rating || props.product.rating || 0;
});

const isStockAvailable = computed(() => {
  return props.product.stock_qty > 0 || props.product.stock > 0;
});

// Methods
const incrementQty = () => {
  qty.value = parseFloat((qty.value + orderStep.value).toFixed(4));
};

const decrementQty = () => {
  if (qty.value > minQty.value) {
    qty.value = parseFloat((qty.value - orderStep.value).toFixed(4));
  }
};

const handleAddToCart = async () => {
  loading.value = true;
  try {
    await cartStore.addItem({
      product_id: props.product.id,
      quantity: qty.value
    });
    toast.success('Produs adăugat în coș');
    qty.value = minQty.value;
  } catch (error) {
    console.error(error);
    toast.error('Eroare la adăugarea în coș');
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.hover-orange:hover {
  color: var(--dd-orange) !important;
}
.transition-transform:hover {
  transform: scale(1.05);
}
.dd-product-card {
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}
.dd-product-card:hover {
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    transform: translateY(-2px);
}
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
