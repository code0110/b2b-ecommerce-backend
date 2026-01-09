<template>
  <div v-if="loading" class="text-center py-5">
    <div class="spinner-border text-orange" role="status">
      <span class="visually-hidden">Se încarcă...</span>
    </div>
    <div class="mt-2 text-muted">Se încarcă detaliile produsului...</div>
  </div>

  <div class="container py-4" v-else-if="product">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="small mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <RouterLink to="/">Acasă</RouterLink>
        </li>
        <li class="breadcrumb-item">
          <RouterLink :to="`/categorie/${product.categorySlug}`">
            {{ categoryTitle }}
          </RouterLink>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
          {{ product.name }}
        </li>
      </ol>
    </nav>

    <!-- Header + preț + stoc -->
    <div class="row g-4 mb-3">
      <div class="col-md-5">
        <div class="border rounded bg-white p-3 mb-2 text-center">
          <div class="ratio ratio-4x3 bg-light d-flex align-items-center justify-content-center">
            <img 
              v-if="product.image_url" 
              :src="product.image_url" 
              :alt="product.name"
              class="img-fluid"
              style="max-height: 100%; object-fit: contain;"
            />
            <span v-else class="text-muted small">
              Imagine indisponibilă
            </span>
          </div>
        </div>
        <div class="d-flex gap-2 small" v-if="product.images && product.images.length > 0">
          <button
            v-for="(img, idx) in product.images"
            :key="idx"
            type="button"
            class="btn btn-outline-secondary btn-sm flex-fill"
          >
            Imagine {{ idx + 1 }}
          </button>
        </div>
      </div>

      <div class="col-md-7">
        <h1 class="h4 mb-1">{{ product.name }}</h1>
        <div class="small text-muted mb-2">
          Cod produs: {{ product.internal_code || product.code }} • Brand: {{ product.brand || 'Generic' }}
        </div>

        <div class="mb-2">
          <span
            class="badge"
            :class="isStockAvailable ? 'bg-success' : 'bg-secondary'"
          >
            {{ isStockAvailable ? 'În stoc' : 'La comandă' }}
          </span>
          <span
            v-if="product.stock_qty !== null && isB2B"
            class="badge bg-light text-dark ms-2"
          >
            Stoc B2B: {{ product.stock_qty }}
          </span>
        </div>

        <div class="mb-3">
          <div class="h5 mb-1">
            <span v-if="product.has_discount" class="text-muted text-decoration-line-through h6 me-2">
              {{ formatPrice(product.list_price || product.price) }}
            </span>
            <span class="fw-semibold">
              {{ formatPrice(displayPrice) }}
            </span>
          </div>
          <div class="small text-muted">
            <span v-if="product.has_discount">
              Preț promoțional activ.
            </span>
            <span v-else>
              Preț de listă.
            </span>
          </div>
          <div class="small mt-1" v-if="isB2B">
            <span class="badge bg-dd-blue me-1">B2B</span>
            Prețuri și condiții comerciale B2B (termen plată, limită credit) se aplică în cont sau la impersonare.
          </div>
        </div>

        <!-- Variante + unități de măsură -->
        <div class="row g-2 mb-3 small">
          <div class="col-md-6" v-if="product.variants && product.variants.length">
            <label class="form-label">Variantă</label>
            <select class="form-select form-select-sm" v-model="selectedVariant">
              <option
                v-for="variant in product.variants"
                :key="variant.code"
                :value="variant.code"
              >
                {{ variant.label }} (cod: {{ variant.code }})
              </option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Unitate de măsură</label>
            <select class="form-select form-select-sm" v-model="selectedUnit">
              <option
                v-for="uom in unitsOfMeasure"
                :key="uom.value"
                :value="uom.value"
              >
                {{ uom.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Cantitate + butoane -->
        <div class="d-flex align-items-center gap-2 mb-4">
          <div style="width: 100px;">
            <input
              type="number"
              class="form-control"
              v-model.number="quantity"
              min="1"
            />
          </div>
          <button
            class="btn btn-orange"
            @click="addToCartDemo"
            :disabled="!isStockAvailable && !product.can_backorder"
          >
            <i class="bi bi-cart-plus"></i> Adaugă în coș
          </button>
          <button
            class="btn btn-outline-secondary"
            title="Adaugă la favorite"
            @click="addToFavoritesDemo"
          >
            <i class="bi bi-heart"></i>
          </button>
          <button
            v-if="isB2B"
            class="btn btn-outline-secondary"
            title="Solicită ofertă personalizată"
            @click="requestOfferDemo"
          >
            <i class="bi bi-file-earmark-text"></i>
          </button>
        </div>

        <!-- Tabs: Descriere / Specificații / Documente -->
        <ul class="nav nav-tabs mb-3" id="productTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button
              class="nav-link active"
              id="desc-tab"
              data-bs-toggle="tab"
              data-bs-target="#desc-pane"
              type="button"
              role="tab"
            >
              Descriere
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              id="specs-tab"
              data-bs-toggle="tab"
              data-bs-target="#specs-pane"
              type="button"
              role="tab"
            >
              Specificații
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              id="docs-tab"
              data-bs-toggle="tab"
              data-bs-target="#docs-pane"
              type="button"
              role="tab"
            >
              Documente
            </button>
          </li>
        </ul>
        <div class="tab-content" id="productTabContent">
          <div
            class="tab-pane fade show active"
            id="desc-pane"
            role="tabpanel"
          >
            <div class="small text-muted" v-html="product.long_description || product.description || 'Fără descriere.'"></div>
          </div>
          <div
            class="tab-pane fade"
            id="specs-pane"
            role="tabpanel"
          >
            <table class="table table-sm table-striped small mb-0" v-if="product.attributes && product.attributes.length">
              <tbody>
                <tr v-for="(attr, idx) in product.attributes" :key="idx">
                  <td class="text-muted" style="width: 40%">{{ attr.name }}</td>
                  <td class="fw-semibold">{{ attr.value }}</td>
                </tr>
              </tbody>
            </table>
            <div v-else class="small text-muted">Nu există specificații.</div>
          </div>
          <div
            class="tab-pane fade"
            id="docs-pane"
            role="tabpanel"
          >
            <div v-if="product.documents && product.documents.length" class="list-group list-group-flush small">
              <div
                v-for="doc in product.documents"
                :key="doc.id"
                class="list-group-item d-flex justify-content-between align-items-center px-0"
              >
                <div>
                  <div class="fw-semibold">
                    <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                    {{ doc.label || doc.name }}
                  </div>
                  <div class="text-muted" style="font-size: 0.85rem;">
                    {{ doc.description }}
                    <span v-if="doc.restriction !== 'public'" class="badge bg-warning text-dark ms-1">
                      {{ doc.restrictionLabel || 'Restricționat' }}
                    </span>
                  </div>
                </div>
                <button
                  class="btn btn-sm btn-outline-secondary"
                  @click="openDocumentDemo(doc)"
                >
                  Descarcă
                </button>
              </div>
            </div>
            <div v-else class="small text-muted">Nu există documente atașate.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Produse similare / complementare -->
    <div class="row g-4 mt-2">
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Produse similare</strong>
          </div>
          <div class="card-body small">
            <div
              v-for="similar in similarProducts"
              :key="similar.slug"
              class="d-flex justify-content-between align-items-center py-1 border-bottom"
            >
              <div>
                <div class="fw-semibold">{{ similar.name }}</div>
                <div class="text-muted">{{ similar.internal_code || similar.code }}</div>
              </div>
              <RouterLink
                :to="`/produs/${similar.slug}`"
                class="btn btn-outline-secondary btn-sm"
              >
                Detalii
              </RouterLink>
            </div>
            <div v-if="similarProducts.length === 0" class="text-muted">
              Nu sunt definite produse similare.
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Produse complementare</strong>
          </div>
          <div class="card-body small">
            <div
              v-for="comp in complementaryProducts"
              :key="comp.slug"
              class="d-flex justify-content-between align-items-center py-1 border-bottom"
            >
              <div>
                <div class="fw-semibold">{{ comp.name }}</div>
                <div class="text-muted">{{ comp.internal_code || comp.code }}</div>
              </div>
              <RouterLink
                :to="`/produs/${comp.slug}`"
                class="btn btn-outline-secondary btn-sm"
              >
                Detalii
              </RouterLink>
            </div>
            <div v-if="complementaryProducts.length === 0" class="text-muted">
              Nu sunt definite produse complementare.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else-if="error" class="container py-4">
     <div class="alert alert-danger">
        {{ error }}
     </div>
      <div class="mt-3">
        <RouterLink to="/" class="btn btn-outline-secondary">
          Înapoi la prima pagină
        </RouterLink>
      </div>
  </div>

  <div v-else class="container py-4">
    <div class="alert alert-warning">
      Produsul nu a fost găsit.
    </div>
    <div class="mt-3">
        <RouterLink to="/" class="btn btn-outline-secondary">
          Înapoi la prima pagină
        </RouterLink>
      </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { fetchProductBySlug } from '@/services/catalog'

const route = useRoute()
const authStore = useAuthStore()

const loading = ref(false)
const error = ref(null)
const product = ref(null)
const quantity = ref(1)

const frontClientType = computed(() => {
  if (authStore.impersonatedCustomer?.clientType) {
    return authStore.impersonatedCustomer.clientType
  }
  if (authStore.user?.role === 'b2b') return 'B2B'
  if (authStore.user?.role === 'b2c') return 'B2C'
  return null
})

const isB2B = computed(() => frontClientType.value === 'B2B')

const slug = computed(() => route.params.slug || '')

const loadProduct = async () => {
  if (!slug.value) return
  
  loading.value = true
  error.value = null
  product.value = null
  
  try {
    const data = await fetchProductBySlug(slug.value)
    product.value = data
    // Select first variant if available
    if (data.variants && data.variants.length > 0) {
        selectedVariant.value = data.variants[0].code
    }
  } catch (err) {
    console.error(err)
    if (err.response && err.response.status === 404) {
       error.value = "Produsul nu există sau a fost dezactivat."
    } else {
       error.value = "A apărut o eroare la încărcarea produsului."
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadProduct()
})

watch(slug, () => {
  loadProduct()
})

const categoryTitle = computed(() => {
  if (!product.value) return 'Categorie produse'
  // Assuming product.category object or category_slug property from API
  return product.value.category?.name || product.value.category_name || 'Categorie produse'
})

const selectedVariant = ref(null)

const unitsOfMeasure = [
  { value: 'buc', label: 'bucată' },
  { value: 'sac', label: 'sac' },
  { value: 'bax', label: 'bax' },
  { value: 'palet', label: 'palet' }
]
const selectedUnit = ref('buc')

const displayPrice = computed(() => {
  if (!product.value) return 0
  if (product.value.price_override) return parseFloat(product.value.price_override)
  if (product.value.has_discount && product.value.promo_price) {
    return parseFloat(product.value.promo_price)
  }
  return parseFloat(product.value.price || 0)
})

const isStockAvailable = computed(() => {
    if (!product.value) return false
    if (product.value.stock_status === 'in_stock') return true
    if (product.value.stock_qty > 0) return true
    return false
})

const similarProducts = computed(() => {
  if (!product.value || !product.value.similar_products) return []
  return product.value.similar_products
})

const complementaryProducts = computed(() => {
  if (!product.value || !product.value.complementary_products) return []
  return product.value.complementary_products
})

const formatPrice = (val) => {
    if (val === null || val === undefined) return '-'
    return new Intl.NumberFormat('ro-RO', { 
        style: 'currency', 
        currency: 'RON' 
    }).format(val)
}

const addToCartDemo = () => {
  if (!product.value) return
  window.alert(
    `Demo: produsul "${product.value.name}" (cantitate: ${quantity.value}) ` +
      `ar fi adăugat în coș.`
  )
}

const addToFavoritesDemo = () => {
  if (!product.value) return
  window.alert(
    `Demo: produsul "${product.value.name}" ar fi adăugat la lista de favorite pentru clientul curent.`
  )
}

const requestOfferDemo = () => {
  if (!product.value) return
  window.alert(
    'Demo: ar porni fluxul "Solicită ofertă" către agent / director, ' +
      'cu produsul preselectat în cerere.'
  )
}

const openDocumentDemo = (doc) => {
    window.alert(`Demo: s-ar descărca documentul "${doc.label || doc.name}".`)
}
</script>