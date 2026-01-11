<template>
  <div class="container py-4">
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <template v-else>
      <!-- Breadcrumbs -->
      <nav aria-label="breadcrumb" class="small mb-3">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item">
            <RouterLink to="/">Acasă</RouterLink>
          </li>
          <li class="breadcrumb-item active" aria-current="page">
            {{ category?.name || 'Categorie' }}
          </li>
        </ol>
      </nav>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h1 class="h4 mb-1">{{ category?.name }}</h1>
          <p class="text-muted small mb-0" v-if="category?.description">
            {{ category.description }}
          </p>
        </div>
        <div class="small text-muted">
          {{ products.length }} produse
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
              <div class="mb-3">
                <label class="form-label">Căutare</label>
                <input
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="denumire, cod..."
                  v-model="filters.search"
                  @input="debouncedSearch"
                />
              </div>

              <div class="mb-3" v-if="availableBrands.length > 0">
                <label class="form-label">Brand</label>
                <select
                  class="form-select form-select-sm"
                  v-model="filters.brand_id"
                  @change="fetchProducts"
                >
                  <option value="">Toate brandurile</option>
                  <option
                    v-for="brand in availableBrands"
                    :key="brand.id"
                    :value="brand.id"
                  >
                    {{ brand.name }}
                  </option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">Preț (max)</label>
                <input
                  type="range"
                  class="form-range"
                  min="0"
                  max="10000"
                  step="50"
                  v-model.number="filters.max_price"
                  @change="fetchProducts"
                />
                <div class="small text-muted">
                  Până la
                  <strong>
                    {{ (filters.max_price || 0).toLocaleString('ro-RO') }} RON
                  </strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Listare produse -->
        <div class="col-lg-9">
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
                Pagina {{ pagination.current_page }} din {{ pagination.last_page }}
              </span>
            </div>
          </div>

          <div class="row g-3">
            <div
              v-for="product in products"
              :key="product.id"
              class="col-md-4 col-sm-6"
            >
              <div class="card h-100">
                <div class="card-body d-flex flex-column">
                  <div class="small text-muted mb-1" v-if="product.brand">
                    Brand: {{ product.brand.name }}
                  </div>
                  <h3 class="h6 mb-1">
                     <RouterLink :to="`/produs/${product.slug}`" class="text-decoration-none text-dark">
                        {{ product.name }}
                     </RouterLink>
                  </h3>
                  <div class="small text-muted mb-2">
                    {{ product.sku }}
                  </div>
                  
                  <div class="mb-2 text-center" style="height: 150px; overflow: hidden;">
                     <img 
                        v-if="product.main_image" 
                        :src="product.main_image" 
                        alt="Product Image" 
                        class="img-fluid h-100 w-auto" 
                        style="object-fit: contain;"
                      />
                     <div v-else class="d-flex align-items-center justify-content-center h-100 bg-light text-muted small">
                        Fără imagine
                     </div>
                  </div>

                  <div class="mb-2 small">
                    <span
                      class="badge"
                      :class="product.in_stock ? 'bg-success' : 'bg-secondary'"
                    >
                      {{ product.in_stock ? 'În stoc' : 'La comandă' }}
                    </span>
                    <span v-if="product.stock_status" class="ms-1 text-muted">
                        ({{ product.stock_status }})
                    </span>
                  </div>
                  <div class="mt-auto">
                    <div class="fw-semibold mb-1">
                      {{ (product.price || 0).toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                      RON
                      <small class="text-muted fw-normal">/ {{ product.unit_of_measure || 'buc' }}</small>
                    </div>
                    <RouterLink
                      :to="`/produs/${product.slug}`"
                      class="btn btn-outline-primary btn-sm w-100"
                    >
                      Detalii produs
                    </RouterLink>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="products.length === 0" class="col-12">
              <div class="alert alert-info small mb-0">
                Nu au fost găsite produse în această categorie.
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <nav v-if="pagination.last_page > 1" class="mt-4">
            <ul class="pagination justify-content-center pagination-sm">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button class="page-link" @click="changePage(pagination.current_page - 1)">
                  &laquo;
                </button>
              </li>
              <li 
                class="page-item" 
                v-for="page in pagination.last_page" 
                :key="page"
                :class="{ active: page === pagination.current_page }"
              >
                <button class="page-link" @click="changePage(page)">
                  {{ page }}
                </button>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <button class="page-link" @click="changePage(pagination.current_page + 1)">
                  &raquo;
                </button>
              </li>
            </ul>
          </nav>

        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/http'
import debounce from 'lodash.debounce'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const error = ref(null)
const category = ref(null)
const products = ref([])
const availableBrands = ref([])
const pagination = reactive({
    current_page: 1,
    last_page: 1,
    total: 0
})

const filters = reactive({
  search: '',
  brand_id: '',
  max_price: 5000,
  sort: 'relevance'
})

const fetchProducts = async (page = 1) => {
    const slug = route.params.slug
    if (!slug) return

    loading.value = true
    error.value = null

    try {
        const params = {
            page,
            sort: filters.sort,
            brand_id: filters.brand_id,
            max_price: filters.max_price,
        }
        
        if (filters.search) {
             params.search = filters.search
        }

        const { data } = await api.get(`/categories/${slug}`, { params })
        
        category.value = data.category
        products.value = data.products.data
        pagination.current_page = data.products.current_page
        pagination.last_page = data.products.last_page
        pagination.total = data.products.total
        
        if (data.filters) {
            if (data.filters.brands) {
                availableBrands.value = data.filters.brands
            }
            // Optional: update max price based on range, but maybe user wants to keep their setting
            // if (data.filters.price && data.filters.price.max > 0) {
            //    filters.max_price = Math.ceil(data.filters.price.max)
            // }
        }
        
    } catch (e) {
        console.error(e)
        error.value = 'Eroare la încărcarea produselor. Vă rugăm încercați din nou.'
    } finally {
        loading.value = false
    }
}

const debouncedSearch = debounce(() => {
    fetchProducts(1)
}, 500)

const setSort = (key) => {
    filters.sort = key
    fetchProducts(1)
}

const changePage = (page) => {
    if (page < 1 || page > pagination.last_page) return
    fetchProducts(page)
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

watch(() => route.params.slug, () => {
    fetchProducts(1)
})

watch(() => filters.search, debouncedSearch)

watch(() => filters.brand_id, () => {
    fetchProducts(1)
})

watch(() => filters.max_price, debounce(() => {
    fetchProducts(1)
}, 500))

onMounted(() => {
    fetchProducts(1)
})
</script>
