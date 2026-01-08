<template>
  <div class="container-fluid py-4">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-7">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h1 class="h3 fw-bold mb-1 text-gray-800">{{ isEdit ? 'Editează Categorie' : 'Categorie Nouă' }}</h1>
            <p class="text-muted small mb-0">Configurează detaliile și ierarhia categoriei.</p>
          </div>
          <RouterLink :to="{ name: 'admin-categories' }" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Înapoi la listă
          </RouterLink>
        </div>

        <div v-if="error" class="alert alert-danger py-2 mb-4 shadow-sm border-0">
          {{ error }}
        </div>

        <!-- Form Card -->
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <form @submit.prevent="saveCategory">
              <div class="row g-4">
                <!-- Basic Info -->
                <div class="col-12">
                  <h6 class="fw-bold text-uppercase text-muted small border-bottom pb-2 mb-3">Informații de bază</h6>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Denumire</label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="form-control"
                    required
                  >
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Slug</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted small">/</span>
                    <input
                      v-model="form.slug"
                      type="text"
                      class="form-control"
                      required
                    >
                  </div>
                  <div class="form-text small">URL-ul prietenos pentru SEO.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Categorie Părinte</label>
                  <select
                    v-model="form.parent_id"
                    class="form-select"
                  >
                    <option :value="null">-- Niciun părinte (Categorie Principală) --</option>
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
                  <label class="form-label small fw-bold text-muted">Ordine</label>
                  <input
                    v-model.number="form.sort_order"
                    type="number"
                    class="form-control"
                    min="0"
                  >
                </div>

                <div class="col-md-3">
                  <label class="form-label small fw-bold text-muted">Status</label>
                  <div class="form-check form-switch mt-2">
                    <input
                      v-model="form.is_published"
                      class="form-check-input"
                      type="checkbox"
                      id="cat-active"
                    >
                    <label class="form-check-label small fw-semibold" for="cat-active">
                      Publicată
                    </label>
                  </div>
                </div>

                <!-- Attributes & Media -->
                <div class="col-12 mt-4">
                  <h6 class="fw-bold text-uppercase text-muted small border-bottom pb-2 mb-3">Detalii Suplimentare</h6>
                </div>

                <div class="col-md-12">
                  <label class="form-label small fw-bold text-muted">Atribute Asociate</label>
                  
                  <div class="card p-3 bg-light border-0">
                    <div v-if="loadingAttributes" class="text-center py-2">
                       <span class="spinner-border spinner-border-sm text-muted"></span>
                    </div>
                    <div v-else class="row g-2">
                       <div v-for="attr in availableAttributes" :key="attr.id" class="col-md-4">
                          <div class="form-check">
                             <input 
                                class="form-check-input" 
                                type="checkbox" 
                                :value="attr.id" 
                                v-model="form.attributes"
                                :id="'attr-' + attr.id"
                             >
                             <label class="form-check-label small" :for="'attr-' + attr.id">
                                {{ attr.name }} <span class="text-muted" style="font-size: 0.75rem">({{ attr.slug }})</span>
                             </label>
                          </div>
                       </div>
                       <div v-if="availableAttributes.length === 0" class="col-12 text-muted small">
                          Nu există atribute definite. <RouterLink :to="{ name: 'admin-attributes' }" class="text-decoration-none">Creează atribute</RouterLink>
                       </div>
                    </div>
                  </div>
                  <div class="form-text small mt-2">
                    Selectează atributele disponibile pentru filtrare în această categorie.
                  </div>
                </div>
                <div class="col-md-12">
                  <label class="form-label small fw-bold text-muted">Imagine (URL)</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-image text-muted"></i></span>
                    <input
                      v-model="form.image_url"
                      type="text"
                      class="form-control"
                      placeholder="https://example.com/image.jpg"
                    >
                  </div>
                </div>
              </div>

              <div class="mt-5 d-flex justify-content-end gap-2 border-top pt-4">
                <RouterLink
                  class="btn btn-light border"
                  :to="{ name: 'admin-categories' }"
                >
                  Anulează
                </RouterLink>
                <button
                  class="btn btn-primary px-4"
                  type="submit"
                  :disabled="saving"
                >
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                  {{ isEdit ? 'Salvează Modificările' : 'Creează Categorie' }}
                </button>
              </div>

              <div v-if="formError" class="alert alert-danger mt-3 mb-0 small">
                {{ formError }}
              </div>
            </form>
          </div>
        </div>
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
import { fetchAllAttributes } from '@/services/admin/attributes'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => !!route.params.id)

const loading = ref(false)
const saving = ref(false)
const loadingAttributes = ref(false)
const error = ref('')
const formError = ref('')

const allCategories = ref([])
const availableAttributes = ref([])

const form = ref({
  name: '',
  slug: '',
  parent_id: null,
  sort_order: 0,
  is_active: true,
  attributes: [],
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

const loadAttributes = async () => {
  loadingAttributes.value = true
  try {
    const resp = await fetchAllAttributes()
    availableAttributes.value = resp.data || resp
  } catch (e) {
    console.error(e)
  } finally {
    loadingAttributes.value = false
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
      attributes: (cat.attributes || []).map(a => a.id),
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
      is_published: form.value.is_published,
      attributes: form.value.attributes,
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
  await Promise.all([
    loadCategories(),
    loadAttributes(),
    loadCategory()
  ])
})
</script>
