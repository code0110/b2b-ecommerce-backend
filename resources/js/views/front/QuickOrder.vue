<template>
  <div class="container py-5">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1 text-primary">
          <i class="bi bi-lightning-charge-fill me-2"></i>Comandă rapidă
        </h1>
        <p class="text-muted mb-0">
          Adaugă rapid produse în coș prin coduri, import fișier sau căutare.
        </p>
      </div>
      
      <!-- Client Info Badge -->
      <div v-if="frontClientLabel" class="mt-3 mt-md-0">
        <div class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-2 shadow-sm">
          <i class="bi bi-person-circle text-secondary me-2"></i>
          <div class="d-flex flex-column lh-1 text-start">
            <span class="small text-muted">Client activ</span>
            <span class="fw-semibold text-dark">{{ frontClientLabel }}</span>
          </div>
          <div v-if="isImpersonating" class="ms-3 ps-3 border-start">
            <span class="badge bg-warning text-dark">Impersonare</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs nav-fill mb-4 border-bottom-0">
      <li class="nav-item">
        <button 
          class="nav-link border mb-0 rounded-top" 
          :class="{ 'active fw-bold bg-white border-bottom-0': activeTab === 'search', 'bg-light text-muted': activeTab !== 'search' }"
          @click="activeTab = 'search'"
        >
          <i class="bi bi-search me-2"></i>Căutare Produse
        </button>
      </li>
      <li class="nav-item mx-1">
        <button 
          class="nav-link border mb-0 rounded-top"
          :class="{ 'active fw-bold bg-white border-bottom-0': activeTab === 'manual', 'bg-light text-muted': activeTab !== 'manual' }"
          @click="activeTab = 'manual'"
        >
          <i class="bi bi-keyboard me-2"></i>Introducere Coduri
        </button>
      </li>
      <li class="nav-item">
        <button 
          class="nav-link border mb-0 rounded-top"
          :class="{ 'active fw-bold bg-white border-bottom-0': activeTab === 'import', 'bg-light text-muted': activeTab !== 'import' }"
          @click="activeTab = 'import'"
        >
          <i class="bi bi-file-earmark-spreadsheet me-2"></i>Import Fișier / Text
        </button>
      </li>
    </ul>

    <!-- TAB 1: Search Section (Existing) -->
    <div v-show="activeTab === 'search'">
        <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Caută produse</h5>
            <div class="input-group input-group-lg border rounded shadow-sm overflow-hidden">
            <span class="input-group-text bg-white border-0 text-muted ps-3">
                <i class="bi bi-search"></i>
            </span>
            <input
                v-model="filters.search"
                type="text"
                class="form-control border-0 ps-2"
                placeholder="Caută după denumire, cod sau SKU..."
                @input="onSearchInput"
                :autofocus="activeTab === 'search'"
            />
            <button 
                v-if="filters.search" 
                class="btn btn-white border-0"
                @click="clearSearch"
            >
                <i class="bi bi-x-lg text-muted"></i>
            </button>
            </div>
            
            <div class="mt-2 d-flex justify-content-between align-items-center">
                <div v-if="loading" class="d-flex align-items-center text-primary small">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    <span>Se caută...</span>
                </div>
                <div v-else-if="products.length > 0" class="text-success small fw-semibold">
                    <i class="bi bi-check-circle-fill me-1"></i> {{ products.length }} produse găsite
                </div>
                <div v-else class="text-muted small">
                    Introduceți minim 3 caractere pentru a căuta.
                </div>
            </div>
        </div>
        </div>

        <!-- Promotions Section -->
        <div v-if="activePromotions.length > 0" class="mb-4">
        <h5 class="fw-bold mb-3">Oferte Speciale</h5>
        <div class="row g-3">
            <div v-for="promo in activePromotions" :key="promo.id" class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary bg-opacity-10 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-primary">
                    <i class="bi bi-gift-fill me-2"></i>{{ promo.name }}
                </h6>
                <button 
                    class="btn btn-sm btn-primary" 
                    :disabled="!hasPromoSelection(promo) || addingToCart" 
                    @click="addPromoToCart(promo)"
                >
                    <i class="bi bi-cart-plus me-1"></i> Adaugă
                </button>
                </div>
                <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li v-for="prod in promo.products" :key="prod.id" class="list-group-item d-flex justify-content-between align-items-center p-3">
                    <div class="me-2">
                        <div class="fw-semibold small">{{ prod.name }}</div>
                        <div class="text-muted small">{{ prod.code }}</div>
                    </div>
                    <div class="input-group input-group-sm" style="width: 120px;">
                        <button class="btn btn-outline-secondary" type="button" @click="decrementPromo(prod)">-</button>
                        <input 
                        type="text" 
                        class="form-control text-center fw-bold" 
                        :value="prod.orderQuantity" 
                        readonly
                        >
                        <button class="btn btn-outline-secondary" type="button" @click="incrementPromo(prod)">+</button>
                    </div>
                    </li>
                </ul>
                </div>
            </div>
            </div>
        </div>
        </div>

        <!-- Results Section -->
        <div class="card border-0 shadow-sm">
        <!-- Table Header (Visible on Desktop) -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-uppercase small text-muted">
                <tr>
                <th class="ps-4 py-3" style="width: 50px;">#</th>
                <th class="py-3">Produs</th>
                <th class="py-3" style="width: 140px;">Cod</th>
                <th class="py-3 text-center" style="width: 120px;">Unitate</th>
                <th class="py-3 text-end" style="width: 130px;">Preț</th>
                <th class="py-3 text-center" style="width: 120px;">Stoc</th>
                <th class="py-3 text-center pe-4" style="width: 140px;">Cantitate</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(product, index) in products" :key="`${product.id}_${product.variant_id || 'main'}`" class="position-relative">
                <td class="ps-4 text-muted small">{{ index + 1 }}</td>
                <td>
                    <div class="d-flex flex-column">
                    <router-link :to="`/product/${product.slug}`" class="fw-bold text-dark text-decoration-none text-truncate" style="max-width: 300px;">
                        {{ product.name }}
                    </router-link>
                    <small class="text-muted">{{ product.category || 'General' }}</small>
                    </div>
                </td>
                <td>
                    <span class="badge bg-light text-dark border font-monospace">{{ product.code }}</span>
                </td>
                <td class="text-center">
                    <select 
                    v-if="product.units && product.units.length > 1" 
                    v-model="product.selectedUnit" 
                    class="form-select form-select-sm"
                    >
                    <option v-for="u in product.units" :key="u.name" :value="u.name">
                        {{ u.name }} (x{{ u.conversion_factor }})
                    </option>
                    </select>
                    <span v-else class="small text-muted">
                    {{ product.selectedUnit || 'buc' }}
                    </span>
                </td>
                <td class="text-end fw-semibold text-dark">
                    {{ formatMoney(product.price) }}
                </td>
                <td class="text-center">
                    <div 
                    class="badge rounded-pill"
                    :class="{
                        'bg-success-subtle text-success border border-success-subtle': product.stock_status === 'in_stock',
                        'bg-warning-subtle text-warning-emphasis border border-warning-subtle': product.stock_status === 'low_stock',
                        'bg-danger-subtle text-danger border border-danger-subtle': product.stock_status === 'out_of_stock',
                        'bg-secondary-subtle text-secondary border border-secondary-subtle': product.stock_status === 'supplier'
                    }"
                    >
                    {{ stockStatusLabel(product.stock_status) }}
                    </div>
                </td>
                <td class="pe-4">
                    <div class="input-group input-group-sm">
                    <button class="btn btn-outline-secondary" type="button" @click="decrement(product)">-</button>
                    <input
                        type="number"
                        min="0"
                        class="form-control text-center fw-bold"
                        v-model.number="product.orderQuantity"
                        @focus="$event.target.select()"
                    />
                    <button class="btn btn-outline-secondary" type="button" @click="increment(product)">+</button>
                    </div>
                </td>
                </tr>
                
                <!-- Empty State -->
                <tr v-if="products.length === 0 && !loading">
                <td colspan="7" class="text-center py-5">
                    <div class="text-muted">
                    <i class="bi bi-search display-4 d-block mb-3 opacity-25"></i>
                    <h5 class="fw-normal">Începe tastarea pentru a căuta produse</h5>
                    <p class="small mb-0">Poți căuta după nume, cod produs sau SKU</p>
                    </div>
                </td>
                </tr>
            </tbody>
            </table>
        </div>
        
        <!-- Footer Action Bar -->
        <div class="card-footer bg-white py-3 border-top">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                <i class="bi bi-cart-check fs-4"></i>
                </div>
                <div>
                <div class="small text-muted text-uppercase fw-bold">Total produse selectate</div>
                <div class="fs-5 fw-bold text-dark">{{ totalQuantity }} <span class="fs-6 text-muted fw-normal">buc.</span></div>
                </div>
            </div>
            
            <div class="d-flex gap-2 w-100 w-md-auto">
                <button
                type="button"
                class="btn btn-light border"
                @click="resetQuantities"
                :disabled="totalQuantity === 0"
                >
                <i class="bi bi-trash me-1"></i> Resetează
                </button>
                <button
                type="button"
                class="btn btn-primary px-4 flex-grow-1 flex-md-grow-0"
                :disabled="totalQuantity === 0 || addingToCart"
                @click="addAllToCart"
                >
                <span v-if="addingToCart" class="spinner-border spinner-border-sm me-2"></span>
                <span v-else><i class="bi bi-cart-plus me-2"></i>Adaugă în coș</span>
                </button>
            </div>
            </div>
        </div>
        </div>
    </div>

    <!-- TAB 2: Manual Entry -->
    <div v-show="activeTab === 'manual'">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <div class="alert alert-info border-0 bg-info bg-opacity-10 d-flex align-items-center mb-4">
            <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
            <div>
              <strong>Introducere rapidă:</strong> Tastează codul produsului (SKU) și apasă Tab sau Enter pentru a valida. Apoi introdu cantitatea.
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="bg-light">
                <tr>
                  <th style="width: 50px;">#</th>
                  <th style="width: 200px;">Cod Produs (SKU)</th>
                  <th>Produs Identificat</th>
                  <th style="width: 150px;" class="text-center">Stoc</th>
                  <th style="width: 120px;" class="text-center">Cantitate</th>
                  <th style="width: 50px;"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in manualRows" :key="idx">
                  <td class="text-center text-muted">{{ idx + 1 }}</td>
                  <td>
                    <input 
                      type="text" 
                      class="form-control font-monospace" 
                      v-model="row.sku"
                      @blur="validateSku(row)"
                      @keydown.enter.prevent="validateSku(row)"
                      placeholder="Ex: PROD-001"
                      :disabled="row.loading"
                    >
                  </td>
                  <td>
                    <div v-if="row.loading" class="spinner-border spinner-border-sm text-primary"></div>
                    <div v-else-if="row.product">
                      <div class="fw-bold text-success">{{ row.product.name }}</div>
                      <small class="text-muted">{{ formatMoney(row.product.price) }} / {{ row.product.unit || 'buc' }}</small>
                    </div>
                    <div v-else-if="row.error" class="text-danger small">
                      <i class="bi bi-exclamation-circle me-1"></i>{{ row.error }}
                    </div>
                    <div v-else class="text-muted small fst-italic">
                      Așteaptă introducerea codului...
                    </div>
                  </td>
                  <td class="text-center">
                    <span v-if="row.product" :class="getStockBadgeClass(row.product.stock_status)" class="badge rounded-pill">
                       {{ stockStatusLabel(row.product.stock_status) }}
                    </span>
                  </td>
                  <td>
                    <input 
                      type="number" 
                      class="form-control text-center" 
                      v-model.number="row.quantity" 
                      min="1"
                      :disabled="!row.product"
                    >
                  </td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-danger border-0" @click="clearRow(idx)" v-if="row.sku || row.product">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div class="d-flex justify-content-between mt-3">
             <button class="btn btn-outline-secondary" @click="addMoreRows">
               <i class="bi bi-plus-lg me-1"></i> Adaugă rânduri
             </button>
             
             <button 
                class="btn btn-primary px-4" 
                :disabled="!hasValidManualRows || addingToCart"
                @click="addManualRowsToCart"
             >
                <span v-if="addingToCart" class="spinner-border spinner-border-sm me-2"></span>
                <span v-else><i class="bi bi-cart-plus me-2"></i>Adaugă Produsele în Coș</span>
             </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: Import / Bulk Text -->
    <div v-show="activeTab === 'import'">
       <div class="row g-4">
          <div class="col-md-6">
             <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3">
                   <i class="bi bi-clipboard me-2 text-primary"></i>Copiază și Lipește (Paste)
                </div>
                <div class="card-body">
                   <p class="small text-muted mb-3">
                      Introduceți codurile produselor și cantitățile, câte unul pe rând.
                      <br>Format acceptat: <code>COD, CANTITATE</code> sau <code>COD CANTITATE</code>
                   </p>
                   <textarea 
                      v-model="bulkText" 
                      class="form-control font-monospace" 
                      rows="10" 
                      placeholder="PROD-001, 5&#10;PROD-002, 10&#10;PROD-003 2"
                   ></textarea>
                </div>
                <div class="card-footer bg-white border-top text-end">
                   <button 
                      class="btn btn-primary" 
                      :disabled="!bulkText"
                      @click="processBulkText"
                   >
                      <i class="bi bi-arrow-right-circle me-2"></i>Procesează Lista
                   </button>
                </div>
             </div>
          </div>
          
          <div class="col-md-6">
             <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3">
                   <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>Import Fișier CSV
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-5">
                   <div class="mb-4 text-muted">
                      <i class="bi bi-cloud-upload display-4"></i>
                   </div>
                   <h6 class="fw-bold">Trage fișierul aici sau click pentru upload</h6>
                   <p class="small text-muted mb-4">
                      Fișier CSV simplu. Coloana A: Cod Produs, Coloana B: Cantitate.
                      <br>Fără antet (header).
                   </p>
                   <input 
                      type="file" 
                      ref="fileInput" 
                      accept=".csv,.txt" 
                      class="d-none" 
                      @change="handleFileUpload"
                   >
                   <button class="btn btn-outline-primary" @click="$refs.fileInput.click()">
                      Alege Fișier
                   </button>
                </div>
             </div>
          </div>
       </div>
    </div>

  </div>
</template>

<script setup>
import { computed, ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '@/store/auth'
import { useCartStore } from '@/store/cart'
import api from '@/services/http'
import { useToast } from 'vue-toastification'

const authStore = useAuthStore()
const cartStore = useCartStore()
const toast = useToast()

const activeTab = ref('search') // 'search', 'manual', 'import'
const user = computed(() => authStore.user || null)
const isImpersonating = computed(() => !!authStore.impersonatedCustomer)
const frontClientLabel = computed(() => {
  if (authStore.impersonatedCustomer) {
    return authStore.impersonatedCustomer.name || ''
  }
  return authStore.user?.name || ''
})

// --- Search Tab Data ---
const activePromotions = ref([])
const filters = reactive({
  search: '',
})
const products = ref([])
const loading = ref(false)
const addingToCart = ref(false)

// --- Manual Entry Tab Data ---
const manualRows = ref(Array.from({ length: 5 }, () => ({ sku: '', quantity: 1, loading: false, product: null, error: null })))

// --- Bulk Tab Data ---
const bulkText = ref('')
const fileInput = ref(null)

// ================= METHODS =================

// 1. Search Logic
const fetchPromotions = async () => {
  try {
    const customerId = authStore.impersonatedCustomer?.id || authStore.user?.customer_id
    if (!customerId) return

    const { data } = await api.post('/account/quick-order/customer-promotions', {
      customer_id: customerId
    })
    
    const promotions = Array.isArray(data) ? data : (data.data || [])
    activePromotions.value = promotions.map(promo => ({
      ...promo,
      products: (promo.products || []).map(p => ({
        ...p,
        orderQuantity: 0
      }))
    }))
  } catch (e) {
    console.error('Error fetching promotions', e)
  }
}

const fetchProducts = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/quick-order/search', {
      params: { 
        q: filters.search,
        per_page: 50
      }
    })
    
    const items = data.data || []
    products.value = items.map(p => {
      let defaultUnit = 'buc'
      if (p.units && p.units.length > 0) {
        const def = p.units.find(u => u.is_default) || p.units[0]
        defaultUnit = def.name
      }
      return {
        ...p,
        orderQuantity: 0, 
        selectedUnit: defaultUnit
      }
    })
  } catch (e) {
    console.error('Error fetching products', e)
    toast.error('Eroare la căutarea produselor')
  } finally {
    loading.value = false
  }
}

let debounceTimer = null
const onSearchInput = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchProducts()
  }, 500)
}

const clearSearch = () => {
  filters.search = ''
  products.value = []
}

// 2. Manual Entry Logic
const addMoreRows = () => {
  for (let i = 0; i < 5; i++) {
    manualRows.value.push({ sku: '', quantity: 1, loading: false, product: null, error: null })
  }
}

const clearRow = (idx) => {
  manualRows.value[idx] = { sku: '', quantity: 1, loading: false, product: null, error: null }
}

const validateSku = async (row) => {
  if (!row.sku || row.sku.length < 3) return
  if (row.product && (row.product.code === row.sku || row.product.sku === row.sku)) return // Already valid

  row.loading = true
  row.error = null
  row.product = null
  
  try {
    // We use the search endpoint, expecting the first result to be the match
    const { data } = await api.get('/quick-order/search', {
      params: { q: row.sku, per_page: 1 }
    })
    
    const items = data.data || []
    if (items.length > 0) {
      // Check for exact match if possible, otherwise take first
      // The search matches contains LIKE %q%, so we should be careful.
      // Ideally we want an exact match endpoint, but search works for now.
      const match = items[0]
      row.product = {
         ...match,
         unit: match.unit_of_measure || 'buc' // Simplification
      }
    } else {
      row.error = 'Produsul nu a fost găsit.'
    }
  } catch (e) {
    row.error = 'Eroare server.'
  } finally {
    row.loading = false
  }
}

const hasValidManualRows = computed(() => {
  return manualRows.value.some(r => r.product && r.quantity > 0)
})

const addManualRowsToCart = async () => {
  const itemsToAdd = manualRows.value
    .filter(r => r.product && r.quantity > 0)
    .map(r => ({
      id: r.product.id,
      quantity: r.quantity,
      variant_id: r.product.variant_id || null,
      unit: r.product.unit
    }))

  if (itemsToAdd.length === 0) return

  addingToCart.value = true
  let successCount = 0

  for (const item of itemsToAdd) {
    const success = await cartStore.addItem(item.id, item.quantity, item.variant_id, item.unit)
    if (success) successCount++
  }

  addingToCart.value = false
  
  if (successCount === itemsToAdd.length) {
    toast.success(`${successCount} produse adăugate în coș!`)
    // Clear added rows
    manualRows.value.forEach(r => {
      if (r.product && r.quantity > 0) {
        r.sku = ''
        r.product = null
        r.quantity = 1
      }
    })
  } else {
    toast.warning('Unele produse nu au putut fi adăugate.')
  }
}

// 3. Bulk / Import Logic
const processBulkText = async () => {
    if (!bulkText.value) return
    
    // Parse text
    // Split by newlines
    const lines = bulkText.value.split(/\r?\n/).filter(l => l.trim())
    
    // Fill manual rows with parsed data
    manualRows.value = [] // Clear existing
    
    for (const line of lines) {
        // Try split by comma first, then space
        let parts = line.split(',')
        if (parts.length < 2) {
            parts = line.split(/\s+/) // Split by whitespace
        }
        
        const sku = parts[0]?.trim()
        let qty = 1
        
        if (parts.length >= 2) {
             const parsedQty = parseFloat(parts[1]?.trim())
             if (!isNaN(parsedQty)) qty = parsedQty
        }
        
        if (sku) {
            manualRows.value.push({ sku, quantity: qty, loading: false, product: null, error: null })
        }
    }
    
    // Pad with empty rows if needed
    while (manualRows.value.length < 5) {
        manualRows.value.push({ sku: '', quantity: 1, loading: false, product: null, error: null })
    }
    
    // Switch to manual tab and validate all
    activeTab.value = 'manual'
    bulkText.value = ''
    
    // Trigger validation for all filled rows
    // Do it sequentially or parallel? Parallel is fine for a few rows.
    for (const row of manualRows.value) {
        if (row.sku) await validateSku(row)
    }
}

const handleFileUpload = (event) => {
    const file = event.target.files[0]
    if (!file) return
    
    const reader = new FileReader()
    reader.onload = (e) => {
        bulkText.value = e.target.result
        processBulkText()
    }
    reader.readAsText(file)
    // Reset input
    event.target.value = ''
}

// --- Utils ---
const incrementPromo = (prod) => {
  prod.orderQuantity = (prod.orderQuantity || 0) + 1
}

const decrementPromo = (prod) => {
  if (prod.orderQuantity > 0) prod.orderQuantity--
}

const hasPromoSelection = (promo) => {
  return promo.products.some(p => p.orderQuantity > 0)
}

const addPromoToCart = async (promo) => {
  const itemsToAdd = promo.products
    .filter(p => p.orderQuantity > 0)
    .map(p => ({
      id: p.id,
      quantity: p.orderQuantity,
    }))

  if (itemsToAdd.length === 0) return

  addingToCart.value = true
  let successCount = 0

  for (const item of itemsToAdd) {
    const success = await cartStore.addItem(item.id, item.quantity)
    if (success) successCount++
  }

  addingToCart.value = false
  
  if (successCount === itemsToAdd.length) {
    toast.success(`Produsele din oferta ${promo.name} au fost adăugate!`)
    promo.products.forEach(p => p.orderQuantity = 0)
  } else {
    toast.warning('Unele produse nu au putut fi adăugate.')
  }
}

const formatMoney = (val) => {
  if (!val) return '0.00 RON'
  return parseFloat(val).toLocaleString('ro-RO', {
    style: 'currency',
    currency: 'RON'
  })
}

const stockStatusLabel = (status) => {
  switch (status) {
    case 'in_stock': return 'In stoc'
    case 'low_stock': return 'Stoc limitat'
    case 'out_of_stock': return 'Stoc epuizat'
    case 'supplier': return 'La furnizor'
    default: return status || 'N/A'
  }
}

const getStockBadgeClass = (status) => {
    switch (status) {
        case 'in_stock': return 'bg-success-subtle text-success border border-success-subtle'
        case 'low_stock': return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
        case 'out_of_stock': return 'bg-danger-subtle text-danger border border-danger-subtle'
        case 'supplier': return 'bg-secondary-subtle text-secondary border border-secondary-subtle'
        default: return 'bg-light text-dark border'
    }
}

const increment = (product) => {
  product.orderQuantity = (product.orderQuantity || 0) + 1
}

const decrement = (product) => {
  if (product.orderQuantity > 0) {
    product.orderQuantity--
  }
}

const totalQuantity = computed(() => {
  return products.value.reduce((sum, p) => sum + (p.orderQuantity || 0), 0)
})

const resetQuantities = () => {
  products.value.forEach(p => {
      p.orderQuantity = 0;
  })
}

const addAllToCart = async () => {
  const itemsToAdd = products.value
    .filter(p => p.orderQuantity > 0)
    .map(p => ({
      id: p.id,
      quantity: p.orderQuantity,
      variant_id: p.variant_id || null,
      unit: p.selectedUnit
    }))

  if (itemsToAdd.length === 0) return

  addingToCart.value = true
  let successCount = 0

  for (const item of itemsToAdd) {
    const success = await cartStore.addItem(item.id, item.quantity, item.variant_id, item.unit)
    if (success) successCount++
  }

  addingToCart.value = false
  
  if (successCount === itemsToAdd.length) {
    toast.success('Produsele au fost adăugate în coș!')
    resetQuantities()
  } else if (successCount > 0) {
    toast.warning(`Doar ${successCount} din ${itemsToAdd.length} produse au fost adăugate.`)
  } else {
    toast.error('Nu s-au putut adăuga produsele în coș.')
  }
}

// Initial fetch
onMounted(() => {
  fetchProducts()
  fetchPromotions()
})
</script>
