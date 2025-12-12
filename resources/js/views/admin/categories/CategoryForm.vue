<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">
        {{ isEdit ? 'Editează categorie' : 'Categorie nouă' }}
      </h1>
      <RouterLink
        :to="{ name: 'admin-categories' }"
        class="btn btn-outline-secondary btn-sm"
      >
        &larr; Înapoi la listă
      </RouterLink>
    </div>

    <div v-if="loading" class="text-muted">
      Se încarcă...
    </div>

    <div v-else>
      <form @submit.prevent="onSubmit" class="card">
        <div class="card-body">
          <div v-if="error" class="alert alert-danger py-2 px-3 small">
            {{ error }}
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Denumire</label>
              <input
                v-model="form.name"
                type="text"
                class="form-control form-control-sm"
                required
                @blur="maybeGenerateSlug"
              />
            </div>

            <div class="col-md-6">
              <label class="form-label">Slug (URL)</label>
              <input
                v-model="form.slug"
                type="text"
                class="form-control form-control-sm"
                required
              />
              <div class="form-text small">
                ex: materiale-constructii. Se poate genera automat din denumire.
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Categorie părinte</label>
              <select
                v-model="form.parent_id"
                class="form-select form-select-sm"
              >
                <option :value="null">— fără părinte (nivel 1) —</option>
                <option
                  v-for="cat in parentOptions"
                  :key="cat.id"
                  :value="cat.id"
                >
                  {{ cat.name }}
                </option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Ordine sortare</label>
              <input
                v-model.number="form.sort_order"
                type="number"
                class="form-control form-control-sm"
              />
            </div>

            <div class="col-md-3 d-flex align-items-center">
              <div class="form-check mt-3">
                <input
                  v-model="form.is_published"
                  class="form-check-input"
                  type="checkbox"
                  id="is_published"
                />
                <label class="form-check-label" for="is_published">
                  Publicată
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="card-footer d-flex justify-content-end gap-2 py-2">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            @click="goBack"
          >
            Anulează
          </button>
          <button
            type="submit"
            class="btn btn-primary btn-sm"
            :disabled="saveLoading"
          >
            <span
              v-if="saveLoading"
              class="spinner-border spinner-border-sm me-1"
            />
            Salvează
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  fetchAdminCategories,
  fetchAdminCategory,
  createAdminCategory,
  updateAdminCategory
} from '@/services/admin/categories';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const saveLoading = ref(false);
const error = ref('');

const categories = ref([]);

const form = ref({
  name: '',
  slug: '',
  parent_id: null,
  sort_order: null,
  is_published: true
});

const isEdit = computed(() => !!route.params.id);

const parentOptions = computed(() =>
  categories.value.filter((c) => !isEdit.value || c.id !== Number(route.params.id))
);

const loadCategories = async () => {
  categories.value = await fetchAdminCategories();
};

const loadCategory = async () => {
  if (!isEdit.value) return;

  const data = await fetchAdminCategory(route.params.id);
  form.value = {
    name: data.name ?? '',
    slug: data.slug ?? '',
    parent_id: data.parent_id ?? null,
    sort_order: data.sort_order ?? null,
    is_published: !!data.is_published
  };
};

const slugify = (value) => {
  return value
    .toString()
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
};

const maybeGenerateSlug = () => {
  if (!form.value.slug && form.value.name) {
    form.value.slug = slugify(form.value.name);
  }
};

const onSubmit = async () => {
  saveLoading.value = true;
  error.value = '';

  const payload = {
    name: form.value.name,
    slug: form.value.slug,
    parent_id: form.value.parent_id || null,
    sort_order: form.value.sort_order ?? null,
    is_published: !!form.value.is_published
  };

  try {
    if (isEdit.value) {
      await updateAdminCategory(route.params.id, payload);
    } else {
      await createAdminCategory(payload);
    }
    router.push({ name: 'admin-categories' });
  } catch (e) {
    console.error('Save category error', e);
    if (e.response?.data?.message) {
      error.value = e.response.data.message;
    } else if (e.response?.status === 422) {
      error.value = 'Date invalide pentru categorie.';
    } else {
      error.value = 'A apărut o eroare la salvare.';
    }
  } finally {
    saveLoading.value = false;
  }
};

const goBack = () => {
  router.push({ name: 'admin-categories' });
};

onMounted(async () => {
  loading.value = true;
  try {
    await loadCategories();
    await loadCategory();
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca datele pentru formular.';
  } finally {
    loading.value = false;
  }
});
</script>
