<template>
  <div class="container py-4">
    <PageHeader
      :title="product ? product.name : 'Se încarcă...'"
      :subtitle="product ? (product.code ? 'Cod: ' + product.code : '') : ''"
    >
      <template #breadcrumbs>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <RouterLink :to="{ name: 'home' }">Acasă</RouterLink>
            </li>
            <li class="breadcrumb-item" v-if="product && product.category">
              <RouterLink :to="{ name: 'category', params: { slug: product.category_slug || 'all' } }">
                {{ product.category }}
              </RouterLink>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              {{ product ? product.name : '...' }}
            </li>
          </ol>
        </nav>
      </template>
    </PageHeader>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-else-if="!product" class="alert alert-warning">
      Produsul nu a fost găsit.
    </div>

    <div v-else class="row g-4">
      <!-- Col stânga: Imagini -->
      <div class="col-md-5">
        <div class="border rounded bg-white p-3 mb-2 text-center position-relative">
          <div class="ratio ratio-4x3 d-flex align-items-center justify-content-center bg-white">
            <img
              v-if="product.main_image_url"
              :src="product.main_image_url"
              class="img-fluid"
              alt="Imagine produs"
              style="max-height: 100%; object-fit: contain;"
            />
            <span v-else class="text-muted small">
              Imagine indisponibilă
            </span>
          </div>
          <!-- Badges -->
          <div class="position-absolute top-0 start-0 p-2 d-flex flex-column gap-1">
             <span v-if="product.flags.is_new" class="badge bg-info text-white">NOU</span>
             <span v-if="product.flags.is_promo" class="badge bg-danger">PROMO</span>
             <span v-if="product.flags.is_recommended" class="badge bg-warning text-dark">TOP</span>
          </div>
        </div>
        <!-- Thumbnails (placeholder logic if needed) -->
        <!-- 
        <div class="d-flex gap-2 overflow-auto">
          <div v-for="img in product.images" :key="img.id" class="border rounded p-1" style="width: 60px; height: 60px; cursor: pointer;">
             <img :src="img.url" class="img-fluid h-100 w-100 object-fit-contain" />
          </div>
        </div>
        -->
      </div>

      <!-- Col dreapta: Detalii și Acțiuni -->
      <div class="col-md-7">
        <h1 class="h3 mb-2">{{ product.name }}</h1>
        
        <div class="d-flex flex-wrap gap-3 small text-muted mb-3">
          <div v-if="product.code">
            Cod: <span class="text-dark fw-semibold">{{ product.code }}</span>
          </div>
          <div v-if="product.brand">
            Brand: <span class="text-dark fw-semibold">{{ product.brand }}</span>
          </div>
          <div>
            Stoc: 
            <span :class="{
              'text-success fw-semibold': product.stock_status === 'in_stock',
              'text-warning fw-semibold': product.stock_status === 'low_stock' || product.stock_status === 'supplier',
              'text-danger fw-semibold': product.stock_status === 'out_of_stock'
            }">
              {{ stockStatusLabel(product.stock_status) }}
            </span>
            <span v-if="isB2B && product.stock_qty > 0" class="ms-1">({{ product.stock_qty }})</span>
          </div>
        </div>

        <div class="mb-4 p-3 bg-light rounded border">
          <div class="d-flex align-items-baseline mb-1">
            <span class="h4 mb-0 fw-bold text-primary">
              {{ formatMoney(currentPrice) }}
            </span>
            <span class="text-muted ms-1 small"> / {{ selectedUnitName }}</span>
          </div>
          <div v-if="product.hasDiscount" class="small text-muted">
            Preț listă: <span class="text-decoration-line-through">{{ formatMoney(currentListPrice) }}</span>
            <span class="text-danger ms-2">-{{ product.discountPercent }}%</span>
          </div>
          <div v-if="product.vat_included" class="small text-muted mt-1">
            Prețul include TVA.
          </div>
          <div v-else class="small text-muted mt-1">
            Prețul nu include TVA ({{ product.vat_rate * 100 }}%).
          </div>
        </div>

        <!-- Selector Variante (dacă există) -->
        <div v-if="hasVariants" class="mb-4">
          <label class="form-label fw-semibold small">Variante disponibile:</label>
          
          <!-- Cazul 1: Avem atribute structurate care variază -->
          <div v-if="variationAxes.length > 0">
             <div v-for="axis in variationAxes" :key="axis.name" class="mb-2">
                <span class="small text-muted d-block mb-1">{{ axis.name }}:</span>
                <div class="d-flex flex-wrap gap-2">
                   <button
                      v-for="val in axis.values"
                      :key="val"
                      class="btn btn-sm"
                      :class="isAttributeSelected(axis.name, val) ? 'btn-dark text-white' : 'btn-outline-secondary'"
                      @click="selectAttribute(axis.name, val)"
                      :disabled="!isCombinationAvailable(axis.name, val)"
                   >
                      {{ val }}
                   </button>
                </div>
             </div>
          </div>

          <!-- Cazul 2: Fallback la listă simplă cu nume scurtate -->
          <div v-else class="d-flex flex-wrap gap-2">
            <button
              v-for="variant in product.variants"
              :key="variant.id"
              class="btn btn-sm"
              :class="variant.id === product.variant_id ? 'btn-dark text-white' : 'btn-outline-secondary'"
              @click="goToVariant(variant.slug)"
            >
              {{ getShortVariantName(variant) }}
            </button>
          </div>
        </div>

        <!-- Selector Unitate de Măsură -->
        <div class="mb-4" v-if="product.units && product.units.length > 0">
          <label class="form-label fw-semibold small">Unitate de măsură:</label>
          <div class="row g-2">
            <div class="col-sm-6">
              <select class="form-select form-select-sm" v-model="selectedUnitId">
                <option v-for="u in product.units" :key="u.id" :value="u.id">
                  {{ u.name }} ({{ u.conversion_factor }} x {{ u.unit }})
                </option>
              </select>
            </div>
            <div class="col-sm-6 d-flex align-items-center">
              <span class="small text-muted">
                Preț unitar calculat: <strong>{{ formatMoney(currentPrice) }}</strong>
              </span>
            </div>
          </div>
        </div>

        <!-- Adaugă în coș -->
        <div class="d-flex gap-2 align-items-stretch mb-4" style="max-width: 400px;">
          <input
            type="number"
            min="1"
            class="form-control text-center"
            style="width: 80px;"
            v-model.number="qty"
          />
          <button
            class="btn btn-primary flex-grow-1 d-flex align-items-center justify-content-center gap-2"
            @click="addToCart"
            :disabled="addingToCart || product.stock_status === 'out_of_stock'"
          >
            <span v-if="addingToCart" class="spinner-border spinner-border-sm"></span>
            <i class="bi bi-cart-plus"></i> Adaugă în coș
          </button>
        </div>

        <!-- Informații suplimentare / Tabs -->
        <ul class="nav nav-tabs mb-3" id="productTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc-pane" type="button" role="tab">Descriere</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="attr-tab" data-bs-toggle="tab" data-bs-target="#attr-pane" type="button" role="tab">Specificații</button>
          </li>
          <li class="nav-item" role="presentation" v-if="product.documents && product.documents.length">
            <button class="nav-link" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs-pane" type="button" role="tab">Documente</button>
          </li>
        </ul>
        <div class="tab-content" id="productTabsContent">
          <div class="tab-pane fade show active" id="desc-pane" role="tabpanel">
            <div v-html="product.description || 'Nicio descriere disponibilă.'" class="small text-muted"></div>
          </div>
          <div class="tab-pane fade" id="attr-pane" role="tabpanel">
            <table class="table table-sm table-striped small">
              <tbody>
                <tr v-for="(attr, idx) in allAttributes" :key="idx">
                  <th style="width: 40%;">{{ attr.name }}</th>
                  <td>{{ attr.value }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="tab-pane fade" id="docs-pane" role="tabpanel">
            <div class="list-group">
              <a
                v-for="doc in product.documents"
                :key="doc.id"
                href="#"
                class="list-group-item list-group-item-action small d-flex justify-content-between align-items-center"
                @click.prevent="downloadDoc(doc)"
              >
                <div>
                  <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>
                  {{ doc.label }}
                </div>
                <span class="badge bg-light text-dark border">{{ doc.restriction || 'Public' }}</span>
              </a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '@/store/cart'
import { useAuthStore } from '@/store/auth'
import { fetchProductBySlug } from '@/services/catalog'
import PageHeader from '@/components/common/PageHeader.vue'
import { useToast } from 'vue-toastification'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()
const toast = useToast()

const product = ref(null)
const loading = ref(false)
const error = ref(null)
const addingToCart = ref(false)

const qty = ref(1)
const selectedUnitId = ref('base') // 'base' sau ID-ul unității

const isB2B = computed(() => {
  return authStore.user?.role === 'b2b' || authStore.impersonatedCustomer
})

// Încărcare produs
const loadProduct = async () => {
  const slug = route.params.slug
  // Dacă nu avem slug în rută, poate fi o problemă de rutare sau e prima încărcare goală.
  // Totuși, fetchProductBySlug are nevoie de slug.
  if (!slug) {
      console.warn('loadProduct: No slug param found in route');
      return
  }

  loading.value = true
  error.value = null
  product.value = null

  try {
    const data = await fetchProductBySlug(slug)
    product.value = data
    
    // Selectare unitate default
    if (data.units && data.units.length > 0) {
      const defaultUnit = data.units.find(u => u.is_default) || data.units[0]
      selectedUnitId.value = defaultUnit.id
    } else {
        selectedUnitId.value = 'base'
    }
    
    qty.value = 1
  } catch (e) {
    console.error(e)
    error.value = 'Nu am putut încărca detaliile produsului.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadProduct()
})

watch(() => route.params.slug, () => {
  loadProduct()
})

// Helpers
const currentUnit = computed(() => {
  if (!product.value || !product.value.units) return null
  return product.value.units.find(u => u.id === selectedUnitId.value)
})

const selectedUnitName = computed(() => {
    return currentUnit.value ? currentUnit.value.name : (product.value?.unit_of_measure || 'buc')
})

const currentPrice = computed(() => {
  if (!currentUnit.value) return product.value?.price || 0
  // Dacă unitatea are preț calculat (din backend PromotionPricingService)
  if (currentUnit.value.price !== undefined) {
      // Dacă există discount global, aplicăm logic, dar backend-ul ar trebui să trimită prețul per unitate deja redus sau nu.
      // `units` din backend conține `price` (calculat cu factor) și `promo_price` (calculat cu factor).
      // Folosim promo_price dacă e mai mic.
      return currentUnit.value.promo_price || currentUnit.value.price
  }
  return product.value?.price || 0
})

const currentListPrice = computed(() => {
   if (!currentUnit.value) return product.value?.list_price || 0
   return currentUnit.value.price // Price-ul din unitate este prețul de listă scalat, promo_price e cel redus
})

const hasVariants = computed(() => {
  return product.value && product.value.variants && product.value.variants.length > 0
})

const allAttributes = computed(() => {
    if (!product.value) return []
    
    let attrs = []

    // 1. Atributele produsului părinte (dacă există)
    if (product.value.attributes && Array.isArray(product.value.attributes)) {
        attrs = [...product.value.attributes]
    }

    // 2. Atributele specifice variantei (suprascriu sau se adaugă)
    if (product.value.variant_id && product.value.selected_attributes && Array.isArray(product.value.selected_attributes)) {
        product.value.selected_attributes.forEach(vAttr => {
            const index = attrs.findIndex(a => a.name === vAttr.name)
            if (index !== -1) {
                // Suprascriem valoarea dacă atributul există deja (ex: Culoare: Generic -> Culoare: Roșu)
                attrs[index] = vAttr 
            } else {
                // Adăugăm atribut nou specific variantei
                attrs.push(vAttr)
            }
        })
    }
    
    return attrs
})

const stockStatusLabel = (status) => {
  const map = {
    'in_stock': 'În stoc',
    'low_stock': 'Stoc limitat',
    'out_of_stock': 'Stoc epuizat',
    'supplier': 'La furnizor'
  }
  return map[status] || status
}

const formatMoney = (val) => {
  return new Intl.NumberFormat('ro-RO', {
    style: 'currency',
    currency: 'RON'
  }).format(val)
}

// Actions
const goToVariant = (slug) => {
  if (!slug) {
      console.warn('goToVariant called with empty slug');
      return;
  }
  if (slug === route.params.slug) return
  router.push({ name: 'product-details', params: { slug } })
}

const addToCart = async () => {
  if (!product.value) return
  
  addingToCart.value = true
  try {
    const unitName = currentUnit.value ? currentUnit.value.name : null
    
    const success = await cartStore.addItem(
      product.value.id,
      qty.value,
      product.value.variant_id, // Poate fi null dacă e produs simplu
      unitName // Trimitem numele unității (ex: 'Bax 10 buc' sau 'buc')
    )
    
    if (success) {
      toast.success(`Adăugat în coș: ${product.value.name} (${qty.value} ${selectedUnitName.value})`)
    } else {
      toast.error('Eroare la adăugarea în coș')
    }
  } catch (e) {
    console.error(e)
    toast.error('Eroare necunoscută')
  } finally {
    addingToCart.value = false
  }
}

const downloadDoc = (doc) => {
  toast.info(`Descarcare document: ${doc.label}`)
}

// ----------------------------------------------------------------------
// LOGICĂ AVANSATĂ PENTRU VARIANTE
// ----------------------------------------------------------------------

// 1. Identificare Axe de Variație
const variationAxes = computed(() => {
    if (!product.value || !product.value.variants || product.value.variants.length === 0) return []
    
    // Colectăm toate atributele din toate variantele
    // Structură temporară: { "Culoare": Set("Alb", "Negru"), "Mărime": Set("S", "M") }
    const axesMap = {}
    
    product.value.variants.forEach(v => {
        if (v.attributes && Array.isArray(v.attributes)) {
            v.attributes.forEach(attr => {
                // Backend trimite structura: { attribute: { name: '...' }, value: '...' }
                // Sau poate fi direct { name: '...', value: '...' } depinde de transformare.
                // Verificăm ce avem. În CatalogController: 'variants.attributes.attribute'.
                // Deci v.attributes e un array de AttributeValue models.
                // Modelul AttributeValue are relația 'attribute'.
                
                const attrName = attr.attribute ? attr.attribute.name : (attr.name || 'N/A')
                const attrVal = attr.value
                
                if (!axesMap[attrName]) {
                    axesMap[attrName] = new Set()
                }
                axesMap[attrName].add(attrVal)
            })
        }
    })
    
    // Convertim în array și filtrăm doar cele cu > 1 opțiune
    const result = []
    Object.keys(axesMap).forEach(key => {
        if (axesMap[key].size > 1) {
            result.push({
                name: key,
                values: Array.from(axesMap[key]).sort() // Sortăm alfabetic valorile
            })
        }
    })
    
    return result
})

// 2. Determină atributele selectate curent
const currentVariantAttributes = computed(() => {
    // Dacă suntem pe o variantă, returnăm atributele ei sub formă de map { Nume: Valoare }
    // Dacă suntem pe produsul părinte (și nu e variantă), returnăm map gol sau default?
    // Produsul părinte nu are atribute specifice de variantă selectate.
    
    const map = {}
    if (product.value.variant_id && product.value.selected_attributes) {
        product.value.selected_attributes.forEach(a => {
            map[a.name] = a.value
        })
    }
    return map
})

// Helper pentru UI: este atributul selectat?
const isAttributeSelected = (axisName, value) => {
    return currentVariantAttributes.value[axisName] === value
}

// Helper pentru UI: este combinația posibilă?
// Verificăm dacă există MĂCAR O variantă care are (Atributul X = Valoarea Y) 
// ȘI (celelalte atribute selectate curent, ignorând axa curentă).
const isCombinationAvailable = (axisName, value) => {
    // Pentru simplificare, verificăm doar dacă există vreo variantă cu această valoare.
    // O validare completă (cross-check) ar fi mai complexă.
    // Deocamdată permitem click și rezolvăm în selectAttribute "cea mai bună potrivire".
    return true 
}

// 3. Acțiune selecție atribut
const selectAttribute = (axisName, value) => {
    // Construim ținta de atribute
    const targetAttrs = { ...currentVariantAttributes.value, [axisName]: value }
    
    // Căutăm cea mai bună potrivire între variante
    let bestMatch = null
    let maxMatches = -1
    
    product.value.variants.forEach(v => {
        // Construim map-ul atributele acestei variante
        const vAttrs = {}
        if (v.attributes) {
            v.attributes.forEach(a => {
                const n = a.attribute ? a.attribute.name : a.name
                vAttrs[n] = a.value
            })
        }
        
        // Numărăm câte atribute se potrivesc cu targetAttrs
        // (Verificăm TOATE axele de variație cunoscute)
        let matches = 0
        let isValidCandidate = true // Trebuie să aibă obligatoriu valoarea nouă selectată pentru axa curentă
        
        // Verificăm condiția obligatorie (axa pe care am dat click)
        if (vAttrs[axisName] !== value) {
            isValidCandidate = false
        }
        
        if (isValidCandidate) {
            // Calculăm scorul de potrivire cu restul selecției
            variationAxes.value.forEach(axis => {
                if (axis.name !== axisName) {
                    // Dacă varianta are aceeași valoare ca selecția curentă, +1 punct
                    if (vAttrs[axis.name] === targetAttrs[axis.name]) {
                        matches++
                    }
                }
            })
            
            if (matches > maxMatches) {
                maxMatches = matches
                bestMatch = v
            }
        }
    })
    
    if (bestMatch && bestMatch.slug) {
        goToVariant(bestMatch.slug)
    } else {
        toast.warning('Această combinație nu este disponibilă.')
    }
}

// 4. Fallback: Scurtare inteligentă a numelui
const getShortVariantName = (variant) => {
    // Găsim prefixul comun al tuturor variantelor
    if (!product.value.variants || product.value.variants.length < 2) return variant.name
    
    const names = product.value.variants.map(v => v.name)
    const commonPrefix = findCommonPrefix(names)
    
    let shortName = variant.name
    if (commonPrefix.length > 3) { // Măcar 3 caractere să tăiem
        shortName = variant.name.substring(commonPrefix.length)
    }
    
    // Curățăm caractere de separare rămase la început (ex: ", " sau " - ")
    shortName = shortName.replace(/^[\s,\-]+/, '')
    
    // Capitalize prima literă
    return shortName.charAt(0).toUpperCase() + shortName.slice(1)
}

const findCommonPrefix = (strings) => {
    if (!strings.length) return ''
    let prefix = strings[0]
    for (let i = 1; i < strings.length; i++) {
        while (strings[i].indexOf(prefix) !== 0) {
            prefix = prefix.substring(0, prefix.length - 1)
            if (!prefix) return ''
        }
    }
    return prefix
}</script>
