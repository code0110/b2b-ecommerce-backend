<template>
  <div class="py-3">
    <div class="container">
      <nav class="small mb-2 text-muted">
        <RouterLink to="/">Acasă</RouterLink>
        <span class="mx-1">/</span>
        <span>{{ category?.name || 'Categorie' }}</span>
      </nav>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h1 class="h4 mb-1">
            {{ category?.name || 'Categorie produse' }}
          </h1>
          <p class="text-muted small mb-0">
            Filtrați după brand, preț și disponibilitate pentru a găsi produsele potrivite.
          </p>
        </div>
      </div>

      <div class="row">
        <!-- Sidebar filtre -->
        <aside class="col-md-3 mb-3">
          <div class="border rounded-3 p-3 bg-white mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-semibold small">Filtre</span>
              <button
                v-if="hasActiveFilters"
                type="button"
                class="btn btn-link btn-sm p-0 small"
                @click="resetFilters"
              >
                Resetează
              </button>
            </div>

            <!-- Branduri -->
            <div class="mb-3">
              <div class="small fw-semibold mb-1">Brand</div>
              <div class="small text-muted mb-1" v-if="!filters.brands?.length">
                Nu există branduri disponibile.
              </div>
              <div v-else class="filter-scroll">
                <div
                  v-for="brand in filters.brands"
                  :key="brand.id"
                  class="form-check small mb-1"
                >
                  <input
                    class="form-check-input"
                    type="checkbox"
                    :id="`brand-${brand.id}`"
                    :value="brand.id"
                    v-model="form.brands"
                    @change="reloadProducts"
                  />
                  <label
                    class="form-check-label d-flex justify-content-between w-100"
                    :for="`brand-${brand.id}`"
                  >
                    <span>{{ brand.name }}</span>
                    <span class="text-muted ms-2">{{ brand.count }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Preț -->
            <div class="mb-3">
              <div class="small fw-semibold mb-1">Preț (RON)</div>
              <div class="d-flex align-items-center gap-2 small">
                <input
                  type="number"
                  class="form-control form-control-sm"
                  v-model.number="form.min_price"
                  :placeholder="Math.floor(filters.price?.min || 0)"
                  @change="reloadProducts"
                />
                <span>–</span>
                <input
                  type="number"
                  class="form-control form-control-sm"
                  v-model.number="form.max_price"
                  :placeholder="Math.ceil(filters.price?.max || 0)"
                  @change="reloadProducts"
                />
              </div>
            </div>

            <!-- Stoc -->
            <div class="mb-2">
              <div class="small fw-semibold mb-1">Disponibilitate</div>
              <select
                v-model="form.stock_status"
                class="form-select form-select-sm"
                @change="reloadProducts"
              >
                <option value="">Toate</option>
                <option value="in_stock">În stoc</option>
                <option value="out_of_stock">Indisponibil</option>
              </select>
            </div>
          </div>
        </aside>

        <!-- Listă produse -->
        <section class="col-md-9">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="small text-muted">
              <span v-if="products?.data?.length">
                {{ products.meta.from }}–{{ products.meta.to }} din {{ products.meta.total }} produse
              </span>
              <span v-else>
                Nicio înregistrare găsită.
              </span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <label class="small text-muted mb-0">Sortează după</label>
              <select
                v-model="form.sort"
                class="form-select form-select-sm"
                @change="reloadProducts"
              >
                <option value="">Relevanță</option>
                <option value="price_asc">Preț crescător</option>
                <option value="price_desc">Preț descrescător</option>
                <option value="newest">Cele mai noi</option>
              </select>
            </div>
          </div>

          <div v-if="loading" class="text-muted small py-3">
            Se încarcă produsele...
          </div>
          <div v-else-if="error" class="alert alert-danger small py-2">
            {{ error }}
          </div>

          <div v-else class="row g-3">
            <div
              v-for="p in products.data"
              :key="p.id"
              class="col-lg-3 col-md-4 col-sm-6"
            >
              <div class="card h-100">
                <div class="card-body d-flex flex-column">
                  <div class="small text-muted mb-1">
                    {{ p.main_category?.name || category?.name }}
                  </div>
                  <h2 class="h6 mb-1">
                    {{ p.name }}
                  </h2>
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
                Nu există produse care să corespundă filtrelor selectate.
              </div>
            </div>
          </div>

          <!-- Paginare simplă -->
          <nav
            v-if="products.meta && products.meta.last_page > 1"
            class="mt-3"
          >
            <ul class="pagination pagination-sm mb-0">
              <li
                class="page-item"
                :class="{ disabled: products.meta.current_page === 1 }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="changePage(products.meta.current_page - 1)"
                >
                  «
                </button>
              </li>

              <li
                v-for="page in pages"
                :key="page"
                class="page-item"
                :class="{ active: page === products.meta.current_page }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="changePage(page)"
                >
                  {{ page }}
                </button>
              </li>

              <li
                class="page-item"
                :class="{ disabled: products.meta.current_page === products.meta.last_page }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="changePage(products.meta.current_page + 1)"
                >
                  »
                </button>
              </li>
            </ul>
          </nav>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchCategoryPage } from '@/services/catalog';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const error = ref('');
const category = ref(null);
const filters = reactive({
  brands: [],
  price: { min: 0, max: 0 },
});
const products = reactive({
  data: [],
  meta: null,
});

const form = reactive({
  page: 1,
  brands: [],
  min_price: null,
  max_price: null,
  stock_status: '',
  sort: '',
});

const hasActiveFilters = computed(() => {
  return (
    form.brands.length ||
    form.min_price !== null ||
    form.max_price !== null ||
    !!form.stock_status ||
    !!form.sort
  );
});

const pages = computed(() => {
  if (!products.meta) return [];
  const total = products.meta.last_page;
  const current = products.meta.current_page;
  const arr = [];

  const start = Math.max(1, current - 2);
  const end = Math.min(total, current + 2);

  for (let i = start; i <= end; i += 1) arr.push(i);
  return arr;
});

const formatMoney = (value) =>
  (Number(value) || 0).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

const loadCategory = async () => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      page: form.page,
      sort: form.sort || undefined,
      stock_status: form.stock_status || undefined,
      min_price: form.min_price || undefined,
      max_price: form.max_price || undefined,
      brands: form.brands.length ? form.brands.join(',') : undefined,
    };

    const data = await fetchCategoryPage(route.params.slug, params);

    category.value = data.category;
    filters.brands = data.filters?.brands || [];
    filters.price = data.filters?.price || { min: 0, max: 0 };

    products.data = data.products?.data || [];
    products.meta = data.products?.meta || null;
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca produsele pentru această categorie.';
  } finally {
    loading.value = false;
  }
};

const reloadProducts = () => {
  form.page = 1;
  loadCategory();
};

const resetFilters = () => {
  form.brands = [];
  form.min_price = null;
  form.max_price = null;
  form.stock_status = '';
  form.sort = '';
  form.page = 1;
  loadCategory();
};

const changePage = (page) => {
  if (!products.meta) return;
  if (page < 1 || page > products.meta.last_page) return;
  form.page = page;
  loadCategory();
};

watch(
  () => route.params.slug,
  () => {
    form.page = 1;
    resetFilters();
  },
);

onMounted(loadCategory);
</script>

<style scoped>
.filter-scroll {
  max-height: 150px;
  overflow-y: auto;
}
</style>
