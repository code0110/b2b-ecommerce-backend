<template>
  <div>
    <!-- Header -->
    <div class="dd-page-header py-3 mb-3 border-bottom bg-white">
      <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div>
            <h1 class="h4 mb-1">Rezultate căutare: <span class="text-primary">"{{ query }}"</span></h1>
            <div class="small text-muted">
              {{ total }} produse găsite
            </div>
          </div>
          
          <!-- Controls -->
          <div class="d-flex gap-2 align-items-center">
             <div class="d-flex align-items-center gap-2">
                <label class="small text-muted text-nowrap">Sortează:</label>
                <select v-model="sortBy" @change="applyFilters" class="form-select form-select-sm" style="min-width: 140px;">
                    <option value="relevance">Relevanță</option>
                    <option value="price_asc">Preț crescător</option>
                    <option value="price_desc">Preț descrescător</option>
                    <option value="newest">Cele mai noi</option>
                    <option value="name_asc">Nume (A-Z)</option>
                </select>
             </div>
             
             <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" :class="{ active: viewMode === 'grid' }" @click="viewMode = 'grid'">
                    <i class="bi bi-grid-3x3-gap"></i>
                </button>
                <button class="btn btn-outline-secondary" :class="{ active: viewMode === 'list' }" @click="viewMode = 'list'">
                    <i class="bi bi-list"></i>
                </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container pb-5">
      <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm p-3 sticky-top" style="top: 100px; z-index: 100;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold">Filtre</h5>
                    <button v-if="hasActiveFilters" class="btn btn-sm btn-link text-danger text-decoration-none p-0" @click="clearFilters">
                        Reset
                    </button>
                </div>
                
                <div v-if="loadingFacets" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-secondary"></div>
                </div>
                
                <div v-else>
                    <!-- Price Range -->
                    <div class="mb-4">
                        <h6 class="fw-bold small text-uppercase mb-2">Preț (RON {{ showVat ? 'cu TVA' : 'fără TVA' }})</h6>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <input type="number" class="form-control form-control-sm" v-model.number="priceMin" placeholder="Min">
                            <span>-</span>
                            <input type="number" class="form-control form-control-sm" v-model.number="priceMax" placeholder="Max">
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100" @click="applyFilters">Aplică Preț</button>
                    </div>

                    <!-- Categories Facet -->
                    <div v-if="facets.categories && facets.categories.length > 0" class="mb-4">
                        <h6 class="fw-bold small text-uppercase mb-2">Categorii</h6>
                        <div class="overflow-auto custom-scrollbar" style="max-height: 250px;">
                            <div v-for="cat in facets.categories" :key="cat.id" class="form-check">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    :value="cat.id" 
                                    v-model="selectedCategories"
                                    :id="'cat-'+cat.id"
                                    @change="applyFilters"
                                >
                                <label class="form-check-label small" :for="'cat-'+cat.id">
                                    {{ cat.name }} <span class="text-muted">({{ cat.count }})</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Brands Facet -->
                    <div v-if="facets.brands && facets.brands.length > 0" class="mb-4">
                        <h6 class="fw-bold small text-uppercase mb-2">Branduri</h6>
                        <div class="overflow-auto custom-scrollbar" style="max-height: 250px;">
                             <div v-for="brand in facets.brands" :key="brand.id" class="form-check">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    :value="brand.id" 
                                    v-model="selectedBrands"
                                    :id="'brand-'+brand.id"
                                    @change="applyFilters"
                                >
                                <label class="form-check-label small" :for="'brand-'+brand.id">
                                    {{ brand.name }} <span class="text-muted">({{ brand.count }})</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div class="col-lg-9">
             <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-orange" style="width: 3rem; height: 3rem;" role="status"></div>
                <div class="mt-3 text-muted fs-5">Căutăm produsele...</div>
            </div>

            <div v-else-if="products.length === 0" class="text-center py-5 bg-white rounded shadow-sm">
                <div class="display-1 text-muted mb-3"><i class="bi bi-search"></i></div>
                <h4>Nu am găsit rezultate</h4>
                <p class="text-muted">Încearcă alți termeni de căutare sau elimină filtrele active.</p>
                <button v-if="hasActiveFilters" class="btn btn-outline-primary mt-2" @click="clearFilters">
                    Șterge filtrele
                </button>
            </div>

            <div v-else>
                 <div :class="viewMode === 'grid' ? 'row g-3' : 'd-flex flex-column gap-3'">
                    <div v-for="product in products" :key="product.id" :class="viewMode === 'grid' ? 'col-6 col-md-4 col-lg-3 col-xxl-2' : 'w-100'">
                        <ProductCard v-if="viewMode === 'grid'" :product="product" />
                        <ProductCardList v-else :product="product" />
                    </div>
                 </div>
                 
                 <!-- Pagination -->
                 <div v-if="lastPage > 1" class="d-flex justify-content-center mt-5">
                    <nav>
                        <ul class="pagination shadow-sm">
                            <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                <button class="page-link" @click="changePage(currentPage - 1)" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </button>
                            </li>
                            
                            <li v-for="page in paginationPages" :key="page" class="page-item" :class="{ active: currentPage === page, disabled: page === '...' }">
                                <button class="page-link" @click="page !== '...' && changePage(page)">{{ page }}</button>
                            </li>
                            
                             <li class="page-item" :class="{ disabled: currentPage === lastPage }">
                                <button class="page-link" @click="changePage(currentPage + 1)" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </button>
                            </li>
                        </ul>
                    </nav>
                 </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { usePreferencesStore } from '@/store/preferences';
import { storeToRefs } from 'pinia';
import { searchProducts } from '@/services/catalog';
import ProductCard from '@/components/common/ProductCard.vue';
import ProductCardList from '@/components/common/ProductCardList.vue';

const route = useRoute();
const router = useRouter();
const preferences = usePreferencesStore();
const { showVat } = storeToRefs(preferences);

// State
const query = ref('');
const products = ref([]);
const facets = ref({ brands: [], categories: [], min_price: 0, max_price: 0 });
const loading = ref(false);
const loadingFacets = ref(false);
const total = ref(0);
const currentPage = ref(1);
const lastPage = ref(1);
const viewMode = ref('grid');

// Filters State
const selectedBrands = ref([]);
const selectedCategories = ref([]);
const priceMin = ref(null);
const priceMax = ref(null);
const sortBy = ref('relevance');

// Computed
const hasActiveFilters = computed(() => {
    return selectedBrands.value.length > 0 || 
           selectedCategories.value.length > 0 || 
           priceMin.value !== null || 
           priceMax.value !== null ||
           sortBy.value !== 'relevance';
});

const paginationPages = computed(() => {
    const delta = 2;
    const range = [];
    const rangeWithDots = [];
    const l = lastPage.value;
    const current = currentPage.value;

    range.push(1);
    for (let i = current - delta; i <= current + delta; i++) {
        if (i < l && i > 1) {
            range.push(i);
        }
    }
    range.push(l);

    let prev;
    for (const i of range) {
        if (prev) {
            if (i - prev === 2) {
                rangeWithDots.push(prev + 1);
            } else if (i - prev !== 1) {
                rangeWithDots.push('...');
            }
        }
        rangeWithDots.push(i);
        prev = i;
    }
    return rangeWithDots;
});

// Methods
const syncFiltersFromQuery = () => {
    query.value = route.query.q || '';
    currentPage.value = parseInt(route.query.page) || 1;
    sortBy.value = route.query.sort || 'relevance';
    
    if (route.query.brands) {
        selectedBrands.value = route.query.brands.split(',').map(Number);
    } else {
        selectedBrands.value = [];
    }
    
    if (route.query.categories) {
        selectedCategories.value = route.query.categories.split(',').map(Number);
    } else {
        selectedCategories.value = [];
    }
    
    priceMin.value = route.query.min_price ? Number(route.query.min_price) : null;
    priceMax.value = route.query.max_price ? Number(route.query.max_price) : null;
};

const performSearch = async () => {
  loading.value = true;
  loadingFacets.value = products.value.length === 0; // Only show facet loader on initial load
  
  const params = {
      q: query.value,
      page: currentPage.value,
      sort: sortBy.value,
      brands: selectedBrands.value.join(','),
      categories: selectedCategories.value.join(','),
      min_price: priceMin.value,
      max_price: priceMax.value,
      price_mode: showVat.value ? 'gross' : 'net'
  };
  
  try {
    const data = await searchProducts(params);
    products.value = data.data || [];
    total.value = data.meta.total;
    lastPage.value = data.meta.last_page;
    
    // Update facets only if we have them in response
    if (data.facets) {
        facets.value = data.facets;
    }
  } catch (e) {
    console.error('Search error', e);
  } finally {
    loading.value = false;
    loadingFacets.value = false;
  }
};

const applyFilters = () => {
    currentPage.value = 1;
    const queryParams = {
        ...route.query,
        page: 1,
        sort: sortBy.value,
        brands: selectedBrands.value.length ? selectedBrands.value.join(',') : undefined,
        categories: selectedCategories.value.length ? selectedCategories.value.join(',') : undefined,
        min_price: priceMin.value || undefined,
        max_price: priceMax.value || undefined
    };
    
    // Remove undefined keys
    Object.keys(queryParams).forEach(key => queryParams[key] === undefined && delete queryParams[key]);
    
    router.push({ query: queryParams });
};

const clearFilters = () => {
    selectedBrands.value = [];
    selectedCategories.value = [];
    priceMin.value = null;
    priceMax.value = null;
    sortBy.value = 'relevance';
    applyFilters();
};

const changePage = (page) => {
    if (page < 1 || page > lastPage.value) return;
    currentPage.value = page;
    router.push({ query: { ...route.query, page } });
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Lifecycle
onMounted(() => {
    syncFiltersFromQuery();
    if (query.value || hasActiveFilters.value) {
        performSearch();
    }
});

watch(() => route.query, () => {
    syncFiltersFromQuery();
    performSearch();
});

watch(showVat, () => {
    performSearch();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #aaa;
}
</style>