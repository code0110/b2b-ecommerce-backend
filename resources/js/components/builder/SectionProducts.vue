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
          <ProductCard :product="product" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import ProductCard from '@/components/common/ProductCard.vue';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const products = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const limit = props.data.limit || 8;
    const response = await axios.get('/api/products', {
      params: {
        limit: limit,
        ...props.data.filters // e.g., { is_promo: true }
      }
    });
    products.value = response.data.data;
  } catch (error) {
    console.error('Failed to load products section:', error);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.hover-underline:hover {
  text-decoration: underline !important;
}
</style>
