<template>
  <div class="flex flex-col h-full min-h-[600px] font-sans bg-gray-50/50 relative">
    
    <!-- Header Section (Sticky) -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-20 shadow-sm flex-shrink-0">
      
      <!-- Top Row: Search & Controls -->
      <div class="px-4 py-3 flex flex-col sm:flex-row gap-3 items-center justify-between">
          <!-- Search -->
          <div class="relative w-full sm:w-72 md:w-96 group">
              <input 
                v-model="searchQuery"
                @input="onSearchInput"
                type="text" 
                placeholder="Caută produse (Nume, SKU)..." 
                class="w-full pl-10 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-sm font-medium"
              >
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400 group-focus-within:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              </div>
              <button v-if="searchQuery" @click="clearSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 cursor-pointer">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
          </div>

          <!-- Right Controls (Sort & View) -->
          <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
               <!-- Total Count -->
               <div class="hidden sm:block text-xs font-bold text-gray-400 bg-gray-50 px-2 py-1 rounded-md border border-gray-100">
                   {{ totalProducts }} Produse
               </div>

               <!-- Sort -->
               <select v-model="sortBy" @change="refresh" class="pl-3 pr-8 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none cursor-pointer hover:border-gray-300 transition-colors">
                 <option value="created_at">Cele mai noi</option>
                 <option value="name">Nume (A-Z)</option>
                 <option value="price_asc">Preț (Cresc)</option>
                 <option value="price_desc">Preț (Desc)</option>
               </select>

               <!-- View Toggle -->
               <div class="flex bg-gray-100 p-1 rounded-lg border border-gray-200">
                  <button @click="viewMode = 'grid'" class="p-1.5 rounded-md transition-all" :class="viewMode === 'grid' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'" title="Grid View">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                  </button>
                  <button @click="viewMode = 'list'" class="p-1.5 rounded-md transition-all" :class="viewMode === 'list' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600'" title="List View">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                  </button>
               </div>
          </div>
      </div>

      <!-- Categories Tabs (Horizontal Scroll) -->
      <div class="px-4 pb-0 overflow-x-auto scrollbar-hide border-t border-gray-100 no-scrollbar">
         <div class="flex items-center gap-3 py-4 min-w-max">
             <button 
                 @click="resetCategory"
                 class="px-5 py-2.5 rounded-full text-sm font-bold whitespace-nowrap transition-all duration-300 flex items-center gap-2 border"
                 :class="!selectedCategory ? 'bg-gray-900 text-white border-gray-900 shadow-lg shadow-gray-200' : 'bg-white text-gray-500 border-gray-200 hover:border-blue-300 hover:text-blue-600 shadow-sm'"
             >
                 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                 Toate
             </button>
             
             <div class="w-px h-8 bg-gray-200 mx-2"></div>

             <button 
                 v-for="cat in rootCategories" 
                 :key="cat.id"
                 @click="selectCategory(cat)"
                 class="px-5 py-2.5 rounded-full text-sm font-bold whitespace-nowrap transition-all duration-300 border"
                 :class="(isSelected(cat) || (selectedCategory && cat.id === selectedCategory.parent_id)) ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-200 transform scale-105' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300 hover:text-blue-600 hover:bg-gray-50 shadow-sm'"
             >
                 {{ cat.name }}
             </button>
         </div>
      </div>

      <!-- Subcategories (Pills) - Visible only if category selected or has children -->
      <div v-if="!hideCategories && selectedCategory && (hasChildren(selectedCategory) || selectedCategory.parent_id)" class="px-4 pb-4 bg-gray-50/80 backdrop-blur-sm border-t border-gray-100 transition-all duration-300">
          <div class="flex items-center gap-2 overflow-x-auto scrollbar-hide py-3 min-w-max no-scrollbar">
              <!-- Back to Parent -->
              <button 
                v-if="selectedCategory.parent_id"
                @click="selectParentCategory"
                class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white border border-gray-200 text-xs font-bold text-gray-500 hover:text-blue-600 hover:border-blue-300 shadow-sm transition-all hover:shadow-md"
              >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                  Înapoi
              </button>

              <!-- Children -->
              <button 
                  v-for="sub in getChildren(selectedCategory.parent_id || selectedCategory.id)" 
                  :key="sub.id"
                  @click="selectCategory(sub)"
                  class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all border shadow-sm"
                  :class="isSelected(sub) ? 'bg-blue-50 text-blue-700 border-blue-200 ring-2 ring-blue-100' : 'bg-white text-gray-500 border-gray-200 hover:border-blue-300 hover:text-blue-600 hover:shadow-md'"
              >
                  {{ sub.name }}
              </button>
          </div>
      </div>

    </div>

    <!-- Main Content (Product Grid) -->
    <div class="flex-1 overflow-y-auto p-4 scrollbar-thin relative" ref="gridContainer">
         
         <!-- Loading State -->
         <div v-if="loading && products.length === 0" class="flex flex-col items-center justify-center h-full py-20 text-blue-600">
            <svg class="animate-spin w-10 h-10 mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span class="font-medium animate-pulse">Se încarcă produsele...</span>
         </div>

         <!-- Empty State -->
         <div v-else-if="!loading && products.length === 0" class="flex flex-col items-center justify-center h-full py-20 text-gray-400">
            <div class="bg-white p-6 rounded-full mb-4 shadow-sm border border-gray-100">
              <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
            </div>
            <p class="text-lg font-bold text-gray-900">Nu s-au găsit produse</p>
            <p class="text-sm text-gray-500 mt-1">Încearcă alte filtre sau altă categorie</p>
            <button v-if="selectedCategory || searchQuery" @click="resetFilters" class="mt-6 px-5 py-2 bg-white border border-gray-300 shadow-sm rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
              Resetează tot
            </button>
         </div>

         <!-- GRID VIEW -->
         <div v-else-if="viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 pb-24">
           <div 
             v-for="product in products" 
             :key="product.id"
             class="group relative bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-lg hover:border-blue-500 transition-all duration-300 flex flex-col h-full overflow-hidden cursor-pointer"
             @click="selectProduct(product)"
           >
             <!-- Badges -->
             <div class="absolute top-0 left-0 z-10 flex flex-col items-start gap-1">
                <span v-if="product.is_promo" class="bg-red-600 text-white text-[9px] font-bold px-2 py-0.5 rounded-br shadow-sm uppercase tracking-wide">
                    Promo
                </span>
                <span v-if="product.is_new" class="bg-blue-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-br shadow-sm uppercase tracking-wide">
                    Nou
                </span>
             </div>

             <div class="absolute top-2 right-2 z-10">
                <span :class="[
                  'w-2 h-2 rounded-full shadow-sm',
                  (product.stock_qty > 0) ? 'bg-green-500' : 'bg-red-500'
                ]" :title="product.stock_qty > 0 ? 'Stoc: ' + product.stock_qty : 'Indisponibil'">
                </span>
             </div>

             <!-- Image Area -->
             <div class="aspect-[1/1] p-3 bg-white relative group-hover:bg-gray-50 transition-colors duration-300 flex items-center justify-center">
                <img 
                  v-if="product.image_url || product.image_path" 
                  :src="product.image_url || product.image_path" 
                  :alt="product.name"
                  class="w-full h-full object-contain transform group-hover:scale-105 transition-transform duration-500 ease-out mix-blend-multiply max-h-[140px]"
                >
                <div v-else class="w-full h-full flex items-center justify-center bg-gray-50 rounded-lg">
                  <svg class="w-10 h-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
             </div>

             <!-- Content -->
             <div class="p-3 flex-1 flex flex-col bg-white">
               <div class="mb-1">
                 <span class="inline-block px-1 py-0.5 rounded text-[9px] font-mono text-gray-500 bg-gray-50 border border-gray-100 truncate max-w-full">
                   {{ product.internal_code || product.sku }}
                 </span>
               </div>
               
               <h4 class="text-xs font-bold text-gray-900 leading-snug line-clamp-2 mb-2 min-h-[2.5em] group-hover:text-blue-600 transition-colors" :title="product.name">
                 {{ product.name }}
               </h4>
               
               <div class="mt-auto pt-2 border-t border-dashed border-gray-100 flex items-end justify-between gap-1">
                 <div class="flex flex-col min-w-0">
                   <!-- Old Price if Promo -->
                   <div v-if="product.is_promo && product.rrp_price > (product.list_price || product.price)" class="text-[10px] text-gray-400 line-through decoration-red-400 decoration-1">
                      {{ formatPrice(product.rrp_price) }}
                   </div>
                   
                   <div class="flex items-baseline gap-0.5">
                       <span class="text-sm font-bold text-blue-700 truncate">{{ formatPrice(product.list_price || product.price) }}</span>
                       <span class="text-[9px] text-gray-400 font-medium">/{{ product.unit_of_measure || 'buc' }}</span>
                   </div>
                 </div>
                 
                 <button class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white hover:shadow-md transition-all duration-300 transform active:scale-95 flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                 </button>
               </div>
             </div>
           </div>
         </div>

         <!-- LIST VIEW -->
         <div v-else class="space-y-2 pb-24">
            <div 
             v-for="product in products" 
             :key="product.id"
             class="group relative bg-white border-b border-gray-100 hover:bg-blue-50/30 transition-all duration-200 flex items-center gap-3 px-3 py-2"
           >
             <!-- Image (Tiny) -->
             <div class="w-10 h-10 bg-white rounded border border-gray-200 flex-shrink-0 overflow-hidden relative p-0.5 cursor-pointer" @click="selectProduct(product)">
                <img 
                  v-if="product.image_url || product.image_path" 
                  :src="product.image_url || product.image_path" 
                  :alt="product.name"
                  class="w-full h-full object-contain mix-blend-multiply"
                >
                <div v-else class="w-full h-full flex items-center justify-center text-gray-200">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
             </div>

             <!-- SKU -->
             <div class="w-24 hidden sm:block">
                 <span class="text-[10px] font-mono text-gray-500 bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100 truncate block text-center select-all">
                     {{ product.internal_code || product.sku }}
                 </span>
             </div>

             <!-- Name & Info -->
             <div class="flex-1 min-w-0 cursor-pointer" @click="selectProduct(product)">
                <div class="flex items-center gap-2">
                    <h4 class="text-xs sm:text-sm font-bold text-gray-900 group-hover:text-blue-700 transition-colors truncate">{{ product.name }}</h4>
                    <span v-if="product.is_promo" class="text-[9px] font-bold text-white bg-red-500 px-1.5 rounded-sm uppercase tracking-wider">Promo</span>
                </div>
                <div class="flex items-center gap-2 mt-0.5">
                    <div class="flex items-center gap-1">
                        <span :class="['w-1.5 h-1.5 rounded-full', product.stock_qty > 0 ? 'bg-green-500' : 'bg-red-500']"></span>
                        <span class="text-[10px] text-gray-400 font-medium">{{ product.stock_qty > 0 ? 'Stoc: ' + product.stock_qty : 'Stoc Epuizat' }}</span>
                    </div>
                </div>
             </div>

             <!-- Price & Actions -->
             <div class="flex items-center gap-4">
                 <div class="text-right">
                     <div v-if="product.is_promo && product.rrp_price > (product.list_price || product.price)" class="text-[10px] text-gray-400 line-through decoration-red-400">
                         {{ formatPrice(product.rrp_price) }}
                     </div>
                     <div class="text-sm font-bold text-blue-700">{{ formatPrice(product.list_price || product.price) }}</div>
                 </div>

                 <!-- Inline Actions -->
                <div class="flex items-center gap-3" @click.stop>
                    <!-- Promotions Button -->
                    <button 
                       @click="emit('check-promotions', product)"
                       class="h-10 w-10 rounded-xl border border-purple-100 text-purple-600 hover:bg-purple-600 hover:text-white hover:border-purple-600 transition-all flex items-center justify-center active:scale-95"
                       title="Vezi Promoții"
                    >
                       <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                    </button>

                    <!-- Quantity Input -->
                    <input 
                       type="number" 
                       min="1" 
                       value="1"
                       :id="'qty-' + product.id"
                       class="w-16 h-10 text-center text-base font-bold border border-gray-200 rounded-xl focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none shadow-sm"
                       @keydown.enter.prevent="(e) => {
                           product.temp_qty = parseInt(e.target.value);
                           emit('select', product);
                       }"
                       @input="(e) => product.temp_qty = parseInt(e.target.value)"
                    >

                    <!-- Add Button -->
                    <button 
                       @click="() => {
                            const qtyInput = document.getElementById('qty-' + product.id);
                            product.temp_qty = qtyInput ? parseInt(qtyInput.value) : 1;
                            emit('select', product);
                       }"
                       class="h-10 px-4 rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-md hover:shadow-lg transition-all text-sm font-bold uppercase tracking-wide active:scale-95 flex items-center"
                    >
                       Adaugă
                    </button>
                </div>
             </div>
           </div>
         </div>
         
         <!-- Load More -->
         <div v-if="hasMorePages" class="py-6 flex justify-center">
            <button 
              @click="loadMore" 
              :disabled="loading"
              class="px-6 py-2.5 bg-white border border-gray-200 shadow-sm rounded-full text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600 hover:border-blue-200 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
            >
              <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              {{ loading ? 'Se încarcă...' : 'Vezi mai multe produse' }}
            </button>
         </div>

      </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import api, { adminApi } from '@/services/http';

const props = defineProps({
  enableFilters: {
    type: Boolean,
    default: true
  },
  hideCategories: {
    type: Boolean,
    default: false
  },
  hideHeader: {
    type: Boolean,
    default: false
  },
  defaultViewMode: {
    type: String,
    default: 'grid'
  }
});

const emit = defineEmits(['select', 'check-promotions']);

// State
const categories = ref([]);
const categoriesLoading = ref(false);
const products = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const selectedCategory = ref(null);
const expandedCategories = ref(new Set());
const sortBy = ref('created_at');
const viewMode = ref(props.defaultViewMode);

// Expose methods and state to parent
defineExpose({
    categories,
    selectCategory: (cat) => selectCategory(cat),
    resetCategory: () => resetCategory(),
    refresh: () => refresh(),
    setSearch: (q) => { searchQuery.value = q; refresh(); },
    setSort: (s) => { sortBy.value = s; refresh(); }
});

// Pagination State
const currentPage = ref(1);
const lastPage = ref(1);
const totalProducts = ref(0);

// Computed
const rootCategories = computed(() => {
    return categories.value.filter(c => !c.parent_id);
});

const hasMorePages = computed(() => {
    return currentPage.value < lastPage.value;
});

// Helpers
const hasChildren = (cat) => {
    return categories.value.some(c => c.parent_id == cat.id);
};

const getChildren = (parentId) => {
    return categories.value.filter(c => c.parent_id == parentId);
};

const isExpanded = (id) => expandedCategories.value.has(id);

const isSelected = (cat) => selectedCategory.value?.id === cat.id;

const toggleCategory = (cat) => {
    if (expandedCategories.value.has(cat.id)) {
        expandedCategories.value.delete(cat.id);
    } else {
        expandedCategories.value.add(cat.id);
    }
    
    // Also select it when expanding? Maybe yes.
    selectCategory(cat);
};

const selectCategory = (cat) => {
    // If clicking already selected, maybe deselect? No, let's keep it simple.
    if (selectedCategory.value?.id === cat.id) {
        // Optional: deselect
        // selectedCategory.value = null;
    } else {
        selectedCategory.value = cat;
    }
    refresh();
};

const selectParentCategory = () => {
    if (selectedCategory.value?.parent_id) {
        const parent = categories.value.find(c => c.id == selectedCategory.value.parent_id);
        if (parent) selectCategory(parent);
    } else {
        resetCategory();
    }
};

const resetCategory = () => {
    selectedCategory.value = null;
    refresh();
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = null;
    refresh();
};

const clearSearch = () => {
    searchQuery.value = '';
    refresh();
};

// Data Fetching
const loadCategories = async () => {
    categoriesLoading.value = true;
    try {
      const response = await adminApi.get('/categories');
      const data = response?.data;
      categories.value = Array.isArray(data) ? data : (data?.data || []);
    } catch (e) {
      console.error('Failed to load categories via Admin API', e);
      // Fallback to Public API
      try {
        const response = await api.get('/categories');
        const treeData = response?.data;
        const tree = Array.isArray(treeData) ? treeData : (treeData?.data || []);
        
        // Flatten
        const flat = [];
        const traverse = (list, parentId = null) => {
            if (!Array.isArray(list)) return;
            list.forEach(cat => {
                flat.push({ ...cat, parent_id: parentId }); 
                if (cat.children && cat.children.length) {
                    traverse(cat.children, cat.id);
                }
            });
        };
        traverse(tree);
        categories.value = flat;
      } catch (e2) {
        console.error('Fallback categories failed', e2);
      }
    } finally {
        categoriesLoading.value = false;
    }
};

let debounceTimer = null;
const onSearchInput = () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        refresh();
    }, 400);
};

const fetchProducts = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page,
            per_page: 12,
            sort_by: sortBy.value === 'price_asc' || sortBy.value === 'price_desc' ? 'list_price' : sortBy.value,
            sort_dir: sortBy.value === 'price_asc' ? 'asc' : 'desc'
        };

        if (searchQuery.value) params.search = searchQuery.value;

        if (selectedCategory.value) {
            // Inclusive filtering
            const mainId = selectedCategory.value.id;
            // Find all descendants
            const descendants = [];
            const findDescendants = (pid) => {
                const children = categories.value.filter(c => c.parent_id == pid);
                children.forEach(c => {
                    descendants.push(c.id);
                    findDescendants(c.id);
                });
            };
            findDescendants(mainId);
            
            params.category_id = [mainId, ...descendants];
        }

        const { data } = await adminApi.get('/products', { params });
        
        // Handle pagination structure
        const items = data.data || [];
        const meta = data.meta || {};
        
        if (page === 1) {
            products.value = items;
        } else {
            products.value = [...products.value, ...items];
        }
        
        currentPage.value = meta.current_page || page;
        lastPage.value = meta.last_page || 1;
        totalProducts.value = meta.total || items.length;

    } catch (e) {
        console.error('Error fetching products', e);
        // Fallback for empty/error
        if (page === 1) products.value = [];
    } finally {
        loading.value = false;
    }
};

const refresh = () => {
    currentPage.value = 1;
    fetchProducts(1);
};

const loadMore = () => {
    if (hasMorePages.value) {
        fetchProducts(currentPage.value + 1);
    }
};

const selectProduct = (product) => {
    emit('select', product);
};

const formatPrice = (val) => {
    return new Intl.NumberFormat('ro-RO', { 
        style: 'currency', 
        currency: 'RON',
        minimumFractionDigits: 2
    }).format(val || 0);
};

// Init
onMounted(() => {
    if (props.enableFilters) {
        loadCategories();
    }
    refresh();
});
</script>

<style scoped>
/* Custom Scrollbar for nicer look */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 20px;
}
</style>