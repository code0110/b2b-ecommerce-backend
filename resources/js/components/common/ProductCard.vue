<template>
  <div 
    class="card h-100 border-0 shadow-sm dd-product-card position-relative"
    :class="{ 'product-card-compact': compact }"
  >
    <!-- Badges -->
    <div class="position-absolute top-0 start-0 p-2 z-2 d-flex flex-column gap-1">
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

    <!-- Actions (Wishlist / Compare) -->
    <div class="position-absolute top-0 end-0 p-2 z-2 d-flex flex-column gap-2">
      <WishlistButton 
        :product="product" 
        :round="true" 
        custom-class="shadow-sm" 
      />
      <CompareButton 
        :product="product" 
        :round="true" 
        custom-class="shadow-sm" 
      />
    </div>

    <!-- Image -->
    <div class="position-relative overflow-hidden p-2 text-center bg-white" :style="{ height: compact ? '140px' : '180px' }">
      <RouterLink :to="productLink" class="d-block h-100 w-100">
        <img 
          v-if="mainImage" 
          :src="mainImage" 
          class="h-100 w-100 object-fit-contain transition-transform" 
          :alt="product.name"
          style="transition: transform 0.3s ease;"
          loading="lazy"
        >
        <div v-else class="h-100 w-100 d-flex align-items-center justify-content-center bg-light text-muted rounded">
          <i class="bi bi-image fs-1 opacity-25"></i>
        </div>
      </RouterLink>
    </div>

    <!-- Body -->
    <div class="card-body d-flex flex-column p-2">
      <!-- Stock Status -->
      <div class="mb-1" v-if="!compact">
        <template v-if="showNumericStock">
           <small v-if="product.stock_qty > 0" class="text-success fw-bold d-flex align-items-center gap-1" style="font-size: 0.7rem;">
             <i class="bi bi-check-circle-fill"></i> {{ displayStock }}
           </small>
           <small v-else class="text-danger fw-bold d-flex align-items-center gap-1" style="font-size: 0.7rem;">
             <i class="bi bi-x-circle-fill"></i> 0
           </small>
        </template>
        <template v-else>
          <small v-if="isStockAvailable" class="text-success fw-bold d-flex align-items-center gap-1" style="font-size: 0.7rem;">
            <i class="bi bi-check-circle-fill"></i> În stoc
          </small>
          <small v-else-if="product.stock_status && product.stock_status.toLowerCase().includes('furnizor')" class="text-info fw-bold d-flex align-items-center gap-1" style="font-size: 0.7rem;">
             <i class="bi bi-truck"></i> Stoc furnizor
          </small>
          <small v-else-if="product.stock_status" class="text-warning fw-bold d-flex align-items-center gap-1" style="font-size: 0.7rem;">
             <i class="bi bi-info-circle-fill"></i> {{ product.stock_status }}
          </small>
          <small v-else class="text-danger fw-bold d-flex align-items-center gap-1" style="font-size: 0.7rem;">
            <i class="bi bi-x-circle-fill"></i> Stoc epuizat
          </small>
        </template>
      </div>

      <!-- Title -->
      <h6 class="card-title mb-1 lh-sm" :title="product.name" style="font-size: 0.9rem; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 2.4em;">
        <RouterLink :to="productLink" class="text-decoration-none text-dark fw-semibold hover-orange">
          {{ product.name }}
        </RouterLink>
      </h6>
      
      <!-- Code / Rating (optional for compact) -->
      <div v-if="!compact" class="d-flex justify-content-between align-items-center mb-1">
         <small class="text-muted" style="font-size: 0.7rem;">{{ product.internal_code || product.sku || '-' }}</small>
         <div v-if="rating > 0" class="d-flex text-warning" style="font-size: 0.7rem;">
            <i class="bi bi-star-fill"></i>
            <span class="text-muted ms-1">({{ rating }})</span>
         </div>
      </div>

      <!-- Price -->
      <div class="mt-auto pt-2 border-top border-light">
        <div v-if="hasDiscount" class="d-flex flex-column">
          <div class="text-muted text-decoration-line-through" style="font-size: 0.8rem;">
            {{ formatPriceGlobal(listPrice, product.vat_rate, product.vat_included) }}
          </div>
          <div class="text-danger fw-bold fs-5">
            {{ formatPriceGlobal(price, product.vat_rate, product.vat_included) }}
          </div>
        </div>
        <div v-else class="fw-bold text-dark fs-5">
          {{ formatPriceGlobal(price, product.vat_rate, product.vat_included) }}
        </div>
        <div v-if="!compact" class="text-muted small" style="font-size: 0.7rem;">
           {{ showVat ? 'TVA inclus' : '+TVA' }}
        </div>
      </div>

      <!-- Actions: Qty + Add to Cart -->
      <div class="mt-3">
        <div class="d-flex gap-2 mb-2" v-if="!compact">
           <div class="input-group input-group-sm">
              <button class="btn btn-outline-secondary" type="button" @click="decrementQty" :disabled="qty <= minQty || loading">
                <i class="bi bi-dash"></i>
              </button>
              <input type="number" class="form-control text-center px-1" v-model.number="qty" :min="minQty" :step="orderStep" readonly>
              <button class="btn btn-outline-secondary" type="button" @click="incrementQty" :disabled="loading">
                <i class="bi bi-plus"></i>
              </button>
           </div>
        </div>

        <button 
          class="btn btn-orange w-100 btn-sm py-2 d-flex align-items-center justify-content-center gap-2"
          @click="handleAddToCart"
          :disabled="loading || (!isStockAvailable && !product.allow_backorder && !hasVariants)"
        >
          <span v-if="loading" class="spinner-border spinner-border-sm"></span>
          <i v-else :class="hasVariants ? 'bi bi-eye fs-6' : 'bi bi-cart-plus fs-6'"></i>
          <span v-if="!compact">
            {{ hasVariants ? 'Vezi opțiuni' : 'Adaugă în coș' }}
          </span>
          <span v-else>
            {{ hasVariants ? 'Vezi' : 'Adaugă' }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
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
  },
  compact: {
    type: Boolean,
    default: false
  }
});

const router = useRouter();
const cartStore = useCartStore();
const authStore = useAuthStore();
const toast = useToast();
const { formatPrice: formatPriceGlobal, showVat } = usePrice();
const loading = ref(false);
const qty = ref(props.product.order_quantity_step && props.product.order_quantity_step > 0 ? parseFloat(props.product.order_quantity_step) : 1);

// Computed Properties
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
  
  // If we have a packaging step > 1 (e.g. 2.196 mp/box), display both total and boxes?
  // User request: "pe site stocul trebuie sa fie impartit la X"
  if (props.product.order_quantity_step && props.product.order_quantity_step > 0) {
      const packs = Math.floor(stock / parseFloat(props.product.order_quantity_step));
      const unit = props.product.packaging_unit || 'cutii'; // fallback if null
      return `${packs} ${unit} (${stock} ${props.product.unit_of_measure || ''})`;
  }
  
  return `${stock} ${props.product.unit_of_measure || 'buc.'}`;
});

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

const hasVariants = computed(() => {
  return props.product.variants && props.product.variants.length > 0;
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
  if (hasVariants.value) {
    router.push(productLink.value);
    return;
  }
  
  loading.value = true;
  try {
    await cartStore.addItem({
      product_id: props.product.id,
      quantity: qty.value
    });
    toast.success('Produs adăugat în coș');
    // Reset to minQty
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
</style>
