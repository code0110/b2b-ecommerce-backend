<template>
  <div class="product-selector position-relative">
    
    <!-- Filters Row -->
    <div v-if="enableFilters" class="row g-2 mb-2">
      <div class="col-md-6">
        <select class="form-select form-select-sm" v-model="selectedMainCategory" @change="onMainCategoryChange">
          <option :value="null">Toate Categoriile</option>
          <option v-for="cat in mainCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </div>
      <div class="col-md-6">
        <select class="form-select form-select-sm" v-model="selectedSubCategory" :disabled="!selectedMainCategory" @change="onSearch">
          <option :value="null">Toate Subcategoriile</option>
          <option v-for="cat in availableSubCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </div>
    </div>

    <div class="input-group">
      <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
      <input
        type="text"
        class="form-control"
        placeholder="Caută produs (nume, cod)..."
        v-model="searchQuery"
        @input="onSearch"
        @focus="showResults = true"
      />
      <button v-if="searchQuery" class="btn btn-outline-secondary" @click="clearSearch">
        <i class="bi bi-x"></i>
      </button>
    </div>

    <div v-if="loading" class="position-absolute w-100 bg-white border rounded shadow-sm p-3 mt-1" style="z-index: 1000;">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div> Căutare...
    </div>

    <div v-else-if="showResults && results && results.length > 0" class="position-absolute w-100 bg-white border rounded shadow-sm mt-1 overflow-auto" style="max-height: 300px; z-index: 1000;">
      <ul class="list-group list-group-flush">
        <li
          v-for="product in results"
          :key="product.id"
          class="list-group-item list-group-item-action cursor-pointer"
          @click="selectProduct(product)"
        >
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold">{{ product.name }}</div>
              <div class="small text-muted">Cod: {{ product.internal_code || product.sku }} | Stoc: {{ product.stock_qty }} {{ product.unit_measure }}</div>
            </div>
            <div class="text-end">
              <div class="fw-bold">{{ formatPrice(product.list_price || product.price) }}</div>
            </div>
          </div>
        </li>
      </ul>
    </div>
    
    <div v-else-if="showResults && !loading" class="position-absolute w-100 bg-white border rounded shadow-sm p-2 mt-1 text-center text-muted small" style="z-index: 1000;">
        Niciun produs găsit.
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed, unref } from 'vue';
import { adminApi } from '@/services/http';

const props = defineProps({
  enableFilters: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['select']);

const searchQuery = ref('');
const results = ref([]);
const loading = ref(false);
const showResults = ref(false);

// Filters State
const categories = ref([]);
const selectedMainCategory = ref(null);
const selectedSubCategory = ref(null);

// Computed Filters
const mainCategories = computed(() => {
  const cats = unref(categories);
  // Ensure cats is an array before calling filter
  if (!Array.isArray(cats)) return [];
  return cats.filter(c => c && !c.parent_id);
});

const availableSubCategories = computed(() => {
  const cats = unref(categories);
  const parentId = unref(selectedMainCategory);
  // Ensure cats is an array before calling filter
  if (!parentId || !Array.isArray(cats)) return [];
  return cats.filter(c => c && c.parent_id === parentId);
});

onMounted(async () => {
  if (props.enableFilters) {
    try {
      const { data } = await adminApi.get('/categories');
      categories.value = Array.isArray(data) ? data : [];
    } catch (e) {
      console.error('Failed to load categories', e);
      categories.value = [];
    }
  }
});

const onMainCategoryChange = () => {
  selectedSubCategory.value = null;
  onSearch();
};

// Debounce search to avoid too many requests
let debounceTimer = null;

const onSearch = () => {
  // If filters enabled, allow empty search query if a category is selected
  if (!props.enableFilters && (!searchQuery.value || searchQuery.value.length < 2)) {
      results.value = [];
      return;
  }
  
  if (props.enableFilters && !searchQuery.value && !selectedMainCategory.value) {
     results.value = [];
     return;
  }
    
    loading.value = true;
    showResults.value = true;
    
    if (debounceTimer) clearTimeout(debounceTimer);
    
    debounceTimer = setTimeout(async () => {
        try {
            const params = {
              per_page: 10
            };
            
            if (searchQuery.value) params.search = searchQuery.value;
            
            if (selectedSubCategory.value) {
              params.category_id = selectedSubCategory.value;
            } else if (selectedMainCategory.value) {
              params.main_category_id = selectedMainCategory.value;
            }

            const { data } = await adminApi.get('/products', { params });
            results.value = data.data || [];
        } catch (e) {
            console.error(e);
            results.value = [];
        } finally {
            loading.value = false;
        }
    }, 300);
};

const selectProduct = (product) => {
    emit('select', product);
    searchQuery.value = ''; // Clear search after selection? Or keep it? User might want to search again.
    // Better to clear to indicate "selection made" and allow new search.
    results.value = [];
    showResults.value = false;
};

const clearSearch = () => {
    searchQuery.value = '';
    results.value = [];
    showResults.value = false;
};

// Close results when clicking outside (simple implementation)
// For now relying on selection to close.

const formatPrice = (val) => new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(val || 0);

</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>
