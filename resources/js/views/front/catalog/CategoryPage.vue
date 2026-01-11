<template>
  <div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="small mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <RouterLink to="/">Acasă</RouterLink>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
          {{ category.name || 'Categorie' }}
        </li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">{{ category.name || 'Încărcare...' }}</h1>
        <div v-if="category.description" class="text-muted small mb-0" v-html="category.description"></div>
      </div>
      <div class="small text-muted">
        {{ pagination.total || 0 }} produse
      </div>
    </div>

    <div class="row g-3">
      <!-- Sidebar filtre -->
      <div class="col-lg-3">
        <div class="card mb-3">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Filtre</strong>
          </div>
          <div class="card-body small">
            
            <!-- Brand Filter -->
            <div class="mb-3" v-if="availableFilters.brands.length">
              <label class="form-label fw-bold">Brand</label>
              <select
                class="form-select form-select-sm"
                v-model="filters.brand_id"
                @change="applyFilters"
              >
                <option value="">Toate brandurile</option>
                <option
                  v-for="brand in availableFilters.brands"
                  :key="brand.id"
                  :value="brand.id"
                >
                  {{ brand.name }}
                </option>
              </select>
            </div>

            <!-- Stock Filter -->
            <div class="mb-3">
              <label class="form-label fw-bold">Disponibilitate</label>
              <div class="form-check">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="filterInStock"
                  v-model="filters.in_stock"
                  @change="applyFilters"
                />
                <label class="form-check-label" for="filterInStock">
                  Doar produse în stoc
                </label>
              </div>
            </div>

            <!-- Price Filter -->
            <div class="mb-3" v-if="availableFilters.price.max > 0">
              <label class="form-label fw-bold">Preț (max)</label>
              <input
                type="range"
                class="form-range"
                :min="availableFilters.price.min"
                :max="availableFilters.price.max"
                step="1"
                v-model.number="filters.max_price"
                @change="applyFilters"
              >
              <div class="small text-muted d-flex justify-content-between">
                <span>{{ filters.max_price }} RON</span>
                <span>(Max: {{ availableFilters.price.max }})</span>
              </div>
            </div>

            <!-- Dynamic Attribute Filters -->
            <div class="mb-3 border-top pt-3" v-for="attr in availableFilters.attributes" :key="attr.id">
              <label class="form-label fw-bold">{{ attr.name }}</label>
              
              <!-- Checkboxes for options -->
              <div class="form-check" v-for="(opt, idx) in attr.options" :key="idx">
                <input
                  class="form-check-input"
                  type="checkbox"
                  :id="`attr-${attr.slug}-${idx}`"
                  :value="opt"
                  v-model="filters.attributes[attr.slug]"
                  @change="applyFilters"
                />
                <label class="form-check-label" :for="`attr-${attr.slug}-${idx}`">
                  {{ opt }}
                </label>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Listare produse -->
      <div class="col-lg-9">
        
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-orange" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

        <div v-else>
          <!-- Toolbar -->
          <div class="d-flex justify-content-between align-items-center mb-2 small">
            <div>
              <strong>Sortare:</strong>
              <button
                type="button"
                class="btn btn-link btn-sm text-decoration-none"
                :class="filters.sort === 'relevance' ? 'fw-semibold' : ''"
                @click="setSort('relevance')"
              >
                Relevanță
              </button>
              <button
                type="button"
                class="btn btn-link btn-sm text-decoration-none"
                :class="filters.sort === 'price_asc' ? 'fw-semibold' : ''"
                @click="setSort('price_asc')"
              >
                Preț crescător
              </button>
              <button
                type="button"
                class="btn btn-link btn-sm text-decoration-none"
                :class="filters.sort === 'price_desc' ? 'fw-semibold' : ''"
                @click="setSort('price_desc')"
              >
                Preț descrescător
              </button>
            </div>
            <div>
              <span class="badge bg-light text-dark">
                {{ pagination.total }} rezultate
              </span>
            </div>
          </div>

          <!-- Products Grid -->
          <div class="row g-3">
            <div
              v-for="product in products"
              :key="product.id"
              class="col-6 col-md-4 col-lg-3 col-xxl-2"
            >
              <ProductCard :product="product" />
            </div>

            <!-- Empty State -->
            <div v-if="products.length === 0" class="col-12">
              <div class="alert alert-info small mb-0">
                Nu au fost găsite produse care să corespundă criteriilor selectate.
              </div>
            </div>
          </div>
          
          <!-- Pagination (Simplified) -->
          <div class="d-flex justify-content-center mt-4" v-if="pagination.last_page > 1">
             <nav>
                <ul class="pagination pagination-sm">
                   <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                      <button class="page-link" @click="changePage(pagination.current_page - 1)">Anterior</button>
                   </li>
                   <li class="page-item active">
                      <span class="page-link">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                   </li>
                   <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                      <button class="page-link" @click="changePage(pagination.current_page + 1)">Următor</button>
                   </li>
                </ul>
             </nav>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { fetchCategoryPage } from '@/services/catalog'
import ProductCard from '@/components/common/ProductCard.vue'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const category = ref({})
const products = ref([])
const pagination = ref({})
const availableFilters = ref({
  brands: [],
  price: { min: 0, max: 0 },
  attributes: []
})

// Filters state
const filters = reactive({
  brand_id: '',
  in_stock: false,
  min_price: null,
  max_price: null,
  attributes: {}, // { slug: [val1, val2] }
  sort: 'relevance',
  page: 1
})

const fetchData = async () => {
  loading.value = true
  try {
    const slug = route.params.slug
    const params = {
       ...filters,
       attributes: filters.attributes // Pass attributes object directly, axios handles it usually or we might need to stringify
    }
    
    // Clean params
    if (!params.brand_id) delete params.brand_id
    if (!params.in_stock) delete params.in_stock
    
    const resp = await fetchCategoryPage(slug, params)
    
    category.value = resp.category
    products.value = resp.products.data
    pagination.value = {
       current_page: resp.products.current_page,
       last_page: resp.products.last_page,
       total: resp.products.total
    }
    
    // Update available filters only if it's the first load or if we want to update facets based on selection
    // Usually facets update on selection to narrow down.
    availableFilters.value = resp.filters
    
    // Initialize attributes in filters object if not present
    resp.filters.attributes.forEach(attr => {
       if (!filters.attributes[attr.slug]) {
          filters.attributes[attr.slug] = []
       }
    })
    
    // Set max price initial if not set
    if (filters.max_price === null && resp.filters.price.max) {
       filters.max_price = resp.filters.price.max
    }

  } catch (e) {
    console.error('Error fetching category:', e)
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  filters.page = 1
  fetchData()
}

const setSort = (sortKey) => {
  filters.sort = sortKey
  applyFilters()
}

const changePage = (page) => {
   if (page < 1 || page > pagination.value.last_page) return
   filters.page = page
   fetchData()
   window.scrollTo({ top: 0, behavior: 'smooth' })
}

watch(
  () => route.params.slug,
  (newSlug) => {
    if (newSlug) {
      // Reset filters on category change
      filters.brand_id = ''
      filters.in_stock = false
      filters.attributes = {}
      filters.page = 1
      fetchData()
    }
  }
)

onMounted(() => {
  fetchData()
})
</script>
