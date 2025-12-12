<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Mărci / Branduri</h1>
      <RouterLink
        :to="{ name: 'admin-brands', query: { new: '1' } }"
        class="btn btn-primary btn-sm"
        @click.prevent="startCreate"
      >
        + Brand nou
      </RouterLink>
    </div>

    <div v-if="loading" class="text-muted">
      Se încarcă brandurile...
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
            <th class="text-center">Ordine</th>
            <th class="text-center">Publicat</th>
            <th style="width: 120px;"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="brand in brands"
            :key="brand.id"
          >
            <td>{{ brand.id }}</td>
            <td>{{ brand.name }}</td>
            <td class="small text-muted">{{ brand.slug }}</td>
            <td class="text-center">
              {{ brand.sort_order ?? '-' }}
            </td>
            <td class="text-center">
              <span
                class="badge"
                :class="brand.is_published ? 'bg-success' : 'bg-secondary'"
              >
                {{ brand.is_published ? 'Da' : 'Nu' }}
              </span>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button
                  class="btn btn-outline-secondary btn-sm"
                  type="button"
                  @click="editBrand(brand)"
                >
                  Editează
                </button>
                <button
                  class="btn btn-outline-danger btn-sm"
                  type="button"
                  @click="confirmDelete(brand)"
                >
                  Șterge
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="brands.length === 0">
            <td colspan="6" class="text-center text-muted py-4">
              Nu există încă niciun brand.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal simplu pentru create/edit -->
    <div
      v-if="showForm"
      class="modal-backdrop fade show"
      style="z-index: 1040;"
    ></div>
    <div
      v-if="showForm"
      class="modal d-block"
      tabindex="-1"
      style="z-index: 1050;"
    >
      <div class="modal-dialog">
        <form class="modal-content" @submit.prevent="saveBrand">
          <div class="modal-header py-2">
            <h5 class="modal-title">
              {{ currentBrand?.id ? 'Editează brand' : 'Brand nou' }}
            </h5>
            <button type="button" class="btn-close" @click="closeForm"></button>
          </div>
          <div class="modal-body">
            <div v-if="formError" class="alert alert-danger py-2 px-3 small">
              {{ formError }}
            </div>

            <div class="mb-2">
              <label class="form-label">Denumire</label>
              <input
                v-model="form.name"
                type="text"
                class="form-control form-control-sm"
                required
                @blur="maybeGenerateSlug"
              />
            </div>

            <div class="mb-2">
              <label class="form-label">Slug</label>
              <input
                v-model="form.slug"
                type="text"
                class="form-control form-control-sm"
                required
              />
            </div>

            <div class="mb-2">
              <label class="form-label">Descriere</label>
              <textarea
                v-model="form.description"
                class="form-control form-control-sm"
                rows="2"
              />
            </div>

            <div class="row">
              <div class="col-6">
                <label class="form-label">Ordine sortare</label>
                <input
                  v-model.number="form.sort_order"
                  type="number"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-6 d-flex align-items-center">
                <div class="form-check mt-3">
                  <input
                    v-model="form.is_published"
                    class="form-check-input"
                    type="checkbox"
                    id="brand_published"
                  />
                  <label class="form-check-label" for="brand_published">
                    Publicat
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer py-2">
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              @click="closeForm"
            >
              Anulează
            </button>
            <button
              type="submit"
              class="btn btn-primary btn-sm"
              :disabled="formLoading"
            >
              <span
                v-if="formLoading"
                class="spinner-border spinner-border-sm me-1"
              />
              Salvează
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- confirmare ștergere -->
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
            <h5 class="modal-title">Ștergere brand</h5>
            <button type="button" class="btn-close" @click="toDelete = null"></button>
          </div>
          <div class="modal-body">
            Ești sigur că vrei să ștergi brandul
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
  fetchAdminBrands,
  createAdminBrand,
  updateAdminBrand,
  deleteAdminBrand
} from '@/services/admin/brands';

const brands = ref([]);
const loading = ref(false);
const error = ref('');

const showForm = ref(false);
const currentBrand = ref(null);

const form = ref({
  name: '',
  slug: '',
  description: '',
  sort_order: null,
  is_published: true
});
const formLoading = ref(false);
const formError = ref('');

const toDelete = ref(null);
const deleteLoading = ref(false);

const loadBrands = async () => {
  loading.value = true;
  error.value = '';

  try {
    brands.value = await fetchAdminBrands();
  } catch (e) {
    console.error('Admin brands error', e);
    error.value = 'Nu s-au putut încărca brandurile.';
  } finally {
    loading.value = false;
  }
};

const startCreate = () => {
  currentBrand.value = null;
  form.value = {
    name: '',
    slug: '',
    description: '',
    sort_order: null,
    is_published: true
  };
  formError.value = '';
  showForm.value = true;
};

const editBrand = (brand) => {
  currentBrand.value = brand;
  form.value = {
    name: brand.name ?? '',
    slug: brand.slug ?? '',
    description: brand.description ?? '',
    sort_order: brand.sort_order ?? null,
    is_published: !!brand.is_published
  };
  formError.value = '';
  showForm.value = true;
};

const closeForm = () => {
  showForm.value = false;
};

const slugify = (value) =>
  value
    .toString()
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');

const maybeGenerateSlug = () => {
  if (!form.value.slug && form.value.name) {
    form.value.slug = slugify(form.value.name);
  }
};

const saveBrand = async () => {
  formLoading.value = true;
  formError.value = '';

  const payload = {
    name: form.value.name,
    slug: form.value.slug,
    description: form.value.description,
    sort_order: form.value.sort_order ?? null,
    is_published: !!form.value.is_published
  };

  try {
    if (currentBrand.value?.id) {
      await updateAdminBrand(currentBrand.value.id, payload);
    } else {
      await createAdminBrand(payload);
    }

    showForm.value = false;
    await loadBrands();
  } catch (e) {
    console.error('Save brand error', e);
    if (e.response?.data?.message) {
      formError.value = e.response.data.message;
    } else if (e.response?.status === 422) {
      formError.value = 'Date invalide pentru brand.';
    } else {
      formError.value = 'A apărut o eroare la salvare.';
    }
  } finally {
    formLoading.value = false;
  }
};

const confirmDelete = (brand) => {
  toDelete.value = brand;
};

const doDelete = async () => {
  if (!toDelete.value) return;

  deleteLoading.value = true;

  try {
    await deleteAdminBrand(toDelete.value.id);
    await loadBrands();
    toDelete.value = null;
  } catch (e) {
    console.error('Delete brand error', e);
  } finally {
    deleteLoading.value = false;
  }
};

onMounted(loadBrands);
</script>
