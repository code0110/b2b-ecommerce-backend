<template>
  <div class="relative font-sans">
    <div class="relative group">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      </div>
      <input
        type="text"
        class="block w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all disabled:opacity-60 disabled:cursor-not-allowed"
        placeholder="Caută client (nume, cui)..."
        v-model="searchQuery"
        @input="onSearch"
        @focus="showResults = true"
        :disabled="disabled"
      />
      <button 
        v-if="searchQuery && !disabled" 
        @click="clearSearch"
        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 transition-colors"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="absolute z-50 w-full mt-1 bg-white border border-gray-100 rounded-xl shadow-lg p-4 flex items-center justify-center text-blue-600 text-sm font-medium">
        <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        Se caută...
    </div>

    <!-- Results Dropdown -->
    <div v-if="showResults && results.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-100 rounded-xl shadow-xl overflow-hidden max-h-[300px] overflow-y-auto custom-scrollbar">
      <ul class="divide-y divide-gray-50">
        <li
          v-for="customer in results"
          :key="customer.id"
          class="p-3 hover:bg-blue-50 cursor-pointer transition-colors group"
          @click="selectCustomer(customer)"
        >
          <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1 mr-2">
              <div class="font-bold text-gray-900 text-sm group-hover:text-blue-700 truncate">{{ customer.name }}</div>
              <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-2 flex-wrap">
                <span v-if="customer.fiscal_code" class="bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded whitespace-nowrap">CUI: {{ customer.fiscal_code }}</span>
                <span v-if="customer.reg_com" class="text-gray-400 truncate">{{ customer.reg_com }}</span>
              </div>
            </div>
            <div class="text-gray-300 group-hover:text-blue-400 flex-shrink-0">
               <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </div>
          </div>
        </li>
      </ul>
    </div>
    
    <!-- No Results -->
    <div v-if="showResults && !loading && searchQuery.length >= 2 && results.length === 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-100 rounded-xl shadow-lg p-4 text-center text-gray-500 text-sm">
        Nu s-au găsit clienți.
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { adminApi } from '@/services/http';

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['select']);

const searchQuery = ref('');
const results = ref([]);
const loading = ref(false);
const showResults = ref(false);

let debounceTimer = null;

const onSearch = () => {
    if (!searchQuery.value || searchQuery.value.length < 2) {
        results.value = [];
        return;
    }
    
    loading.value = true;
    showResults.value = true;
    
    if (debounceTimer) clearTimeout(debounceTimer);
    
    debounceTimer = setTimeout(async () => {
        try {
            const { data } = await adminApi.get('/customers', {
                params: { 
                    q: searchQuery.value,
                    per_page: 10 
                }
            });
            results.value = Array.isArray(data.data) ? data.data : [];
        } catch (e) {
            console.error(e);
            results.value = [];
        } finally {
            loading.value = false;
        }
    }, 300);
};

const selectCustomer = (customer) => {
    emit('select', customer);
    searchQuery.value = customer.name; // Show selected name
    results.value = [];
    showResults.value = false;
};

const clearSearch = () => {
    searchQuery.value = '';
    results.value = [];
    showResults.value = false;
    emit('select', null);
};

</script>

<style scoped>
.cursor-pointer { cursor: pointer; }
</style>
