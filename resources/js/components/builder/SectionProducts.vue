<template>
  <div class="section-products mb-5">
    <div class="container">
      <div class="d-flex justify-content-between align-items-end mb-4 border-bottom border-secondary pb-2" style="--bs-border-opacity: .1;">
        <h3 class="h4 fw-bold mb-0 text-dark">{{ data.title || 'Produse Recomandate' }}</h3>
        <RouterLink to="/promotii" class="text-decoration-none fw-bold small text-orange hover-underline">
          Vezi toate produsele <i class="bi bi-arrow-right ms-1"></i>
        </RouterLink>
      </div>
      
      <div v-if="loading" class="row g-3">
         <div v-for="n in 4" :key="n" class="col-xl-3 col-lg-3 col-md-4 col-sm-6 col-6">
             <div class="card h-100 border-0 shadow-sm" aria-hidden="true">
                 <div class="card-body">
                     <h5 class="card-title placeholder-glow">
                         <span class="placeholder col-6"></span>
                     </h5>
                     <p class="card-text placeholder-glow">
                         <span class="placeholder col-7"></span>
                         <span class="placeholder col-4"></span>
                     </p>
                 </div>
             </div>
         </div>
      </div>
      
      <div v-else class="row g-3">
        <div v-for="product in products" :key="product.id" class="col-xl-3 col-lg-3 col-md-4 col-sm-6 col-6">
          <div class="card h-100 bg-white dd-product-card position-relative">
            <!-- Badges -->
            <div class="position-absolute top-0 start-0 p-2 z-2">
                <span v-if="product.promo_price" class="badge bg-danger rounded-1 shadow-sm mb-1 d-block">-{{ calculateDiscount(product) }}%</span>
                <span v-if="product.is_new" class="badge bg-success rounded-1 shadow-sm d-block">NOU</span>
            </div>

            <!-- Image -->
            <div class="position-relative overflow-hidden p-3 text-center bg-white" style="height: 220px;">
                <RouterLink :to="`/produs/${product.slug}`" class="d-block h-100 w-100">
                    <img 
                        v-if="product.image_url" 
                        :src="product.image_url" 
                        class="h-100 w-100 object-fit-contain transition-transform" 
                        :alt="product.name"
                        style="transition: transform 0.3s ease;"
                    >
                    <div v-else class="h-100 w-100 d-flex align-items-center justify-content-center bg-light text-muted rounded">
                        <i class="bi bi-image fs-1 opacity-25"></i>
                    </div>
                </RouterLink>
            </div>

            <!-- Body -->
            <div class="card-body d-flex flex-column pt-0 px-3 pb-3">
              <!-- Stock Status -->
              <div class="mb-2">
                  <small v-if="product.stock > 0" class="text-success fw-bold d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                    <i class="bi bi-check-circle-fill"></i> In stoc magazin
                  </small>
                  <small v-else class="text-danger fw-bold d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                    <i class="bi bi-x-circle-fill"></i> Stoc epuizat
                  </small>
              </div>

              <!-- Title -->
              <h6 class="card-title mb-2 lh-sm" style="height: 2.5rem; overflow: hidden;">
                  <RouterLink :to="`/produs/${product.slug}`" class="text-decoration-none text-dark fw-semibold small hover-orange">
                      {{ product.name }}
                  </RouterLink>
              </h6>

              <!-- Price -->
              <div class="mt-auto pt-2 border-top border-light">
                <div v-if="product.promo_price" class="d-flex flex-column">
                    <div class="text-muted text-decoration-line-through" style="font-size: 0.8rem;">
                        {{ formatPrice(product.price) }}
                    </div>
                    <div class="text-danger fw-bold fs-5">
                        {{ formatPrice(product.promo_price) }}
                    </div>
                </div>
                <div v-else class="fw-bold text-dark fs-5">
                    {{ formatPrice(product.price) }}
                </div>
              </div>

              <!-- Action -->
              <div class="mt-3">
                  <button class="btn btn-orange w-100 btn-sm py-2 d-flex align-items-center justify-content-center gap-2">
                      <i class="bi bi-cart-plus fs-6"></i> Adaugă în coș
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
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({ title: '', type: 'latest', limit: 4 })
  }
});

const products = ref([]);
const loading = ref(true);

const formatPrice = (price) => {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(price);
};

const calculateDiscount = (product) => {
    if(!product.price || !product.promo_price) return 0;
    return Math.round(((product.price - product.promo_price) / product.price) * 100);
};

onMounted(async () => {
  try {
    loading.value = true;
    let endpoint = '/api/products';
    const params = { limit: props.data.limit || 8 };
    
    if (props.data.source === 'promo') {
        params.on_sale = true;
    }
    
    const response = await axios.get(endpoint, { params });
    products.value = response.data.data;
  } catch (e) {
    console.error('Failed to load products', e);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.hover-underline:hover {
    text-decoration: underline !important;
}
.hover-orange:hover {
    color: var(--dd-orange) !important;
}
.transition-transform:hover {
    transform: scale(1.05);
}
</style>
