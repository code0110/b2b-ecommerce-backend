<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import {
  fetchAdminProducts,
  deleteAdminProduct,
} from '@/services/admin/products';

const router = useRouter();

const loading = ref(false);
const error = ref('');

const products = ref([]);

// pentru paginare
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
});

// filtre simple – le poți extinde după nevoie
const filters = ref({
  search: '',
  status: '',
  stock_status: '',
  category_id: '',
  brand_id: '',
});

const loadProducts = async (page = 1) => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      page,
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
      stock_status: filters.value.stock_status || undefined,
      category_id: filters.value.category_id || undefined,
      brand_id: filters.value.brand_id || undefined,
    };

    const response = await fetchAdminProducts(params);

    // Acceptăm atât format paginat clasic cât și un simplu array
    if (Array.isArray(response)) {
      products.value = response;
      pagination.value = {
        current_page: 1,
        last_page: 1,
        per_page: response.length,
        total: response.length,
      };
    } else {
      products.value = response.data || [];
      if (response.meta) {
        pagination.value = {
          current_page: response.meta.current_page,
          last_page: response.meta.last_page,
          per_page: response.meta.per_page,
          total: response.meta.total,
        };
      }
    }
  } catch (e) {
    console.error('Admin products error', e);
    error.value = 'Nu s-au putut încărca produsele.';
  } finally {
    loading.value = false;
  }
};

const handleDelete = async (product) => {
  if (!confirm(`Sigur vrei să ștergi produsul "${product.name}"?`)) {
    return;
  }

  try {
    await deleteAdminProduct(product.id);
    await loadProducts(pagination.value.current_page);
  } catch (e) {
    console.error('Delete product error', e);
    alert('Ștergerea produsului a eșuat.');
  }
};

const goToNew = () => {
  router.push({ name: 'admin-products-new' });
};

const goToEdit = (product) => {
  router.push({ name: 'admin-products-edit', params: { id: product.id } });
};

// reload când se schimbă filtrele (cu un mic debounce dacă vrei)
watch(
  filters,
  () => {
    loadProducts(1);
  },
  { deep: true },
);

onMounted(() => {
  loadProducts(1);
});
</script>

<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h4 mb-0">Produse</h1>
      <button class="btn btn-primary" type="button" @click="goToNew">
        Adaugă produs
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <!-- Filtre simple -->
    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-3" @submit.prevent="loadProducts(1)">
          <div class="col-md-4">
            <label class="form-label">Căutare</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control"
              placeholder="Denumire, cod..."
            />
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select v-model="filters.status" class="form-select">
              <option value="">Toate</option>
              <option value="published">Publicat</option>
              <option value="hidden">Ascuns</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Stoc</label>
            <select v-model="filters.stock_status" class="form-select">
              <option value="">Toate</option>
              <option value="in_stock">În stoc</option>
              <option value="low_stock">Stoc limitat</option>
              <option value="out_of_stock">Epuizat</option>
            </select>
          </div>

          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-outline-primary w-100">
              Aplică filtre
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div v-if="loading" class="text-muted">
          Se încarcă produsele...
        </div>

        <div v-else>
          <div v-if="!products.length" class="text-muted">
            Nu există produse în listă.
          </div>

          <div v-else class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Denumire</th>
                  <th>Cod intern</th>
                  <th>Categorie</th>
                  <th>Brand</th>
                  <th>Stoc</th>
                  <th>Preț listă</th>
                  <th class="text-end">Acțiuni</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="product in products" :key="product.id">
                  <td>{{ product.name }}</td>
                  <td>{{ product.internal_code }}</td>
                  <td>{{ product.category?.name }}</td>
                  <td>{{ product.brand?.name }}</td>
                  <td>
                    {{ product.stock_qty }}
                  </td>
                  <td>
                    {{ product.list_price }}
                  </td>
                  <td class="text-end">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary me-2"
                      @click="goToEdit(product)"
                    >
                      Editează
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-danger"
                      @click="handleDelete(product)"
                    >
                      Șterge
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Paginare simplă -->
          <div
            v-if="pagination.last_page > 1"
            class="d-flex justify-content-between align-items-center mt-3"
          >
            <div class="text-muted">
              Pagina {{ pagination.current_page }} din
              {{ pagination.last_page }} – total {{ pagination.total }} produse
            </div>
            <div class="btn-group">
              <button
                class="btn btn-sm btn-outline-secondary"
                type="button"
                :disabled="pagination.current_page === 1"
                @click="loadProducts(pagination.current_page - 1)"
              >
                « Anterioara
              </button>
              <button
                class="btn btn-sm btn-outline-secondary"
                type="button"
                :disabled="pagination.current_page === pagination.last_page"
                @click="loadProducts(pagination.current_page + 1)"
              >
                Următoarea »
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
