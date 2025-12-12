<template>
  <div class="container py-4">
    <h1 class="h4 mb-3">Produse în promoție</h1>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>
    <div v-if="loading" class="alert alert-info">
      Se încarcă produsele cu reducere...
    </div>

    <div v-if="!loading && !products.length" class="text-muted">
      Momentan nu există produse în promoție.
    </div>

    <div class="row g-3">
      <div
        v-for="product in products"
        :key="product.slug || product.id"
        class="col-md-3 col-sm-6"
      >
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <div class="small text-muted mb-1">
              {{ product.category?.name || product.category || '—' }}
            </div>
            <h2 class="h6 mb-1">{{ product.name }}</h2>
            <div class="small text-muted mb-2">
              {{ product.internal_code || product.code }}
            </div>
            <div class="mt-auto">
              <div
                class="small text-muted"
                v-if="product.promo_price || product.promoPrice"
              >
                <span class="text-decoration-line-through me-1">
                  {{ formatPrice(product.price ?? product.list_price) }}
                </span>
                <span class="fw-semibold">
                  {{ formatPrice(product.promo_price ?? product.promoPrice ?? product.final_price) }} RON
                </span>
              </div>
              <div v-else class="fw-semibold mb-1">
                {{ formatPrice(product.final_price ?? product.price ?? product.list_price) }}
                <span
                  v-if="product.final_price || product.price || product.list_price"
                >
                  RON
                </span>
              </div>
              <RouterLink
                v-if="product.slug"
                :to="`/produs/${product.slug}`"
                class="btn btn-outline-primary btn-sm"
              >
                Detalii produs
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { searchProducts } from '@/services/catalog';

const loading = ref(false);
const error = ref('');
const products = ref([]);

const formatPrice = (value) => {
  if (value == null) return '-';
  const num = Number(value);
  if (Number.isNaN(num)) return String(value);
  return num.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const loadDiscountedProducts = async () => {
  loading.value = true;
  error.value = '';
  products.value = [];

  try {
    const data = await searchProducts({ flag: 'discounted' }); // adaptează: ex. on_promo: 1

    if (Array.isArray(data)) {
      products.value = data;
    } else if (Array.isArray(data.data)) {
      products.value = data.data;
    } else if (Array.isArray(data.products)) {
      products.value = data.products;
    }
  } catch (e) {
    console.error('Discounted products load error', e);
    error.value =
      e.response?.data?.message ||
      'Nu s-au putut încărca produsele cu reducere.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadDiscountedProducts();
});
</script>
