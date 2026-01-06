<template>
  <div class="bg-light border-bottom py-3 mb-3">
    <div class="container">
      <nav aria-label="breadcrumb" class="small mb-2">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item">
            <RouterLink to="/">Acasă</RouterLink>
          </li>
          <li class="breadcrumb-item active" aria-current="page">
            {{ category?.name || 'Catalog' }}
          </li>
        </ol>
      </nav>

      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
          <h1 class="h4 mb-1">
            {{ category?.name || 'Catalog produse' }}
          </h1>
          <p v-if="category?.short_description" class="text-muted mb-0 small">
            {{ category.short_description }}
          </p>
        </div>

      <div class="d-flex align-items-center gap-2">
        <span class="small text-muted">
          {{ pagination.total }} produse găsite
        </span>
        <div class="btn-group" role="group" aria-label="View modes">
          <button type="button" class="btn btn-outline-secondary btn-sm" :class="{ active: displayMode === 'grid' }" @click="displayMode = 'grid'">
            <i class="bi bi-grid-3x3-gap"></i>
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" :class="{ active: displayMode === 'list' }" @click="displayMode = 'list'">
            <i class="bi bi-list-ul"></i>
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" :class="{ active: displayMode === 'compact' }" @click="displayMode = 'compact'">
            <i class="bi bi-view-stacked"></i>
          </button>
        </div>
        <select
          v-model="sort"
          class="form-select form-select-sm w-auto"
        >
          <option value="relevance">Relevanță</option>
          <option value="newest">Cele mai noi</option>
            <option value="price_asc">Preț crescător</option>
            <option value="price_desc">Preț descrescător</option>
            <option value="availability">Disponibilitate</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="container mb-4">
    <div class="row">
      <!-- Sidebar filtre -->
      <aside class="col-lg-3 mb-4 mb-lg-0">
        <div class="border rounded p-3 mb-3 bg-white">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 mb-0">Filtre</h2>
            <button
              type="button"
              class="btn btn-link btn-sm text-decoration-none p-0"
              @click="resetFilters"
            >
              Resetare
            </button>
          </div>

          <!-- Branduri -->
          <div v-if="brandFilters.length" class="mb-3">
            <div class="fw-semibold small mb-1">Brand</div>
            <div class="border rounded px-2 py-1" style="max-height: 200px; overflow-y: auto;">
              <div
                v-for="brand in brandFilters"
                :key="brand.id"
                class="form-check small py-1"
              >
                <input
                  class="form-check-input"
                  type="checkbox"
                  :id="`brand-${brand.id}`"
                  :value="brand.id"
                  v-model="selectedBrands"
                >
                <label class="form-check-label" :for="`brand-${brand.id}`">
                  {{ brand.name }}
                </label>
              </div>
            </div>
          </div>

          <!-- Preț -->
          <div v-if="priceFilter.min !== null && priceFilter.max !== null" class="mb-3">
            <div class="fw-semibold small mb-1">Preț</div>
            <div class="d-flex align-items-center gap-2 small mb-2">
              <div class="flex-grow-1">
                <label class="form-label small mb-1">De la</label>
                <input
                  type="number"
                  class="form-control form-control-sm"
                  v-model.number="priceFilter.from"
                  :min="priceFilter.min"
                  :max="priceFilter.max"
                >
              </div>
              <div class="flex-grow-1">
                <label class="form-label small mb-1">Până la</label>
                <input
                  type="number"
                  class="form-control form-control-sm"
                  v-model.number="priceFilter.to"
                  :min="priceFilter.min"
                  :max="priceFilter.max"
                >
              </div>
            </div>
            <div class="small text-muted">
              Interval disponibil:
              {{ priceFilter.min.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
              – {{ priceFilter.max.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
            </div>
          </div>

          <div v-if="!showFilters" class="text-muted small">
            Pentru această categorie nu sunt filtre suplimentare disponibile.
          </div>
        </div>

        <!-- Info B2B/B2C -->
        <div class="border rounded p-3 bg-white small text-muted">
          <div class="fw-semibold mb-1">Informații comerciale</div>
          <ul class="mb-0 ps-3">
            <li>Prețurile pot fi diferite pentru clienți B2B / B2C.</li>
            <li>Condițiile contractuale (discount, termen plată) se aplică la checkout.</li>
            <li>Stocurile și prețurile pot proveni din ERP.</li>
          </ul>
        </div>
      </aside>

      <!-- Listă produse -->
      <section class="col-lg-9">
        <div v-if="loading" class="row g-3">
          <div v-for="n in 8" :key="n" class="col-12 col-sm-6 col-md-4">
            <div class="card h-100 shadow-sm">
              <div class="ratio ratio-4x3 bg-light">
                <div class="skeleton-img"></div>
              </div>
              <div class="card-body">
                <div class="skeleton-line w-50 mb-2"></div>
                <div class="skeleton-line w-75 mb-2"></div>
                <div class="skeleton-line w-25"></div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <div v-else>
          <div v-if="products.length === 0" class="text-center py-5 text-muted">
            Nu am găsit produse pentru filtrele selectate.
          </div>

          <div v-else>
            <div v-if="displayMode === 'grid'" class="row g-3">
              <div v-for="product in products" :key="product.id" class="col-12 col-sm-6 col-md-4">
                <div class="card h-100 shadow-sm">
                  <div class="ratio ratio-4x3 bg-light">
                    <img v-if="product.main_image_url" :src="product.main_image_url" :alt="product.name" class="card-img-top object-fit-cover" loading="lazy">
                    <div v-else class="d-flex align-items-center justify-content-center text-muted small">Fără imagine</div>
                  </div>
                  <div class="card-body d-flex flex-column">
                    <div class="small text-muted mb-1">{{ product.main_category_name || category?.name }}</div>
                    <h2 class="h6 mb-1">{{ product.name }}</h2>
                    <div class="small text-muted mb-2">Cod: {{ product.internal_code || product.sku || '-' }}</div>
                    <div class="mt-2">
                      <div class="input-group input-group-sm">
                        <input type="number" class="form-control" min="1" v-model.number="quantities[product.id]">
                        <select class="form-select" v-model="units[product.id]">
                          <option v-for="u in unitsOfMeasure" :key="u.value" :value="u.value">{{ u.label }}</option>
                        </select>
                        <button class="btn btn-primary" :disabled="addLoading === product.id" @click="addToCart(product)">
                          <span v-if="addLoading === product.id" class="spinner-border spinner-border-sm me-1"></span>
                          Adaugă
                        </button>
                      </div>
                    </div>
                    <div class="mt-auto">
                      <div v-if="product.effective_price" class="mb-2">
                        <div v-if="product.has_promo_price" class="small text-muted">
                          <span class="text-decoration-line-through me-1">
                            {{ product.base_price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                          </span>
                          <span class="fw-semibold text-danger">
                            {{ product.effective_price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                          </span>
                        </div>
                        <div v-else class="fw-semibold">
                          {{ product.effective_price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                        </div>
                      </div>
                      <div v-else class="small text-muted mb-2">Preț indisponibil</div>
                      <RouterLink :to="`/produs/${product.slug}`" class="btn btn-outline-primary btn-sm w-100">Detalii produs</RouterLink>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else-if="displayMode === 'list'" class="row g-3">
              <div v-for="product in products" :key="product.id" class="col-12">
                <div class="card shadow-sm">
                  <div class="row g-0 align-items-center">
                    <div class="col-4 col-sm-3">
                      <div class="ratio ratio-4x3 bg-light">
                        <img v-if="product.main_image_url" :src="product.main_image_url" :alt="product.name" class="img-fluid object-fit-cover rounded-start" loading="lazy">
                        <div v-else class="d-flex align-items-center justify-content-center text-muted small">Fără imagine</div>
                      </div>
                    </div>
                    <div class="col-8 col-sm-9">
                      <div class="p-3 d-flex flex-column h-100">
                        <div class="small text-muted">{{ product.main_category_name || category?.name }}</div>
                        <h2 class="h6 mb-1">{{ product.name }}</h2>
                        <div class="small text-muted mb-2">Cod: {{ product.internal_code || product.sku || '-' }}</div>
                        <div class="mb-2">
                          <div class="input-group input-group-sm">
                            <input type="number" class="form-control" min="1" v-model.number="quantities[product.id]">
                            <select class="form-select" v-model="units[product.id]">
                              <option v-for="u in unitsOfMeasure" :key="u.value" :value="u.value">{{ u.label }}</option>
                            </select>
                            <button class="btn btn-primary" :disabled="addLoading === product.id" @click="addToCart(product)">
                              <span v-if="addLoading === product.id" class="spinner-border spinner-border-sm me-1"></span>
                              Adaugă
                            </button>
                          </div>
                        </div>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                          <div>
                            <div v-if="product.effective_price">
                              <span v-if="product.has_promo_price" class="text-muted text-decoration-line-through me-1">
                                {{ product.base_price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                              </span>
                              <span class="fw-semibold">
                                {{ product.effective_price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                              </span>
                            </div>
                            <div v-else class="small text-muted">Preț indisponibil</div>
                          </div>
                          <RouterLink :to="`/produs/${product.slug}`" class="btn btn-outline-primary btn-sm">Detalii produs</RouterLink>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="list-group">
              <div v-for="product in products" :key="product.id" class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">{{ product.name }}</div>
                  <div class="small text-muted">Cod: {{ product.internal_code || product.sku || '-' }}</div>
                </div>
                <div class="text-end">
                  <div class="small">
                    <span v-if="product.effective_price" class="fw-semibold">
                      {{ product.effective_price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                    </span>
                    <span v-else class="text-muted">Preț indisponibil</span>
                  </div>
                  <div class="mt-2 d-flex justify-content-end">
                    <div class="input-group input-group-sm" style="max-width: 360px;">
                      <input type="number" class="form-control" min="1" v-model.number="quantities[product.id]">
                      <select class="form-select" v-model="units[product.id]">
                        <option v-for="u in unitsOfMeasure" :key="u.value" :value="u.value">{{ u.label }}</option>
                      </select>
                      <button class="btn btn-primary" :disabled="addLoading === product.id" @click="addToCart(product)">
                        <span v-if="addLoading === product.id" class="spinner-border spinner-border-sm me-1"></span>
                        Adaugă
                      </button>
                      <RouterLink :to="`/produs/${product.slug}`" class="btn btn-outline-secondary">Detalii</RouterLink>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Paginare -->
          <nav
            v-if="pagination.last_page > 1"
            class="mt-4 d-flex justify-content-center"
            aria-label="Paginare produse"
          >
            <ul class="pagination pagination-sm mb-0">
              <li
                class="page-item"
                :class="{ disabled: pagination.current_page === 1 }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="goToPage(pagination.current_page - 1)"
                >
                  «
                </button>
              </li>

              <li
                v-for="page in pagination.last_page"
                :key="page"
                class="page-item"
                :class="{ active: page === pagination.current_page }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="goToPage(page)"
                >
                  {{ page }}
                </button>
              </li>

              <li
                class="page-item"
                :class="{ disabled: pagination.current_page === pagination.last_page }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="goToPage(pagination.current_page + 1)"
                >
                  »
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchCategoryPage } from '@/services/catalog';
import { addCartItem } from '@/services/cart';
import { useToast } from 'vue-toastification';
import { setTitle, setMeta, setMetaProperty, setCanonical, setJsonLd } from '@/utils/seo';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const loading = ref(false);
const error = ref('');

const category = ref(null);
const products = ref([]);

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 24,
  total: 0,
});

const displayMode = ref('grid');

const quantities = ref({});
const units = ref({});
const addLoading = ref(false);
const unitsOfMeasure = [
  { value: 'buc', label: 'bucată' },
  { value: 'sac', label: 'sac' },
  { value: 'bax', label: 'bax' },
  { value: 'palet', label: 'palet' }
];

// filtre
const brandFilters = ref([]);
const selectedBrands = ref([]);

const priceFilter = ref({
  min: null,
  max: null,
  from: null,
  to: null,
});

const sort = ref('relevance');

const showFilters = computed(() => {
  return (
    brandFilters.value.length > 0 ||
    (priceFilter.value.min !== null &&
      priceFilter.value.max !== null &&
      priceFilter.value.min !== priceFilter.value.max)
  );
});

const buildParams = () => {
  const params = {
    page: pagination.value.current_page,
    sort: sort.value,
  };

  if (selectedBrands.value.length) {
    params.brands = selectedBrands.value;
  }

  if (
    priceFilter.value.from !== null &&
    priceFilter.value.to !== null &&
    priceFilter.value.from <= priceFilter.value.to
  ) {
    params.min_price = priceFilter.value.from;
    params.max_price = priceFilter.value.to;
  }

  return params;
};

const loadCategory = async () => {
  loading.value = true;
  error.value = '';

  try {
    // Fallback pentru ruta /produse fără slug: nu încărcăm și nu afișăm eroare
    if (!route.params.slug) {
      category.value = { name: 'Catalog produse' };
      products.value = [];
      pagination.value = { current_page: 1, last_page: 1, per_page: 24, total: 0 };
      brandFilters.value = [];
      priceFilter.value = { min: null, max: null, from: null, to: null };
      return;
    }

    const params = buildParams();
    // backend acceptă brand_id (unic); dacă selectăm multiple, trimitem primul
    if (params.brands && params.brands.length) {
      params.brand_id = params.brands[0];
      delete params.brands;
    }
    const data = await fetchCategoryPage(route.params.slug, params);

    category.value = data.category ?? null;

    const prod = data.products ?? {};
    products.value = prod.data ?? prod.items ?? [];
    products.value.forEach(p => {
      if (quantities.value[p.id] === undefined) quantities.value[p.id] = 1;
      if (units.value[p.id] === undefined) units.value[p.id] = 'buc';
    });

    pagination.value = {
      current_page: prod.current_page ?? prod.meta?.current_page ?? 1,
      last_page: prod.last_page ?? prod.meta?.last_page ?? 1,
      per_page: prod.per_page ?? prod.meta?.per_page ?? products.value.length,
      total: prod.total ?? prod.meta?.total ?? products.value.length,
    };

    const filters = data.filters ?? {};

    // branduri
    brandFilters.value = filters.brands ?? [];

    // preț – mapăm atât min/max cât și from/to dacă vin din backend
    const apiPrice = filters.price ?? {};
    const min = apiPrice.min ?? apiPrice.from ?? null;
    const max = apiPrice.max ?? apiPrice.to ?? null;

    // inițializăm o singură dată from/to cu intervalul complet, dacă nu a fost deja setat
    priceFilter.value = {
      min,
      max,
      from:
        priceFilter.value.from !== null
          ? priceFilter.value.from
          : min,
      to:
        priceFilter.value.to !== null
          ? priceFilter.value.to
          : max,
    };
    const title = (category.value?.meta_title || category.value?.name || 'Catalog produse') + ' | ' + (document?.querySelector('meta[name=\"application-name\"]')?.getAttribute('content') || '');
    const desc = category.value?.meta_description || category.value?.short_description || 'Catalog de produse';
    const url = window.location.origin + (router.resolve({ name: 'category', params: { slug: route.params.slug } }).href || window.location.pathname);
    setTitle(title);
    setMeta('description', desc);
    setMetaProperty('og:type', 'website');
    setMetaProperty('og:title', title);
    setMetaProperty('og:description', desc);
    setMetaProperty('og:url', url);
    setCanonical(url);
    const breadcrumb = {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', position: 1, name: 'Acasă', item: window.location.origin + '/' },
        { '@type': 'ListItem', position: 2, name: category.value?.name || 'Catalog produse', item: url }
      ]
    };
    const collection = {
      '@context': 'https://schema.org',
      '@type': 'CollectionPage',
      'name': category.value?.name || 'Catalog produse',
      'url': url
    };
    setJsonLd({ '@graph': [breadcrumb, collection] });
  } catch (e) {
    console.error('Category load error', e);
    error.value = 'Nu s-a putut încărca această categorie.';
  } finally {
    loading.value = false;
  }
};

const addToCart = async (product) => {
  try {
    addLoading.value = product.id;
    const qty = Number(quantities.value[product.id]) || 1;
    await addCartItem({
      product_id: product.id,
      quantity: qty,
      unit: units.value[product.id]
    });
    toast.success('Produs adăugat în coș');
  } catch (e) {
    toast.error('Nu s-a putut adăuga produsul în coș');
  } finally {
    addLoading.value = false;
  }
};

const goToPage = (page) => {
  if (
    page === pagination.value.current_page ||
    page < 1 ||
    page > pagination.value.last_page
  ) {
    return;
  }

  pagination.value.current_page = page;

  router.push({
    name: 'category',
    params: { slug: route.params.slug },
    query: { ...route.query, page },
  });

  loadCategory();
};

const resetFilters = () => {
  selectedBrands.value = [];
  if (priceFilter.value.min !== null && priceFilter.value.max !== null) {
    priceFilter.value.from = priceFilter.value.min;
    priceFilter.value.to = priceFilter.value.max;
  } else {
    priceFilter.value.from = null;
    priceFilter.value.to = null;
  }
  pagination.value.current_page = 1;
  loadCategory();
};

onMounted(() => {
  const savedMode = localStorage.getItem('categoryDisplayMode');
  if (savedMode === 'grid' || savedMode === 'list' || savedMode === 'compact') {
    displayMode.value = savedMode;
  }
  pagination.value.current_page = Number(route.query.page || 1);
  loadCategory();
});

// când se schimbă slug-ul (altă categorie)
watch(
  () => route.params.slug,
  () => {
    category.value = null;
    products.value = [];
    pagination.value.current_page = 1;
    selectedBrands.value = [];
    priceFilter.value = { min: null, max: null, from: null, to: null };
    loadCategory();
});

// re-încarcă produsele la schimbarea sortării / brandurilor
watch([selectedBrands, sort], () => {
  pagination.value.current_page = 1;
  loadCategory();
});

watch(displayMode, (val) => {
  localStorage.setItem('categoryDisplayMode', val);
});
</script>

<style scoped>
.skeleton-img {
  height: 100%;
  width: 100%;
  position: relative;
  overflow: hidden;
}
.skeleton-line {
  height: 12px;
  border-radius: 4px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e6e6e6 37%, #f0f0f0 63%);
  background-size: 400% 100%;
  animation: shimmer 1.4s ease infinite;
}
@keyframes shimmer {
  0% { background-position: 100% 0; }
  100% { background-position: -100% 0; }
}
.input-group .form-select {
  max-width: 120px;
}
.input-group .btn {
  white-space: nowrap;
}
</style>
