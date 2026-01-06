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
      <!-- Sidebar filtre Desktop -->
      <aside class="col-lg-3 mb-4 mb-lg-0 d-none d-lg-block">
        <div class="border rounded p-3 mb-3 bg-white shadow-sm">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0 fw-bold">Filtre</h2>
            <button
              type="button"
              class="btn btn-link btn-sm text-decoration-none p-0"
              @click="resetFilters"
              v-if="showFilters"
            >
              Resetare
            </button>
          </div>

          <!-- Branduri -->
          <div v-if="brandFilters.length" class="mb-4">
            <div class="fw-semibold small mb-2">Brand</div>
            <input 
              v-if="brandFilters.length > 5"
              type="text" 
              class="form-control form-control-sm mb-2" 
              placeholder="Caută brand..." 
              v-model="brandSearch"
            >
            <div class="border rounded px-2 py-1 bg-light" style="max-height: 200px; overflow-y: auto;">
              <div
                v-for="brand in filteredBrands"
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
                <label class="form-check-label w-100" :for="`brand-${brand.id}`" style="cursor: pointer;">
                  {{ brand.name }}
                  <span class="text-muted ms-1" v-if="brand.count">({{ brand.count }})</span>
                </label>
              </div>
              <div v-if="!filteredBrands.length" class="small text-muted p-2">
                Niciun brand găsit.
              </div>
            </div>
          </div>

          <!-- Preț -->
          <div v-if="priceFilter.min !== null && priceFilter.max !== null" class="mb-3">
            <div class="fw-semibold small mb-2">Interval preț</div>
            <div class="d-flex align-items-center gap-2 small mb-2">
              <div class="flex-grow-1">
                <input
                  type="number"
                  class="form-control form-control-sm"
                  v-model.number="priceFilter.from"
                  :min="priceFilter.min"
                  :max="priceFilter.max"
                  placeholder="Min"
                >
              </div>
              <span class="text-muted">-</span>
              <div class="flex-grow-1">
                <input
                  type="number"
                  class="form-control form-control-sm"
                  v-model.number="priceFilter.to"
                  :min="priceFilter.min"
                  :max="priceFilter.max"
                  placeholder="Max"
                >
              </div>
            </div>
            <button class="btn btn-outline-primary btn-sm w-100 mt-2" @click="loadCategory">
              Aplică preț
            </button>
          </div>

          <div v-if="!showFilters && !brandFilters.length" class="text-muted small">
            Fără filtre disponibile.
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
                <div class="card h-100 border-0 shadow-sm product-card">
                  <div class="ratio ratio-4x3 bg-light position-relative">
                    <img v-if="product.main_image_url" :src="product.main_image_url" :alt="product.name" class="card-img-top object-fit-cover rounded-top" loading="lazy">
                    <div v-else class="d-flex align-items-center justify-content-center text-muted small w-100 h-100">Fără imagine</div>
                    
                    <!-- Discount Badge -->
                     <span v-if="product.has_promo_price || product.discount_percent" class="position-absolute top-0 start-0 m-2 badge bg-danger rounded-pill shadow-sm">
                        <span v-if="product.discount_percent">-{{ product.discount_percent }}%</span>
                        <span v-else>Promo</span>
                    </span>
                  </div>
                  <div class="card-body d-flex flex-column p-3">
                    <div class="small text-muted mb-1">{{ product.main_category_name || category?.name }}</div>
                    <h3 class="h6 mb-2 fw-bold text-truncate-2">
                      <RouterLink :to="`/produs/${product.slug}`" class="text-decoration-none text-dark stretched-link">
                        {{ product.name }}
                      </RouterLink>
                    </h3>
                    <div class="small text-muted mb-2">Cod: {{ product.internal_code || product.sku || '-' }}</div>
                    
                    <div class="mb-2" v-if="product.average_rating || product.rating">
                      <i v-for="n in 5" :key="'star-' + product.id + '-' + n" :class="(Number(product.average_rating || product.rating) >= n) ? 'bi-star-fill text-warning me-1' : 'bi-star text-muted me-1'"></i>
                      <span class="text-muted small ms-1" v-if="product.reviews_count">({{ product.reviews_count }})</span>
                    </div>

                    <div class="mt-auto pt-2 border-top">
                         <div class="d-flex justify-content-between align-items-center">
                            <div class="price-container">
                                <div v-if="product.effective_price">
                                    <span v-if="product.has_promo_price" class="text-danger fw-bold fs-5 me-2">{{ formatPrice(product.effective_price) }}</span>
                                    <span v-else class="fw-bold fs-5 text-primary">{{ formatPrice(product.effective_price) }}</span>
                                    <span v-if="product.has_promo_price" class="text-decoration-line-through text-muted small d-block">{{ formatPrice(product.base_price) }}</span>
                                </div>
                                <div v-else class="text-muted small">Preț indisponibil</div>
                            </div>
                            <button class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; z-index: 2; position: relative;" title="Adaugă în coș" @click.prevent="addToCart(product)">
                                <i class="bi bi-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- List Mode -->
            <div v-else-if="displayMode === 'list'" class="d-flex flex-column gap-3">
                <div v-for="product in products" :key="product.id" class="card border-0 shadow-sm overflow-hidden">
                    <div class="row g-0">
                        <div class="col-md-3 bg-light position-relative">
                             <div class="ratio ratio-4x3 h-100">
                                <img v-if="product.main_image_url" :src="product.main_image_url" :alt="product.name" class="object-fit-cover w-100 h-100" loading="lazy">
                                <div v-else class="d-flex align-items-center justify-content-center text-muted small w-100 h-100">Fără imagine</div>
                             </div>
                              <span v-if="product.has_promo_price || product.discount_percent" class="position-absolute top-0 start-0 m-2 badge bg-danger rounded-pill shadow-sm">
                                <span v-if="product.discount_percent">-{{ product.discount_percent }}%</span>
                                <span v-else>Promo</span>
                            </span>
                        </div>
                        <div class="col-md-9">
                            <div class="card-body h-100 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                     <div>
                                        <div class="small text-muted mb-1">{{ product.main_category_name || category?.name }}</div>
                                        <h3 class="h5 mb-1 fw-bold">
                                            <RouterLink :to="`/produs/${product.slug}`" class="text-decoration-none text-dark">
                                                {{ product.name }}
                                            </RouterLink>
                                        </h3>
                                        <div class="small text-muted">Cod: {{ product.internal_code || product.sku || '-' }}</div>
                                     </div>
                                     <div class="text-end">
                                          <div class="mb-1" v-if="product.average_rating || product.rating">
                                              <i v-for="n in 5" :key="'star-list-' + product.id + '-' + n" :class="(Number(product.average_rating || product.rating) >= n) ? 'bi-star-fill text-warning me-1' : 'bi-star text-muted me-1'"></i>
                                          </div>
                                     </div>
                                </div>
                                <p class="card-text text-muted small mb-3 flex-grow-1 line-clamp-2">{{ product.short_description || product.description }}</p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-auto border-top pt-3">
                                     <div class="price-container">
                                        <div v-if="product.effective_price">
                                            <span v-if="product.has_promo_price" class="text-danger fw-bold fs-4 me-2">{{ formatPrice(product.effective_price) }}</span>
                                            <span v-else class="fw-bold fs-4 text-primary">{{ formatPrice(product.effective_price) }}</span>
                                            <span v-if="product.has_promo_price" class="text-decoration-line-through text-muted small">{{ formatPrice(product.base_price) }}</span>
                                        </div>
                                         <div v-else class="text-muted small">Preț indisponibil</div>
                                    </div>
                                    <button class="btn btn-primary d-flex align-items-center gap-2" @click.prevent="addToCart(product)">
                                        <i class="bi bi-cart-plus"></i> Adaugă în coș
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compact Mode -->
            <div v-else class="row row-cols-2 row-cols-md-4 row-cols-lg-5 g-2">
                 <div v-for="product in products" :key="product.id" class="col">
                    <div class="card h-100 border-0 shadow-sm product-card-compact">
                         <div class="ratio ratio-1x1 bg-light position-relative">
                            <img v-if="product.main_image_url" :src="product.main_image_url" :alt="product.name" class="card-img-top object-fit-cover rounded-top" loading="lazy">
                             <div v-else class="d-flex align-items-center justify-content-center text-muted small w-100 h-100">Fără imagine</div>
                        </div>
                        <div class="card-body p-2 d-flex flex-column">
                             <h3 class="h6 mb-1 fw-bold text-truncate small">
                                <RouterLink :to="`/produs/${product.slug}`" class="text-decoration-none text-dark stretched-link">
                                    {{ product.name }}
                                </RouterLink>
                            </h3>
                             <div class="mt-auto">
                                <div v-if="product.effective_price">
                                    <span v-if="product.has_promo_price" class="text-danger fw-bold small">{{ formatPrice(product.effective_price) }}</span>
                                    <span v-else class="fw-bold text-primary small">{{ formatPrice(product.effective_price) }}</span>
                                </div>
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

  <!-- SEO Content Section -->
  <section class="bg-white py-5 border-top" v-if="category?.description">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="prose" v-html="category.description"></div>
            </div>
        </div>
    </div>
  </section>

  <div class="offcanvas offcanvas-start" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="filterOffcanvasLabel">Filtre</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
       <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 fw-bold">Filtre active</h6>
        <button
          type="button"
          class="btn btn-link btn-sm text-decoration-none p-0"
          @click="resetFilters"
          v-if="showFilters"
        >
          Resetare
        </button>
      </div>

      <!-- Branduri -->
      <div v-if="brandFilters.length" class="mb-4">
        <div class="fw-semibold small mb-2">Brand</div>
        <input 
          v-if="brandFilters.length > 5"
          type="text" 
          class="form-control form-control-sm mb-2" 
          placeholder="Caută brand..." 
          v-model="brandSearch"
        >
        <div class="border rounded px-2 py-1 bg-light" style="max-height: 200px; overflow-y: auto;">
          <div
            v-for="brand in filteredBrands"
            :key="'mob-' + brand.id"
            class="form-check small py-1"
          >
            <input
              class="form-check-input"
              type="checkbox"
              :id="`mob-brand-${brand.id}`"
              :value="brand.id"
              v-model="selectedBrands"
            >
            <label class="form-check-label w-100" :for="`mob-brand-${brand.id}`">
              {{ brand.name }}
            </label>
          </div>
        </div>
      </div>

      <!-- Preț -->
      <div v-if="priceFilter.min !== null && priceFilter.max !== null" class="mb-3">
        <div class="fw-semibold small mb-2">Interval preț</div>
        <div class="d-flex align-items-center gap-2 small mb-2">
          <div class="flex-grow-1">
            <input
              type="number"
              class="form-control form-control-sm"
              v-model.number="priceFilter.from"
              :min="priceFilter.min"
              :max="priceFilter.max"
              placeholder="Min"
            >
          </div>
          <span class="text-muted">-</span>
          <div class="flex-grow-1">
            <input
              type="number"
              class="form-control form-control-sm"
              v-model.number="priceFilter.to"
              :min="priceFilter.min"
              :max="priceFilter.max"
              placeholder="Max"
            >
          </div>
        </div>
        <button class="btn btn-outline-primary btn-sm w-100 mt-2" @click="loadCategory" data-bs-dismiss="offcanvas">
          Aplică filtre
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchCategoryPage } from '@/services/catalog';
import { addCartItem } from '@/services/cart';
import { useToast } from 'vue-toastification';
import { setTitle, setMeta, setMetaProperty, setCanonical, setJsonLd, setLink } from '@/utils/seo';

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

// Helper formatting
const formatPrice = (value) => {
    if (value === undefined || value === null) return '';
    return Number(value).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' RON';
};

// filtre
const brandFilters = ref([]);
const selectedBrands = ref([]);
const brandSearch = ref('');

const filteredBrands = computed(() => {
  if (!brandSearch.value) return brandFilters.value;
  return brandFilters.value.filter(b => 
    b.name.toLowerCase().includes(brandSearch.value.toLowerCase())
  );
});

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
    const title = (category.value?.meta_title || category.value?.name || 'Catalog produse') + ' | ' + (document?.querySelector('meta[name="application-name"]')?.getAttribute('content') || '');
    const desc = category.value?.meta_description || category.value?.short_description || 'Catalog de produse';
    const currentUrl = window.location.href;
    setTitle(title);
    setMeta('description', desc);
    setMetaProperty('og:type', 'website');
    setMetaProperty('og:title', title);
    setMetaProperty('og:description', desc);
    setMetaProperty('og:url', currentUrl);
    setCanonical(currentUrl);
    const prevUrl = pagination.value.current_page > 1
      ? window.location.origin + (router.resolve({
          name: 'category',
          params: { slug: route.params.slug },
          query: { ...route.query, page: pagination.value.current_page - 1 }
        }).href || '')
      : null;
    const nextUrl = pagination.value.current_page < pagination.value.last_page
      ? window.location.origin + (router.resolve({
          name: 'category',
          params: { slug: route.params.slug },
          query: { ...route.query, page: pagination.value.current_page + 1 }
        }).href || '')
      : null;
    if (prevUrl) setLink('prev', prevUrl); else if (typeof document !== 'undefined') { const el = document.querySelector('link[rel="prev"]'); if (el) el.remove(); }
    if (nextUrl) setLink('next', nextUrl); else if (typeof document !== 'undefined') { const el = document.querySelector('link[rel="next"]'); if (el) el.remove(); }
    const breadcrumb = {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', position: 1, name: 'Acasă', item: window.location.origin + '/' },
        { '@type': 'ListItem', position: 2, name: category.value?.name || 'Catalog produse', item: currentUrl }
      ]
    };
    const collection = {
      '@context': 'https://schema.org',
      '@type': 'CollectionPage',
      'name': category.value?.name || 'Catalog produse',
      'url': currentUrl
    };
    const itemList = {
      '@context': 'https://schema.org',
      '@type': 'ItemList',
      'itemListElement': products.value.map((product, index) => ({
        '@type': 'ListItem',
        'position': index + 1,
        'item': {
          '@type': 'Product',
          'name': product.name,
          'url': window.location.origin + (router.resolve({ name: 'product-details', params: { slug: product.slug } }).href || ('/produs/' + product.slug))
        }
      }))
    };
    setJsonLd({ '@graph': [breadcrumb, collection, itemList] });
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
