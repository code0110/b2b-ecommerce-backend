<template>
  <div class="container py-5">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1 text-primary">
          <i class="bi bi-lightning-charge-fill me-2"></i>Comandă rapidă
        </h1>
        <p class="text-muted mb-0">
          Caută produse după denumire sau cod și adaugă-le rapid în coș.
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

    <!-- Search Section -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
      <div class="card-body p-4 bg-light bg-opacity-50">
        <div class="row g-3 align-items-center">
          <div class="col-md-8 col-lg-6">
            <div class="input-group input-group-lg shadow-sm">
              <span class="input-group-text bg-white border-end-0 text-muted">
                <i class="bi bi-search"></i>
              </span>
              <input
                v-model="filters.search"
                type="text"
                class="form-control border-start-0 ps-0"
                placeholder="Caută produs (nume, cod, SKU)..."
                @input="onSearchInput"
                autofocus
              />
              <button 
                v-if="filters.search" 
                class="btn btn-white border border-start-0"
                @click="clearSearch"
              >
                <i class="bi bi-x-lg text-muted"></i>
              </button>
            </div>
          </div>
          <div class="col-md-4 col-lg-3">
             <div v-if="loading" class="d-flex align-items-center text-primary">
                <span class="spinner-border spinner-border-sm me-2"></span>
                <span class="small fw-semibold">Se caută...</span>
             </div>
             <div v-else-if="products.length > 0" class="text-success small fw-semibold">
                <i class="bi bi-check-circle-fill me-1"></i> {{ products.length }} produse găsite
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

const user = computed(() => authStore.user || null)
const isImpersonating = computed(() => !!authStore.impersonatedCustomer)
const frontClientLabel = computed(() => {
  if (authStore.impersonatedCustomer) {
    return authStore.impersonatedCustomer.name || ''
  }
  return authStore.user?.name || ''
})

const filters = reactive({
  search: '',
})

const products = ref([])
const loading = ref(false)
const addingToCart = ref(false)

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

const clearSearch = () => {
  filters.search = ''
  products.value = []
}

const increment = (product) => {
  product.orderQuantity = (product.orderQuantity || 0) + 1
}

const decrement = (product) => {
  if (product.orderQuantity > 0) {
    product.orderQuantity--
  }
}

// Debounce logic
let debounceTimer = null
const onSearchInput = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchProducts()
  }, 500)
}

const fetchProducts = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/quick-order/search', {
      params: { q: filters.search }
    })
    products.value = data.map(p => {
      let defaultUnit = 'buc'
      if (p.units && p.units.length > 0) {
        const def = p.units.find(u => u.is_default) || p.units[0]
        defaultUnit = def.name
      }
      return {
        ...p,
        orderQuantity: 0, // Local state for input
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

  // Sequential add to ensure order and avoid race conditions
  for (const item of itemsToAdd) {
    // Pass unit as 4th argument
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
