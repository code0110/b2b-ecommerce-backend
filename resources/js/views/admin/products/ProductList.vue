<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Produse</h1>
      <RouterLink
        class="btn btn-sm btn-primary"
        :to="{ name: 'admin-products-new' }"
      >
        Adaugă produs
      </RouterLink>
    </div>

    <div class="card mb-3">
      <div class="card-body py-2">
        <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
          <div class="col-md-4">
            <label class="form-label form-label-sm">Căutare</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="denumire, cod intern..."
            >
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Status</label>
            <select
              v-model="filters.status"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="active">Active / publicate</option>
              <option value="inactive">Inactive / ascunse</option>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button
              type="submit"
              class="btn btn-sm btn-primary"
              :disabled="loading"
            >
              Aplică filtre
            </button>
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Denumire</th>
              <th>Cod intern</th>
              <th>Categorie</th>
              <th>Brand</th>
              <th class="text-end">Preț</th>
              <th>Status</th>
              <th style="width: 160px;">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr v-if="!loading && !products.length">
              <td colspan="7" class="text-center text-muted py-3">
                Nu există produse pentru filtrele selectate.
              </td>
            </tr>
            <tr
              v-for="product in products"
              :key="product.id"
            >
              <td class="small">
                <RouterLink
                  class="fw-semibold text-decoration-none"
                  :to="{ name: 'admin-products-edit', params: { id: product.id } }"
                >
                  {{ product.name }}
                </RouterLink>
                <div class="text-muted">
                  {{ product.slug }}
                </div>
              </td>
              <td class="small">
                {{ product.sku || product.internal_code || '-' }}
              </td>
              <td class="small">
                {{ product.main_category?.name || product.main_category_name || '-' }}
              </td>
              <td class="small">
                {{ product.brand?.name || product.brand_name || '-' }}
              </td>
              <td class="small text-end">
                {{ formatMoney(product.price || product.list_price || 0) }}
              </td>
              <td class="small">
                <span
                  class="badge"
                  :class="product.is_active ? 'bg-success' : 'bg-secondary'"
                >
                  {{ product.is_active ? 'Activ' : 'Inactiv' }}
                </span>
              </td>
              <td class="small">
                <div class="btn-group btn-group-sm">
                  <RouterLink
                    class="btn btn-outline-secondary"
                    :to="{ name: 'admin-products-edit', params: { id: product.id } }"
                  >
                    Editează
                  </RouterLink>
                  <button
                    class="btn btn-outline-danger"
                    type="button"
                    @click="removeProduct(product)"
                  >
                    Șterge
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginare simplă (dacă backend-ul trimite meta) -->
      <div
        v-if="meta && (meta.current_page && meta.last_page)"
        class="card-footer py-2 d-flex justify-content-between align-items-center small"
      >
        <div>
          Pagina {{ meta.current_page }} / {{ meta.last_page }}
        </div>
        <div class="btn-group btn-group-sm">
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="meta.current_page <= 1 || loading"
            @click="changePage(meta.current_page - 1)"
          >
            «
          </button>
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="meta.current_page >= meta.last_page || loading"
            @click="changePage(meta.current_page + 1)"
          >
            »
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  fetchProducts,
  deleteProduct
} from '@/services/admin/products'

const products = ref([])
const meta = ref(null)
const loading = ref(false)
const error = ref('')

const filters = ref({
  search: '',
  status: '',
  page: 1
})

const formatMoney = (value) => {
  if (value == null) return '0,00 RON'
  return `${Number(value).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })} RON`
}

const loadProducts = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
      page: filters.value.page || 1
    }

    const resp = await fetchProducts(params)
    products.value = resp.data || resp || []
    meta.value = resp.meta || null
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca produsele.'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  filters.value.page = 1
  loadProducts()
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    page: 1
  }
  loadProducts()
}

const changePage = (page) => {
  filters.value.page = page
  loadProducts()
}

const removeProduct = async (product) => {
  if (!confirm(`Ștergi produsul "${product.name}"?`)) return

  try {
    await deleteProduct(product.id)
    await loadProducts()
  } catch (e) {
    console.error(e)
    alert('Nu s-a putut șterge produsul.')
  }
}

onMounted(loadProducts)
</script>
