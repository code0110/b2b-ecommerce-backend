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
            <span class="badge bg-primary me-1">B2B</span>
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
            class="btn btn-primary"
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

        <!-- Mesaje după adăugare -->
        <div v-if="addMessage" class="alert alert-success py-2 px-3 small">
          {{ addMessage }}
        </div>
        <div v-if="addError" class="alert alert-danger py-2 px-3 small">
          {{ addError }}
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
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { useProductsStore } from '@/store/products'
import { fetchProductBySlug } from '@/services/catalog'
import { addCartItem } from '@/services/cart'
import axios from '@/services/http'
import { useToast } from 'vue-toastification'
import { setTitle, setMeta, setMetaProperty, setCanonical, setJsonLd } from '@/utils/seo'

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
  const url = window.location.origin + (router.resolve({ name: 'product-details', params: { slug: product.value.slug } }).href || window.location.pathname)
  const image = product.value.image_url || product.value.main_image_url || ''
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
  if (image) setMetaProperty('og:image', image)
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
    'image': image ? [image] : undefined,
    'description': desc,
    'sku': sku || undefined,
    'brand': brand ? { '@type': 'Brand', 'name': brand } : undefined,
    'offers': {
      '@type': 'Offer',
      'priceCurrency': 'RON',
      'price': price,
      'availability': availability,
      'url': url
    }
  }
  setJsonLd({ '@graph': [breadcrumb, productJson] })
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
