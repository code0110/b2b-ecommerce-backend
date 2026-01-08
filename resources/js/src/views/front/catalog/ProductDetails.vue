<template>
  <div class="container py-4" v-if="product">
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
            <span class="text-muted small">
              Imagine produs (demo)
            </span>
          </div>
        </div>
        <div class="d-flex gap-2 small">
          <button
            v-for="n in 3"
            :key="n"
            type="button"
            class="btn btn-outline-secondary btn-sm flex-fill"
          >
            Imagine {{ n }}
          </button>
        </div>
      </div>

      <div class="col-md-7">
        <h1 class="h4 mb-1">{{ product.name }}</h1>
        <div class="small text-muted mb-2">
          Cod produs: {{ product.code }} • Brand: {{ product.brand }}
        </div>

        <div class="mb-2">
          <span
            class="badge"
            :class="product.inStock ? 'bg-success' : 'bg-secondary'"
          >
            {{ product.inStock ? 'În stoc' : 'La comandă' }}
          </span>
          <span
            v-if="product.stockB2B !== null"
            class="badge bg-light text-dark ms-2"
          >
            Stoc B2B: {{ product.stockB2B }}
          </span>
        </div>

        <div class="mb-3">
          <div class="h5 mb-1">
            <span v-if="product.hasDiscount" class="text-muted text-decoration-line-through h6 me-2">
              {{ product.price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
            </span>
            <span class="fw-semibold">
              {{ displayPrice.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
            </span>
          </div>
          <div class="small text-muted">
            <span v-if="product.hasDiscount">
              Preț promoțional (demo); în producție promoția ar fi calculată pe baza regulilor din admin.
            </span>
            <span v-else>
              Preț de listă (demo).
            </span>
          </div>
          <div class="small mt-1" v-if="isB2B">
            <span class="badge bg-primary me-1">B2B</span>
            Prețuri și condiții comerciale B2B (termen plată, limită credit) se aplică în cont sau la impersonare.
          </div>
        </div>

        <!-- Variante + unități de măsură -->
        <div class="row g-2 mb-3 small">
          <div class="col-md-6">
            <label class="form-label">Variantă (culoare)</label>
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
                v-for="unit in unitsOfMeasure"
                :key="unit.value"
                :value="unit.value"
              >
                {{ unit.label }}
              </option>
            </select>
            <div class="form-text">
              Conversie demo între buc / sac / palet.
            </div>
          </div>
        </div>

        <!-- Acțiuni -->
        <div class="d-flex flex-wrap gap-2 mb-3">
          <button type="button" class="btn btn-primary btn-sm" @click="addToCartDemo">
            Adaugă în coș
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="addToFavoritesDemo">
            Adaugă la favorite
          </button>
          <button type="button" class="btn btn-outline-primary btn-sm" @click="requestOfferDemo">
            Solicită ofertă
          </button>
        </div>

        <!-- Atribute -->
        <div class="card small mb-3">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Atribute produs</strong>
          </div>
          <div class="card-body p-2">
            <table class="table table-sm mb-0">
              <tbody>
                <tr v-for="attr in product.attributes" :key="attr.name">
                  <th style="width: 40%;">{{ attr.name }}</th>
                  <td>{{ attr.value }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Documente -->
        <div class="card small">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Documente atașate</strong>
          </div>
          <div class="card-body p-2">
            <ul class="list-unstyled mb-0">
              <li
                v-for="doc in product.documents"
                :key="doc.id"
                class="d-flex justify-content-between align-items-center py-1 border-bottom small"
              >
                <div>
                  <span class="me-2">
                    <span v-if="doc.type === 'pdf'">📄</span>
                    <span v-else-if="doc.type === 'doc'">📝</span>
                    <span v-else>📁</span>
                  </span>
                  <span class="fw-semibold">{{ doc.label }}</span>
                  <span class="text-muted ms-1">
                    ({{ doc.description }})
                  </span>
                  <span
                    v-if="doc.restriction !== 'public'"
                    class="badge bg-light text-dark ms-1"
                  >
                    {{ doc.restrictionLabel }}
                  </span>
                </div>
                <button
                  type="button"
                  class="btn btn-link btn-sm text-decoration-none"
                  @click="openDocumentDemo(doc)"
                >
                  Descarcă
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Produse similare & complementare -->
    <div class="row g-3 mt-4">
      <div class="col-md-6">
        <div class="card">
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
                <div class="text-muted">{{ similar.code }}</div>
              </div>
              <RouterLink
                :to="`/produs/${similar.slug}`"
                class="btn btn-outline-secondary btn-sm"
              >
                Detalii
              </RouterLink>
            </div>
            <div v-if="similarProducts.length === 0" class="text-muted">
              Nu sunt definite produse similare în acest demo.
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
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
                <div class="text-muted">{{ comp.code }}</div>
              </div>
              <RouterLink
                :to="`/produs/${comp.slug}`"
                class="btn btn-outline-secondary btn-sm"
              >
                Detalii
              </RouterLink>
            </div>
            <div v-if="complementaryProducts.length === 0" class="text-muted">
              Nu sunt definite produse complementare în acest demo.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="container py-4">
    <div class="alert alert-warning">
      Produsul nu a fost găsit în setul de date demo.
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const route = useRoute()
const authStore = useAuthStore()

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

const allProducts = [
  {
    slug: 'ciment-portland-40kg',
    name: 'Ciment Portland 40kg',
    code: 'PRD-001',
    brand: 'BrandX',
    categorySlug: 'materiale-constructii',
    inStock: true,
    stockB2B: 124,
    price: 45.0,
    hasDiscount: true,
    promoPrice: 40.5,
    attributes: [
      { name: 'Tip', value: 'Ciment Portland' },
      { name: 'Rezistență', value: '40kg / sac' },
      { name: 'Utilizare', value: 'Structuri beton, șape, zidărie' }
    ],
    variants: [
      { code: 'PRD-001-GRI', label: 'Gri' },
      { code: 'PRD-001-ALB', label: 'Alb' }
    ],
    documents: [
      {
        id: 1,
        type: 'pdf',
        label: 'Fișă tehnică',
        description: 'Proprietăți și instrucțiuni de utilizare',
        restriction: 'public',
        restrictionLabel: 'Public'
      },
      {
        id: 2,
        type: 'pdf',
        label: 'Declarație de conformitate',
        description: 'Document conformitate CE',
        restriction: 'purchased',
        restrictionLabel: 'Doar clienți care au cumpărat'
      }
    ]
  }
]

const product = computed(() => allProducts.find((p) => p.slug === slug.value) || null)

const categoryTitle = computed(() => {
  if (!product.value) return 'Categorie produse'
  if (product.value.categorySlug === 'materiale-constructii') return 'Materiale de construcții'
  return 'Categorie produse'
})

const selectedVariant = ref(product.value?.variants[0]?.code || null)

const unitsOfMeasure = [
  { value: 'buc', label: 'bucată' },
  { value: 'sac', label: 'sac (40kg)' },
  { value: 'palet', label: 'palet (48 saci)' }
]
const selectedUnit = ref('sac')

const displayPrice = computed(() => {
  if (!product.value) return 0
  return product.value.price || product.value.list_price || 0
})

const hasDiscount = computed(() => {
  if (!product.value) return false
  const list = product.value.list_price || 0
  const final = product.value.price || list
  return list > final
})

const similarProducts = computed(() => {
  if (!product.value) return []
  return allProducts.filter((p) => p.slug !== product.value.slug)
})

const complementaryProducts = computed(() => {
  if (!product.value) return []
  return [
    {
      slug: 'adeziv-gresie-faianta',
      name: 'Adeziv gresie / faianță',
      code: 'PRD-005'
    }
  ]
})

const addToCartDemo = () => {
  if (!product.value) return
  window.alert(
    `Demo: produsul "${product.value.name}" (varianta ${selectedVariant.value || '-'}) ` +
      `ar fi adăugat în coș în unitatea de măsură "${selectedUnit.value}".`
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
  if (doc.restriction === 'public') {
    window.alert(`Demo: s-ar descărca documentul "${doc.label}".`)
  } else if (doc.restriction === 'purchased') {
    window.alert(
      `Demo: documentul "${doc.label}" este disponibil doar pentru clienții care au cumpărat produsul.`
    )
  } else {
    window.alert(`Demo: restricție necunoscută pentru documentul "${doc.label}".`)
  }
}
</script>
