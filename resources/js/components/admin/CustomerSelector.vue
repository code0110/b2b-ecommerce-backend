<template>
  <div class="customer-selector position-relative">
    <div class="input-group">
      <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
      <input
        type="text"
        class="form-control"
        placeholder="Caută client (nume, cui)..."
        v-model="searchQuery"
        @input="onSearch"
        @focus="showResults = true"
        :disabled="disabled"
      />
      <button v-if="searchQuery && !disabled" class="btn btn-outline-secondary" @click="clearSearch">
        <i class="bi bi-x"></i>
      </button>
    </div>

    <div v-if="loading" class="position-absolute w-100 bg-white border rounded shadow-sm p-3 mt-1" style="z-index: 1000;">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div> Căutare...
    </div>

    <div v-if="showResults && results.length > 0" class="position-absolute w-100 bg-white border rounded shadow-sm mt-1 overflow-auto" style="max-height: 300px; z-index: 1000;">
      <ul class="list-group list-group-flush">
        <li
          v-for="customer in results"
          :key="customer.id"
          class="list-group-item list-group-item-action cursor-pointer"
          @click="selectCustomer(customer)"
        >
          <div class="fw-bold">{{ customer.name }}</div>
          <div class="small text-muted">CUI: {{ customer.cif }} | Reg: {{ customer.reg_com }}</div>
        </li>
      </ul>
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
                    search: searchQuery.value,
                    per_page: 10 
                }
            });
            results.value = data.data;
        } catch (e) {
            console.error(e);
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
