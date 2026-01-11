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
            <button class="btn btn-orange btn-sm w-100 mt-2" @click="loadCategory">
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
              <div class="d-lg-none mb-3">
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-center gap-2"
                  data-bs-toggle="offcanvas"
                  data-bs-target="#filterOffcanvas"
                  aria-controls="filterOffcanvas"
                >
                  <i class="bi bi-funnel"></i>
                  Filtre
                </button>
<<<<<<< HEAD
=======
              </div>

            <div v-if="displayMode === 'grid'" class="row g-3">
              <div v-for="product in products" :key="product.id" class="col-12 col-sm-6 col-md-4">
                <div class="card h-100 border-0 shadow-sm dd-product-card product-card">
                  <div class="ratio ratio-4x3 bg-light position-relative">
                    <img v-if="product.main_image_url" :src="product.main_image_url" :alt="product.name" class="card-img-top object-fit-cover rounded-top" loading="lazy">
                    <div v-else class="d-flex align-items-center justify-content-center text-muted small w-100 h-100">Fără imagine</div>
                    
                    <!-- Discount Badge -->
                     <span v-if="product.hasDiscount || product.discountPercent" class="position-absolute top-0 start-0 m-2 badge bg-danger rounded-pill shadow-sm">
                        <span v-if="product.discountPercent">-{{ product.discountPercent }}%</span>
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
                                <div v-if="getUnitPrice(product) !== null">
                                    <span v-if="getListPrice(product) !== null && getListPrice(product) > getUnitPrice(product)" class="text-decoration-line-through text-muted small d-block">{{ formatPrice(getListPrice(product)) }}</span>
                                    <span :class="(getListPrice(product) !== null && getListPrice(product) > getUnitPrice(product)) ? 'text-danger fw-bold fs-5' : 'fw-bold fs-5 text-dd-blue'">{{ formatPrice(getUnitPrice(product)) }}</span>
                                </div>
                                <div v-else class="text-muted small">Preț indisponibil</div>
                            </div>
                            <button
                              class="btn btn-orange btn-sm rounded-circle d-flex align-items-center justify-content-center"
                              style="width: 32px; height: 32px; z-index: 2; position: relative;"
                              title="Adaugă în coș"
                              :disabled="addLoading === product.id"
                              @click.prevent="addToCart(product)"
                            >
                                <span v-if="addLoading === product.id" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <i v-else class="bi bi-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                  </div>
                </div>
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
              </div>

            <!-- Grid Mode -->
            <div v-if="displayMode === 'grid'" class="row g-3">
                <div v-for="product in products" :key="product.id" class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12">
                    <ProductCard :product="product" />
                </div>
            </div>

            <!-- List Mode -->
            <div v-else-if="displayMode === 'list'" class="d-flex flex-column gap-3">
<<<<<<< HEAD
                <div v-for="product in products" :key="product.id">
                   <ProductCardList :product="product" />
=======
                <div v-for="product in products" :key="product.id" class="card border-0 shadow-sm overflow-hidden">
                    <div class="row g-0">
                        <div class="col-md-3 bg-light position-relative">
                             <div class="ratio ratio-4x3 h-100">
                                <img v-if="product.main_image_url" :src="product.main_image_url" :alt="product.name" class="object-fit-cover w-100 h-100" loading="lazy">
                                <div v-else class="d-flex align-items-center justify-content-center text-muted small w-100 h-100">Fără imagine</div>
                             </div>
                              <span v-if="product.hasDiscount || product.discountPercent" class="position-absolute top-0 start-0 m-2 badge bg-danger rounded-pill shadow-sm">
                                <span v-if="product.discountPercent">-{{ product.discountPercent }}%</span>
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
                                        <div v-if="getUnitPrice(product) !== null">
                                            <span v-if="getListPrice(product) !== null && getListPrice(product) > getUnitPrice(product)" class="text-decoration-line-through text-muted small d-block">{{ formatPrice(getListPrice(product)) }}</span>
                                            <span :class="(getListPrice(product) !== null && getListPrice(product) > getUnitPrice(product)) ? 'text-danger fw-bold fs-4' : 'fw-bold fs-4 text-dd-blue'">{{ formatPrice(getUnitPrice(product)) }}</span>
                                        </div>
                                         <div v-else class="text-muted small">Preț indisponibil</div>
                                    </div>
                                    <button class="btn btn-orange d-flex align-items-center gap-2" :disabled="addLoading === product.id" @click.prevent="addToCart(product)">
                                        <span v-if="addLoading === product.id" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        <i v-else class="bi bi-cart-plus"></i>
                                        Adaugă în coș
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
                </div>
            </div>

            <!-- Compact Mode -->
            <div v-else class="row row-cols-2 row-cols-md-4 row-cols-lg-5 g-2">
                 <div v-for="product in products" :key="product.id" class="col">
<<<<<<< HEAD
                    <ProductCard :product="product" :compact="true" />
=======
                    <div class="card h-100 border-0 shadow-sm dd-product-card product-card-compact">
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
                                <div v-if="getUnitPrice(product) !== null">
                                     <span v-if="getListPrice(product) !== null && getListPrice(product) > getUnitPrice(product)" class="text-decoration-line-through text-muted small d-block" style="font-size: 0.75rem;">{{ formatPrice(getListPrice(product)) }}</span>
                                    <span :class="(getListPrice(product) !== null && getListPrice(product) > getUnitPrice(product)) ? 'text-danger fw-bold small' : 'fw-bold small'">{{ formatPrice(getUnitPrice(product)) }}</span>
                                </div>
                                <button class="btn btn-orange btn-sm w-100 mt-2" :disabled="addLoading === product.id" @click.prevent="addToCart(product)">
                                    <span v-if="addLoading === product.id" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    <i v-else class="bi bi-cart-plus"></i>
                                    Adaugă
                                </button>
                            </div>
                        </div>
                    </div>
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
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
        <button class="btn btn-orange btn-sm w-100 mt-2" @click="loadCategory" data-bs-dismiss="offcanvas">
          Aplică filtre
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/store/auth';
<<<<<<< HEAD
import ProductCard from '@/components/common/ProductCard.vue';
import ProductCardList from '@/components/common/ProductCardList.vue';
import { fetchCategoryPage, fetchAllProducts } from '@/services/catalog';
=======
import { useCartStore } from '@/store/cart';
import { fetchCategoryPage } from '@/services/catalog';
import { addCartItem } from '@/services/cart';
import { useToast } from 'vue-toastification';
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
import { setTitle, setMeta, setMetaProperty, setCanonical, setJsonLd, setLink } from '@/utils/seo';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
<<<<<<< HEAD
=======
const cartStore = useCartStore();
const toast = useToast();
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76

const isB2B = computed(() => authStore.user?.role === 'b2b');

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

<<<<<<< HEAD
// Filtre
const showFilters = computed(() => {
    return brandFilters.value.length > 0 || (priceFilter.value.min !== null && priceFilter.value.max !== null);
});
const sort = ref('relevance');
=======
const quantities = ref({});
const units = ref({});
const addLoading = ref(null);
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

const getUnitPrice = (product) => {
  const raw =
    product?.promo_price ??
    product?.promoPrice ??
    product?.price ??
    product?.unit_price ??
    null;
  if (raw === null || raw === undefined || raw === '') return null;
  const v = Number(raw);
  return Number.isFinite(v) ? v : null;
};

const getListPrice = (product) => {
  const raw = product?.list_price ?? product?.listPrice ?? null;
  if (raw === null || raw === undefined || raw === '') return null;
  const v = Number(raw);
  return Number.isFinite(v) ? v : null;
};

// filtre
const brandFilters = ref([]);
const selectedBrands = ref([]);
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
const brandSearch = ref('');
const selectedBrands = ref([]);
const brandFilters = ref([]);
const priceFilter = ref({
    min: null,
    max: null,
    from: null,
    to: null
});

const filteredBrands = computed(() => {
  if (!brandSearch.value) return brandFilters.value;
  return brandFilters.value.filter(b => 
    b.name.toLowerCase().includes(brandSearch.value.toLowerCase())
  );
});

const buildParams = () => {
  const params = {
    sort: sort.value,
    page: pagination.value.current_page
  };
  
  if (selectedBrands.value.length) {
    params.brands = selectedBrands.value;
  }
  
  if (priceFilter.value.from !== null && priceFilter.value.from !== priceFilter.value.min) {
    params.price_min = priceFilter.value.from;
  }
  
  if (priceFilter.value.to !== null && priceFilter.value.to !== priceFilter.value.max) {
    params.price_max = priceFilter.value.to;
  }
  
  return params;
};

const loadCategory = async () => {
  loading.value = true;
  error.value = '';

  try {
    const params = buildParams();
    // backend acceptă brand_id (unic); dacă selectăm multiple, trimitem primul
    if (params.brands && params.brands.length) {
      params.brand_id = params.brands[0];
      delete params.brands;
    }

    let data;
    if (!route.params.slug) {
        data = await fetchAllProducts(params);
    } else {
        data = await fetchCategoryPage(route.params.slug, params);
    }

    category.value = data.category ?? null;

    const prod = data.products ?? {};
    products.value = prod.data ?? prod.items ?? [];

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
    
    const routeName = route.name;
    const routeParams = { ...route.params };

    const prevUrl = pagination.value.current_page > 1
      ? window.location.origin + (router.resolve({
          name: routeName,
          params: routeParams,
          query: { ...route.query, page: pagination.value.current_page - 1 }
        }).href || '')
      : null;
    const nextUrl = pagination.value.current_page < pagination.value.last_page
      ? window.location.origin + (router.resolve({
          name: routeName,
          params: routeParams,
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
      'url': currentUrl,
      'description': desc,
      'breadcrumb': breadcrumb
    };
    setJsonLd(collection);

  } catch (e) {
    console.error('Failed to load category', e);
    error.value = 'A apărut o eroare la încărcarea produselor.';
  } finally {
    loading.value = false;
  }
};

<<<<<<< HEAD
=======
const addToCart = async (product) => {
  try {
    addLoading.value = product.id;
    const qty = Number(quantities.value[product.id]) || 1;
    const cart = await addCartItem({
      product_id: product.id,
      quantity: qty,
      unit: units.value[product.id]
    });
    if (cart) {
      cartStore.setCartData(cart);
    } else {
      await cartStore.fetchCart();
    }
    toast.success('Produs adăugat în coș');
  } catch (e) {
    toast.error('Nu s-a putut adăuga produsul în coș');
  } finally {
    addLoading.value = null;
  }
};

>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
const goToPage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return;
  pagination.value.current_page = page;
  window.scrollTo({ top: 0, behavior: 'smooth' });
  loadCategory();
};

const resetFilters = () => {
  selectedBrands.value = [];
  priceFilter.value.from = priceFilter.value.min;
  priceFilter.value.to = priceFilter.value.max;
  loadCategory();
};

watch(() => route.params.slug, () => {
    // Reset filters on category change
    selectedBrands.value = [];
    brandSearch.value = '';
    priceFilter.value = { min: null, max: null, from: null, to: null };
    pagination.value.current_page = 1;
    loadCategory();
}, { immediate: true });

watch(sort, () => {
    pagination.value.current_page = 1;
    loadCategory();
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
