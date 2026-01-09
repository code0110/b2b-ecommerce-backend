<template>
  <div class="section-categories mb-5">
    <div class="container">
      <div class="d-flex justify-content-between align-items-end mb-4 border-bottom border-secondary pb-2" style="--bs-border-opacity: .1;">
        <h3 class="h4 fw-bold mb-0 text-dark">{{ data.title || 'Categorii Populare' }}</h3>
        <a href="/categorii" class="text-decoration-none fw-bold small text-orange hover-underline">
            Vezi toate categoriile <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
      <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-lg-6">
        <div v-for="(item, index) in categories" :key="index" class="col">
          <RouterLink :to="`/categorie/${item.slug}`" class="text-decoration-none text-dark h-100 d-block">
            <div class="card h-100 border-0 bg-white text-center hover-card transition-all p-3">
                <div class="ratio ratio-1x1 mb-3 position-relative">
                    <img 
                        v-if="item.image_path" 
                        :src="item.image_path" 
                        class="img-fluid object-fit-contain p-2" 
                        :alt="item.name"
                    >
                    <div v-else class="d-flex align-items-center justify-content-center bg-light rounded-circle mx-auto" style="width: 100%; height: 100%;">
                         <i class="bi bi-grid fs-1 text-secondary opacity-50"></i>
                    </div>
                </div>
                <h6 class="card-title small fw-bold mb-0 lh-sm">{{ item.name }}</h6>
            </div>
          </RouterLink>
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
    default: () => ({ title: '', count: 6 })
  }
});

const categories = ref([]);

onMounted(async () => {
  try {
    const limit = props.data.count || 6;
    const response = await axios.get(`/api/categories?limit=${limit}&featured=true`);
    categories.value = response.data.data || response.data || [];
  } catch (e) {
    console.error('Failed to load categories', e);
  }
});
</script>

<style scoped>
.hover-underline:hover {
    text-decoration: underline !important;
}
.hover-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    z-index: 10;
}
</style>
