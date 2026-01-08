<template>
  <div class="container-fluid py-4">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-6">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h1 class="h3 fw-bold mb-1 text-gray-800">{{ isEdit ? 'Editează Atribut' : 'Atribut Nou' }}</h1>
            <p class="text-muted small mb-0">Definește detaliile atributului.</p>
          </div>
          <RouterLink :to="{ name: 'admin-attributes' }" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Înapoi la listă
          </RouterLink>
        </div>

        <div v-if="error" class="alert alert-danger py-2 mb-4 shadow-sm border-0">
          {{ error }}
        </div>

        <!-- Form Card -->
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <form @submit.prevent="saveAttribute">
              <div class="row g-4">
                <div class="col-md-12">
                  <label class="form-label small fw-bold text-muted">Denumire</label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="form-control"
                    required
                  >
                </div>

                <div class="col-md-12">
                  <label class="form-label small fw-bold text-muted">Slug</label>
                  <input
                    v-model="form.slug"
                    type="text"
                    class="form-control"
                    required
                  >
                  <div class="form-text small">Identificator unic pentru URL și sistem.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Tip</label>
                  <select v-model="form.type" class="form-select" required>
                    <option value="string">Text (String)</option>
                    <option value="number">Număr</option>
                    <option value="boolean">Boolean (Da/Nu)</option>
                    <option value="select">Selecție (Listă)</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Opțiuni</label>
                  <div class="form-check form-switch mt-2">
                    <input
                      v-model="form.is_filterable"
                      class="form-check-input"
                      type="checkbox"
                      id="attr-filterable"
                    >
                    <label class="form-check-label small fw-semibold" for="attr-filterable">
                      Utilizabil ca filtru
                    </label>
                  </div>
                </div>
              </div>

              <div class="mt-5 d-flex justify-content-end gap-2 border-top pt-4">
                <RouterLink
                  class="btn btn-light border"
                  :to="{ name: 'admin-attributes' }"
                >
                  Anulează
                </RouterLink>
                <button
                  class="btn btn-primary px-4"
                  type="submit"
                  :disabled="saving"
                >
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                  {{ isEdit ? 'Salvează Modificările' : 'Creează Atribut' }}
                </button>
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
import { fetchAttribute, createAttribute, updateAttribute } from '@/services/admin/attributes'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => !!route.params.id)
const loading = ref(false)
const saving = ref(false)
const error = ref('')

const form = ref({
  name: '',
  slug: '',
  type: 'string',
  is_filterable: false
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

const loadAttribute = async () => {
  if (!isEdit.value) return
  loading.value = true
  try {
    const data = await fetchAttribute(route.params.id)
    const attr = data.data || data
    form.value = {
      name: attr.name,
      slug: attr.slug,
      type: attr.type,
      is_filterable: !!attr.is_filterable
    }
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-a putut încărca atributul.'
  } finally {
    loading.value = false
  }
}

const saveAttribute = async () => {
  saving.value = true
  error.value = ''
  try {
    if (isEdit.value) {
      await updateAttribute(route.params.id, form.value)
    } else {
      await createAttribute(form.value)
    }
    router.push({ name: 'admin-attributes' })
  } catch (e) {
    console.error(e)
    error.value = e?.response?.data?.message || 'Nu s-a putut salva atributul.'
  } finally {
    saving.value = false
  }
}

onMounted(loadAttribute)
</script>
