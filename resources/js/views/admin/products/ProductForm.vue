<template>
  <div class="container-fluid">
    <PageHeader
      :title="isEdit ? 'Produs - editare' : 'Produs - creare'"
      subtitle="Formular complet pentru configurarea produselor, prețurilor, stocurilor și variantelor."
    >
      <div class="btn-group btn-group-sm">
        <RouterLink :to="{ name: 'admin-products' }" class="btn btn-outline-secondary">
          Închide
        </RouterLink>
        <button type="button" class="btn btn-primary" @click="onSubmit">
          Salvează
        </button>
      </div>
    </PageHeader>

    <form @submit.prevent="onSubmit">
      <div class="row g-3">
        <!-- Informații generale -->
        <div class="col-lg-7">
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <strong>Informații generale</strong>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label small text-muted">Denumire produs</label>
                <input v-model="form.name" type="text" class="form-control form-control-sm" required />
              </div>

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label small text-muted">Cod produs intern</label>
                  <input v-model="form.internalCode" type="text" class="form-control form-control-sm" />
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label small text-muted">Cod de bare (barcode)</label>
                  <input v-model="form.barcode" type="text" class="form-control form-control-sm" />
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label small text-muted">ERP ID</label>
                  <input v-model="form.erpId" type="text" class="form-control form-control-sm" />
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label small text-muted">Categorie principală</label>
                  <input
                    v-model="form.mainCategory"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Ex.: Plăci gips-carton"
                  />
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label small text-muted">Categorii secundare (virgule)</label>
                  <input
                    v-model="categoriesSecondaryInput"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Ex.: Pardoseli, Izolații"
                  />
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label small text-muted">Marcă (Brand)</label>
                  <input v-model="form.brand" type="text" class="form-control form-control-sm" />
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label small text-muted">Status</label>
                  <select v-model="form.isPublished" class="form-select form-select-sm">
                    <option :value="true">Publicat</option>
                    <option :value="false">Ascuns</option>
                  </select>
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label small text-muted">Ordine sortare</label>
                  <input
                    v-model.number="form.sortOrder"
                    type="number"
                    class="form-control form-control-sm"
                  />
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small text-muted">Descriere scurtă</label>
                <textarea
                  v-model="form.shortDescription"
                  class="form-control form-control-sm"
                  rows="2"
                />
              </div>

              <div class="mb-0">
                <label class="form-label small text-muted">Descriere lungă (HTML / rich text placeholder)</label>
                <textarea
                  v-model="form.longDescription"
                  class="form-control form-control-sm"
                  rows="4"
                  placeholder="<p>Descriere detaliată...</p>"
                />
              </div>
            </div>
          </div>

          <!-- Atribute & filtre -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
              <strong>Atribute & filtre</strong>
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                @click="addAttributeRow"
              >
                + Atribut
              </button>
            </div>
            <div class="card-body">
              <p class="small text-muted">
                Set de atribute la nivel de categorie (ex.: material, grosime, dimensiune, culoare).
              </p>

              <div
                v-for="(attr, index) in attributesArray"
                :key="index"
                class="row g-2 align-items-center mb-2"
              >
                <div class="col-md-4">
                  <input
                    v-model="attr.key"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Nume atribut (ex: material)"
                  />
                </div>
                <div class="col-md-6">
                  <input
                    v-model="attr.value"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Valoare (ex: gips-carton)"
                  />
                </div>
                <div class="col-md-2 text-end">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    @click="removeAttributeRow(index)"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Variante de produs -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
              <strong>Variante produs</strong>
              <div class="form-check form-check-inline small">
                <input
                  id="simpleProduct"
                  class="form-check-input"
                  type="radio"
                  value="simple"
                  v-model="form.variationType"
                />
                <label class="form-check-label" for="simpleProduct">Produs simplu</label>
              </div>
              <div class="form-check form-check-inline small">
                <input
                  id="variantProduct"
                  class="form-check-input"
                  type="radio"
                  value="with_variants"
                  v-model="form.variationType"
                />
                <label class="form-check-label" for="variantProduct">Produs cu variante</label>
              </div>
            </div>
            <div class="card-body" v-if="form.variationType === 'with_variants'">
              <p class="small text-muted">
                Definește atribute de variație (ex.: culoare, dimensiune) și lista de variante.
              </p>

              <div class="mb-3">
                <label class="form-label small text-muted">Atribute variație (virgule)</label>
                <input
                  v-model="variationAttributesInput"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Ex.: culoare, dimensiune"
                />
              </div>

              <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="small">Variante</strong>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  @click="addVariantRow"
                >
                  + Variantă
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light small">
                    <tr>
                      <th>Denumire</th>
                      <th>Cod</th>
                      <th>Barcode</th>
                      <th>ERP ID</th>
                      <th>Preț</th>
                      <th>Stoc</th>
                      <th>Slug URL</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="form.variants.length === 0">
                      <td colspan="8" class="text-center text-muted small">
                        Nu există variante definite.
                      </td>
                    </tr>
                    <tr v-for="(variant, index) in form.variants" :key="index">
                      <td>
                        <input
                          v-model="variant.name"
                          type="text"
                          class="form-control form-control-sm"
                          placeholder="Ex.: Culoare roșu / mărime L"
                        />
                      </td>
                      <td>
                        <input
                          v-model="variant.code"
                          type="text"
                          class="form-control form-control-sm"
                        />
                      </td>
                      <td>
                        <input
                          v-model="variant.barcode"
                          type="text"
                          class="form-control form-control-sm"
                        />
                      </td>
                      <td>
                        <input
                          v-model="variant.erpId"
                          type="text"
                          class="form-control form-control-sm"
                        />
                      </td>
                      <td style="width: 90px;">
                        <input
                          v-model.number="variant.price"
                          type="number"
                          step="0.01"
                          class="form-control form-control-sm"
                        />
                      </td>
                      <td style="width: 80px;">
                        <input
                          v-model.number="variant.stockQty"
                          type="number"
                          class="form-control form-control-sm"
                        />
                      </td>
                      <td>
                        <input
                          v-model="variant.slug"
                          type="text"
                          class="form-control form-control-sm"
                        />
                      </td>
                      <td class="text-end">
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-danger"
                          @click="removeVariantRow(index)"
                        >
                          ×
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Col dreapta: prețuri, stoc, imagini, documente, produse asociate -->
        <div class="col-lg-5">
          <!-- Prețuri -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <strong>Prețuri</strong>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <label class="form-label small text-muted">Preț de listă (din ERP)</label>
                <div class="input-group input-group-sm">
                  <input
                    v-model.number="form.listPrice"
                    type="number"
                    step="0.01"
                    class="form-control"
                  />
                  <span class="input-group-text">RON</span>
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label small text-muted">PRP (preț recomandat producător)</label>
                <div class="input-group input-group-sm">
                  <input
                    v-model.number="form.prp"
                    type="number"
                    step="0.01"
                    class="form-control"
                  />
                  <span class="input-group-text">RON</span>
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label small text-muted">TVA</label>
                <div class="input-group input-group-sm">
                  <input
                    v-model.number="form.vat"
                    type="number"
                    step="0.01"
                    class="form-control"
                  />
                  <span class="input-group-text">%</span>
                </div>
              </div>
              <div class="mb-0">
                <label class="form-label small text-muted">Override preț (opțional)</label>
                <div class="input-group input-group-sm">
                  <input
                    v-model.number="form.overridePrice"
                    type="number"
                    step="0.01"
                    class="form-control"
                    placeholder="Lasă gol pentru preț ERP"
                  />
                  <span class="input-group-text">RON</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Stoc -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <strong>Stoc</strong>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <label class="form-label small text-muted">Status stoc</label>
                <select v-model="form.stockStatus" class="form-select form-select-sm">
                  <option value="in_stock">În stoc</option>
                  <option value="on_order">La comandă</option>
                  <option value="low_stock">Stoc limitat</option>
                  <option value="out_of_stock">Epuizat</option>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label small text-muted">Cantitate stoc</label>
                <input
                  v-model.number="form.stockQty"
                  type="number"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="mb-2">
                <label class="form-label small text-muted">Stoc furnizor (informație ERP)</label>
                <input
                  v-model.number="form.supplierStockQty"
                  type="number"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="mb-0">
                <label class="form-label small text-muted">Termen estimativ livrare (zile)</label>
                <input
                  v-model.number="form.leadTimeDays"
                  type="number"
                  class="form-control form-control-sm"
                />
              </div>
            </div>
          </div>

          <!-- Imagini -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <strong>Imagini</strong>
            </div>
            <div class="card-body">
              <p class="small text-muted">
                Placeholder pentru upload galerie imagini (drag&drop). În demo folosim doar URL-uri text.
              </p>
              <div
                v-for="(img, index) in form.images"
                :key="index"
                class="input-group input-group-sm mb-2"
              >
                <span class="input-group-text">
                  <input
                    class="form-check-input mt-0"
                    type="radio"
                    name="mainImage"
                    :checked="index === form.mainImageIndex"
                    @change="form.mainImageIndex = index"
                  />
                </span>
                <input
                  v-model="img.url"
                  type="text"
                  class="form-control"
                  placeholder="URL imagine"
                />
                <button
                  type="button"
                  class="btn btn-outline-danger"
                  @click="removeImage(index)"
                >
                  ×
                </button>
              </div>
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                @click="addImage"
              >
                + Adaugă imagine
              </button>
            </div>
          </div>

          <!-- Documente -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
              <strong>Documente</strong>
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                @click="addDocument"
              >
                + Document
              </button>
            </div>
            <div class="card-body">
              <p class="small text-muted">
                Fișiere (PDF, DOC) precum fișe tehnice, certificate, manuale. În demo folosim URL-uri.
              </p>
              <div
                v-for="(doc, index) in form.documents"
                :key="index"
                class="row g-2 align-items-center mb-2"
              >
                <div class="col-4">
                  <select v-model="doc.type" class="form-select form-select-sm">
                    <option value="fisa-tehnica">Fișă tehnică</option>
                    <option value="certificat">Certificat</option>
                    <option value="manual">Manual</option>
                  </select>
                </div>
                <div class="col-5">
                  <input
                    v-model="doc.url"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="URL document"
                  />
                </div>
                <div class="col-2">
                  <select v-model="doc.access" class="form-select form-select-sm">
                    <option value="public">Public</option>
                    <option value="purchased">Clienți care au cumpărat</option>
                    <option value="request">Pe bază de cerere</option>
                  </select>
                </div>
                <div class="col-1 text-end">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    @click="removeDocument(index)"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Produse asociate -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <strong>Produse asociate</strong>
            </div>
            <div class="card-body">
              <p class="small text-muted">
                Selectează manual produse similare și complementare (cross-sell / upsell).
                În demo folosim câmpuri text pentru coduri produse.
              </p>
              <div class="mb-2">
                <label class="form-label small text-muted">Produse similare (coduri, virgule)</label>
                <input
                  v-model="relatedInput"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Ex.: PGC-12.5, UW-50"
                />
              </div>
              <div class="mb-0">
                <label class="form-label small text-muted">
                  Produse complementare (coduri, virgule)
                </label>
                <input
                  v-model="complementaryInput"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Ex.: PROF-ACC, ADEZIV-XYZ"
                />
              </div>
            </div>
          </div>

          <!-- Meta / ERP -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <strong>Meta & ERP</strong>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <label class="form-label small text-muted">Status sincronizare ERP</label>
                <select v-model="form.erpSyncStatus" class="form-select form-select-sm">
                  <option value="synced">Sincronizat</option>
                  <option value="pending">În așteptare</option>
                  <option value="error">Eroare</option>
                </select>
              </div>
              <div class="form-check small">
                <input
                  id="promoted"
                  v-model="form.isPromoted"
                  class="form-check-input"
                  type="checkbox"
                />
                <label class="form-check-label" for="promoted">
                  Produs promovat / în promoție
                </label>
              </div>
            </div>
          </div>

          <div class="d-grid mb-5">
            <button type="submit" class="btn btn-primary btn-sm">
              Salvează produs
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { useProductsStore } from '@/store/products'

const route = useRoute()
const router = useRouter()
const productsStore = useProductsStore()

const isEdit = computed(() => !!route.params.id)

const emptyForm = () => ({
  id: null,
  name: '',
  internalCode: '',
  barcode: '',
  erpId: '',
  mainCategory: '',
  categories: [],
  brand: '',
  isPublished: true,
  sortOrder: 0,
  shortDescription: '',
  longDescription: '',
  listPrice: 0,
  prp: 0,
  vat: 19,
  overridePrice: null,
  stockStatus: 'in_stock',
  stockQty: 0,
  supplierStockQty: 0,
  leadTimeDays: null,
  attributes: {},
  variationType: 'simple',
  variationAttributes: [],
  variants: [],
  images: [],
  mainImageIndex: 0,
  documents: [],
  relatedProducts: [],
  complementaryProducts: [],
  erpSyncStatus: 'synced',
  isPromoted: false
})

const form = reactive(emptyForm())

const categoriesSecondaryInput = ref('')
const variationAttributesInput = ref('')
const relatedInput = ref('')
const complementaryInput = ref('')

const attributesArray = ref([])

onMounted(() => {
  if (isEdit.value) {
    const product = productsStore.getById(route.params.id)
    if (product) {
      Object.assign(form, emptyForm(), JSON.parse(JSON.stringify(product)))
      categoriesSecondaryInput.value = (form.categories || [])
        .filter(c => c !== form.mainCategory)
        .join(', ')
      variationAttributesInput.value = (form.variationAttributes || []).join(', ')
      relatedInput.value = (form.relatedProducts || []).join(', ')
      complementaryInput.value = (form.complementaryProducts || []).join(', ')
      attributesArray.value = Object.entries(form.attributes || {}).map(
        ([key, value]) => ({ key, value })
      )
    }
  } else {
    attributesArray.value = []
  }
})

const normalizeArraysBeforeSave = () => {
  form.categories = []
  if (form.mainCategory) {
    form.categories.push(form.mainCategory)
  }
  if (categoriesSecondaryInput.value) {
    form.categories.push(
      ...categoriesSecondaryInput.value
        .split(',')
        .map(c => c.trim())
        .filter(Boolean)
    )
  }

  form.variationAttributes = variationAttributesInput.value
    ? variationAttributesInput.value
        .split(',')
        .map(v => v.trim())
        .filter(Boolean)
    : []

  form.relatedProducts = relatedInput.value
    ? relatedInput.value
        .split(',')
        .map(v => v.trim())
        .filter(Boolean)
    : []

  form.complementaryProducts = complementaryInput.value
    ? complementaryInput.value
        .split(',')
        .map(v => v.trim())
        .filter(Boolean)
    : []

  const attrs = {}
  attributesArray.value
    .filter(a => a.key && a.value)
    .forEach(a => {
      attrs[a.key] = a.value
    })
  form.attributes = attrs
}

const onSubmit = () => {
  normalizeArraysBeforeSave()
  productsStore.saveProduct({ ...form })
  router.push({ name: 'admin-products' })
}

const addAttributeRow = () => {
  attributesArray.value.push({ key: '', value: '' })
}
const removeAttributeRow = (index) => {
  attributesArray.value.splice(index, 1)
}

const addVariantRow = () => {
  form.variants.push({
    name: '',
    code: '',
    barcode: '',
    erpId: '',
    price: form.listPrice,
    stockQty: 0,
    slug: ''
  })
}
const removeVariantRow = (index) => {
  form.variants.splice(index, 1)
}

const addImage = () => {
  form.images.push({ url: '' })
  if (form.images.length === 1) {
    form.mainImageIndex = 0
  }
}
const removeImage = (index) => {
  form.images.splice(index, 1)
  if (form.mainImageIndex >= form.images.length) {
    form.mainImageIndex = Math.max(0, form.images.length - 1)
  }
}

const addDocument = () => {
  form.documents.push({
    type: 'fisa-tehnica',
    url: '',
    access: 'public'
  })
}
const removeDocument = (index) => {
  form.documents.splice(index, 1)
}
</script>
