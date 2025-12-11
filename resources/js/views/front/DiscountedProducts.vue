<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Produse în promoție</h1>
        <p class="text-muted small mb-0">
          Listare demo de produse cu reduceri active.
        </p>
      </div>
      <div class="small text-muted">
        {{ filteredProducts.length }} produse în promoție
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body small">
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Căutare</label>
            <input
              type="text"
              class="form-control form-control-sm"
              placeholder="denumire sau cod produs..."
              v-model="search"
            />
          </div>
          <div class="col-md-4">
            <label class="form-label">Tip promoție (demo)</label>
            <select
              class="form-select form-select-sm"
              v-model="promoType"
            >
              <option value="">Toate</option>
              <option value="percent">Discount procentual</option>
              <option value="value">Discount valoric</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Sortare</label>
            <select
              class="form-select form-select-sm"
              v-model="sortKey"
            >
              <option value="discount">Discount descrescător</option>
              <option value="price-asc">Preț crescător</option>
              <option value="price-desc">Preț descrescător</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div
        v-for="product in sortedProducts"
        :key="product.slug"
        class="col-md-3 col-sm-6"
      >
        <div class="card h-100">
          <div class="card-body d-flex flex-column small">
            <div class="text-muted mb-1">{{ product.category }}</div>
            <h2 class="h6 mb-1">{{ product.name }}</h2>
            <div class="text-muted mb-2">{{ product.code }}</div>
            <div class="mb-2">
              <span class="badge bg-success me-1">
                -{{ product.discountPercent }}%
              </span>
              <span
                class="badge"
                :class="product.inStock ? 'bg-success' : 'bg-secondary'"
              >
                {{ product.inStock ? 'În stoc' : 'La comandă' }}
              </span>
            </div>
            <div class="mb-2">
              <span class="text-muted text-decoration-line-through me-1">
                {{ product.price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
              </span>
              <span class="fw-semibold">
                {{ product.promoPrice.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                RON
              </span>
            </div>
            <div class="mt-auto">
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
          Nu sunt produse în promoție care să corespundă filtrării.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const products = [
  {
    slug: 'ciment-portland-40kg',
    name: 'Ciment Portland 40kg',
    code: 'PRD-001',
    category: 'Materiale de construcții',
    price: 45.0,
    promoPrice: 40.5,
    discountPercent: 10,
    promoType: 'percent',
    inStock: true
  },
  {
    slug: 'pavaj-beton',
    name: 'Pavaj beton 20x10',
    code: 'PRD-020',
    category: 'Pavaje',
    price: 3.2,
    promoPrice: 3.04,
    discountPercent: 5,
    promoType: 'percent',
    inStock: true
  },
  {
    slug: 'echipament-protectie-kit',
    name: 'Kit echipament protecție',
    code: 'PRD-030',
    category: 'Echipamente protecție',
    price: 150.0,
    promoPrice: 130.0,
    discountPercent: 13,
    promoType: 'value',
    inStock: false
  }
]

const search = ref('')
const promoType = ref('')
const sortKey = ref('discount')

const filteredProducts = computed(() => {
  return products.filter((p) => {
    const matchesSearch =
      !search.value ||
      p.name.toLowerCase().includes(search.value.toLowerCase()) ||
      p.code.toLowerCase().includes(search.value.toLowerCase())

    const matchesType = !promoType.value || p.promoType === promoType.value

    return matchesSearch && matchesType
  })
})

const sortedProducts = computed(() => {
  const arr = [...filteredProducts.value]
  if (sortKey.value === 'price-asc') {
    arr.sort((a, b) => a.promoPrice - b.promoPrice)
  } else if (sortKey.value === 'price-desc') {
    arr.sort((a, b) => b.promoPrice - a.promoPrice)
  } else {
    arr.sort((a, b) => b.discountPercent - a.discountPercent)
  }
  return arr
})
</script>
