<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Produse</h1>
      <RouterLink
        :to="{ name: 'admin-products-new' }"
        class="btn btn-primary btn-sm"
      >
        + Produs nou
      </RouterLink>
    </div>

    <!-- Filtre simple -->
    <form class="card mb-3" @submit.prevent="applyFilters">
      <div class="card-body py-2">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small">Căutare (denumire / cod)</label>
            <input
              v-model="filters.q"
              type="text"
              class="form-control form-control-sm"
              placeholder="Ex: ciment, ABC123"
            />
          </div>
          <div class="col-md-2">
            <label class="form-label small">Status</label>
            <select
              v-model="filters.status"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="published">Publicate</option>
              <option value="hidden">Ascunse</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Categorie principală</label>
            <select
              v-model="filters.category_id"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option
                v-for="cat in categories"
                :key="cat.id"
                :value="cat.id"
              >
                {{ cat.name }}
              </option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Brand</label>
            <select
              v-model="filters.brand_id"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option
                v-for="brand in brands"
                :key="brand.id"
                :value="brand.id"
              >
                {{ brand.name }}
              </option>
            </select>
          </div>
          <div class="col-md-2 text-md-end">
            <button
              type="submit"
              class="btn btn-primary btn-sm me-1"
              :disabled="loading"
            >
              Filtrează
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </div>
      </div>
    </form>

    <div v-if="loading" class="text-muted">
      Se încarcă produsele...
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-else>
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th style="width: 40px;">#</th>
            <th>Denumire</th>
            <th>Cod intern</th>
            <th>Categorie</th>
            <th>Brand</th>
            <th class="text-end">Preț listă</th>
            <th class="text-center">Status</th>
            <th style="width: 120px;"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="prod in products"
            :key="prod.id"
          >
            <td>{{ prod.id }}</td>
            <td>{{ prod.name }}</td>
            <td class="small text-muted">
              {{ prod.internal_code }}
            </td>
            <td class="small">
              {{ prod.main_category?.name ?? '-' }}
            </td>
            <td class="small">
              {{ prod.brand?.name ?? '-' }}
            </td>
            <td class="text-end small">
              {{ formatPrice(prod.list_price) }} RON
            </td>
            <td class="text-center">
              <span
                class="badge"
                :class="prod.status === 'published' ? 'bg-success' : 'bg-secondary'"
              >
                {{ prod.status === 'published' ? 'Publicat' : 'Ascuns' }}
              </span>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <RouterLink
                  :to="{ name: 'admin-products-edit', params: { id: prod.id } }"
                  class="btn btn-outline-secondary btn-sm"
                >
                  Editează
                </RouterLink>
                <button
                  class="btn btn-outline-danger btn-sm"
                  type="button"
                  @click="confirmDelete(prod)"
                >
                  Șterge
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="products.length === 0">
            <td colspan="8" class="text-center text-muted py-4">
              Nu există încă produse conform filtrării curente.
            </td>
          </tr>
        </tbody>
      </table>

      <!-- paginare simplă -->
      <nav v-if="pagination.last_page > 1" aria-label="Page navigation">
        <ul class="pagination pagination-sm">
          <li
            class="page-item"
            :class="{ disabled: pagination.current_page <= 1 }"
          >
            <button
              class="page-link"
              type="button"
              @click="changePage(pagination.current_page - 1)"
            >
              &laquo;
            </button>
          </li>
          <li
            v-for="page in pages"
            :key="page"
            class="page-item"
            :class="{ active: page === pagination.current_page }"
          >
            <button
              class="page-link"
              type="button"
              @click="changePage(page)"
            >
              {{ page }}
            </button>
          </li>
          <li
            class="page-item"
            :class="{ disabled: pagination.current_page >= pagination.last_page }"
          >
            <button
              class="page-link"
              type="button"
              @click="changePage(pagination.current_page + 1)"
            >
              &raquo;
            </button>
          </li>
        </ul>
      </nav>
    </div>

    <!-- confirm delete -->
    <div
      v-if="toDelete"
      class="modal-backdrop fade show"
      style="z-index: 1040;"
    ></div>
    <div
      v-if="toDelete"
      class="modal d-block"
      tabindex="-1"
      style="z-index: 1050;"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h5 class="modal-title">Ștergere produs</h5>
            <button type="button" class="btn-close" @click="toDelete = null"></button>
          </div>
          <div class="modal-body">
            Ești sigur că vrei să ștergi produsul
            <strong>{{ toDelete?.name }}</strong>?
          </div>
          <div class="modal-footer py-2">
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              @click="toDelete = null"
            >
              Anulează
            </button>
            <button
              type="button"
              class="btn btn-danger btn-sm"
              :disabled="deleteLoading"
              @click="doDelete"
            >
              <span
                v-if="deleteLoading"
                class="spinner-border spinner-border-sm me-1"
              />
              Șterge
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
  fetchAdminProducts,
  deleteAdminProduct
} from '@/services/admin/products';
import { fetchAdminCategories } from '@/services/admin/categories';
import { fetchAdminBrands } from '@/services/admin/brands';

const products = ref([]);
const categories = ref([]);
const brands = ref([]);

const loading = ref(false);
const error = ref('');

const filters = ref({
  q: '',
  status: '',
  category_id: '',
  brand_id: '',
  page: 1
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
});

const toDelete = ref(null);
const deleteLoading = ref(false);

const loadFiltersData = async () => {
  [categories.value, brands.value] = await Promise.all([
    fetchAdminCategories(),
    fetchAdminBrands()
  ]);
};

const loadProducts = async () => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      q: filters.value.q || undefined,
      status: filters.value.status || undefined,
      category_id: filters.value.category_id || undefined,
      brand_id: filters.value.brand_id || undefined,
      page: filters.value.page || 1
    };

    const data = await fetchAdminProducts(params);

    products.value = data.data ?? [];
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total
    };
  } catch (e) {
    console.error('Admin products error', e);
    error.value = 'Nu s-au putut încărca produsele.';
  } finally {
    loading.value = false;
  }
};

const applyFilters = () => {
  filters.value.page = 1;
  loadProducts();
};

const resetFilters = () => {
  filters.value = {
    q: '',
    status: '',
    category_id: '',
    brand_id: '',
    page: 1
  };
  loadProducts();
};

const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return;
  filters.value.page = page;
  loadProducts();
};

const pages = computed(() => {
  const last = pagination.value.last_page || 1;
  const current = pagination.value.current_page || 1;

  const out = [];
  const start = Math.max(1, current - 2);
  const end = Math.min(last, current + 2);

  for (let p = start; p <= end; p += 1) {
    out.push(p);
  }
  return out;
});

const formatPrice = (val) => {
  const num = Number(val || 0);
  return num.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const confirmDelete = (prod) => {
  toDelete.value = prod;
};

const doDelete = async () => {
  if (!toDelete.value) return;
  deleteLoading.value = true;

  try {
    await deleteAdminProduct(toDelete.value.id);
    await loadProducts();
    toDelete.value = null;
  } catch (e) {
    console.error('Delete product error', e);
  } finally {
    deleteLoading.value = false;
  }
};

onMounted(async () => {
  await loadFiltersData();
  await loadProducts();
});
</script>
