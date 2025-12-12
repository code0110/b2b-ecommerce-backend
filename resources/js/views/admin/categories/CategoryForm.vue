<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-0">
          {{ isEdit ? 'Editează categorie' : 'Categorie nouă' }}
        </h1>
        <div class="small text-muted">
          <RouterLink :to="{ name: 'admin-categories' }">
            ← Înapoi la lista de categorii
          </RouterLink>
        </div>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div class="card">
      <div class="card-body">
        <form @submit.prevent="saveCategory">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-label-sm">Denumire</label>
              <input
                v-model="form.name"
                type="text"
                class="form-control form-control-sm"
                required
              >
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-sm">Slug</label>
              <input
                v-model="form.slug"
                type="text"
                class="form-control form-control-sm"
                required
              >
              <div class="form-text small">
                Folosit în URL (ex: <code>materiale-constructii</code>).
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label form-label-sm">Categorie părinte</label>
              <select
                v-model="form.parent_id"
                class="form-select form-select-sm"
              >
                <option :value="null">— fără părinte —</option>
                <option
                  v-for="cat in parentOptions"
                  :key="cat.id"
                  :value="cat.id"
                >
                  {{ cat.name }}
                </option>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label form-label-sm">Ordine sortare</label>
              <input
                v-model.number="form.sort_order"
                type="number"
                class="form-control form-control-sm"
                min="0"
              >
            </div>

            <div class="col-md-3 d-flex align-items-end">
              <div class="form-check">
                <input
                  v-model="form.is_active"
                  class="form-check-input"
                  type="checkbox"
                  id="cat-active"
                >
                <label class="form-check-label small" for="cat-active">
                  Publicată / vizibilă în front
                </label>
              </div>
            </div>
          </div>

          <!-- Atribute / imagine – deocamdată doar placeholder simplu -->
          <hr class="my-3">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-label-sm">Atribute asociate (text liber demo)</label>
              <textarea
                v-model="form.attributes_raw"
                rows="3"
                class="form-control form-control-sm"
                placeholder="Ex: material, culoare, dimensiune..."
              />
              <div class="form-text small">
                Pentru integrare reală cu atribute, poți mappa la un endpoint dedicat în backend.
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-sm">Imagine reprezentativă (URL simplu demo)</label>
              <input
                v-model="form.image_url"
                type="text"
                class="form-control form-control-sm"
                placeholder="https://..."
              >
              <div class="form-text small">
                Pentru upload real, se configurează endpoint separat.
              </div>
            </div>
          </div>

          <div class="mt-3 d-flex justify-content-between">
            <div>
              <button
                class="btn btn-sm btn-primary me-2"
                type="submit"
                :disabled="saving"
              >
                Salvează categoria
              </button>
              <RouterLink
                class="btn btn-sm btn-outline-secondary"
                :to="{ name: 'admin-categories' }"
              >
                Anulează
              </RouterLink>
            </div>
            <div class="small text-muted" v-if="isEdit">
              ID: {{ route.params.id }}
            </div>
          </div>

          <div v-if="formError" class="text-danger small mt-2">
            {{ formError }}
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  fetchCategory,
  fetchAllCategories,
  createCategory,
  updateCategory
} from '@/services/admin/categories'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => !!route.params.id)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const formError = ref('')

const allCategories = ref([])

const form = ref({
  name: '',
  slug: '',
  parent_id: null,
  sort_order: 0,
  is_active: true,
  attributes_raw: '',
  image_url: ''
})

const slugify = (value) => {
  return value
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

watch(
  () => form.value.name,
  (val) => {
    if (!isEdit.value && !form.value.slug && val) {
      form.value.slug = slugify(val)
    }
  }
)

const parentOptions = computed(() => {
  const id = isEdit.value ? Number(route.params.id) : null
  return allCategories.value.filter((cat) => cat.id !== id)
})

const loadCategories = async () => {
  try {
    const resp = await fetchAllCategories()
    allCategories.value = resp
  } catch (e) {
    console.error(e)
  }
}

const loadCategory = async () => {
  if (!isEdit.value) return
  loading.value = true
  error.value = ''
  try {
    const data = await fetchCategory(route.params.id)
    const cat = data.data || data
    form.value = {
      name: cat.name || '',
      slug: cat.slug || '',
      parent_id: cat.parent_id,
      sort_order: cat.sort_order ?? 0,
      is_active: cat.is_active ?? true,
      attributes_raw: cat.attributes_raw || '',
      image_url: cat.image_url || ''
    }
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-a putut încărca categoria.'
  } finally {
    loading.value = false
  }
}

const saveCategory = async () => {
  formError.value = ''
  saving.value = true

  try {
    const payload = {
      name: form.value.name,
      slug: form.value.slug,
      parent_id: form.value.parent_id,
      sort_order: form.value.sort_order,
      is_active: form.value.is_active,
      attributes_raw: form.value.attributes_raw,
      image_url: form.value.image_url
    }

    if (isEdit.value) {
      await updateCategory(route.params.id, payload)
    } else {
      await createCategory(payload)
    }

    router.push({ name: 'admin-categories' })
  } catch (e) {
    console.error(e)
    formError.value =
      e?.response?.data?.message || 'Nu s-a putut salva categoria.'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadCategories()
  await loadCategory()
})
</script>
