<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Produse</h1>
        <p class="text-muted mb-0">
          Management catalog produse – listare, filtre, status, prețuri.
        </p>
      </div>
      <RouterLink
        :to="{ name: 'admin-products-new' }"
        class="btn btn-primary"
      >
        <i class="bi bi-plus-lg me-1" />
        Produs nou
      </RouterLink>
    </div>

    <!-- FILTRE -->
    <div class="card mb-3">
      <div class="card-body">
        <form @submit.prevent="applyFilters">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label">Căutare</label>
              <input
                v-model="filters.search"
                type="text"
                class="form-control"
                placeholder="Denumire, cod intern, barcode, ERP..."
              />
            </div>

            <div class="col-md-2">
              <label class="form-label">Categorie</label>
              <select
                v-model="filters.category_id"
                class="form-select"
              >
                <option :value="''">Toate</option>
                <option
                  v-for="cat in flatCategories"
                  :key="cat.id"
                  :value="cat.id"
                >
                  {{ cat.indented_name }}
                </option>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Brand</label>
              <select
                v-model="filters.brand_id"
                class="form-select"
              >
                <option :value="''">Toate</option>
                <option
                  v-for="brand in brands"
                  :key="brand.id"
                  :value="brand.id"
                >
                  {{ brand.name }}
                </option>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Status</label>
              <select
                v-model="filters.status"
                class="form-select"
              >
                <option :value="''">Toate</option>
                <option value="published">Publicat</option>
                <option value="draft">Draft</option>
                <option value="hidden">Ascuns</option>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Stoc</label>
              <select
                v-model="filters.stock_status"
                class="form-select"
              >
                <option :value="''">Toate</option>
                <option value="in_stock">În stoc</option>
                <option value="limited">Stoc limitat</option>
                <option value="out_of_stock">Epuizat</option>
                <option value="on_order">La comandă</option>
              </select>
            </div>

            <div class="col-md-1 d-flex gap-2">
              <button
                type="submit"
                class="btn btn-primary btn-sm w-100"
                :disabled="loading"
              >
                Filtrează
              </button>
            </div>

            <div class="col-12">
              <div class="d-flex flex-wrap gap-3 mt-2">
                <div class="form-check form-check-inline">
                  <input
                    id="filterIsNew"
                    v-model="filters.is_new"
                    class="form-check-input"
                    type="checkbox"
                    :true-value="1"
                    :false-value="''"
                  />
                  <label class="form-check-label" for="filterIsNew">
                    Doar „noi”
                  </label>
                </div>

                <div class="form-check form-check-inline">
                  <input
                    id="filterIsPromo"
                    v-model="filters.is_promo"
                    class="form-check-input"
                    type="checkbox"
                    :true-value="1"
                    :false-value="''"
                  />
                  <label class="form-check-label" for="filterIsPromo">
                    Doar promoții
                  </label>
                </div>

                <div class="form-check form-check-inline">
                  <input
                    id="filterIsBest"
                    v-model="filters.is_best_seller"
                    class="form-check-input"
                    type="checkbox"
                    :true-value="1"
                    :false-value="''"
                  />
                  <label class="form-check-label" for="filterIsBest">
                    Doar best sellers
                  </label>
                </div>

                <div class="ms-auto d-flex gap-2">
                  <select
                    v-model="filters.sort_by"
                    class="form-select form-select-sm"
                    style="max-width: 180px"
                  >
                    <option value="created_at">Data creare</option>
                    <option value="name">Denumire</option>
                    <option value="list_price">Preț</option>
                    <option value="stock_qty">Stoc</option>
                    <option value="sort_order">Ordine sortare</option>
                  </select>
                  <select
                    v-model="filters.sort_dir"
                    class="form-select form-select-sm"
                    style="max-width: 120px"
                  >
                    <option value="desc">Descrescător</option>
                    <option value="asc">Crescător</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- LISTĂ PRODUSE -->
    <div class="card">
      <div class="card-body p-0">
        <div v-if="error" class="alert alert-danger m-3">
          {{ error }}
        </div>

        <div v-if="loading" class="p-3 text-center">
          Se încarcă produsele...
        </div>

        <div v-else>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 60px">ID</th>
                  <th>Produs</th>
                  <th>Categorie</th>
                  <th>Brand</th>
                  <th class="text-end">Preț listă</th>
                  <th class="text-center">Stoc</th>
                  <th class="text-center">Flag-uri</th>
                  <th>Status</th>
                  <th style="width: 140px" class="text-end">Acțiuni</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="products.length === 0">
                  <td colspan="9" class="text-center py-4">
                    Nu s-au găsit produse pentru criteriile selectate.
                  </td>
                </tr>

                <tr
                  v-for="product in products"
                  :key="product.id"
                >
                  <td>{{ product.id }}</td>
                  <td>
                    <div class="fw-semibold">
                      {{ product.name }}
                    </div>
                    <div class="small text-muted">
                      Cod intern: {{ product.internal_code || '-' }}
                    </div>
                  </td>
                  <td>
                    <div v-if="product.main_category">
                      {{ product.main_category.name }}
                    </div>
                    <div v-else class="text-muted small">—</div>
                  </td>
                  <td>
                    <div v-if="product.brand">
                      {{ product.brand.name }}
                    </div>
                    <div v-else class="text-muted small">—</div>
                  </td>
                  <td class="text-end">
                    {{ formatPrice(product.list_price) }}
                  </td>
                  <td class="text-center">
                    <span
                      class="badge"
                      :class="stockBadgeClass(product.stock_status)"
                    >
                      {{ stockStatusLabel(product.stock_status) }}
                    </span>
                    <div class="small text-muted">
                      {{ product.stock_qty }} buc.
                    </div>
                  </td>
                  <td class="text-center">
                    <span
                      v-if="product.is_new"
                      class="badge bg-info me-1"
                    >
                      Nou
                    </span>
                    <span
                      v-if="product.is_promo"
                      class="badge bg-danger me-1"
                    >
                      Promo
                    </span>
                    <span
                      v-if="product.is_best_seller"
                      class="badge bg-success"
                    >
                      Best seller
                    </span>
                  </td>
                  <td>
                    <span
                      class="badge"
                      :class="statusBadgeClass(product.status)"
                    >
                      {{ statusLabel(product.status) }}
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <RouterLink
                        :to="{
                          name: 'admin-products-edit',
                          params: { id: product.id }
                        }"
                        class="btn btn-outline-secondary"
                      >
                        Editare
                      </RouterLink>
                      <button
                        type="button"
                        class="btn btn-outline-danger"
                        @click="confirmDelete(product)"
                      >
                        Șterge
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- PAGINARE -->
          <div
            v-if="pagination.total > pagination.per_page"
            class="d-flex justify-content-between align-items-center p-3 border-top"
          >
            <div class="small text-muted">
              Afișare
              {{ pagination.from }}–{{ pagination.to }}
              din {{ pagination.total }} produse
            </div>
            <nav>
              <ul class="pagination pagination-sm mb-0">
                <li
                  class="page-item"
                  :class="{ disabled: pagination.current_page <= 1 }"
                >
                  <button
                    class="page-link"
                    type="button"
                    @click="goToPage(pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1"
                  >
                    «
                  </button>
                </li>
                <li
                  v-for="page in pagesToShow"
                  :key="page"
                  class="page-item"
                  :class="{ active: page === pagination.current_page }"
                >
                  <button
                    class="page-link"
                    type="button"
                    @click="goToPage(page)"
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
                    @click="goToPage(pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page"
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

    <!-- MODAL ȘTERGERE (simplu, nativ) -->
    <div
      v-if="deleteCandidate"
      class="modal-backdrop fade show"
    ></div>
    <div
      v-if="deleteCandidate"
      class="modal d-block"
      tabindex="-1"
      role="dialog"
    >
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirmare ștergere</h5>
            <button
              type="button"
              class="btn-close"
              @click="deleteCandidate = null"
            ></button>
          </div>
          <div class="modal-body">
            Sigur dorești să ștergi produsul
            <strong>{{ deleteCandidate.name }}</strong> (ID:
            {{ deleteCandidate.id }})? Acțiunea este ireversibilă.
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              @click="deleteCandidate = null"
            >
              Anulează
            </button>
            <button
              type="button"
              class="btn btn-danger btn-sm"
              :disabled="deleteLoading"
              @click="performDelete"
            >
              {{ deleteLoading ? 'Se șterge...' : 'Șterge' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { fetchProducts, deleteProduct } from '@/services/admin/products';
import { fetchCategories } from '@/services/admin/categories';
import { fetchBrands } from '@/services/admin/brands';

const loading = ref(false);
const error = ref('');

const products = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  from: 0,
  to: 0,
});

const filters = ref({
  search: '',
  category_id: '',
  brand_id: '',
  status: '',
  stock_status: '',
  is_new: '',
  is_promo: '',
  is_best_seller: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
});

const categories = ref([]);
const brands = ref([]);

const deleteCandidate = ref(null);
const deleteLoading = ref(false);

const loadMeta = async () => {
  try {
    const cats = await fetchCategories({ per_page: 1000 });
    categories.value = cats.data || cats;

    const br = await fetchBrands({ per_page: 1000 });
    brands.value = br.data || br;
  } catch (e) {
    console.error('Meta load error', e);
  }
};

const loadProducts = async (page = 1) => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      ...filters.value,
      page,
      per_page: 20,
    };

    const res = await fetchProducts(params);

    products.value = res.data || res.items || [];
    pagination.value = {
      current_page: res.current_page ?? 1,
      last_page: res.last_page ?? 1,
      per_page: res.per_page ?? 20,
      total: res.total ?? (res.data ? res.data.length : 0),
      from: res.from ?? 0,
      to: res.to ?? 0,
    };
  } catch (e) {
    console.error('Products load error', e);
    error.value = 'Nu s-au putut încărca produsele.';
  } finally {
    loading.value = false;
  }
};

const applyFilters = () => {
  loadProducts(1);
};

const goToPage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return;
  loadProducts(page);
};

const pagesToShow = computed(() => {
  const pages = [];
  const current = pagination.value.current_page;
  const last = pagination.value.last_page;

  const start = Math.max(1, current - 2);
  const end = Math.min(last, current + 2);

  for (let p = start; p <= end; p += 1) {
    pages.push(p);
  }
  return pages;
});

const formatPrice = (value) => {
  const number = Number(value) || 0;
  return `${number.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })} RON`;
};

const stockStatusLabel = (status) => {
  switch (status) {
    case 'in_stock':
      return 'În stoc';
    case 'limited':
      return 'Stoc limitat';
    case 'out_of_stock':
      return 'Epuizat';
    case 'on_order':
      return 'La comandă';
    default:
      return '—';
  }
};

const stockBadgeClass = (status) => {
  switch (status) {
    case 'in_stock':
      return 'bg-success';
    case 'limited':
      return 'bg-warning';
    case 'out_of_stock':
      return 'bg-danger';
    case 'on_order':
      return 'bg-secondary';
    default:
      return 'bg-light text-muted';
  }
};

const statusLabel = (status) => {
  switch (status) {
    case 'published':
      return 'Publicat';
    case 'draft':
      return 'Draft';
    case 'hidden':
      return 'Ascuns';
    default:
      return status || '—';
  }
};

const statusBadgeClass = (status) => {
  switch (status) {
    case 'published':
      return 'bg-success';
    case 'draft':
      return 'bg-secondary';
    case 'hidden':
      return 'bg-dark';
    default:
      return 'bg-light text-muted';
  }
};

const confirmDelete = (product) => {
  deleteCandidate.value = product;
};

const performDelete = async () => {
  if (!deleteCandidate.value) return;

  deleteLoading.value = true;
  try {
    await deleteProduct(deleteCandidate.value.id);
    deleteCandidate.value = null;
    await loadProducts(pagination.value.current_page);
  } catch (e) {
    console.error('Delete error', e);
    alert('Nu s-a putut șterge produsul. Verifică dacă nu are comenzi asociate.');
  } finally {
    deleteLoading.value = false;
  }
};

const flatCategories = computed(() => {
  const result = [];

  const build = (nodes, level = 0) => {
    nodes.forEach((cat) => {
      result.push({
        id: cat.id,
        indented_name: `${'— '.repeat(level)}${cat.name}`,
      });

      if (cat.children && cat.children.length) {
        build(cat.children, level + 1);
      }
    });
  };

  build(categories.value);
  return result;
});

onMounted(async () => {
  await loadMeta();
  await loadProducts();
});
</script>
