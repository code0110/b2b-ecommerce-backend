<template>
  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1 text-gray-800">Mărci / Branduri</h1>
        <p class="text-muted small mb-0">Gestionează brandurile comercializate.</p>
      </div>
      <button class="btn btn-primary shadow-sm" @click="startCreate">
        <i class="bi bi-plus-lg me-2"></i>Adaugă Brand
      </button>
    </div>

    <div v-if="error" class="alert alert-danger py-2 mb-4 shadow-sm border-0">
      {{ error }}
    </div>

    <!-- Brands Table -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Se încarcă...</span>
          </div>
        </div>

        <div v-else-if="!brands.length" class="text-center py-5">
          <div class="mb-3">
            <i class="bi bi-tags text-muted" style="font-size: 3rem;"></i>
          </div>
          <h5 class="text-muted">Nu există branduri definite</h5>
          <p class="text-muted small">Adaugă primul brand folosind butonul de mai sus.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 py-3 text-muted small text-uppercase fw-bold border-0">Denumire</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Slug</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Ordine</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Status</th>
                <th class="pe-4 py-3 text-muted small text-uppercase fw-bold border-0 text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="brand in brands" :key="brand.id">
                <td class="ps-4 fw-semibold text-dark">{{ brand.name }}</td>
                <td><code class="text-muted bg-light px-2 py-1 rounded small">{{ brand.slug }}</code></td>
                <td class="text-muted">{{ brand.sort_order ?? '-' }}</td>
                <td>
                  <span class="badge rounded-pill" :class="brand.is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary'">
                    {{ brand.is_active ? 'Activ' : 'Inactiv' }}
                  </span>
                </td>
                <td class="pe-4 text-end">
                  <div class="btn-group">
                    <button class="btn btn-sm btn-light border" @click="editBrand(brand)" title="Editează">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-light border text-danger" @click="removeBrand(brand)" title="Șterge">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold">{{ currentBrand?.id ? 'Editează Brand' : 'Brand Nou' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body pt-4">
            <form @submit.prevent="saveBrand">
              <div class="row g-3">
                <div class="col-md-12">
                  <label class="form-label small fw-bold text-muted">Denumire</label>
                  <input v-model="form.name" type="text" class="form-control" required />
                </div>
                <div class="col-md-12">
                  <label class="form-label small fw-bold text-muted">Slug</label>
                  <input v-model="form.slug" type="text" class="form-control" required />
                  <div class="form-text small">Identificator URL unic (ex: brand-x).</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Ordine</label>
                  <input v-model.number="form.sort_order" type="number" class="form-control" min="0" />
                </div>
                <div class="col-md-12">
                  <label class="form-label small fw-bold text-muted">Descriere</label>
                  <textarea v-model="form.description" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-md-12">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="brandActive" v-model="form.is_active">
                    <label class="form-check-label small fw-bold" for="brandActive">Brand Activ</label>
                  </div>
                </div>
              </div>

              <div v-if="formError" class="alert alert-danger mt-3 mb-0 small">
                {{ formError }}
              </div>

              <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light" @click="closeModal">Anulează</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                  {{ currentBrand?.id ? 'Salvează' : 'Creează' }}
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
const showModal = ref(false)

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
  showModal.value = true
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
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
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
    closeModal()
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
