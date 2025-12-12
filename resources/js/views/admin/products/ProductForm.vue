<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-0">
          {{ isEdit ? 'Editează produs' : 'Produs nou' }}
        </h1>
        <div class="small text-muted">
          <RouterLink :to="{ name: 'admin-products' }">
            ← Înapoi la lista de produse
          </RouterLink>
        </div>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div class="card">
      <div class="card-body">
        <form @submit.prevent="saveProduct">
          <!-- Date generale -->
          <h2 class="h6 mb-3">Date generale</h2>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-label-sm">Denumire produs</label>
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
                Folosit în URL-ul din front (<code>/produs/[slug]</code>).
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">Cod intern / SKU</label>
              <input
                v-model="form.sku"
                type="text"
                class="form-control form-control-sm"
              >
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">ERP ID</label>
              <input
                v-model="form.erp_id"
                type="text"
                class="form-control form-control-sm"
              >
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">Categorie principală</label>
              <select
                v-model="form.main_category_id"
                class="form-select form-select-sm"
                required
              >
                <option :value="null">— alege —</option>
                <option
                  v-for="cat in categories"
                  :key="cat.id"
                  :value="cat.id"
                >
                  {{ cat.name }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">Brand</label>
              <select
                v-model="form.brand_id"
                class="form-select form-select-sm"
              >
                <option :value="null">— fără brand —</option>
                <option
                  v-for="brand in brands"
                  :key="brand.id"
                  :value="brand.id"
                >
                  {{ brand.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Descrieri -->
          <hr class="my-3">
          <h2 class="h6 mb-2">Descrieri</h2>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label form-label-sm">Descriere scurtă</label>
              <textarea
                v-model="form.short_description"
                class="form-control form-control-sm"
                rows="3"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label form-label-sm">Descriere detaliată</label>
              <textarea
                v-model="form.description"
                class="form-control form-control-sm"
                rows="5"
              />
            </div>
          </div>

          <!-- Preț & stoc -->
          <hr class="my-3">
          <h2 class="h6 mb-2">Preț & stoc</h2>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label form-label-sm">Preț listă (RON)</label>
              <input
                v-model.number="form.price"
                type="number"
                step="0.01"
                class="form-control form-control-sm"
              >
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">Preț promoțional (RON)</label>
              <input
                v-model.number="form.promo_price"
                type="number"
                step="0.01"
                class="form-control form-control-sm"
              >
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">Stoc disponibil</label>
              <input
                v-model.number="form.stock_qty"
                type="number"
                min="0"
                class="form-control form-control-sm"
              >
            </div>
            <div class="col-md-3">
              <label class="form-label form-label-sm">Status stoc</label>
              <select
                v-model="form.stock_status"
                class="form-select form-select-sm"
              >
                <option value="in_stock">În stoc</option>
                <option value="limited">Stoc limitat</option>
                <option value="by_order">La comandă</option>
                <option value="out_of_stock">Epuizat</option>
              </select>
            </div>
          </div>

          <!-- Status & flag-uri -->
          <hr class="my-3">
          <h2 class="h6 mb-2">Status & vizibilitate</h2>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label form-label-sm">Status publicare</label>
              <select
                v-model="form.is_active"
                class="form-select form-select-sm"
              >
                <option :value="true">Activ / publicat</option>
                <option :value="false">Inactiv / ascuns</option>
              </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <div class="form-check">
                <input
                  v-model="form.is_new"
                  class="form-check-input"
                  type="checkbox"
                  id="prod-new"
                >
                <label class="form-check-label small" for="prod-new">
                  Marcat ca „produs nou”
                </label>
              </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <div class="form-check">
                <input
                  v-model="form.is_promo"
                  class="form-check-input"
                  type="checkbox"
                  id="prod-promo"
                >
                <label class="form-check-label small" for="prod-promo">
                  Marcat ca „în promoție”
                </label>
              </div>
            </div>
          </div>

          <!-- Placeholder pentru atribute/variante/documente -->
          <hr class="my-3">
          <p class="small text-muted mb-0">
            Pentru atribute avansate, variante și documente tehnice,
            putem extinde acest formular după ce standardizăm structura din backend.
          </p>

          <div class="mt-3 d-flex justify-content-between">
            <div>
              <button
                class="btn btn-sm btn-primary me-2"
                type="submit"
                :disabled="saving"
              >
                Salvează produsul
              </button>
              <RouterLink
                class="btn btn-sm btn-outline-secondary"
                :to="{ name: 'admin-products' }"
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
  fetchProduct,
  createProduct,
  updateProduct
} from '@/services/admin/products'
import { fetchAllCategories } from '@/services/admin/categories'
import { fetchBrands } from '@/services/admin/brands'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => !!route.params.id)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const formError = ref('')

const categories = ref([])
const brands = ref([])

const form = ref({
  name: '',
  slug: '',
  sku: '',
  erp_id: '',
  main_category_id: null,
  brand_id: null,
  short_description: '',
  description: '',
  price: null,
  promo_price: null,
  stock_qty: null,
  stock_status: 'in_stock',
  is_active: true,
  is_new: false,
  is_promo: false
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

const loadCategories = async () => {
  try {
    const resp = await fetchAllCategories()
    categories.value = resp
  } catch (e) {
    console.error(e)
  }
}

const loadBrands = async () => {
  try {
    const resp = await fetchBrands({ per_page: 1000 })
    brands.value = resp.data || resp || []
  } catch (e) {
    console.error(e)
  }
}

const loadProduct = async () => {
  if (!isEdit.value) return
  loading.value = true
  error.value = ''
  try {
    const data = await fetchProduct(route.params.id)
    const p = data.data || data
    form.value = {
      name: p.name || '',
      slug: p.slug || '',
      sku: p.sku || p.internal_code || '',
      erp_id: p.erp_id || '',
      main_category_id: p.main_category_id || null,
      brand_id: p.brand_id || null,
      short_description: p.short_description || '',
      description: p.description || p.long_description || '',
      price: p.price ?? p.list_price ?? null,
      promo_price: p.promo_price ?? null,
      stock_qty: p.stock_qty ?? p.stock_quantity ?? null,
      stock_status: p.stock_status || 'in_stock',
      is_active: p.is_active ?? p.is_published ?? true,
      is_new: p.is_new ?? false,
      is_promo: p.is_promo ?? false
    }
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-a putut încărca produsul.'
  } finally {
    loading.value = false
  }
}

const saveProduct = async () => {
  formError.value = ''
  saving.value = true

  try {
    const payload = {
      name: form.value.name,
      slug: form.value.slug,
      sku: form.value.sku,
      erp_id: form.value.erp_id,
      main_category_id: form.value.main_category_id,
      brand_id: form.value.brand_id,
      short_description: form.value.short_description,
      description: form.value.description,
      price: form.value.price,
      promo_price: form.value.promo_price,
      stock_qty: form.value.stock_qty,
      stock_status: form.value.stock_status,
      is_active: form.value.is_active,
      is_new: form.value.is_new,
      is_promo: form.value.is_promo
    }

    if (isEdit.value) {
      await updateProduct(route.params.id, payload)
    } else {
      await createProduct(payload)
    }

    router.push({ name: 'admin-products' })
  } catch (e) {
    console.error(e)
    formError.value =
      e?.response?.data?.message || 'Nu s-a putut salva produsul.'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadCategories(), loadBrands()])
  await loadProduct()
})
</script>
