<template>
  <div class="container">
    <PageHeader
      :title="product ? product.name : 'Detaliu produs'"
      subtitle="Pagină produs cu prețuri, stocuri, documente și opțiuni B2B precum &bdquo;Solicită ofertă&rdquo;."
    >
      <template #breadcrumbs>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <RouterLink :to="{ name: 'home' }">Acasă</RouterLink>
            </li>
            <li class="breadcrumb-item">
              <RouterLink
                v-if="product"
                :to="{ name: 'category', params: { slug: product.mainCategory || 'categorie-demo' } }"
              >
                {{ product.mainCategory || 'Categorie demo' }}
              </RouterLink>
              <span v-else>
                Categorie
              </span>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              {{ product ? product.name : 'Produs demo' }}
            </li>
          </ol>
        </nav>
      </template>
    </PageHeader>

    <div v-if="!product" class="alert alert-warning">
      Produsul nu a putut fi încărcat în template. În implementarea reală se face un apel către
      backend/ERP folosind <code>slug</code>-ul din URL.
    </div>

    <div v-else class="row g-3">
      <!-- Col stânga: imagine + info -->
      <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <div class="ratio ratio-4x3 border rounded mb-2 d-flex align-items-center justify-content-center bg-light">
              <span class="text-muted small">
                Galerie imagini produs<br />
                (înlocuiește cu un carusel real)
              </span>
            </div>
            <p class="small text-muted mb-1">
              Cod intern: <strong>{{ product.internalCode }}</strong><br />
              Brand: <strong>{{ product.brand || 'Nespecificat' }}</strong><br />
              ERP ID: <strong>{{ product.erpId }}</strong><br />
              Barcode: <strong>{{ product.barcode }}</strong>
            </p>
          </div>
        </div>

        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Documente</strong>
          </div>
          <div class="card-body small">
            <div v-if="!product.documents || !product.documents.length" class="text-muted">
              Nu sunt definite documente pentru acest produs în demo.
            </div>
            <ul v-else class="list-unstyled mb-0">
              <li
                v-for="doc in product.documents"
                :key="doc.id"
                class="d-flex align-items-center mb-1"
              >
                <i class="bi bi-file-earmark-text me-2"></i>
                <span class="me-1">{{ doc.name }}</span>
                <span class="badge bg-light text-dark border small">{{ doc.type }}</span>
                <span
                  v-if="doc.restriction === 'purchased'"
                  class="badge bg-secondary ms-1"
                >
                  Doar clienți care au cumpărat
                </span>
                <span
                  v-else-if="doc.restriction === 'request'"
                  class="badge bg-warning text-dark ms-1"
                >
                  Doar pe bază de cerere
                </span>
              </li>
            </ul>
            <p class="small text-muted mt-2 mb-0">
              Restricțiile de acces (public / doar clienți / pe bază de cerere) se vor implementa
              în backend și se vor reflecta aici.
            </p>
          </div>
        </div>
      </div>

      <!-- Col dreapta: preț, stoc, acțiuni B2C/B2B -->
      <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <h4 class="mb-1">{{ product.name }}</h4>
            <p class="text-muted mb-2">
              {{ product.shortDescription }}
            </p>

            <div class="d-flex align-items-center mb-2">
              <div class="me-3">
                <div class="fs-4 fw-bold">
                  {{ formatMoney(effectivePrice) }}
                </div>
                <div class="small text-muted">
                  <span v-if="product.overridePrice">
                    Preț promoțional
                    <span class="text-decoration-line-through">
                      {{ formatMoney(product.listPrice) }}
                    </span>
                  </span>
                  <span v-else>
                    Preț de listă
                  </span>
                  <span v-if="product.prp" class="ms-1">
                    (PRP: {{ formatMoney(product.prp) }})
                  </span>
                </div>
              </div>
              <div>
                <span
                  v-if="product.stockStatus === 'in_stock'"
                  class="badge bg-success"
                >
                  În stoc ({{ product.stockQty }} buc)
                </span>
                <span
                  v-else-if="product.stockStatus === 'low_stock'"
                  class="badge bg-warning text-dark"
                >
                  Stoc limitat ({{ product.stockQty }} buc)
                </span>
                <span
                  v-else-if="product.stockStatus === 'out_of_stock'"
                  class="badge bg-danger"
                >
                  Stoc epuizat
                </span>
                <span
                  v-else
                  class="badge bg-secondary"
                >
                  La comandă
                </span>
              </div>
            </div>

            <div class="row g-2 align-items-end mb-3">
              <div class="col-4 col-sm-3">
                <label class="form-label text-muted small">Cantitate</label>
                <input
                  v-model.number="qty"
                  type="number"
                  min="1"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-8 col-sm-9 d-flex flex-wrap gap-2">
                <button
                  type="button"
                  class="btn btn-primary btn-sm"
                  @click="addToCart"
                >
                  Adaugă în coș
                </button>
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  @click="addToFavorites"
                >
                  Adaugă la favorite
                </button>
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  @click="toggleCompare"
                >
                  {{ isInCompare ? 'Scapă din comparare' : 'Adaugă la comparare' }}
                </button>
                <button
                  type="button"
                  class="btn btn-outline-primary btn-sm"
                  @click="showOfferForm = !showOfferForm"
                >
                  Solicită ofertă
                </button>
              </div>
            </div>

            <p class="small text-muted mb-0">
              În proiectul real, prețurile afișate aici vor ține cont de tipul de client (B2B/B2C),
              grupuri de clienți, promoții, discounturi contractuale și limite de credit.
            </p>
          </div>
        </div>

        <!-- Atribute & unități de măsură -->
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Atribute & filtrare</strong>
          </div>
          <div class="card-body small">
            <div v-if="!product.attributes" class="text-muted">
              Nu sunt definite atribute pentru acest produs în demo.
            </div>
            <div v-else class="table-responsive">
              <table class="table table-sm mb-0">
                <tbody>
                  <tr
                    v-for="(value, key) in product.attributes"
                    :key="key"
                  >
                    <th style="width: 30%;">{{ key }}</th>
                    <td>{{ value }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="small text-muted mt-2 mb-0">
              Atributele pot fi folosite ca filtre în listările de categorie și pentru compararea
              produselor.
            </p>
          </div>
        </div>

        <!-- Formular Solicită ofertă -->
        <div
          v-if="showOfferForm"
          class="card shadow-sm mb-3 border-primary"
        >
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Solicită ofertă pentru acest produs</strong>
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="showOfferForm = false"
            >
              Închide
            </button>
          </div>
          <div class="card-body small">
            <div class="alert alert-info py-2 small">
              Template: acest formular generează o cerere de ofertă în sistem. În implementarea
              reală, cererea ajunge la agent / director pentru analiză, iar răspunsul se va vedea
              în contul de client la secțiunea &bdquo;Oferte&rdquo;.
            </div>

            <form @submit.prevent="submitOfferRequest">
              <div class="row g-2 mb-2">
                <div class="col-md-6">
                  <label class="form-label text-muted">Cantitate estimată</label>
                  <input
                    v-model.number="offerForm.requestedQty"
                    type="number"
                    min="1"
                    class="form-control form-control-sm"
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label text-muted">Unitate de măsură</label>
                  <select
                    v-model="offerForm.unit"
                    class="form-select form-select-sm"
                  >
                    <option value="buc">buc</option>
                    <option value="cutie">cutie</option>
                    <option value="palet">palet</option>
                  </select>
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label text-muted">Detalii proiect / cerințe</label>
                <textarea
                  v-model="offerForm.notes"
                  class="form-control form-control-sm"
                  rows="3"
                  placeholder="Descrie pe scurt proiectul și așteptările de la ofertă..."
                ></textarea>
              </div>
              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary btn-sm">
                  Trimite cererea de ofertă
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Descriere lungă -->
        <div class="card shadow-sm">
          <div class="card-header py-2">
            <strong>Descriere produs</strong>
          </div>
          <div class="card-body small">
            <div v-if="product.longDescription" v-html="product.longDescription"></div>
            <p v-else class="text-muted mb-0">
              Nu este definită o descriere detaliată pentru acest produs în demo.
            </p>
          </div>
        </div>
      </div>
    </div>

    <p v-if="infoMessage" class="small text-muted mt-3 mb-0">
      {{ infoMessage }}
    </p>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { useProductsStore } from '@/store/products'
import { useProductNavigationStore } from '@/store/productNavigation'
import { useCartStore } from '@/store/cart'
import { useOffersStore } from '@/store/offers'
import { useAuthStore } from '@/store/auth'

const route = useRoute()
const productsStore = useProductsStore()
const cartStore = useCartStore()
const productNavigationStore = useProductNavigationStore()
const offersStore = useOffersStore()
const authStore = useAuthStore()

// În demo, nu avem mapare reală slug -> produs. Alegem primul produs ca exemplu.
// În implementarea reală, vei face fetch după slug sau vei avea câmp slug în productsStore.
const product = computed(() => {
  if (productsStore.all.length === 0) return null
  return productsStore.all[0]
})

watch(
  product,
  (val) => {
    if (val && val.id) {
      productNavigationStore.addRecentlyViewed(val.id)
    }
  },
  { immediate: true }
)

const isInCompare = computed(() => {
  if (!product.value) return false
  return productNavigationStore.compareIds.includes(product.value.id)
})

const toggleCompare = () => {
  if (!product.value) return
  productNavigationStore.toggleCompare(product.value.id)
}

const qty = ref(1)
const showOfferForm = ref(false)
const infoMessage = ref('')

const offerForm = reactive({
  requestedQty: 50,
  unit: 'buc',
  notes: ''
})

const effectivePrice = computed(() => {
  if (!product.value) return 0
  return product.value.overridePrice || product.value.listPrice || 0
})

const formatMoney = (value) => {
  const v = Number(value || 0)
  return v.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' RON'
}

const addToCart = () => {
  if (!product.value) return
  const q = Number(qty.value || 0)
  if (q <= 0) return
  cartStore.addItem(product.value.id, q)
  infoMessage.value =
    'Template: produsul a fost adăugat în coș. În implementarea reală se vor recalcula promoțiile, taxele și costurile de transport.'
}

const addToFavorites = () => {
  infoMessage.value =
    'Template: produsul a fost adăugat la favorite. În implementarea reală se va salva preferința în contul utilizatorului.'
}

const submitOfferRequest = () => {
  if (!product.value) return
  const qtyVal = Number(offerForm.requestedQty || qty.value || 1)

  const payload = {
    customerName: authStore.user?.name || '',
    customerEmail: authStore.user?.email || '',
    customerType: authStore.role === 'b2b' ? 'B2B' : 'B2C',
    notesFromCustomer: offerForm.notes || '',
    totalList: qtyVal * effectivePrice.value,
    lines: [
      {
        id: 1,
        productId: product.value.id,
        productName: product.value.name,
        productCode: product.value.internalCode,
        requestedQty: qtyVal,
        unit: offerForm.unit,
        listPrice: effectivePrice.value,
        proposedPrice: null
      }
    ]
  }

  const offer = offersStore.createOfferRequest(payload)
  infoMessage.value =
    'Template: cererea ta de ofertă a fost înregistrată (' +
    offer.code +
    '). Vei putea urmări răspunsul în contul de client, la secțiunea „Oferte”.'

  // reset minimal
  showOfferForm.value = false
  offerForm.notes = ''
}
</script>
