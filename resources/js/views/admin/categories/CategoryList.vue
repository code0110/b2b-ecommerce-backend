<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Categorii produse</h1>
      <RouterLink
        :to="{ name: 'admin-categories-new' }"
        class="btn btn-primary btn-sm"
      >
        + Categorie nouă
      </RouterLink>
    </div>

    <div v-if="loading" class="text-muted">
      Se încarcă categoriile...
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
            <th>Slug</th>
            <th>Părinte</th>
            <th class="text-center">Ordine</th>
            <th class="text-center">Publicat</th>
            <th style="width: 120px;"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="cat in categories"
            :key="cat.id"
          >
            <td>{{ cat.id }}</td>
            <td>{{ cat.name }}</td>
            <td class="small text-muted">{{ cat.slug }}</td>
            <td class="small">
              {{ cat.parent ? cat.parent.name : '-' }}
            </td>
            <td class="text-center">
              {{ cat.sort_order ?? '-' }}
            </td>
            <td class="text-center">
              <span
                class="badge"
                :class="cat.is_published ? 'bg-success' : 'bg-secondary'"
              >
                {{ cat.is_published ? 'Da' : 'Nu' }}
              </span>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <RouterLink
                  :to="{ name: 'admin-categories-edit', params: { id: cat.id } }"
                  class="btn btn-outline-secondary btn-sm"
                >
                  Editează
                </RouterLink>
                <button
                  class="btn btn-outline-danger btn-sm"
                  type="button"
                  @click="confirmDelete(cat)"
                >
                  Șterge
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="categories.length === 0">
            <td colspan="7" class="text-center text-muted py-4">
              Nu există încă nicio categorie.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- confirmare ștergere simplă -->
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
            <h5 class="modal-title">Ștergere categorie</h5>
            <button
              type="button"
              class="btn-close"
              @click="toDelete = null"
            ></button>
          </div>
          <div class="modal-body">
            Ești sigur că vrei să ștergi categoria
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
import { ref, onMounted } from 'vue';
import {
  fetchAdminCategories,
  deleteAdminCategory
} from '@/services/admin/categories';

const categories = ref([]);
const loading = ref(false);
const error = ref('');

const toDelete = ref(null);
const deleteLoading = ref(false);

const loadCategories = async () => {
  loading.value = true;
  error.value = '';

  try {
    categories.value = await fetchAdminCategories();
  } catch (e) {
    console.error('Admin categories error', e);
    error.value = 'Nu s-au putut încărca categoriile.';
  } finally {
    loading.value = false;
  }
};

const confirmDelete = (cat) => {
  toDelete.value = cat;
};

const doDelete = async () => {
  if (!toDelete.value) return;

  deleteLoading.value = true;

  try {
    await deleteAdminCategory(toDelete.value.id);
    await loadCategories();
    toDelete.value = null;
  } catch (e) {
    console.error('Delete category error', e);
    // poți adăuga un mesaj mai explicit dacă vrei
  } finally {
    deleteLoading.value = false;
  }
};

onMounted(loadCategories);
</script>
