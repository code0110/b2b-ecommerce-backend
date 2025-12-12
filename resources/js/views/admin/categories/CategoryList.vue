<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import {
  fetchAdminCategoriesPaginated,
  deleteAdminCategory,
} from '@/services/admin/categories';

const router = useRouter();

const loading = ref(false);
const error = ref('');

const categories = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
});

const filters = ref({
  search: '',
  status: '',
});

const loadCategories = async (page = 1) => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      page,
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
    };

    const response = await fetchAdminCategoriesPaginated(params);

    if (Array.isArray(response)) {
      categories.value = response;
      pagination.value = {
        current_page: 1,
        last_page: 1,
        per_page: response.length,
        total: response.length,
      };
    } else {
      categories.value = response.data || [];
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
    console.error('Admin categories error', e);
    error.value = 'Nu s-au putut încărca categoriile.';
  } finally {
    loading.value = false;
  }
};

const goToNew = () => {
  router.push({ name: 'admin-categories-new' });
};

const goToEdit = (category) => {
  router.push({ name: 'admin-categories-edit', params: { id: category.id } });
};

const handleDelete = async (category) => {
  if (
    !confirm(
      `Sigur vrei să ștergi categoria "${category.name}"? (dacă are produse, backend-ul o poate bloca)`,
    )
  ) {
    return;
  }

  try {
    await deleteAdminCategory(category.id);
    await loadCategories(pagination.value.current_page);
  } catch (e) {
    console.error('Delete category error', e);
    alert('Ștergerea categoriei a eșuat. Verifică dacă nu are produse asociate.');
  }
};

// reîncărcăm la schimbarea filtrelor
watch(
  filters,
  () => {
    loadCategories(1);
  },
  { deep: true },
);

onMounted(() => {
  loadCategories(1);
});
</script>

<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h4 mb-0">Categorii produse</h1>
      <button class="btn btn-primary" type="button" @click="goToNew">
        Creează categorie
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <!-- Filtre simple -->
    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-3" @submit.prevent="loadCategories(1)">
          <div class="col-md-4">
            <label class="form-label">Căutare</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control"
              placeholder="Denumire, slug..."
            />
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select v-model="filters.status" class="form-select">
              <option value="">Toate</option>
              <option value="published">Publicate</option>
              <option value="hidden">Ascunse</option>
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

    <!-- Tabel categorii -->
    <div class="card">
      <div class="card-body">
        <div v-if="loading">Se încarcă categoriile...</div>

        <div v-else>
          <div v-if="!categories.length" class="text-muted">
            Nu există categorii în listă.
          </div>

          <div v-else class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Denumire</th>
                  <th>Slug</th>
                  <th>Categorie părinte</th>
                  <th>Ordine sortare</th>
                  <th>Status</th>
                  <th class="text-end">Acțiuni</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="category in categories" :key="category.id">
                  <td>{{ category.name }}</td>
                  <td>{{ category.slug }}</td>
                  <td>{{ category.parent?.name }}</td>
                  <td>{{ category.sort_order }}</td>
                  <td>
                    <span
                      class="badge"
                      :class="category.is_published ? 'bg-success' : 'bg-secondary'"
                    >
                      {{ category.is_published ? 'Publicat' : 'Ascuns' }}
                    </span>
                  </td>
                  <td class="text-end">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary me-2"
                      @click="goToEdit(category)"
                    >
                      Editează
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-danger"
                      @click="handleDelete(category)"
                    >
                      Șterge
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- paginare simplă -->
          <div
            v-if="pagination.last_page > 1"
            class="d-flex justify-content-between align-items-center mt-3"
          >
            <div class="text-muted">
              Pagina {{ pagination.current_page }} din
              {{ pagination.last_page }} – total {{ pagination.total }}
              categorii
            </div>
            <div class="btn-group">
              <button
                class="btn btn-sm btn-outline-secondary"
                type="button"
                :disabled="pagination.current_page === 1"
                @click="loadCategories(pagination.current_page - 1)"
              >
                « Anterioară
              </button>
              <button
                class="btn btn-sm btn-outline-secondary"
                type="button"
                :disabled="pagination.current_page === pagination.last_page"
                @click="loadCategories(pagination.current_page + 1)"
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
