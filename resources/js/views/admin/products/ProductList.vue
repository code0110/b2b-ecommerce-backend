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
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body">
        <form @submit.prevent="applyFilters">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label small fw-bold text-muted">Căutare</label>
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input
                  v-model="filters.search"
                  type="text"
                  class="form-control border-start-0 ps-0"
                  placeholder="Denumire, cod, barcode..."
                />
                <button
                  type="button"
                  class="btn btn-outline-secondary"
                  @click="clearFilter('search')"
                  :disabled="!filters.search"
                  title="Șterge căutarea"
                >
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>

            <div class="col-md-2">
              <label class="form-label small fw-bold text-muted">Categorie</label>
              <select
                v-model="filters.category_id"
                class="form-select"
              >
                <option :value="''">Toate Categoriile</option>
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
              <label class="form-label small fw-bold text-muted">Brand</label>
              <select
                v-model="filters.brand_id"
                class="form-select"
              >
                <option :value="''">Toate Brandurile</option>
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
              <label class="form-label small fw-bold text-muted">Status</label>
              <select
                v-model="filters.status"
                class="form-select"
              >
                <option :value="''">Toate Statusurile</option>
                <option value="published">Publicat</option>
                <option value="draft">Draft</option>
                <option value="hidden">Ascuns</option>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label small fw-bold text-muted">Stoc</label>
              <select
                v-model="filters.stock_status"
                class="form-select"
              >
                <option :value="''">Toate Stocurile</option>
                <option value="in_stock">În stoc</option>
                <option value="limited">Stoc limitat</option>
                <option value="out_of_stock">Epuizat</option>
                <option value="on_order">La comandă</option>
              </select>
            </div>

            <div class="col-md-1 d-flex gap-2">
              <button
                type="submit"
                class="btn btn-primary flex-grow-1"
                :disabled="loading"
                title="Aplică filtre"
              >
                <i class="bi bi-funnel"></i>
              </button>
              <button
                type="button"
                class="btn btn-outline-secondary"
                @click="resetFilters"
                :disabled="loading || !hasActiveFilters"
                title="Reset filtre"
              >
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>

            <div class="col-12 border-top pt-3 mt-3">
              <div class="d-flex flex-wrap gap-4 align-items-center">
                <div class="form-check form-switch">
                  <input
                    id="filterIsNew"
                    v-model="filters.is_new"
                    class="form-check-input"
                    type="checkbox"
                    :true-value="1"
                    :false-value="''"
                  />
                  <label class="form-check-label small" for="filterIsNew">Doar „Noi”</label>
                </div>

                <div class="form-check form-switch">
                  <input
                    id="filterIsPromo"
                    v-model="filters.is_promo"
                    class="form-check-input"
                    type="checkbox"
                    :true-value="1"
                    :false-value="''"
                  />
                  <label class="form-check-label small" for="filterIsPromo">Doar Promoții</label>
                </div>

                <div class="form-check form-switch">
                  <input
                    id="filterIsBest"
                    v-model="filters.is_best_seller"
                    class="form-check-input"
                    type="checkbox"
                    :true-value="1"
                    :false-value="''"
                  />
                  <label class="form-check-label small" for="filterIsBest">Doar Best Sellers</label>
                </div>

                <div class="ms-auto d-flex gap-2 align-items-center">
                  <span class="text-muted small fw-bold">Sortare:</span>
                  <select
                    v-model="filters.sort_by"
                    class="form-select form-select-sm border-0 bg-light"
                    style="width: auto;"
                  >
                    <option value="created_at">Data creare</option>
                    <option value="name">Denumire</option>
                    <option value="list_price">Preț</option>
                    <option value="stock_qty">Stoc</option>
                    <option value="sort_order">Ordine</option>
                  </select>
                  <select
                    v-model="filters.sort_dir"
                    class="form-select form-select-sm border-0 bg-light"
                    style="width: auto;"
                  >
                    <option value="desc">Desc</option>
                    <option value="asc">Asc</option>
                  </select>
                </div>
              </div>
              <div v-if="activeFilters.length" class="mt-3 d-flex flex-wrap gap-2">
                <span
                  v-for="chip in activeFilters"
                  :key="chip.key"
                  class="badge rounded-pill bg-primary bg-opacity-10 text-primary d-flex align-items-center gap-2"
                  style="padding: 0.5rem 0.75rem;"
                >
                  <i class="bi bi-funnel"></i>
                  <span>{{ chip.label }}: <strong class="text-dark">{{ chip.value }}</strong></span>
                  <button
                    type="button"
                    class="btn btn-sm btn-link text-decoration-none text-primary px-0"
                    @click="clearFilter(chip.key)"
                    title="Elimină filtru"
                  >
                    <i class="bi bi-x-lg"></i>
                  </button>
                </span>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- LISTĂ PRODUSE -->
    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        <div v-if="error" class="alert alert-danger m-3">
          {{ error }}
        </div>

        <div v-if="loading" class="p-5 text-center text-muted">
          <div class="spinner-border text-primary mb-2" role="status"></div>
          <div>Se încarcă produsele...</div>
        </div>

        <div v-else>
          <div v-if="products.length === 0" class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
            Nu s-au găsit produse pentru criteriile selectate.
          </div>

          <div v-else class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3 p-3">
             <div v-for="product in products" :key="product.id" class="col">
               <div class="card h-100 border shadow-sm product-card">
                 <!-- Image & Flags Overlay -->
                 <div class="position-relative border-bottom bg-light d-flex align-items-center justify-content-center" style="height: 180px; overflow: hidden;">
                    <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="img-fluid" style="max-height: 100%; object-fit: contain;">
                    <i v-else class="bi bi-image text-muted fs-1 opacity-25"></i>
                    
                    <div class="position-absolute top-0 end-0 p-2 d-flex flex-column gap-1">
                      <span v-if="product.is_new" class="badge bg-info text-white shadow-sm" title="Nou">N</span>
                      <span v-if="product.is_promo" class="badge bg-danger text-white shadow-sm" title="Promo">P</span>
                      <span v-if="product.is_best_seller" class="badge bg-success text-white shadow-sm" title="Best Seller">B</span>
                    </div>

                    <div class="position-absolute top-0 start-0 p-2">
                       <span class="badge shadow-sm" :class="statusBadgeClass(product.status)">
                          {{ statusLabel(product.status) }}
                       </span>
                    </div>
                 </div>

                 <div class="card-body d-flex flex-column">
                    <div class="mb-2">
                      <div class="d-flex justify-content-between align-items-start mb-1">
                         <small class="text-muted font-monospace">#{{ product.id }}</small>
                         <small class="text-muted font-monospace">{{ product.internal_code || '-' }}</small>
                      </div>
                      <RouterLink :to="{ name: 'admin-products-edit', params: { id: product.id } }" 
                                  class="h6 text-dark text-decoration-none fw-bold d-block text-truncate mb-1 hover-link" 
                                  :title="product.name">
                         {{ product.name }}
                      </RouterLink>
                      <div class="d-flex gap-1 flex-wrap small">
                        <span v-if="product.main_category" class="text-muted">
                          <i class="bi bi-folder2 me-1"></i>{{ product.main_category.name }}
                        </span>
                        <span v-if="product.brand" class="text-muted border-start ps-1 ms-1">
                          <i class="bi bi-tag me-1"></i>{{ product.brand.name }}
                        </span>
                      </div>
                    </div>

                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-end">
                       <div>
                          <div class="text-muted small" style="font-size: 0.75rem;">PREȚ LISTĂ</div>
                          <div class="fw-bold text-dark fs-5">{{ formatPrice(product.list_price) }}</div>
                       </div>
                       <div class="text-end">
                          <span class="badge mb-1 d-block" :class="stockBadgeClass(product.stock_status)">
                              {{ stockStatusLabel(product.stock_status) }}
                          </span>
                          <div class="small text-muted">{{ product.stock_qty }} buc.</div>
                       </div>
                    </div>
                 </div>

                 <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
                    <button class="btn btn-sm btn-link text-muted p-0 text-decoration-none" disabled>
                        <i class="bi bi-clock me-1"></i>
                        <span style="font-size: 0.75rem;">Actualizat: {{ new Date(product.updated_at || product.created_at).toLocaleDateString('ro-RO') }}</span>
                    </button>
                    <div class="d-flex gap-1">
                      <RouterLink
                        :to="{
                          name: 'admin-products-edit',
                          params: { id: product.id }
                        }"
                        class="btn btn-sm btn-outline-primary"
                        title="Editare"
                      >
                        <i class="bi bi-pencil"></i>
                      </RouterLink>
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        @click="confirmDelete(product)"
                        title="Șterge"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                 </div>
               </div>
             </div>
          </div>

          <!-- PAGINARE -->
          <div
            v-if="pagination.total > pagination.per_page"
            class="d-flex justify-content-between align-items-center p-3 border-top bg-light bg-opacity-10"
          >
            <div class="small text-muted">
              Afișare <span class="fw-bold text-dark">{{ pagination.from }}–{{ pagination.to }}</span> din <span class="fw-bold text-dark">{{ pagination.total }}</span> produse
            </div>
            <nav>
              <ul class="pagination pagination-sm mb-0 shadow-sm">
                <li
                  class="page-item"
                  :class="{ disabled: pagination.current_page <= 1 }"
                >
                  <button
                    class="page-link border-0"
                    type="button"
                    @click="goToPage(pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1"
                  >
                    <i class="bi bi-chevron-left"></i>
                  </button>
                </li>
                <li
                  v-for="page in pagesToShow"
                  :key="page"
                  class="page-item"
                  :class="{ active: page === pagination.current_page }"
                >
                  <button
                    class="page-link border-0"
                    :class="page === pagination.current_page ? 'bg-primary text-white' : ''"
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
                    class="page-link border-0"
                    type="button"
                    @click="goToPage(pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page"
                  >
                    <i class="bi bi-chevron-right"></i>
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

const clearFilter = (key) => {
  if (key in filters.value) {
    filters.value[key] = '';
    loadProducts(1);
  }
};

const resetFilters = () => {
  filters.value = {
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
  };
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

const hasActiveFilters = computed(() => {
  return Boolean(
    filters.value.search ||
      filters.value.category_id ||
      filters.value.brand_id ||
      filters.value.status ||
      filters.value.stock_status ||
      filters.value.is_new ||
      filters.value.is_promo ||
      filters.value.is_best_seller
  );
});

const activeFilters = computed(() => {
  const result = [];
  if (filters.value.search) {
    result.push({ key: 'search', label: 'Căutare', value: filters.value.search });
  }
  if (filters.value.category_id) {
    const cat = flatCategories.value.find(c => String(c.id) === String(filters.value.category_id));
    result.push({ key: 'category_id', label: 'Categorie', value: cat ? cat.indented_name.trim() : `#${filters.value.category_id}` });
  }
  if (filters.value.brand_id) {
    const br = brands.value.find(b => String(b.id) === String(filters.value.brand_id));
    result.push({ key: 'brand_id', label: 'Brand', value: br ? br.name : `#${filters.value.brand_id}` });
  }
  if (filters.value.status) {
    result.push({ key: 'status', label: 'Status', value: statusLabel(filters.value.status) });
  }
  if (filters.value.stock_status) {
    result.push({ key: 'stock_status', label: 'Stoc', value: stockStatusLabel(filters.value.stock_status) });
  }
  if (filters.value.is_new) {
    result.push({ key: 'is_new', label: 'Flag', value: 'Nou' });
  }
  if (filters.value.is_promo) {
    result.push({ key: 'is_promo', label: 'Flag', value: 'Promo' });
  }
  if (filters.value.is_best_seller) {
    result.push({ key: 'is_best_seller', label: 'Flag', value: 'Best Seller' });
  }
  return result;
});

onMounted(async () => {
  await loadMeta();
  await loadProducts();
});
</script>

<style scoped>
.sticky-header th {
  position: sticky;
  top: 0;
  z-index: 1;
  background: var(--bs-light);
}
</style>
