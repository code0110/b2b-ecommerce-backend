<template>
  <div class="container py-4">
    <div class="row mb-3">
      <div class="col-md-8">
        <h1 class="h5 mb-1">Comandă rapidă</h1>
        <p class="text-muted small mb-0">
          Caută produse după denumire sau cod și adaugă-le rapid în coș.
        </p>
      </div>
      <div class="col-md-4 text-md-end small mt-2 mt-md-0" v-if="frontClientLabel">
        <div class="text-muted">
          Client activ: <strong>{{ frontClientLabel }}</strong>
        </div>
        <div class="text-muted" v-if="isImpersonating">
          (mod impersonare – {{ user?.name || 'agent/director' }})
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body small">
        <div class="row g-2 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Căutare rapidă</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="Caută după denumire, cod produs..."
              @input="onSearchInput"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label">Status</label>
             <div v-if="loading" class="text-muted form-control-plaintext form-control-sm">
                Se încarcă...
             </div>
             <div v-else class="text-muted form-control-plaintext form-control-sm">
                Gata de căutare
             </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong class="small text-uppercase">Rezultate căutare</strong>
        <span class="badge bg-light text-dark small">
          {{ products.length }} produse găsite
        </span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light small text-uppercase text-muted">
              <tr>
                <th style="width: 40px;" class="text-center">#</th>
                <th>Produs</th>
                <th style="width: 120px;">Cod intern</th>
                <th style="width: 100px;" class="text-center">Unitate</th>
                <th style="width: 110px;" class="text-end">Preț Listă</th>
                <th style="width: 90px;" class="text-center">Stoc</th>
                <th style="width: 120px;" class="text-center">Cantitate</th>
              </tr>
            </thead>
            <tbody class="small">
              <tr v-for="(product, index) in products" :key="`${product.id}_${product.variant_id || 'main'}`">
                <td class="text-center">{{ index + 1 }}</td>
                <td>
                  <div class="fw-semibold">
                    <router-link :to="`/product/${product.slug}`" class="text-decoration-none text-dark">
                      {{ product.name }}
                    </router-link>
                  </div>
                  <div class="text-muted">
                    {{ product.category || 'Fără categorie' }}
                  </div>
                </td>
                <td>
                  <span class="text-monospace">{{ product.code }}</span>
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
                  <span v-else class="badge bg-light text-dark border">
                    {{ product.selectedUnit || 'buc' }}
                  </span>
                </td>
                <td class="text-end">
                  {{ formatMoney(product.price) }}
                </td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="{
                      'bg-success': product.stock_status === 'in_stock',
                      'bg-secondary': product.stock_status !== 'in_stock'
                    }"
                  >
                    {{ stockStatusLabel(product.stock_status) }}
                  </span>
                </td>
                <td class="text-center" style="max-width: 120px;">
                  <input
                    type="number"
                    min="0"
                    class="form-control form-control-sm text-center"
                    v-model.number="product.orderQuantity"
                  />
                </td>
              </tr>
              <tr v-if="products.length === 0 && !loading">
                <td colspan="6">
                  <div class="text-center text-muted py-4">
                    Nu au fost găsite produse. Încearcă alt termen de căutare.
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center small">
        <div class="text-muted">
          Cantitate totală selectată: <strong>{{ totalQuantity }}</strong> buc.
          <span v-if="addingToCart" class="ms-2 text-orange">Se adaugă în coș...</span>
        </div>
        <div class="d-flex gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            @click="resetQuantities"
          >
            Resetează
          </button>
          <button
            type="button"
            class="btn btn-orange btn-sm"
            :disabled="totalQuantity === 0 || addingToCart"
            @click="addAllToCart"
          >
            Adaugă tot în coș
          </button>
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
})
</script>
