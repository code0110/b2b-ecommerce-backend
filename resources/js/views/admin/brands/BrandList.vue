<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Mărci / Branduri</h1>
      <button
        class="btn btn-sm btn-primary"
        type="button"
        @click="startCreate"
      >
        Adaugă brand
      </button>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <!-- Formular brand (create / edit) -->
    <div class="card mb-3">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span class="small fw-semibold">
          {{ currentBrand?.id ? 'Editează brand' : 'Brand nou' }}
        </span>
        <span v-if="saving" class="small text-muted">
          Salvare...
        </span>
      </div>
      <div class="card-body">
        <form @submit.prevent="saveBrand">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label form-label-sm">Denumire</label>
              <input
                v-model="form.name"
                type="text"
                class="form-control form-control-sm"
                required
              >
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-sm">Slug</label>
              <input
                v-model="form.slug"
                type="text"
                class="form-control form-control-sm"
                required
              >
              <div class="form-text small">
                Identificator URL (ex: <code>brand-x</code>).
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-sm">Ordine sortare</label>
              <input
                v-model.number="form.sort_order"
                type="number"
                class="form-control form-control-sm"
                min="0"
              >
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label form-label-sm">Descriere</label>
              <textarea
                v-model="form.description"
                class="form-control form-control-sm"
                rows="2"
              />
            </div>
            <div class="col-md-3 d-flex align-items-center">
              <div class="form-check mt-3">
                <input
                  v-model="form.is_active"
                  class="form-check-input"
                  type="checkbox"
                  id="brand-active"
                >
                <label class="form-check-label small" for="brand-active">
                  Publicat / activ
                </label>
              </div>
            </div>
          </div>

          <div class="mt-3 d-flex justify-content-between align-items-center">
            <div>
              <button
                class="btn btn-sm btn-primary me-2"
                type="submit"
                :disabled="saving"
              >
                Salvează
              </button>
              <button
                class="btn btn-sm btn-outline-secondary"
                type="button"
                @click="resetForm"
                :disabled="saving"
              >
                Reset
              </button>
            </div>
            <div class="small text-muted" v-if="currentBrand?.id">
              ID: {{ currentBrand.id }}
            </div>
          </div>

          <div v-if="formError" class="text-danger small mt-2">
            {{ formError }}
          </div>
        </form>
      </div>
    </div>

    <!-- Lista branduri -->
    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Denumire</th>
              <th>Slug</th>
              <th>Ordine</th>
              <th>Status</th>
              <th style="width: 140px;">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr v-if="!loading && !brands.length">
              <td colspan="5" class="text-center text-muted py-3">
                Nu există branduri definite.
              </td>
            </tr>
            <tr
              v-for="brand in brands"
              :key="brand.id"
            >
              <td class="small">
                <div class="fw-semibold">{{ brand.name }}</div>
              </td>
              <td class="small">
                <code>{{ brand.slug }}</code>
              </td>
              <td class="small">
                {{ brand.sort_order ?? '-' }}
              </td>
              <td class="small">
                <span
                  class="badge"
                  :class="brand.is_active ? 'bg-success' : 'bg-secondary'"
                >
                  {{ brand.is_active ? 'Activ' : 'Inactiv' }}
                </span>
              </td>
              <td class="small">
                <div class="btn-group btn-group-sm">
                  <button
                    class="btn btn-outline-secondary"
                    type="button"
                    @click="editBrand(brand)"
                  >
                    Editează
                  </button>
                  <button
                    class="btn btn-outline-danger"
                    type="button"
                    @click="removeBrand(brand)"
                  >
                    Șterge
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import {
  fetchBrands,
  createBrand,
  updateBrand,
  deleteBrand
} from '@/services/admin/brands'

const brands = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const formError = ref('')
const currentBrand = ref(null)

const form = ref({
  name: '',
  slug: '',
  description: '',
  is_active: true,
  sort_order: 0
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
    if (!currentBrand.value && !form.value.slug && val) {
      form.value.slug = slugify(val)
    }
  }
)

const loadBrands = async () => {
  loading.value = true
  error.value = ''
  try {
    const resp = await fetchBrands({ per_page: 1000 })
    brands.value = resp.data || resp || []
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca brandurile.'
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  currentBrand.value = null
  form.value = {
    name: '',
    slug: '',
    description: '',
    is_active: true,
    sort_order: 0
  }
  formError.value = ''
}

const startCreate = () => {
  resetForm()
}

const editBrand = (brand) => {
  currentBrand.value = brand
  form.value = {
    name: brand.name || '',
    slug: brand.slug || '',
    description: brand.description || '',
    is_active: brand.is_active ?? true,
    sort_order: brand.sort_order ?? 0
  }
  formError.value = ''
}

const saveBrand = async () => {
  formError.value = ''
  saving.value = true

  try {
    const payload = { ...form.value }

    if (currentBrand.value?.id) {
      await updateBrand(currentBrand.value.id, payload)
    } else {
      await createBrand(payload)
    }

    await loadBrands()
    resetForm()
  } catch (e) {
    console.error(e)
    formError.value =
      e?.response?.data?.message || 'A apărut o eroare la salvarea brandului.'
  } finally {
    saving.value = false
  }
}

const removeBrand = async (brand) => {
  if (!confirm(`Ștergi brandul "${brand.name}"?`)) return
  try {
    await deleteBrand(brand.id)
    await loadBrands()
  } catch (e) {
    console.error(e)
    alert('Nu s-a putut șterge brandul.')
  }
}

onMounted(loadBrands)
</script>
