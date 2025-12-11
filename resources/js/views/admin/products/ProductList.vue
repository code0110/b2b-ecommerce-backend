<template>
  <div class="container-fluid">
    <PageHeader
      title="Produse - listă"
      subtitle="Administrare catalog produse: status, stoc, prețuri și promovare."
    >
      <RouterLink :to="{ name: 'admin-products-new' }" class="btn btn-primary btn-sm">
        + Produs nou
      </RouterLink>
    </PageHeader>

    <!-- Filtre simple -->
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <form class="row g-3 align-items-end" @submit.prevent>
          <div class="col-md-3">
            <label class="form-label small text-muted">Căutare</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="Denumire produs, cod intern..."
            />
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Categorie</label>
            <select v-model="filters.category" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option v-for="cat in categories" :key="cat" :value="cat">
                {{ cat }}
              </option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Status publicare</label>
            <select v-model="filters.status" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="published">Publicat</option>
              <option value="hidden">Ascuns</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Stoc</label>
            <select v-model="filters.stockStatus" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="in_stock">În stoc</option>
              <option value="out_of_stock">Epuizat</option>
              <option value="low_stock">Sub limită</option>
              <option value="on_order">La comandă</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Brand</label>
            <select v-model="filters.brand" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option v-for="brand in brands" :key="brand" :value="brand">
                {{ brand }}
              </option>
            </select>
          </div>
          <div class="col-md-1 text-end">
            <button type="button" class="btn btn-link btn-sm" @click="showAdvanced = !showAdvanced">
              Filtre avansate
            </button>
          </div>
        </form>

        <!-- Filtre avansate -->
        <transition name="fade">
          <div v-if="showAdvanced" class="border-top pt-3 mt-3">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label small text-muted">Interval preț minim</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">De la</span>
                  <input v-model.number="filters.priceMin" type="number" class="form-control" />
                  <span class="input-group-text">RON</span>
                </div>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-muted">Interval preț maxim</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">Până la</span>
                  <input v-model.number="filters.priceMax" type="number" class="form-control" />
                  <span class="input-group-text">RON</span>
                </div>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-muted">Etichete</label>
                <select v-model="filters.tag" class="form-select form-select-sm">
                  <option value="">Toate</option>
                  <option value="nou">Nou</option>
                  <option value="promo">Promoție</option>
                  <option value="bestseller">Best seller</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-muted">Status sincronizare ERP</label>
                <select v-model="filters.erpSync" class="form-select form-select-sm">
                  <option value="">Toate</option>
                  <option value="synced">Sincronizat</option>
                  <option value="pending">În așteptare</option>
                  <option value="error">Eroare</option>
                </select>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>

    <!-- Lista produse -->
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th @click="changeSort('name')" role="button">
                  Denumire produs
                  <SortIcon :active="sortBy === 'name'" :direction="sortDir" />
                </th>
                <th @click="changeSort('internalCode')" role="button">
                  Cod intern
                  <SortIcon :active="sortBy === 'internalCode'" :direction="sortDir" />
                </th>
                <th>
                  Categorie principală
                </th>
                <th @click="changeSort('stockQty')" role="button" class="text-center">
                  Stoc
                  <SortIcon :active="sortBy === 'stockQty'" :direction="sortDir" />
                </th>
                <th @click="changeSort('listPrice')" role="button" class="text-end">
                  Preț listă
                  <SortIcon :active="sortBy === 'listPrice'" :direction="sortDir" />
                </th>
                <th class="text-center">Promovat</th>
                <th class="text-center">Publicat</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="pagedProducts.length === 0">
                <td colspan="8" class="text-center text-muted py-4">
                  Nu s-au găsit produse pentru filtrele selectate.
                </td>
              </tr>
              <tr v-for="product in pagedProducts" :key="product.id">
                <td>
                  <div class="fw-semibold">{{ product.name }}</div>
                  <div class="small text-muted">
                    Brand: {{ product.brand }} · ERP: {{ product.erpId }}
                  </div>
                </td>
                <td>{{ product.internalCode }}</td>
                <td>{{ product.mainCategory }}</td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="stockBadgeClass(product.stockStatus)"
                  >
                    {{ stockLabel(product.stockStatus) }}
                  </span>
                  <div class="small text-muted" v-if="product.stockStatus === 'in_stock' || product.stockStatus === 'low_stock'">
                    {{ product.stockQty }} buc
                  </div>
                </td>
                <td class="text-end">
                  {{ product.listPrice.toFixed(2) }} RON
                </td>
                <td class="text-center">
                  <span
                    class="badge bg-warning text-dark"
                    v-if="product.isPromoted"
                  >
                    Promo
                  </span>
                </td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="product.isPublished ? 'bg-success' : 'bg-secondary'"
                  >
                    {{ product.isPublished ? 'Publicat' : 'Ascuns' }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <RouterLink
                      :to="{ name: 'admin-products-edit', params: { id: product.id } }"
                      class="btn btn-outline-primary btn-sm"
                    >
                      Editează
                    </RouterLink>
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-sm"
                      @click="togglePublish(product.id)"
                    >
                      {{ product.isPublished ? 'Ascunde' : 'Publică' }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginare -->
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top small">
          <div>
            Afișate
            <strong v-if="totalProducts > 0">
              {{ (page - 1) * pageSize + 1 }}–{{ Math.min(page * pageSize, totalProducts) }}
            </strong>
            <span v-else>0</span>
            din {{ totalProducts }} produse.
          </div>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li :class="['page-item', { disabled: page === 1 }]">
                <button class="page-link" type="button" @click="page = Math.max(1, page - 1)">
                  «
                </button>
              </li>
              <li
                v-for="p in pages"
                :key="p"
                :class="['page-item', { active: p === page }]"
              >
                <button class="page-link" type="button" @click="page = p">
                  {{ p }}
                </button>
              </li>
              <li :class="['page-item', { disabled: page === pages }]" v-if="pages > 0">
                <button
                  class="page-link"
                  type="button"
                  @click="page = Math.min(pages, page + 1)"
                >
                  »
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { useProductsStore } from '@/store/products'

const productsStore = useProductsStore()

const filters = reactive({
  search: '',
  category: '',
  status: '',
  stockStatus: '',
  brand: '',
  priceMin: null,
  priceMax: null,
  tag: '',
  erpSync: ''
})

const showAdvanced = ref(false)
const page = ref(1)
const pageSize = 10
const sortBy = ref('name')
const sortDir = ref('asc')

const categories = computed(() => {
  const set = new Set()
  productsStore.all.forEach(p => {
    if (p.mainCategory) set.add(p.mainCategory)
  })
  return Array.from(set).sort()
})

const brands = computed(() => {
  const set = new Set()
  productsStore.all.forEach(p => {
    if (p.brand) set.add(p.brand)
  })
  return Array.from(set).sort()
})

const filteredProducts = computed(() => {
  return productsStore.all.filter(p => {
    if (filters.search) {
      const s = filters.search.toLowerCase()
      if (
        !(
          p.name.toLowerCase().includes(s) ||
          p.internalCode.toLowerCase().includes(s) ||
          (p.erpId && p.erpId.toLowerCase().includes(s))
        )
      ) {
        return false
      }
    }

    if (filters.category && p.mainCategory !== filters.category) return false

    if (filters.status === 'published' && !p.isPublished) return false
    if (filters.status === 'hidden' && p.isPublished) return false

    if (filters.stockStatus && p.stockStatus !== filters.stockStatus) return false

    if (filters.brand && p.brand !== filters.brand) return false

    if (filters.priceMin != null && filters.priceMin !== '' && p.listPrice < filters.priceMin) {
      return false
    }
    if (filters.priceMax != null && filters.priceMax !== '' && p.listPrice > filters.priceMax) {
      return false
    }

    if (filters.erpSync && p.erpSyncStatus !== filters.erpSync) return false

    return true
  })
})

const sortedProducts = computed(() => {
  const list = [...filteredProducts.value]
  list.sort((a, b) => {
    const dir = sortDir.value === 'asc' ? 1 : -1
    let av = a[sortBy.value]
    let bv = b[sortBy.value]

    if (typeof av === 'string') av = av.toLowerCase()
    if (typeof bv === 'string') bv = bv.toLowerCase()

    if (av < bv) return -1 * dir
    if (av > bv) return 1 * dir
    return 0
  })
  return list
})

const totalProducts = computed(() => sortedProducts.value.length)
const pages = computed(() =>
  totalProducts.value === 0 ? 0 : Math.ceil(totalProducts.value / pageSize)
)

const pagedProducts = computed(() => {
  const start = (page.value - 1) * pageSize
  return sortedProducts.value.slice(start, start + pageSize)
})

watch(
  () => ({ ...filters }),
  () => {
    page.value = 1
  },
  { deep: true }
)

const changeSort = (field) => {
  if (sortBy.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = field
    sortDir.value = 'asc'
  }
}

const togglePublish = (id) => {
  productsStore.togglePublish(id)
}

const stockLabel = (status) => {
  switch (status) {
    case 'in_stock':
      return 'În stoc'
    case 'out_of_stock':
      return 'Epuizat'
    case 'low_stock':
      return 'Stoc limitat'
    case 'on_order':
      return 'La comandă'
    default:
      return status
  }
}

const stockBadgeClass = (status) => {
  switch (status) {
    case 'in_stock':
      return 'bg-success'
    case 'out_of_stock':
      return 'bg-danger'
    case 'low_stock':
      return 'bg-warning text-dark'
    case 'on_order':
      return 'bg-info text-dark'
    default:
      return 'bg-secondary'
  }
}

const SortIcon = defineComponent({
  name: 'SortIcon',
  props: {
    active: { type: Boolean, default: false },
    direction: { type: String, default: 'asc' }
  },
  setup(props) {
    return () =>
      props.active
        ? h(
            'span',
            { class: 'ms-1 small' },
            props.direction === 'asc' ? '▲' : '▼'
          )
        : null
  }
})
</script>



<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
