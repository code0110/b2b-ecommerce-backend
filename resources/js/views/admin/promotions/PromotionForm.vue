<template>
  <div class="container-fluid py-4 bg-gray-50">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div class="d-flex align-items-center gap-3">
        <button @click="router.back()" class="btn btn-white border shadow-sm rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
          <i class="bi bi-arrow-left text-secondary"></i>
        </button>
        <div>
          <h1 class="h3 fw-bold mb-0 text-dark">{{ isEdit ? 'Editare Promoție' : 'Promoție Nouă' }}</h1>
          <div class="text-muted small">Configurează detaliile campaniei, regulile de aplicare și beneficiile.</div>
        </div>
      </div>
      
      <div class="d-flex gap-2">
        <button 
          @click="submit" 
          :disabled="saving" 
          class="btn btn-primary d-flex align-items-center gap-2 shadow-sm px-4"
        >
          <span v-if="saving" class="spinner-border spinner-border-sm"></span>
          <i v-else class="bi bi-check-lg"></i>
          <span class="fw-medium">{{ isEdit ? 'Salvează Modificările' : 'Publică Promoția' }}</span>
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <div v-else-if="error" class="alert alert-danger shadow-sm border-0 rounded-3 d-flex align-items-center">
      <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
      <div>{{ error }}</div>
    </div>

    <form v-else @submit.prevent="submit" class="row g-4">
      
      <!-- Left Column: Main Configuration -->
      <div class="col-lg-8">
        
        <!-- 1. General Info Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-info-circle me-2"></i>Informații Generale</h6>
          </div>
          <div class="card-body p-4">
            <div class="row g-3 mb-4">
              <div class="col-md-8">
                <label class="form-label small fw-bold text-uppercase text-muted">Titlu Promoție</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="form-control form-control-lg"
                  placeholder="ex: Reduceri de Vară"
                  :class="{ 'is-invalid': validationErrors.name }"
                />
                <div class="invalid-feedback">{{ validationErrors.name?.[0] }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-uppercase text-muted">Cod Intern / Slug</label>
                <input
                  v-model="form.slug"
                  type="text"
                  class="form-control form-control-lg"
                  placeholder="summer-sale-2024"
                />
              </div>
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label small fw-bold text-uppercase text-muted">Descriere (Opțional)</label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="form-control"
                  placeholder="Detalii interne sau publice despre campanie..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Type & Value Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-tag me-2"></i>Tipul și Valoarea Reducerii</h6>
          </div>
          <div class="card-body p-4">
            <div class="mb-4">
              <label class="form-label small fw-bold text-uppercase text-muted mb-3">Alege Tipul de Promoție</label>
              <div class="row row-cols-1 row-cols-md-3 g-3">
                <div class="col" v-for="type in promoTypes" :key="type.value">
                  <label 
                    class="card h-100 cursor-pointer transition-all" 
                    :class="form.type === type.value ? 'border-primary bg-primary bg-opacity-10 ring-2' : 'border-light hover-shadow'"
                    style="border-width: 2px;"
                  >
                    <div class="card-body text-center p-3">
                      <input type="radio" name="promoType" :value="type.value" v-model="form.type" class="d-none">
                      <i class="bi fs-2 mb-2 d-block" :class="[type.icon, form.type === type.value ? 'text-primary' : 'text-muted']"></i>
                      <span class="d-block fw-bold small" :class="form.type === type.value ? 'text-dark' : 'text-muted'">{{ type.label }}</span>
                    </div>
                  </label>
                </div>
              </div>
            </div>

            <!-- Value Configuration -->
            <div class="bg-light p-4 rounded-3 border" v-if="['standard', 'special_price'].includes(form.type)">
               <div class="row g-3 align-items-end">
                 <div class="col-md-5">
                   <label class="form-label small fw-bold text-muted">Cum se aplică?</label>
                   <select v-model="form.value_type" class="form-select">
                     <option value="percent">Procentual (%)</option>
                     <option value="fixed_amount">Sumă Fixă (Scădere)</option>
                     <option value="fixed_price">Preț Final (Setare)</option>
                   </select>
                 </div>
                 <div class="col-md-7">
                   <label class="form-label small fw-bold text-muted">Valoare</label>
                   <div class="input-group">
                     <input 
                        type="number" 
                        v-model="form.value" 
                        class="form-control fw-bold" 
                        step="0.01" 
                        style="font-size: 1.2rem;"
                     >
                     <span class="input-group-text bg-white text-muted fw-bold">
                       {{ form.value_type === 'percent' ? '%' : 'RON' }}
                     </span>
                   </div>
                 </div>
               </div>
            </div>
            
            <div class="bg-light p-4 rounded-3 border" v-else-if="form.type === 'volume'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Praguri de Volum</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addTier">
                        <i class="bi bi-plus-lg"></i> Adaugă Prag
                    </button>
                </div>
                <div v-for="(tier, idx) in form.tiers" :key="idx" class="d-flex gap-2 mb-2 align-items-center">
                    <div class="input-group input-group-sm" style="width: 150px;">
                        <span class="input-group-text">Min Qty</span>
                        <input type="number" v-model="tier.min_qty" class="form-control">
                    </div>
                    <div class="input-group input-group-sm" style="width: 150px;">
                        <span class="input-group-text">Discount</span>
                        <input type="number" v-model="tier.value" class="form-control">
                        <span class="input-group-text">%</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-danger" @click="removeTier(idx)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <p v-if="!form.tiers.length" class="text-muted small mb-0 fst-italic">Niciun prag definit.</p>
            </div>
          </div>
        </div>

        <!-- 3. Target (Products) Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-2"></i>Produse Incluse</h6>
          </div>
          <div class="card-body p-4">
            <div class="mb-3">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="product_scope" id="scope_all" :value="true" v-model="allProductsScope">
                    <label class="form-check-label" for="scope_all">Toate Produsele</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="product_scope" id="scope_specific" :value="false" v-model="allProductsScope">
                    <label class="form-check-label" for="scope_specific">Selectare Manuală (Produse/Categorii/Branduri)</label>
                </div>
            </div>

            <div v-if="!allProductsScope" class="animate__animated animate__fadeIn">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Produse Individuale</label>
                    <SearchableSelect
                        v-model="form.product_ids"
                        :options="availableProducts"
                        label="name"
                        track-by="id"
                        placeholder="Caută produse..."
                        :remote="true"
                        :remote-method="onSearchProducts"
                        :loading="loadingProducts"
                        :min-search-length="0"
                    >
                        <template #option="{ option }">
                            <div class="d-flex align-items-center gap-2">
                                <div v-if="option.main_image" 
                                     class="bg-white border rounded" 
                                     style="width: 30px; height: 30px; background-size: cover; background-position: center;"
                                     :style="{ backgroundImage: `url(${option.main_image})` }"
                                ></div>
                                <div>
                                    <div class="fw-bold text-truncate" style="max-width: 300px;">{{ option.name }}</div>
                                    <div class="small text-muted">{{ option.internal_code }}</div>
                                </div>
                            </div>
                        </template>
                    </SearchableSelect>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Categorii</label>
                        <SearchableSelect
                            v-model="form.category_ids"
                            :options="availableCategories"
                            label="name"
                            track-by="id"
                            placeholder="Alege categorii..."
                            :remote="true"
                            :remote-method="onSearchCategories"
                            :loading="loadingCategories"
                            :min-search-length="0"
                        />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Branduri</label>
                        <SearchableSelect
                            v-model="form.brand_ids"
                            :options="availableBrands"
                            label="name"
                            track-by="id"
                            placeholder="Alege branduri..."
                            :remote="true"
                            :remote-method="onSearchBrands"
                            :loading="loadingBrands"
                            :min-search-length="0"
                        />
                    </div>
                </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column: Settings & Sidebar -->
      <div class="col-lg-4">
        
        <!-- Status & Dates -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-body p-4">
            <h6 class="fw-bold mb-3 text-dark">Setări Publicare</h6>
            
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select v-model="form.status" class="form-select" :class="statusBadgeClass(form.status)">
                    <option value="draft" class="bg-white text-dark">Draft</option>
                    <option value="active" class="bg-white text-dark">Activă</option>
                    <option value="inactive" class="bg-white text-dark">Inactivă</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Dată Start</label>
                <input v-model="form.start_at" type="date" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Dată Stop</label>
                <input v-model="form.end_at" type="date" class="form-control">
            </div>
          </div>
        </div>

        <!-- Targeting -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-body p-4">
            <h6 class="fw-bold mb-3 text-dark">Segmentare Clienți</h6>
            
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Tip Client</label>
                <select v-model="form.customer_type" class="form-select">
                    <option value="both">Toți (B2B + B2C)</option>
                    <option value="b2b">Doar B2B</option>
                    <option value="b2c">Doar B2C</option>
                </select>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="loggedIn" v-model="form.logged_in_only">
                <label class="form-check-label small" for="loggedIn">Doar autentificați</label>
            </div>

            <hr class="border-light">

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Clienți Specifici</label>
                <SearchableSelect
                    v-model="form.customer_ids"
                    :options="availableCustomers"
                    label="name"
                    track-by="id"
                    placeholder="Caută clienți..."
                    :remote="true"
                    :remote-method="onSearchCustomers"
                    :loading="loadingCustomers"
                    :min-search-length="0"
                >
                    <template #option="{ option }">
                        <div>
                            <div class="fw-bold">{{ option.name }}</div>
                            <div class="small text-muted">{{ option.email }}</div>
                        </div>
                    </template>
                </SearchableSelect>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Grupuri Clienți</label>
                <SearchableSelect
                    v-model="form.customer_group_ids"
                    :options="availableGroups"
                    label="name"
                    track-by="id"
                    placeholder="Alege grupuri..."
                    :remote="true"
                    :remote-method="onSearchGroups"
                    :min-search-length="0"
                    :loading="loadingGroups"
                />
            </div>
          </div>
        </div>

        <!-- Advanced Rules -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-dark">Reguli Avansate</h6>
                
                <div class="mb-2">
                    <label class="form-label small fw-bold text-muted">Minim Coș (RON)</label>
                    <input type="number" class="form-control form-control-sm" v-model="form.min_cart_total">
                </div>
            </div>
        </div>

      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchPromotion, createPromotion, updatePromotion } from '@/services/admin/promotions';
import { fetchProducts } from '@/services/admin/products';
import { fetchCustomers } from '@/services/admin/customers';
import { adminApi } from '@/services/http';
import SearchableSelect from '@/components/common/SearchableSelect.vue';
import { useToast } from "vue-toastification";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isEdit = computed(() => route.name === 'admin-promotions-edit');
const loading = ref(false);
const saving = ref(false);
const error = ref('');
const validationErrors = ref({});

// Options
const availableGroups = ref([]);
const availableCategories = ref([]);
const availableBrands = ref([]);
const availableProducts = ref([]);
const availableCustomers = ref([]);

const loadingGroups = ref(false);
const loadingCategories = ref(false);
const loadingBrands = ref(false);
const loadingProducts = ref(false);
const loadingCustomers = ref(false);

const allProductsScope = ref(true); // Helper for UI toggle

const form = ref({
  name: '',
  slug: '',
  description: '',
  status: 'draft',
  start_at: '',
  end_at: '',
  
  type: 'standard',
  value_type: 'percent',
  value: 0,
  
  customer_type: 'both',
  logged_in_only: false,
  
  customer_group_ids: [],
  customer_ids: [],
  category_ids: [],
  brand_ids: [],
  product_ids: [],
  
  tiers: [],
  
  min_cart_total: 0,
  min_qty_per_product: 0,
});

const promoTypes = [
    { value: 'standard', label: 'Discount Standard', icon: 'bi-percent' },
    { value: 'volume', label: 'Discount Volum', icon: 'bi-collection' },
    { value: 'bundle', label: 'Pachet (Bundle)', icon: 'bi-box-seam' },
    { value: 'shipping', label: 'Livrare Gratuită', icon: 'bi-truck' },
    { value: 'special_price', label: 'Preț Fix', icon: 'bi-tag' },
    { value: 'gift', label: 'Produs Cadou', icon: 'bi-gift' },
];

const statusBadgeClass = (status) => {
    if(status === 'active') return 'bg-success text-white border-success';
    if(status === 'inactive') return 'bg-secondary text-white border-secondary';
    return 'bg-warning text-dark border-warning';
};

let searchGroupTimeout = null;
const onSearchGroups = (query) => {
    if (searchGroupTimeout) clearTimeout(searchGroupTimeout);
    searchGroupTimeout = setTimeout(async () => {
        loadingGroups.value = true;
        try {
            const res = await adminApi.get('/customer-groups', { params: { q: query } });
            const results = Array.isArray(res.data) ? res.data : (res.data.data || []);
            const selected = availableGroups.value.filter(c => form.value.customer_group_ids.includes(c.id));
            const newOptions = results.filter(r => !selected.find(s => s.id === r.id));
            availableGroups.value = [...selected, ...newOptions];
        } catch (e) {
            console.error('[Search Groups Error]', e);
        } finally {
            loadingGroups.value = false;
        }
    }, 300);
};

let searchCategoryTimeout = null;
const onSearchCategories = (query) => {
    if (searchCategoryTimeout) clearTimeout(searchCategoryTimeout);
    searchCategoryTimeout = setTimeout(async () => {
        loadingCategories.value = true;
        try {
            const res = await adminApi.get('/categories', { params: { q: query } });
            const results = Array.isArray(res.data) ? res.data : (res.data.data || []);
            const selected = availableCategories.value.filter(c => form.value.category_ids.includes(c.id));
            const newOptions = results.filter(r => !selected.find(s => s.id === r.id));
            availableCategories.value = [...selected, ...newOptions];
        } catch (e) {
            console.error('[Search Categories Error]', e);
        } finally {
            loadingCategories.value = false;
        }
    }, 300);
};

let searchBrandTimeout = null;
const onSearchBrands = (query) => {
    if (searchBrandTimeout) clearTimeout(searchBrandTimeout);
    searchBrandTimeout = setTimeout(async () => {
        loadingBrands.value = true;
        try {
            const res = await adminApi.get('/brands', { params: { q: query } });
            const results = Array.isArray(res.data) ? res.data : (res.data.data || []);
            const selected = availableBrands.value.filter(c => form.value.brand_ids.includes(c.id));
            const newOptions = results.filter(r => !selected.find(s => s.id === r.id));
            availableBrands.value = [...selected, ...newOptions];
        } catch (e) {
            console.error('[Search Brands Error]', e);
        } finally {
            loadingBrands.value = false;
        }
    }, 300);
};

const loadOptions = async () => {
    onSearchGroups('');
    onSearchCategories('');
    onSearchBrands('');
};

const loadPromotion = async () => {
    if (!isEdit.value) return;
    
    loading.value = true;
    try {
        const response = await fetchPromotion(route.params.id);
        const p = response.data || response;
        console.log('Loaded Promotion Data:', p);
        
        if (!p) {
            throw new Error('Datele promoției sunt invalide (raspuns gol)');
        }

        // Safe assignment helper
        const safeIds = (arr) => Array.isArray(arr) ? arr.filter(Boolean).map(x => x.id) : [];
        const safeArr = (arr) => Array.isArray(arr) ? arr.filter(Boolean) : [];

        form.value = {
            ...p,
            customer_group_ids: safeIds(p.customer_groups),
            customer_ids: safeIds(p.customers),
            category_ids: safeIds(p.categories),
            brand_ids: safeIds(p.brands),
            product_ids: safeIds(p.products),
            tiers: Array.isArray(p.tiers) ? p.tiers : [],
            start_at: p.start_at && typeof p.start_at === 'string' ? p.start_at.substring(0, 10) : '',
            end_at: p.end_at && typeof p.end_at === 'string' ? p.end_at.substring(0, 10) : '',
        };

        // Determine scope toggle state
        if (form.value.product_ids.length > 0 || form.value.category_ids.length > 0 || form.value.brand_ids.length > 0) {
            allProductsScope.value = false;
        } else {
            allProductsScope.value = true;
        }

        // Merge strategies for available options to avoid race conditions
        const mergeOptions = (current, incoming) => {
            const currentIds = new Set(current.map(c => c.id));
            const newItems = incoming.filter(i => !currentIds.has(i.id));
            return [...current, ...newItems];
        };

        if (p.products) availableProducts.value = mergeOptions(availableProducts.value, safeArr(p.products));
        if (p.customers) availableCustomers.value = mergeOptions(availableCustomers.value, safeArr(p.customers));
        if (p.customer_groups) availableGroups.value = mergeOptions(availableGroups.value, safeArr(p.customer_groups));
        if (p.categories) availableCategories.value = mergeOptions(availableCategories.value, safeArr(p.categories));
        if (p.brands) availableBrands.value = mergeOptions(availableBrands.value, safeArr(p.brands));

    } catch (e) {
        // Log less verbosely for 404
        if (e.response && e.response.status === 404) {
             console.warn(`[Promotion load] Not found: ${e.config?.url}`);
             error.value = 'Promoția nu a fost găsită (404).';
        } else {
             console.error('[Promotion load] Error:', e);
             const msg = e.response?.data?.message || e.message || 'Unknown error';
             error.value = 'Nu s-a putut încărca promoția: ' + msg;
        }
    } finally {
        loading.value = false;
    }
};

let searchTimeout = null;
const onSearchProducts = (query) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        loadingProducts.value = true;
        try {
            const res = await fetchProducts({ search: query });
            const results = res.data || [];
            const selected = availableProducts.value.filter(p => form.value.product_ids.includes(p.id));
            const newOptions = results.filter(r => !selected.find(s => s.id === r.id));
            availableProducts.value = [...selected, ...newOptions];
        } catch (e) {
            console.error('[Search Products Error]', e);
        } finally {
            loadingProducts.value = false;
        }
    }, 300);
};

let searchCustomerTimeout = null;
const onSearchCustomers = (query) => {
    if (searchCustomerTimeout) clearTimeout(searchCustomerTimeout);
    searchCustomerTimeout = setTimeout(async () => {
        loadingCustomers.value = true;
        try {
            const res = await fetchCustomers({ q: query, per_page: 50 });
            const results = res.data || [];
            const selected = availableCustomers.value.filter(c => form.value.customer_ids.includes(c.id));
            const newOptions = results.filter(r => !selected.find(s => s.id === r.id));
            availableCustomers.value = [...selected, ...newOptions];
        } catch (e) {
            console.error('[Search Customers Error]', e);
        } finally {
            loadingCustomers.value = false;
        }
    }, 300);
};

const addTier = () => {
    form.value.tiers.push({ min_qty: 1, value: 0 });
};

const removeTier = (index) => {
    form.value.tiers.splice(index, 1);
};

const submit = async () => {
    saving.value = true;
    validationErrors.value = {};
    
    // Clear product targeting if "All Products" is selected
    if (allProductsScope.value) {
        form.value.product_ids = [];
        form.value.category_ids = [];
        form.value.brand_ids = [];
    }
    
    try {
        if (isEdit.value) {
            await updatePromotion(route.params.id, form.value);
            toast.success("Promoția a fost actualizată!");
        } else {
            await createPromotion(form.value);
            toast.success("Promoția a fost creată!");
            router.push({ name: 'admin-promotions' });
        }
    } catch (e) {
        if (e.response && e.response.status === 422) {
            validationErrors.value = e.response.data.errors;
            toast.error("Verifică erorile din formular.");
        } else {
            toast.error("Eroare la salvare.");
        }
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    loadOptions();
    loadPromotion();
});
</script>

<style scoped>
.hover-shadow:hover {
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
.cursor-pointer {
    cursor: pointer;
}
.transition-all {
    transition: all 0.2s ease;
}
</style>
