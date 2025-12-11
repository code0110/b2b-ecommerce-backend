<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Produse noi</h1>
        <p class="text-muted small mb-0">
          Listare demo de produse marcate ca noutăți.
        </p>
      </div>
      <div class="small text-muted">
        {{ filteredProducts.length }} produse noi
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
            <label class="form-label">Sortare</label>
            <select
              class="form-select form-select-sm"
              v-model="sortKey"
            >
              <option value="recency">Cele mai noi</option>
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
              <span class="badge bg-success me-1">Nou</span>
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
          Nu sunt produse noi care să corespundă filtrării.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const products = [
  {
    slug: 'ciment-premium-42-5',
    name: 'Ciment Premium 42.5',
    code: 'PRD-NEW-001',
    category: 'Materiale de construcții',
    price: 48.5,
    inStock: true,
    createdAt: '2025-02-20'
  },
  {
    slug: 'vopsea-lavabila-interior',
    name: 'Vopsea lavabilă interior 15L',
    code: 'PRD-NEW-002',
    category: 'Finisaje',
    price: 210.0,
    inStock: true,
    createdAt: '2025-02-18'
  },
  {
    slug: 'bormasina-compacta',
    name: 'Bormașină compactă 18V',
    code: 'PRD-NEW-003',
    category: 'Unelte electrice',
    price: 399.9,
    inStock: false,
    createdAt: '2025-02-16'
  },
  {
    slug: 'sistem-scaffolding-aluminiu',
    name: 'Sistem schelă aluminiu',
    code: 'PRD-NEW-004',
    category: 'Echipamente șantier',
    price: 2850.0,
    inStock: true,
    createdAt: '2025-02-10'
  }
]

const search = ref('')
const sortKey = ref('recency')

const filteredProducts = computed(() => {
  return products.filter((p) => {
    if (!search.value) return true
    return (
      p.name.toLowerCase().includes(search.value.toLowerCase()) ||
      p.code.toLowerCase().includes(search.value.toLowerCase())
    )
  })
})

const sortedProducts = computed(() => {
  const arr = [...filteredProducts.value]
  if (sortKey.value === 'price-asc') {
    arr.sort((a, b) => a.price - b.price)
  } else if (sortKey.value === 'price-desc') {
    arr.sort((a, b) => b.price - a.price)
  } else {
    arr.sort((a, b) => (a.createdAt < b.createdAt ? 1 : -1))
  }
  return arr
})
</script>
