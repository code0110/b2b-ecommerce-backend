<template>
  <div class="search-autocomplete position-relative" ref="container">
    <div class="input-group input-group-lg">
      <input 
        v-model="query" 
        @input="onInput"
        @keydown.down.prevent="navigateDown"
        @keydown.up.prevent="navigateUp"
        @keydown.enter.prevent="onEnter"
        @focus="showDropdown = true"
        type="text" 
        class="form-control border-end-0 shadow-none fs-6" 
        placeholder="Caută produse, branduri..." 
        aria-label="Search"
        style="border-color: #ced4da; border-radius: 4px 0 0 4px;"
      >
      <button 
        class="btn btn-orange text-white fw-bold px-4" 
        type="button"
        @click="submitSearch"
        style="border-radius: 0 4px 4px 0;"
      >
        <i class="bi bi-search"></i>
      </button>
    </div>

    <!-- Dropdown -->
    <div v-if="showDropdown && query.length >= 2" 
         class="search-dropdown-menu position-absolute w-100 bg-white shadow-lg rounded-bottom-4 border-0 mt-2 overflow-hidden" 
         style="z-index: 1050; top: 100%;">
      
      <div class="search-dropdown-content">
        <div v-if="loading" class="p-4 text-center text-muted">
            <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
            <div class="small fw-semibold">Se caută...</div>
        </div>

        <div v-else-if="hasResults">
            <!-- Categories -->
            <div v-if="suggestions.categories.length > 0" class="search-section">
                <div class="bg-light-subtle px-3 py-2 small fw-bold text-uppercase text-secondary border-bottom">
                    <i class="bi bi-grid me-1"></i> Categorii
                </div>
                <ul class="list-unstyled mb-0">
                    <li v-for="(cat, index) in suggestions.categories" :key="'cat-'+cat.id">
                        <a href="#" 
                           @click.prevent="goToCategory(cat)" 
                           class="dropdown-item px-3 py-2 d-flex align-items-center justify-content-between gap-2 border-bottom-dashed"
                           :class="{ 'active-item': selectedIndex === index }"
                        >
                            <span class="text-dark" v-html="highlight(cat.name)"></span>
                            <i class="bi bi-chevron-right text-muted small opacity-50"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Brands -->
            <div v-if="suggestions.brands.length > 0" class="search-section">
                <div class="bg-light-subtle px-3 py-2 small fw-bold text-uppercase text-secondary border-bottom border-top">
                    <i class="bi bi-tag me-1"></i> Branduri
                </div>
                <ul class="list-unstyled mb-0">
                    <li v-for="(brand, index) in suggestions.brands" :key="'brand-'+brand.id">
                        <a href="#" 
                           @click.prevent="goToBrand(brand)" 
                           class="dropdown-item px-3 py-2 d-flex align-items-center justify-content-between gap-2 border-bottom-dashed"
                           :class="{ 'active-item': selectedIndex === (suggestions.categories.length + index) }"
                        >
                            <span class="text-dark" v-html="highlight(brand.name)"></span>
                            <i class="bi bi-chevron-right text-muted small opacity-50"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Products -->
            <div v-if="suggestions.products.length > 0" class="search-section">
                <div class="bg-light-subtle px-3 py-2 small fw-bold text-uppercase text-secondary border-bottom border-top">
                    <i class="bi bi-box-seam me-1"></i> Produse
                </div>
                <ul class="list-unstyled mb-0">
                    <li v-for="(prod, index) in suggestions.products" :key="'prod-'+prod.id">
                        <a href="#" 
                           @click.prevent="goToProduct(prod)" 
                           class="dropdown-item px-3 py-2 border-bottom position-relative product-item"
                           :class="{ 'active-item': selectedIndex === (suggestions.categories.length + suggestions.brands.length + index) }"
                        >
                            <div class="d-flex align-items-center gap-3">
                                <!-- Image -->
                                <div class="product-image-container bg-white rounded-3 border d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0">
                                    <img v-if="prod.main_image_path" :src="prod.main_image_path" class="w-100 h-100 object-fit-cover transition-transform" :alt="prod.name">
                                    <i v-else class="bi bi-image text-muted opacity-25 fs-4"></i>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-grow-1 min-w-0 py-1">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="d-flex flex-column gap-1 min-w-0">
                                            <div class="fw-semibold text-dark lh-sm text-truncate-2" v-html="highlight(prod.name)"></div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 small">
                                                <span class="badge bg-light text-secondary border fw-normal px-2">
                                                    {{ prod.internal_code }}
                                                </span>
                                                <span v-if="prod.stock_status" 
                                                      class="badge border fw-normal px-2 d-flex align-items-center gap-1"
                                                      :class="getStockBadgeClass(prod.stock_status)">
                                                    <i class="bi bi-circle-fill" style="font-size: 6px;"></i>
                                                    {{ getStockLabel(prod.stock_status) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <div class="fw-bold text-primary fs-6 text-nowrap">{{ formatPriceGlobal(prod.price, prod.vat_rate, prod.vat_included) }}</div>
                                            <small class="text-muted" style="font-size: 0.75rem;">cu TVA</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="p-3 text-center bg-light-subtle border-top sticky-bottom">
                <a href="#" @click.prevent="submitSearch" class="btn btn-primary w-100 rounded-pill fw-semibold shadow-sm">
                    Vezi toate rezultatele <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div v-else class="p-5 text-center text-muted">
            <i class="bi bi-search fs-1 opacity-25 mb-2 d-block"></i>
            <span class="fw-medium">Nu am găsit rezultate pentru "{{ query }}"</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { debounce } from 'lodash';
import axios from '../../services/http';
import { usePrice } from '@/composables/usePrice';

const router = useRouter();
const { formatPrice: formatPriceGlobal } = usePrice();
const query = ref('');
const suggestions = ref({ products: [], categories: [], brands: [] });
const loading = ref(false);
const showDropdown = ref(false);
const container = ref(null);
const selectedIndex = ref(-1);

const hasResults = computed(() => {
    return suggestions.value.products.length > 0 || 
           suggestions.value.categories.length > 0 || 
           suggestions.value.brands.length > 0;
});

const flatSuggestions = computed(() => {
    const list = [];
    suggestions.value.categories.forEach(c => list.push({ type: 'category', ...c }));
    suggestions.value.brands.forEach(b => list.push({ type: 'brand', ...b }));
    suggestions.value.products.forEach(p => list.push({ type: 'product', ...p }));
    return list;
});

const regex = computed(() => {
    if (!query.value) return null;
    const safeQuery = query.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return new RegExp(`(${safeQuery})`, 'gi');
});

const highlight = (text) => {
    if (!regex.value) return text;
    return text.replace(regex.value, '<span class="bg-warning-subtle text-dark fw-bold">$1</span>');
};

const fetchSuggestions = debounce(async (q) => {
    if (q.length < 2) {
        suggestions.value = { products: [], categories: [], brands: [] };
        return;
    }
    loading.value = true;
    try {
        const { data } = await axios.get('/search/suggestions', { params: { q } });
        suggestions.value = data;
        selectedIndex.value = -1; // Reset selection on new results
    } catch (error) {
        console.error('Search suggestions error:', error);
    } finally {
        loading.value = false;
    }
}, 200);

const onInput = () => {
    showDropdown.value = true;
    fetchSuggestions(query.value);
};

const submitSearch = () => {
    showDropdown.value = false;
    if (!query.value.trim()) return;
    router.push({ name: 'search-results', query: { q: query.value } });
};

const onEnter = () => {
    if (selectedIndex.value >= 0 && flatSuggestions.value[selectedIndex.value]) {
        const item = flatSuggestions.value[selectedIndex.value];
        if (item.type === 'category') goToCategory(item);
        else if (item.type === 'brand') goToBrand(item);
        else if (item.type === 'product') goToProduct(item);
    } else {
        submitSearch();
    }
};

const goToProduct = (product) => {
    showDropdown.value = false;
    router.push({ name: 'product-details', params: { slug: product.slug } });
};

const goToCategory = (category) => {
    showDropdown.value = false;
    router.push({ name: 'category', params: { slug: category.slug } });
};

const goToBrand = (brand) => {
    showDropdown.value = false;
    router.push({ name: 'search-results', query: { q: brand.name, brands: brand.id } });
};

const getStockBadgeClass = (status) => {
    if (status === 'in_stock') return 'bg-success-subtle text-success border-success-subtle';
    if (status === 'low_stock') return 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
    return 'bg-danger-subtle text-danger border-danger-subtle';
};

const getStockLabel = (status) => {
    if (status === 'in_stock') return 'In stoc';
    if (status === 'low_stock') return 'Limitat';
    return 'Epuizat';
};

// Click outside to close
const handleClickOutside = (event) => {
    if (container.value && !container.value.contains(event.target)) {
        showDropdown.value = false;
    }
};

const navigateDown = () => {
    if (!showDropdown.value) {
        showDropdown.value = true;
        return;
    }
    if (flatSuggestions.value.length === 0) return;
    
    if (selectedIndex.value < flatSuggestions.value.length - 1) {
        selectedIndex.value++;
    } else {
        selectedIndex.value = 0;
    }
};

const navigateUp = () => {
    if (!showDropdown.value) return;
    if (flatSuggestions.value.length === 0) return;
    
    if (selectedIndex.value > 0) {
        selectedIndex.value--;
    } else {
        selectedIndex.value = flatSuggestions.value.length - 1;
    }
};

watch(query, () => {
    selectedIndex.value = -1;
});

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.search-dropdown-menu {
    transition: all 0.2s ease-in-out;
}

/* Desktop styles */
@media (min-width: 768px) {
    .search-dropdown-menu {
        max-height: 600px;
        display: flex;
        flex-direction: column;
    }
    .search-dropdown-content {
        overflow-y: auto;
        flex-grow: 1;
    }
    /* Custom scrollbar */
    .search-dropdown-content::-webkit-scrollbar {
        width: 6px;
    }
    .search-dropdown-content::-webkit-scrollbar-track {
        background: #f8f9fa;
    }
    .search-dropdown-content::-webkit-scrollbar-thumb {
        background: #dee2e6;
        border-radius: 3px;
    }
    .search-dropdown-content::-webkit-scrollbar-thumb:hover {
        background: #ced4da;
    }
}

/* Mobile styles */
@media (max-width: 767.98px) {
    .search-dropdown-menu {
        position: fixed !important;
        top: 60px !important; /* Approx header height */
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        height: calc(100vh - 60px) !important;
        max-height: none !important;
        border-radius: 0 !important;
        margin-top: 0 !important;
        border: none !important;
        z-index: 2000 !important;
        display: flex;
        flex-direction: column;
    }
    .search-dropdown-content {
        overflow-y: auto;
        flex-grow: 1;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 20px;
    }
}

.dropdown-item {
    color: #212529;
    text-decoration: none;
    transition: background-color 0.15s ease;
    cursor: pointer;
    white-space: normal; /* Force wrapping for long text */
}

.dropdown-item:hover, .active-item {
    background-color: #f8f9fa;
}

.dropdown-item:active {
    background-color: #e9ecef;
}

.border-bottom-dashed {
    border-bottom: 1px dashed #dee2e6;
}

.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-image-container {
    width: 56px; 
    height: 56px; 
    min-width: 56px;
}

.product-item:hover .product-image-container {
    border-color: #dee2e6 !important;
}

.transition-transform {
    transition: transform 0.2s ease;
}

.product-item:hover .transition-transform {
    transform: scale(1.05);
}
</style>
