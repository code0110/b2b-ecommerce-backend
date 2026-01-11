<template>
  <div class="container py-4">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-orange" role="status"></div>
    </div>

    <div v-else-if="error" class="text-center py-5">
      <div class="alert alert-danger">{{ error }}</div>
      <RouterLink to="/" class="btn btn-primary">Înapoi la prima pagină</RouterLink>
    </div>

    <div v-else>
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h1 class="h3 mb-1">Lista de favorite: {{ wishlist?.name }}</h1>
          <p class="text-muted mb-0">Această listă a fost partajată cu tine.</p>
        </div>
        <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
          {{ items.length }} produse
        </span>
      </div>

      <div v-if="items.length === 0" class="text-center py-5 bg-light rounded-3">
        <i class="bi bi-heart text-muted display-1 mb-3"></i>
        <h2 class="h5">Această listă este goală</h2>
      </div>

      <div v-else class="row g-3">
        <div v-for="item in items" :key="item.id" class="col-xl-3 col-lg-4 col-md-6">
          <ProductCard :product="item.product" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import * as wishlistApi from '@/services/wishlist';
import { useToast } from 'vue-toastification';
import ProductCard from '@/components/common/ProductCard.vue';

const route = useRoute();
const toast = useToast();

const loading = ref(true);
const error = ref(null);
const wishlist = ref(null);
const items = ref([]);

const loadSharedWishlist = async () => {
  const token = route.params.token;
  if (!token) {
    error.value = 'Link invalid';
    loading.value = false;
    return;
  }

  try {
    const response = await wishlistApi.getSharedWishlist(token);
    wishlist.value = response.data.wishlist;
    items.value = response.data.items;
  } catch (e) {
    console.error(e);
    error.value = 'Lista nu a fost găsită sau nu este publică.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadSharedWishlist();
});
</script>
