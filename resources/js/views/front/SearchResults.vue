<template>
  <div class="container py-4">
    <h1 class="h4 mb-3">
      Rezultate căutare
      <span v-if="query" class="text-muted h6">„{{ query }}”</span>
    </h1>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>
    <div v-if="loading" class="alert alert-info">
      Se caută produse...
    </div>

    <div class="row mb-3">
      <div class="col-md-8">
        <form class="input-group" @submit.prevent="triggerSearch">
          <input
            v-model="searchInput"
            type="text"
            class="form-control"
            placeholder="Caută după denumire, cod produs, cod client, cod de bare..."
          />
          <button class="btn btn-outline-secondary" type="submit">
            Caută
          </button>
        </form>
      </div>
    </div>

    <div v-if="!loading && !products.length && query" class="text-muted">
      Nu au fost găsite produse pentru termenul introdus.
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
              <div class="fw-semibold mb-1">
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

    <!-- Paginare simplă, dacă backend-ul suportă -->
    <div
      v-if="pagination.last_page > 1"
      class="d-flex justify-content-between align-items-center mt-3"
    >
      <div class="text-muted small">
        Pagina {{ pagination.current_page }} din {{ pagination.last_page }}
      </div>
      <div class="btn-group">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="pagination.current_page === 1"
          @click="changePage(pagination.current_page - 1)"
        >
          « Anterioară
        </button>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="pagination.current_page === pagination.last_page"
          @click="changePage(pagination.current_page + 1)"
        >
          Următoarea »
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { searchProducts } from '@/services/catalog';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const error = ref('');
const products = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
});

const searchInput = ref(route.query.q || '');

const query = computed(() => route.query.q || '');

const formatPrice = (value) => {
  if (value == null) return '-';
  const num = Number(value);
  if (Number.isNaN(num)) return String(value);
  return num.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const performSearch = async (page = 1) => {
  if (!query.value) {
    products.value = [];
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const params = {
      q: query.value,
      page,
    };

    const data = await searchProducts(params);

    if (Array.isArray(data)) {
      products.value = data;
      pagination.value = {
        current_page: 1,
        last_page: 1,
      };
    } else if (Array.isArray(data.data)) {
      products.value = data.data;
      pagination.value = {
        current_page: data.meta?.current_page || 1,
        last_page: data.meta?.last_page || 1,
      };
    } else if (Array.isArray(data.products)) {
      products.value = data.products;
      pagination.value = {
        current_page: data.meta?.current_page || 1,
        last_page: data.meta?.last_page || 1,
      };
    }
  } catch (e) {
    console.error('Search error', e);
    error.value =
      e.response?.data?.message || 'Căutarea a eșuat. Încearcă din nou.';
  } finally {
    loading.value = false;
  }
};

const triggerSearch = () => {
  router.push({
    name: 'search-results',
    query: { q: searchInput.value || undefined },
  });
};

const changePage = (page) => {
  performSearch(page);
};

// rulează când se schimbă query-ul în URL
watch(
  () => route.query.q,
  () => {
    performSearch(1);
  },
);

onMounted(() => {
  if (query.value) {
    performSearch(1);
  }
});
</script>
