<template>
  <div class="py-3">
    <div class="container">
      <h1 class="h5 mb-2">
        Rezultate căutare
      </h1>
      <p class="text-muted small mb-3">
        Termen căutat: <strong>"{{ query }}"</strong>
      </p>

      <div class="row mb-3">
        <div class="col-md-6">
          <input
            v-model="localQuery"
            type="text"
            class="form-control form-control-sm"
            placeholder="Refaceți căutarea..."
            @keyup.enter="doSearch"
          />
        </div>
        <div class="col-md-3">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            @click="doSearch"
          >
            Caută
          </button>
        </div>
      </div>

      <div v-if="loading" class="text-muted small py-3">
        Se caută produse...
      </div>
      <div v-else-if="error" class="alert alert-danger small py-2">
        {{ error }}
      </div>

      <div v-else>
        <!-- Sugestii categorii / branduri -->
        <div class="row mb-3" v-if="categories.length || brands.length">
          <div class="col-md-6" v-if="categories.length">
            <div class="small fw-semibold mb-1">Categorii</div>
            <div class="d-flex flex-wrap gap-2 small">
              <RouterLink
                v-for="cat in categories"
                :key="cat.id"
                :to="`/categorie/${cat.slug}`"
                class="badge rounded-pill text-bg-light text-decoration-none"
              >
                {{ cat.name }}
              </RouterLink>
            </div>
          </div>
          <div class="col-md-6" v-if="brands.length">
            <div class="small fw-semibold mb-1">Branduri</div>
            <div class="d-flex flex-wrap gap-2 small">
              <span
                v-for="brand in brands"
                :key="brand.id"
                class="badge rounded-pill text-bg-light"
              >
                {{ brand.name }}
              </span>
            </div>
          </div>
        </div>

        <!-- Produse -->
        <div class="row g-3">
          <div
            v-for="p in products.data"
            :key="p.id"
            class="col-lg-3 col-md-4 col-sm-6"
          >
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <div class="small text-muted mb-1">
                  {{ p.main_category?.name || 'Categorie' }}
                </div>
                <h2 class="h6 mb-1">{{ p.name }}</h2>
                <div class="small text-muted mb-2">
                  {{ p.code || p.sku }}
                </div>
                <div class="mt-auto">
                  <div class="fw-semibold mb-1">
                    {{ formatMoney(p.promo_price || p.price || p.list_price || 0) }}
                    RON
                  </div>
                  <RouterLink
                    :to="`/produs/${p.slug}`"
                    class="btn btn-outline-primary btn-sm"
                  >
                    Detalii
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>

          <div v-if="!products.data.length" class="col-12">
            <div class="alert alert-light border small mb-0">
              Nu am găsit produse pentru acest termen de căutare.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { searchCatalog } from '@/services/search';

const route = useRoute();
const router = useRouter();

const query = ref('');
const localQuery = ref('');
const loading = ref(false);
const error = ref('');

const products = reactive({
  data: [],
  meta: null,
});

const categories = ref([]);
const brands = ref([]);

const formatMoney = (value) =>
  (Number(value) || 0).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

const load = async () => {
  query.value = (route.query.q || '').toString();
  localQuery.value = query.value;

  if (!query.value) {
    products.data = [];
    products.meta = null;
    categories.value = [];
    brands.value = [];
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const data = await searchCatalog({ q: query.value });
    products.data = data.products?.data || [];
    products.meta = data.products?.meta || null;
    categories.value = data.categories || [];
    brands.value = data.brands || [];
  } catch (e) {
    console.error(e);
    error.value = 'A apărut o problemă la căutare.';
  } finally {
    loading.value = false;
  }
};

const doSearch = () => {
  router.push({
    name: 'search-results',
    query: { q: localQuery.value || '' },
  });
};

watch(
  () => route.query.q,
  () => {
    load();
  },
);

onMounted(load);
</script>
