<template>
  <div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <RouterLink to="/">Acasă</RouterLink>
        </li>
        <li class="breadcrumb-item">
          <RouterLink to="/produse">Catalog</RouterLink>
        </li>
        <li
          v-if="!isAllProductsPage && category"
          class="breadcrumb-item active"
          aria-current="page"
        >
          {{ category.name }}
        </li>
        <li
          v-else
          class="breadcrumb-item active"
          aria-current="page"
        >
          Toate produsele
        </li>
      </ol>
    </nav>

    <!-- Titlu -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">
          {{ pageTitle }}
        </h1>
        <p class="small text-muted mb-0" v-if="category && category.description">
          {{ category.description }}
        </p>
      </div>
    </div>

    <!-- Mesaje -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>
    <div v-if="loading" class="alert alert-info">
      Se încarcă produsele...
    </div>

    <!-- Filtre simple -->
    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-3" @submit.prevent="applyFilters">
          <div class="col-md-3">
            <label class="form-label small">Brand</label>
            <input
              v-model="filters.brand"
              type="text"
              class="form-control form-control-sm"
              placeholder="ex: Bosch"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label small">Disponibilitate</label>
            <select
              v-model="filters.availability"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="in_stock">În stoc</option>
              <option value="limited">Stoc limitat</option>
              <option value="on_order">La comandă</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Preț minim</label>
            <input
              v-model="filters.min_price"
              type="number"
              min="0"
              step="0.01"
              class="form-control form-control-sm"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label small">Preț maxim</label>
            <input
              v-model="filters.max_price"
              type="number"
              min="0"
              step="0.01"
              class="form-control form-control-sm"
            />
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-outline-primary btn-sm">
              Aplică filtre
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Listă produse -->
    <div v-if="!loading && products.length === 0" class="text-muted">
      Nu există produse pentru criteriile selectate.
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
            <h2 class="h6 mb-1">
              {{ product.name }}
            </h2>
            <div class="small text-muted mb-2">
              Cod: {{ product.internal_code || product.code || '—' }}
            </div>
            <div class="mt-auto">
              <div class="small text-muted mb-1">
                <span v-if="product.stock_status === 'in_stock'">
                  În stoc
                </span>
                <span v-else-if="product.stock_status === 'on_order'">
                  La comandă
                </span>
                <span v-else-if="product.stock_status === 'limited'">
                  Stoc limitat
                </span>
                <span v-else>
                  Disponibilitate necunoscută
                </span>
              </div>

              <div
                v-if="product.promo_price || product.final_price"
                class="small text-muted"
              >
                <span class="text-decoration-line-through me-1" v-if="product.list_price">
                  {{ formatPrice(product.list_price) }}
                </span>
                <span class="fw-semibold">
                  {{ formatPrice(product.promo_price ?? product.final_price) }} RON
                </span>
              </div>
              <div v-else class="fw-semibold mb-1">
                {{ formatPrice(product.final_price ?? product.list_price ?? product.price) }}
                <span
                  v-if="product.final_price || product.list_price || product.price"
                >
                  RON
                </span>
              </div>

              <RouterLink
                v-if="product.slug"
                :to="`/produs/${product.slug}`"
                class="btn btn-outline-primary btn-sm mt-2"
              >
                Detalii produs
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Paginare -->
    <div
      v-if="meta.last_page > 1"
      class="d-flex justify-content-between align-items-center mt-3"
    >
      <div class="small text-muted">
        Pagina {{ meta.current_page }} din {{ meta.last_page }} –
        {{ meta.total }} produse
      </div>
      <div class="btn-group">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="meta.current_page === 1"
          @click="changePage(meta.current_page - 1)"
        >
          « Anterioară
        </button>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="meta.current_page === meta.last_page"
          @click="changePage(meta.current_page + 1)"
        >
          Următoarea »
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { fetchCategory, searchProducts } from '@/services/catalog';

const route = useRoute();

const loading = ref(false);
const error = ref('');

const category = ref(null);
const products = ref([]);
const meta = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

const filters = ref({
  brand: '',
  availability: '',
  min_price: '',
  max_price: '',
});

const isAllProductsPage = computed(() => !route.params.slug);

const pageTitle = computed(() => {
  if (isAllProductsPage.value) {
    return 'Toate produsele';
  }
  return category.value?.name || 'Categorie produse';
});

const formatPrice = (value) => {
  if (value == null) return '-';
  const num = Number(value);
  if (Number.isNaN(num)) return String(value);
  return num.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const loadData = async (page = 1) => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      page,
      brand: filters.value.brand || undefined,
      availability: filters.value.availability || undefined,
      min_price: filters.value.min_price || undefined,
      max_price: filters.value.max_price || undefined,
    };

    if (isAllProductsPage.value) {
      // /produse – listă generală → folosim searchProducts
      const data = await searchProducts(params);

      let items = [];
      let m = { current_page: 1, last_page: 1, total: 0 };

      if (Array.isArray(data)) {
        items = data;
        m.total = data.length;
      } else if (Array.isArray(data.data)) {
        items = data.data;
        m = data.meta || m;
      } else if (Array.isArray(data.products)) {
        items = data.products;
        m = data.meta || m;
      }

      products.value = items;
      meta.value = m;
      category.value = {
        name: 'Toate produsele',
        slug: null,
      };
    } else {
      // /categorie/:slug – categorie specifică
      const slug = route.params.slug;
      const data = await fetchCategory(slug, params);

      category.value = data.category ?? data;

      let itemsSource =
        data.products ??
        data.items ??
        data.data ??
        [];

      if (!Array.isArray(itemsSource) && Array.isArray(itemsSource.data)) {
        itemsSource = itemsSource.data;
      }

      products.value = Array.isArray(itemsSource) ? itemsSource : [];

      const m = data.meta || data.pagination || {
        current_page: 1,
        last_page: 1,
        total: products.value.length,
      };
      meta.value = m;
    }
  } catch (e) {
    console.error('Category / products load error', e);
    if (e.response?.status === 404) {
      error.value = 'Categoria nu a fost găsită.';
    } else {
      error.value =
        e.response?.data?.message ||
        'Nu s-au putut încărca produsele.';
    }
  } finally {
    loading.value = false;
  }
};

const changePage = (page) => {
  loadData(page);
};

const applyFilters = () => {
  loadData(1);
};

watch(
  () => route.params.slug,
  () => {
    loadData(1);
  },
);

onMounted(() => {
  loadData(1);
});
</script>
