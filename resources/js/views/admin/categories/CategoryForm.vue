<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  fetchAdminCategory,
  fetchAdminCategories,
  createAdminCategory,
  updateAdminCategory,
} from '@/services/admin/categories';

const route = useRoute();
const router = useRouter();

const categoryId = computed(() => route.params.id ?? null);
const isEdit = computed(() => !!categoryId.value);

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const successMessage = ref('');

const form = ref({
  name: '',
  slug: '',
  parent_id: null,
  sort_order: 0,
  is_published: true,
});

// liste pentru select
const allCategories = ref([]);

// helper pentru slug
const slugify = (value) => {
  return String(value || '')
    .toLowerCase()
    .trim()
    .replace(/[\s\_]+/g, '-')
    .replace(/[^a-z0-9\-]/g, '')
    .replace(/\-+/g, '-');
};

// generează slug când se schimbă numele și slug-ul e gol (la create)
watch(
  () => form.value.name,
  (newVal) => {
    if (!isEdit.value && !form.value.slug) {
      form.value.slug = slugify(newVal);
    }
  },
);

const loadCategoryOptions = async () => {
  try {
    const cats = await fetchAdminCategories({ per_page: 999 });
    allCategories.value = cats;
  } catch (e) {
    console.error('Load categories options error', e);
  }
};

const loadCategory = async () => {
  if (!isEdit.value) return;
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchAdminCategory(categoryId.value);

    form.value = {
      name: data.name ?? '',
      slug: data.slug ?? '',
      parent_id: data.parent_id ?? null,
      sort_order: data.sort_order ?? 0,
      is_published: data.is_published ?? true,
    };
  } catch (e) {
    console.error('Load category error', e);
    error.value = 'Nu s-a putut încărca categoria pentru editare.';
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  error.value = '';
  successMessage.value = '';
  saving.value = true;

  try {
    const payload = { ...form.value };

    if (isEdit.value) {
      await updateAdminCategory(categoryId.value, payload);
      successMessage.value = 'Categoria a fost actualizată.';
    } else {
      await createAdminCategory(payload);
      successMessage.value = 'Categoria a fost creată.';

      // după creare, te poți întoarce la listă
      router.push({ name: 'admin-categories' });
      return;
    }
  } catch (e) {
    console.error('Save category error', e);

    if (e.response?.data?.errors) {
      const errs = e.response.data.errors;
      error.value = Object.values(errs).flat().join(' ');
    } else {
      error.value =
        e.response?.data?.message ||
        'Salvarea categoriei a eșuat. Verifică datele și încearcă din nou.';
    }
  } finally {
    saving.value = false;
  }
};

const cancel = () => {
  router.push({ name: 'admin-categories' });
};

onMounted(async () => {
  await loadCategoryOptions();
  await loadCategory();
});
</script>

<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h4 mb-0">
        {{ isEdit ? 'Editează categorie' : 'Creează categorie' }}
      </h1>
      <button type="button" class="btn btn-outline-secondary" @click="cancel">
        Înapoi la listă
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>
    <div v-if="successMessage" class="alert alert-success">
      {{ successMessage }}
    </div>

    <div class="card">
      <div class="card-body">
        <div v-if="loading">Se încarcă datele categoriei...</div>

        <form v-else @submit.prevent="handleSubmit" class="row g-3">
          <div class="col-12">
            <h5 class="mb-3">Date categorie</h5>
          </div>

          <div class="col-md-6">
            <label class="form-label">Denumire</label>
            <input
              v-model="form.name"
              type="text"
              class="form-control"
              required
            />
          </div>

          <div class="col-md-6">
            <label class="form-label">Slug</label>
            <input
              v-model="form.slug"
              type="text"
              class="form-control"
              required
            />
            <div class="form-text">
              URL friendly, folosit în /categorie/[slug].
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Categorie părinte</label>
            <select v-model="form.parent_id" class="form-select">
              <option :value="null">— Fără părinte (categorie rădăcină) —</option>
              <option
                v-for="cat in allCategories"
                :key="cat.id"
                :value="cat.id"
              >
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Ordine sortare</label>
            <input
              v-model.number="form.sort_order"
              type="number"
              class="form-control"
            />
          </div>

          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select v-model="form.is_published" class="form-select">
              <option :value="true">Publicat</option>
              <option :value="false">Ascuns</option>
            </select>
          </div>

          <div class="col-12 mt-4 d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="cancel"
            >
              Anulează
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="saving"
            >
              <span v-if="!saving">
                {{ isEdit ? 'Salvează modificările' : 'Creează categorie' }}
              </span>
              <span v-else>Se salvează...</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
