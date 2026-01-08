<template>
  <div v-if="loading" class="text-center py-5">
    <div class="spinner-border text-primary" role="status">
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
          <RouterLink :to="product.categorySlug ? `/categorie/${product.categorySlug}` : '/produse'">
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
        <figure class="figure bg-white border rounded p-3 mb-2 w-100 text-center">
          <div class="ratio ratio-4x3 bg-light d-flex align-items-center justify-content-center">
            <img
              v-if="selectedImage"
              :src="selectedImage"
              :alt="product.name"
              class="figure-img img-fluid"
              loading="eager"
              decoding="async"
              fetchpriority="high"
              @load="mainImageLoaded = true"
              :style="mainImageLoaded ? { maxHeight: '100%', objectFit: 'contain' } : { maxHeight: '100%', objectFit: 'contain', filter: 'blur(8px)', transition: 'filter .3s ease' }"
            />
            <figcaption v-else class="figure-caption text-muted small mb-0">
              Imagine indisponibilă
            </figcaption>
          </div>
        </figure>
        <ul v-if="thumbnails.length" class="list-inline mb-0">
          <li
            v-for="(thumb, idx) in thumbnails"
            :key="idx"
            class="list-inline-item"
          >
            <button
              type="button"
              class="btn btn-light p-1 border"
              :class="{ 'border-primary': thumb === selectedImage }"
              @click="selectedImage = thumb"
              :aria-current="thumb === selectedImage ? 'true' : null"
              :aria-label="`Selectează miniatura ${idx + 1} pentru ${product.name}`"
              style="width: 64px; height: 64px;"
            >
              <img
                :src="thumb"
                :alt="`Miniatură ${idx + 1} pentru ${product.name}`"
                loading="lazy"
                decoding="async"
                width="64"
                height="64"
                @load="loadedThumbs[thumb] = true"
                :style="{ width: '100%', height: '100%', objectFit: 'cover', filter: loadedThumbs[thumb] ? 'none' : 'blur(6px)', transition: 'filter .3s ease' }"
              />
            </button>
          </li>
        </ul>
      </div>

      <div class="col-md-7">
        <div class="card border-0 shadow-sm sticky-summary">
          <div class="card-body">
            <h1 class="h4 mb-1 d-flex align-items-center gap-2 product-title">
              <span>{{ product.name }}</span>
              <span v-if="product.aggregate_rating?.ratingValue || product.average_rating" class="d-inline-flex align-items-center">
                <i v-for="n in 5" :key="'star-' + n" :class="starClass(n)" class="bi me-1"></i>
              </span>
            </h1>
            <div class="small text-muted mb-2">
              Cod: {{ product.internal_code || product.code }} • Brand: {{ product.brand?.name || product.brand || 'Generic' }}
            </div>
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="price-main">
                <span v-if="product.list_price && product.list_price > displayPrice" class="text-muted text-decoration-line-through me-2 h6">
                  {{ formatPrice(product.list_price) }}
                </span>
                <span class="price-value fw-bold mb-0" :class="{'text-danger': (product.list_price && product.list_price > displayPrice)}">
                  {{ formatPrice(displayPrice) }}
                </span>
              </div>
              <div class="text-end">
                <span v-if="product.has_discount || (product.list_price && product.list_price > displayPrice)" class="badge bg-danger rounded-pill me-2">
                    {{ product.has_discount ? 'Promoție' : 'Preț redus' }}
                </span>
                <span v-if="product.discountPercent" class="badge bg-success rounded-pill">-{{ product.discountPercent }}%</span>
                <span v-else-if="product.list_price && product.list_price > displayPrice" class="badge bg-success rounded-pill">
                    -{{ Math.round(((product.list_price - displayPrice) / product.list_price) * 100) }}%
                </span>
                <span v-else class="badge bg-secondary rounded-pill">Preț de listă</span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2 mb-3">
              <span class="badge rounded-pill" :class="isStockAvailable ? 'bg-success' : 'bg-secondary'">
                {{ isStockAvailable ? 'În stoc' : 'La comandă' }}
              </span>
              <span v-if="product.stock_qty !== null && isB2B" class="badge bg-light text-dark rounded-pill">
                Stoc B2B: {{ product.stock_qty }}
              </span>
            </div>
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
            <div class="d-grid gap-2 d-sm-flex align-items-center mb-4">
              <div style="width: 120px;">
                <input
                  type="number"
                  class="form-control"
                  v-model.number="quantity"
                  min="1"
                />
              </div>
              <button
                class="btn btn-primary btn-lg flex-grow-1"
                @click="addToCart"
                :disabled="addLoading || (!isStockAvailable && !product.can_backorder)"
              >
                <span v-if="addLoading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-cart-plus"></i>
                Adaugă în coș
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
            <div v-if="addMessage" class="alert alert-success py-2 px-3 small">
              {{ addMessage }}
            </div>
            <div v-if="addError" class="alert alert-danger py-2 px-3 small">
              {{ addError }}
            </div>
            <ul class="nav nav-pills nav-fill mb-3" id="productTab" role="tablist">
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
                      class="btn btn-sm btn-outline-primary"
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
                <div class="small mt-1" v-if="similar.price || similar.list_price">
                    <span v-if="similar.list_price && similar.list_price > (similar.promo_price || similar.price)" class="text-decoration-line-through text-muted me-1">
                        {{ formatPrice(similar.list_price) }}
                    </span>
                    <span :class="(similar.list_price > (similar.promo_price || similar.price)) ? 'text-danger fw-bold' : 'fw-bold'">
                        {{ formatPrice(similar.promo_price || similar.price) }}
                    </span>
                </div>
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
              class="d-flex justify-content-between align-items-center py-2 border-bottom"
            >
              <div>
                <div class="fw-semibold">{{ comp.name }}</div>
                <div class="text-muted small">{{ comp.internal_code || comp.code }}</div>
                <div class="small mt-1" v-if="comp.price || comp.list_price">
                    <span v-if="comp.list_price && comp.list_price > (comp.promo_price || comp.price)" class="text-decoration-line-through text-muted me-1">
                        {{ formatPrice(comp.list_price) }}
                    </span>
                    <span :class="(comp.list_price > (comp.promo_price || comp.price)) ? 'text-danger fw-bold' : 'fw-bold'">
                        {{ formatPrice(comp.promo_price || comp.price) }}
                    </span>
                </div>
              </div>
              <RouterLink
                :to="`/produs/${comp.slug}`"
                class="btn btn-outline-secondary btn-sm ms-2"
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
    <div class="row g-4 mt-2">
      <div class="col-12">
        <div class="card">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong class="small text-uppercase">Recenzii</strong>
            <div class="d-flex align-items-center gap-2">
              <div v-if="product.aggregate_rating || product.average_rating" class="review-stars">
                <i v-for="n in 5" :key="'avg-star-' + n" :class="starClass(n)" class="bi"></i>
              </div>
              <div class="small text-muted">
                <span v-if="product.aggregate_rating">{{ Number(product.aggregate_rating.ratingValue || 0).toFixed(1) }}/5</span>
                <span v-else-if="product.average_rating">{{ Number(product.average_rating || 0).toFixed(1) }}/5</span>
                <span>&nbsp;•&nbsp;</span>
                <span>{{ product.rating_count || product.reviews_count || 0 }} recenzii</span>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div v-if="product.reviews && product.reviews.length">
              <div v-for="(r, idx) in product.reviews" :key="idx" class="border-bottom py-2">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="fw-semibold">{{ r.author_name }}</div>
                  <div class="small text-muted">{{ r.created_at }}</div>
                </div>
                <div class="review-stars mb-1">
                  <i v-for="n in 5" :key="'rstar-' + idx + '-' + n" :class="reviewStarClass(r.rating, n)" class="bi me-1"></i>
                </div>
                <div v-if="r.title" class="fw-semibold small mb-1">{{ r.title }}</div>
                <div class="small">{{ r.body }}</div>
              </div>
            </div>
            <div v-else class="text-muted small">Nu există recenzii.</div>
          </div>
          <div class="card-footer">
            <form class="row g-2" @submit.prevent="submitReview">
              <div class="col-md-3">
                <input v-model="reviewAuthor" type="text" class="form-control form-control-sm" placeholder="Nume" />
              </div>
              <div class="col-md-2">
                <div class="star-picker d-flex align-items-center">
                  <button
                    v-for="n in 5"
                    :key="'pick-' + n"
                    type="button"
                    class="btn btn-link p-0 me-1"
                    :aria-label="`Alege ${n} stele`"
                    @click="reviewRating = n"
                  >
                    <i :class="n <= reviewRating ? 'bi-star-fill text-warning' : 'bi-star text-muted'"></i>
                  </button>
                </div>
              </div>
              <div class="col-md-3">
                <input v-model="reviewTitle" type="text" class="form-control form-control-sm" placeholder="Titlu (opțional)" />
              </div>
              <div class="col-md-4">
                <input v-model="reviewBody" type="text" class="form-control form-control-sm" placeholder="Recenzie" />
              </div>
              <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary btn-sm" :disabled="reviewSubmitting">
                  <span v-if="reviewSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                  Trimite recenzia
                </button>
              </div>
            </form>
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
        <RouterLink to="/" class="btn btn-outline-primary">
          Înapoi la prima pagină
        </RouterLink>
      </div>
  </div>

  <div v-else class="container py-4">
    <div class="alert alert-warning">
      Produsul nu a fost găsit.
    </div>
    <div class="mt-3">
        <RouterLink to="/" class="btn btn-outline-primary">
          Înapoi la prima pagină
        </RouterLink>
      </div>
  </div>
</template>

<script setup>
import { computed, ref, reactive, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { useProductsStore } from '@/store/products'
import { fetchProductBySlug, submitProductReview } from '@/services/catalog'
import { addCartItem } from '@/services/cart'
import axios from '@/services/http'
import { useToast } from 'vue-toastification'
import { setTitle, setMeta, setMetaProperty, setMetaPropertyList, setCanonical, setJsonLd } from '@/utils/seo'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const productsStore = useProductsStore()
const toast = useToast()

const loading = ref(false)
const error = ref(null)
const product = ref(null)
const quantity = ref(1)
const addLoading = ref(false)
const addMessage = ref('')
const addError = ref('')
const isSubscribed = ref(false)
const subscribeLoading = ref(false)

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
const selectedImage = ref('')
const thumbnails = ref([])
const mainImageLoaded = ref(false)
const loadedThumbs = reactive({})

const mapDemoProductToView = (demoProduct) => {
    return {
        id: demoProduct.id,
        name: demoProduct.name,
        slug: 'produs-demo-' + demoProduct.id,
        internal_code: demoProduct.internalCode,
        code: demoProduct.internalCode,
        brand: demoProduct.brand,
        categorySlug: '', // demoProducts doesn't have slug for category easily accessible, or we can guess
        category_name: demoProduct.mainCategory,
        image_url: null, // demo doesn't have images
        stock_status: demoProduct.stockStatus,
        stock_qty: demoProduct.stockQty,
        list_price: demoProduct.listPrice,
        price: demoProduct.listPrice,
        price_override: demoProduct.overridePrice,
        promo_price: null, // demo doesn't seem to have promoPrice in the object I saw, or maybe it does?
        has_discount: false,
        short_description: demoProduct.shortDescription,
        description: demoProduct.longDescription,
        attributes: Object.entries(demoProduct.attributes || {}).map(([key, value]) => ({ name: key, value })),
        variants: demoProduct.variants || [],
        documents: demoProduct.documents || [],
        similar_products: [],
        complementary_products: []
    }
}

const loadProduct = async () => {
  if (!slug.value) return
  
  loading.value = true
  error.value = null
  product.value = null
  addMessage.value = ''
  addError.value = ''
  
  // Check for demo product
  if (slug.value.toString().startsWith('produs-demo-')) {
     const id = slug.value.replace('produs-demo-', '')
     const demoProduct = productsStore.getById(id)
     if (demoProduct) {
        product.value = mapDemoProductToView(demoProduct)
        if (product.value.variants && product.value.variants.length > 0) {
            selectedVariant.value = product.value.variants[0].code
        }
        loading.value = false
        return
     }
  }

  try {
    const data = await fetchProductBySlug(slug.value)
    product.value = data.product ?? data
    // Select first variant if available
    if (product.value.variants && product.value.variants.length > 0) {
        selectedVariant.value = product.value.variants[0].code
    }
    checkSubscriptionStatus()
    applySeo()
    initImages()
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

const checkSubscriptionStatus = async () => {
  try {
    if (!authStore.user || !product.value) {
      isSubscribed.value = false
      return
    }
    let variantId = null
    if (selectedVariant.value && product.value.variants) {
      const v = product.value.variants.find(v => v.code === selectedVariant.value)
      if (v) variantId = v.id
    }
    const { data } = await axios.get('/api/products/stock-alert/status', {
      params: {
        product_id: product.value.id,
        product_variant_id: variantId
      }
    })
    isSubscribed.value = !!data?.subscribed
  } catch (e) {
    isSubscribed.value = false
  }
}

watch(slug, () => {
  loadProduct()
})

const applySeo = () => {
  if (!product.value) return
  const title = (product.value.meta_title || product.value.name || '') + ' | ' + (document?.querySelector('meta[name=\"application-name\"]')?.getAttribute('content') || '')
  const desc = product.value.meta_description || product.value.short_description || ''
  const url = window.location.href
  const imgCandidates = []
  if (product.value.image_url) imgCandidates.push(product.value.image_url)
  if (product.value.main_image_url) imgCandidates.push(product.value.main_image_url)
  if (Array.isArray(product.value.images)) {
    product.value.images.forEach(i => {
      if (i?.url) imgCandidates.push(i.url)
    })
  }
  const images = Array.from(new Set(imgCandidates.map(u => {
    if (!u) return null
    try {
      const abs = new URL(u, window.location.origin).href
      return abs
    } catch (e) {
      return null
    }
  }).filter(Boolean)))
  const brand = product.value.brand?.name || product.value.brand || ''
  const sku = product.value.sku || product.value.internal_code || ''
  const price = parseFloat(product.value.price_override || product.value.promo_price || product.value.price || 0)
  const availability = (product.value.stock_status === 'in_stock' || (product.value.stock_qty && product.value.stock_qty > 0)) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
  setTitle(title)
  setMeta('description', desc)
  setMetaProperty('og:type', 'product')
  setMetaProperty('og:title', title)
  setMetaProperty('og:description', desc)
  setMetaProperty('og:url', url)
  if (images.length) setMetaPropertyList('og:image', images)
  setCanonical(url)
  const breadcrumb = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    'itemListElement': [
      { '@type': 'ListItem', position: 1, name: 'Acasă', item: window.location.origin + '/' },
      { '@type': 'ListItem', position: 2, name: product.value.category?.name || product.value.category_name || 'Categorie produse', item: window.location.origin + (router.resolve({ name: 'category', params: { slug: product.value.categorySlug || product.value.category?.slug || '' } }).href || '/produse') },
      { '@type': 'ListItem', position: 3, name: product.value.name, item: url }
    ]
  }
  const productJson = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    'name': product.value.name,
    'image': images.length ? images : undefined,
    'description': desc,
    'sku': sku || undefined,
    'mpn': product.value.internal_code || undefined,
    ...(product.value.barcode
      ? (() => {
          const code = String(product.value.barcode)
          if (code.length === 8) return { gtin8: code }
          if (code.length === 12) return { gtin12: code }
          if (code.length === 13) return { gtin13: code }
          if (code.length === 14) return { gtin14: code }
          return {}
        })()
      : {}),
    'brand': brand ? { '@type': 'Brand', 'name': brand } : undefined,
    'offers': {
      '@type': 'Offer',
      'priceCurrency': 'RON',
      'price': price,
      'availability': availability,
      'url': url
    },
    ...(function () {
      const ratingValue = product.value.aggregate_rating?.ratingValue
        ?? product.value.average_rating
        ?? product.value.rating
      const ratingCount = product.value.aggregate_rating?.ratingCount
        ?? product.value.rating_count
        ?? product.value.reviews_count
      if (ratingValue && ratingCount) {
        return {
          aggregateRating: {
            '@type': 'AggregateRating',
            'ratingValue': Number(ratingValue),
            'ratingCount': Number(ratingCount),
            'bestRating': 5,
            'worstRating': 1
          }
        }
      }
      return {}
    })(),
    ...(Array.isArray(product.value.reviews) && product.value.reviews.length
      ? {
          review: product.value.reviews.map(r => ({
            '@type': 'Review',
            'author': r.author_name ? { '@type': 'Person', name: r.author_name } : undefined,
            'datePublished': r.created_at || r.date || undefined,
            'reviewBody': r.body || r.content || r.text || undefined,
            'name': r.title || undefined,
            'reviewRating': (r.rating || r.stars) ? {
              '@type': 'Rating',
              'ratingValue': Number(r.rating || r.stars),
              'bestRating': 5,
              'worstRating': 1
            } : undefined
          }))
        }
      : {})
  }
  setJsonLd({ '@graph': [breadcrumb, productJson] })
}
const initImages = () => {
  const imgs = []
  if (product.value?.image_url) imgs.push(product.value.image_url)
  if (product.value?.main_image_url) imgs.push(product.value.main_image_url)
  if (Array.isArray(product.value?.images)) {
    product.value.images.forEach(i => {
      if (i?.url) imgs.push(i.url)
    })
  }
  const unique = Array.from(new Set(imgs))
  thumbnails.value = unique
  selectedImage.value = unique[0] || ''
}
const starClass = (index) => {
  const rating = product.value?.aggregate_rating?.ratingValue ?? product.value?.average_rating ?? 0
  const full = Math.floor(rating)
  const hasHalf = rating - full >= 0.5
  if (index <= full) return 'bi-star-fill text-warning'
  if (index === full + 1 && hasHalf) return 'bi-star-half text-warning'
  return 'bi-star text-muted'
}
const reviewStarClass = (rating, index) => {
  const full = Math.floor(Number(rating) || 0)
  if (index <= full) return 'bi-star-fill text-warning'
  return 'bi-star text-muted'
}

const categoryTitle = computed(() => {
  if (!product.value) return 'Categorie produse'
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
  
  const promo = product.value.promoPrice || product.value.promo_price
  const hasDisc = product.value.hasDiscount || product.value.has_discount
  
  if (hasDisc && promo) {
    return parseFloat(promo)
  }
  return parseFloat(product.value.price || product.value.list_price || 0)
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

const addToCart = async () => {
  if (!product.value) return;

  // Demo alert for demo products
  if (slug.value.toString().startsWith('produs-demo-')) {
      window.alert(`Demo: produsul "${product.value.name}" (cantitate: ${quantity.value}) ar fi adăugat în coș.`)
      return
  }

  addLoading.value = true;
  addMessage.value = '';
  addError.value = '';

  try {
    const payload = {
      product_id: product.value.id,
      quantity: Number(quantity.value) || 1,
    };

    const cartData = await addCartItem(payload);
    addMessage.value = 'Produsul a fost adăugat în coș.';
    // Refresh header cart count if needed
  } catch (e) {
    console.error('Add to cart error', e);
    if (e.response?.data?.message) {
      addError.value = e.response.data.message;
    } else if (e.response?.status === 422) {
      addError.value = 'Date invalide pentru adăugarea în coș.';
    } else {
      addError.value = 'Nu s-a putut adăuga produsul în coș.';
    }
  } finally {
    addLoading.value = false;
  }
};

const subscribeToStock = async () => {
    if (!authStore.user) {
        toast.info("Trebuie să fii autentificat pentru a primi notificări.");
        router.push('/login');
        return;
    }
    
    subscribeLoading.value = true;
    try {
        let variantId = null;
        if (selectedVariant.value && product.value.variants) {
             const v = product.value.variants.find(v => v.code === selectedVariant.value);
             if (v) variantId = v.id;
        }

        await axios.post('/api/products/stock-alert', {
            product_id: product.value.id,
            product_variant_id: variantId
        });
        isSubscribed.value = true;
        toast.success("Te-ai abonat cu succes la alerta de stoc!");
    } catch (e) {
        toast.error(e.response?.data?.message || "A apărut o eroare la abonare.");
    } finally {
        subscribeLoading.value = false;
    }
}

const reviewAuthor = ref('')
const reviewRating = ref(5)
const reviewTitle = ref('')
const reviewBody = ref('')
const reviewSubmitting = ref(false)

const submitReview = async () => {
  if (!product.value) return
  reviewSubmitting.value = true
  try {
    await submitProductReview(product.value.id, {
      author_name: reviewAuthor.value.trim(),
      rating: Number(reviewRating.value),
      title: reviewTitle.value.trim() || null,
      body: reviewBody.value.trim(),
    })
    reviewAuthor.value = ''
    reviewRating.value = 5
    reviewTitle.value = ''
    reviewBody.value = ''
    const data = await fetchProductBySlug(product.value.slug)
    product.value = data.product ?? data
    applySeo()
    toast.success('Recenzia a fost publicată')
  } catch (e) {
    toast.error('Nu s-a putut publica recenzia')
  } finally {
    reviewSubmitting.value = false
  }
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
<style scoped>
.price-main .h4 {
  line-height: 1.1;
}
.product-title {
  letter-spacing: 0.2px;
}
.price-value {
  font-size: clamp(1.6rem, 2vw + 1rem, 2.2rem);
}
.list-inline-item .btn {
  border-radius: 8px;
}
.list-inline-item .btn:hover {
  box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.08);
}
.nav-pills .nav-link {
  border-radius: 999px;
}
.card.border-0.shadow-sm {
  border-radius: 12px;
}
.sticky-summary {
  position: sticky;
  top: 16px;
  z-index: 1;
}
.review-stars .bi {
  font-size: 1rem;
}
.star-picker .bi {
  font-size: 1.25rem;
}
.star-picker .btn-link {
  text-decoration: none;
}
.star-picker .btn-link:hover .bi {
  transform: scale(1.1);
  transition: transform .1s ease;
}
</style>
