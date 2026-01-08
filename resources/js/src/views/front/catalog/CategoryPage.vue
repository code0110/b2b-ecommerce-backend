<template>
  <div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="small mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <RouterLink to="/">Acasă</RouterLink>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
          {{ categoryTitle }}
        </li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">{{ categoryTitle }}</h1>
        <p class="text-muted small mb-0">
          Pagină demo de categorie, cu filtre și listare de produse.
        </p>
      </div>
      <div class="small text-muted">
        {{ filteredProducts.length }} produse demo
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
              />
            </div>

            <div class="mb-3">
              <label class="form-label">Brand</label>
              <select
                class="form-select form-select-sm"
                v-model="filters.brand"
              >
                <option value="">Toate brandurile</option>
                <option
                  v-for="brand in availableBrands"
                  :key="brand"
                  :value="brand"
                >
                  {{ brand }}
                </option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Disponibilitate</label>
              <div class="form-check">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="filterInStock"
                  v-model="filters.inStockOnly"
                />
                <label class="form-check-label" for="filterInStock">
                  Doar produse în stoc
                </label>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Preț (max)</label>
              <input
                type="range"
                class="form-range"
                min="0"
                max="5000"
                step="50"
                v-model.number="filters.maxPrice"
              />
              <div class="small text-muted">
                Până la
                <strong>
                  {{ filters.maxPrice.toLocaleString('ro-RO') }} RON
                </strong>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Atribute</label>
              <div class="form-check" v-for="attr in attributeOptions" :key="attr.value">
                <input
                  class="form-check-input"
                  type="checkbox"
                  :id="`attr-${attr.value}`"
                  :value="attr.value"
                  v-model="filters.attributes"
                />
                <label class="form-check-label" :for="`attr-${attr.value}`">
                  {{ attr.label }}
                </label>
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
              :class="sort.key === 'relevance' ? 'fw-semibold' : ''"
              @click="setSort('relevance')"
            >
              Relevanță
            </button>
            <button
              type="button"
              class="btn btn-link btn-sm text-decoration-none"
              :class="sort.key === 'price-asc' ? 'fw-semibold' : ''"
              @click="setSort('price-asc')"
            >
              Preț crescător
            </button>
            <button
              type="button"
              class="btn btn-link btn-sm text-decoration-none"
              :class="sort.key === 'price-desc' ? 'fw-semibold' : ''"
              @click="setSort('price-desc')"
            >
              Preț descrescător
            </button>
          </div>
          <div>
            <span class="badge bg-light text-dark">
              {{ filteredProducts.length }} rezultate
            </span>
          </div>
        </div>

        <div class="row g-3">
          <div
            v-for="product in sortedProducts"
            :key="product.slug"
            class="col-md-4 col-sm-6"
          >
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <div class="small text-muted mb-1">
                  Brand: {{ product.brand }}
                </div>
                <h3 class="h6 mb-1">{{ product.name }}</h3>
                <div class="small text-muted mb-2">
                  {{ product.code }}
                </div>
                <div class="mb-2 small">
                  <span
                    class="badge"
                    :class="product.inStock ? 'bg-success' : 'bg-secondary'"
                  >
                    {{ product.inStock ? 'În stoc' : 'La comandă' }}
                  </span>
                </div>
                <div class="mt-auto">
                  <div class="fw-semibold mb-1">
                    {{ product.price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                    RON
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
          <div v-if="sortedProducts.length === 0" class="col-12">
            <div class="alert alert-info small mb-0">
              Nu au fost găsite produse care să corespundă filtrării curente.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const categorySlug = computed(() => route.params.slug || 'materiale-constructii')

const demoCategories = {
  'materiale-constructii': 'Materiale de construcții',
  finisaje: 'Finisaje',
  'echipamente-santier': 'Echipamente șantier'
}

const categoryTitle = computed(() => demoCategories[categorySlug.value] || 'Categorie produse')

const allProducts = [
  {
    slug: 'ciment-portland-40kg',
    name: 'Ciment Portland 40kg',
    code: 'PRD-001',
    brand: 'BrandX',
    categorySlug: 'materiale-constructii',
    inStock: true,
    list_price: 45.0,
    price: 40.5,
    attributes: ['greu', 'bulk']
  },
  {
    slug: 'ciment-premium-42-5',
    name: 'Ciment Premium 42.5',
    code: 'PRD-002',
    brand: 'BrandX',
    categorySlug: 'materiale-constructii',
    inStock: true,
    list_price: 48.5,
    price: 48.5,
    attributes: ['premium', 'bulk']
  },
  {
    slug: 'adeziv-gresie-faianta',
    name: 'Adeziv gresie / faianță',
    code: 'PRD-005',
    brand: 'BrandY',
    categorySlug: 'finisaje',
    inStock: false,
    list_price: 35.0,
    price: 35.0,
    attributes: ['finisaj']
  },
  {
    slug: 'vopsea-lavabila-interior',
    name: 'Vopsea lavabilă interior 15L',
    code: 'PRD-006',
    brand: 'BrandZ',
    categorySlug: 'finisaje',
    inStock: true,
    list_price: 250.0,
    price: 210.0,
    attributes: ['finisaj', 'premium']
  },
  {
    slug: 'sistem-scaffolding-aluminiu',
    name: 'Sistem schelă aluminiu',
    code: 'PRD-010',
    brand: 'BrandPro',
    categorySlug: 'echipamente-santier',
    inStock: true,
    list_price: 2850.0,
    price: 2850.0,
    attributes: ['echipament', 'premium']
  }
]

const filters = reactive({
  search: '',
  brand: '',
  inStockOnly: false,
  maxPrice: 5000,
  attributes: []
})

const attributeOptions = [
  { value: 'premium', label: 'Premium' },
  { value: 'bulk', label: 'Livrare palet / vrac' },
  { value: 'finisaj', label: 'Produse de finisaj' },
  { value: 'echipament', label: 'Echipament' }
]

const availableProducts = computed(() =>
  allProducts.filter((p) => p.categorySlug === categorySlug.value)
)

const availableBrands = computed(() => {
  const set = new Set(availableProducts.value.map((p) => p.brand))
  return Array.from(set).sort()
})

const filteredProducts = computed(() => {
  return availableProducts.value.filter((p) => {
    const matchesSearch =
      !filters.search ||
      p.name.toLowerCase().includes(filters.search.toLowerCase()) ||
      p.code.toLowerCase().includes(filters.search.toLowerCase())

    const matchesBrand = !filters.brand || p.brand === filters.brand
    const matchesStock = !filters.inStockOnly || p.inStock
    const matchesPrice = !filters.maxPrice || p.price <= filters.maxPrice

    let matchesAttrs = true
    if (filters.attributes.length > 0) {
      matchesAttrs = filters.attributes.every((attr) => p.attributes.includes(attr))
    }

    return matchesSearch && matchesBrand && matchesStock && matchesPrice && matchesAttrs
  })
})

const sort = reactive({
  key: 'relevance'
})

const setSort = (key) => {
  sort.key = key
}

const sortedProducts = computed(() => {
  const arr = [...filteredProducts.value]
  if (sort.key === 'price-asc') {
    arr.sort((a, b) => a.price - b.price)
  } else if (sort.key === 'price-desc') {
    arr.sort((a, b) => b.price - a.price)
  }
  return arr
})
</script>
