<template>
  <div class="container-fluid">
    <PageHeader
      :title="isEdit ? 'Promoție - editare' : 'Promoție - creare'"
      subtitle="Definire model promoție: reguli, segmentare și landing page."
    >
      <div class="btn-group btn-group-sm">
        <RouterLink :to="{ name: 'admin-promotions' }" class="btn btn-outline-secondary">
          Înapoi la listă
        </RouterLink>
        <button type="button" class="btn btn-primary" @click="onSubmit">
          Salvează promoția
        </button>
      </div>
    </PageHeader>

    <form @submit.prevent="onSubmit">
      <div class="row g-3">
        <!-- Col stânga: detalii și segmentare -->
        <div class="col-lg-7">
          <!-- Detalii generale -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <strong>Detalii generale</strong>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label small text-muted">Denumire promoție</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="form-control form-control-sm"
                  required
                />
              </div>
              <div class="mb-3">
                <label class="form-label small text-muted">Slug (pentru URL landing)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">/promotii/</span>
                  <input
                    v-model="form.slug"
                    type="text"
                    class="form-control"
                    placeholder="ex: promotie-gips-carton-10"
                  />
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label small text-muted">Descriere scurtă</label>
                <textarea
                  v-model="form.shortDescription"
                  class="form-control form-control-sm"
                  rows="2"
                />
              </div>
              <div class="mb-3">
                <label class="form-label small text-muted">Descriere detaliată (landing)</label>
                <textarea
                  v-model="form.longDescription"
                  class="form-control form-control-sm"
                  rows="4"
                />
              </div>

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label small text-muted">Data start</label>
                  <input
                    v-model="form.startDate"
                    type="date"
                    class="form-control form-control-sm"
                  />
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label small text-muted">Data stop</label>
                  <input
                    v-model="form.endDate"
                    type="date"
                    class="form-control form-control-sm"
                  />
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label small text-muted">Status</label>
                  <select v-model="form.status" class="form-select form-select-sm">
                    <option value="active">Activă</option>
                    <option value="upcoming">În curând</option>
                    <option value="inactive">Inactivă</option>
                  </select>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label small text-muted">Tip promoție</label>
                  <select v-model="form.type" class="form-select form-select-sm">
                    <option value="discount_percent">Discount procentual</option>
                    <option value="discount_fixed">Discount valoric</option>
                    <option value="x_get_y">Cumperi X → primești Y</option>
                    <option value="bundle">Pachet (bundle)</option>
                  </select>
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label small text-muted">Tip pachet</label>
                  <select v-model="form.packageType" class="form-select form-select-sm">
                    <option value="exclusive">Exclusivă</option>
                    <option value="iterative">Iterativă</option>
                  </select>
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label small text-muted">Tip bonus</label>
                  <select v-model="form.bonusType" class="form-select form-select-sm">
                    <option value="valoare">Valoare / discount</option>
                    <option value="gratuitate">Gratuitate</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Segmentare -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <strong>Segmentare</strong>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <label class="form-label small text-muted">Tip client</label>
                <div class="d-flex gap-3 small">
                  <div class="form-check">
                    <input
                      id="clientB2B"
                      v-model="clientTypes.b2b"
                      class="form-check-input"
                      type="checkbox"
                    />
                    <label class="form-check-label" for="clientB2B">B2B</label>
                  </div>
                  <div class="form-check">
                    <input
                      id="clientB2C"
                      v-model="clientTypes.b2c"
                      class="form-check-input"
                      type="checkbox"
                    />
                    <label class="form-check-label" for="clientB2C">B2C</label>
                  </div>
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label small text-muted">Utilizator</label>
                <select v-model="form.loggedIn" class="form-select form-select-sm">
                  <option value="any">Logați și nelogați</option>
                  <option value="logged">Doar utilizatori logați</option>
                  <option value="guest">Doar vizitatori nelogați</option>
                </select>
              </div>

              <div class="mb-2">
                <label class="form-label small text-muted">Grupuri de clienți (virgule)</label>
                <input
                  v-model="customerGroupsInput"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Ex.: Distribuitori, Clienți VIP"
                />
              </div>

              <div class="mb-2">
                <label class="form-label small text-muted">Clienți individuali (ID-uri / coduri, virgule)</label>
                <input
                  v-model="customersInput"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Ex.: CUST-001, CUST-002"
                />
              </div>

              <div class="mb-2">
                <label class="form-label small text-muted">Categorii produse (virgule)</label>
                <input
                  v-model="categoriesInput"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Ex.: Plăci gips-carton, Profile metalice"
                />
              </div>

              <div class="mb-2">
                <label class="form-label small text-muted">Branduri (virgule)</label>
                <input
                  v-model="brandsInput"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Ex.: DemoBrand, SteelBrand"
                />
              </div>

              <div class="mb-0">
                <label class="form-label small text-muted">Listă produse (coduri, virgule)</label>
                <input
                  v-model="productListInput"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Ex.: PGC-12.5, UW-50"
                />
              </div>
            </div>
          </div>

          <!-- Reguli -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <strong>Reguli promoție</strong>
            </div>
            <div class="card-body">
              <h6 class="small text-muted">Condiții de declanșare</h6>
              <div class="row">
                <div class="col-md-6 mb-2">
                  <label class="form-label small text-muted">
                    Cantitate minimă per produs
                  </label>
                  <input
                    v-model.number="form.trigger.minQtyPerProduct"
                    type="number"
                    class="form-control form-control-sm"
                  />
                </div>
                <div class="col-md-6 mb-2">
                  <label class="form-label small text-muted">
                    Valoare minimă coș (RON)
                  </label>
                  <input
                    v-model.number="form.trigger.minCartValue"
                    type="number"
                    class="form-control form-control-sm"
                  />
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label small text-muted">
                  Note / combinații de produse/categorii
                </label>
                <textarea
                  v-model="form.trigger.notes"
                  class="form-control form-control-sm"
                  rows="2"
                  placeholder="Ex.: minim 2 produse din categoria X și 1 din categoria Y."
                />
              </div>

              <h6 class="small text-muted">Beneficiu</h6>
              <div class="row">
                <div class="col-md-4 mb-2">
                  <label class="form-label small text-muted">Discount %</label>
                  <input
                    v-model.number="form.benefit.discountPercent"
                    type="number"
                    class="form-control form-control-sm"
                  />
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label small text-muted">Discount fix (RON)</label>
                  <input
                    v-model.number="form.benefit.discountValue"
                    type="number"
                    class="form-control form-control-sm"
                  />
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label small text-muted">Produs gratuit (cod)</label>
                  <input
                    v-model="form.benefit.freeProductCode"
                    type="text"
                    class="form-control form-control-sm"
                  />
                </div>
              </div>

              <div class="mb-0">
                <label class="form-label small text-muted">Produs cu preț special (cod)</label>
                <input
                  v-model="form.benefit.specialPriceCode"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Col dreapta: imagini & landing -->
        <div class="col-lg-5">
          <!-- Imagini campanie -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <strong>Imagini campanie</strong>
            </div>
            <div class="card-body">
              <p class="small text-muted">
                Placeholder pentru upload bannere; în demo folosim doar URL-uri text.
              </p>
              <div class="mb-2">
                <label class="form-label small text-muted">Imagine listă</label>
                <input
                  v-model="form.images.list"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="mb-2">
                <label class="form-label small text-muted">Imagine header desktop</label>
                <input
                  v-model="form.images.header"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="mb-0">
                <label class="form-label small text-muted">Imagine header mobile</label>
                <input
                  v-model="form.images.mobile"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>
            </div>
          </div>

          <!-- Landing page -->
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <strong>Landing page</strong>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <label class="form-label small text-muted">Titlu hero</label>
                <input
                  v-model="form.landingTitle"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Ex.: Promoție gips-carton -10%"
                />
              </div>
              <div class="mb-2">
                <label class="form-label small text-muted">Subtitlu hero</label>
                <input
                  v-model="form.landingSubtitle"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Ex.: Reduceri dedicate proiectelor tale"
                />
              </div>
              <div class="form-check small mb-0">
                <input
                  id="landingShowFilters"
                  v-model="form.landingShowFilters"
                  class="form-check-input"
                  type="checkbox"
                />
                <label class="form-check-label" for="landingShowFilters">
                  Afișează filtre în listarea de produse din landing
                </label>
              </div>
            </div>
          </div>

          <div class="d-grid mb-5">
            <button type="submit" class="btn btn-primary btn-sm">
              Salvează promoția
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
import { usePromotionsStore } from '@/store/promotions'

const route = useRoute()
const router = useRouter()
const store = usePromotionsStore()

const isEdit = computed(() => !!route.params.id)

const emptyForm = () => ({
  id: null,
  name: '',
  slug: '',
  shortDescription: '',
  longDescription: '',
  type: 'discount_percent',
  images: {
    list: '',
    header: '',
    mobile: ''
  },
  startDate: '',
  endDate: '',
  status: 'active',
  packageType: 'iterative',
  bonusType: 'valoare',
  clientTypes: ['B2B', 'B2C'],
  loggedIn: 'any',
  customerGroups: [],
  customers: [],
  categories: [],
  brands: [],
  productList: [],
  trigger: {
    minQtyPerProduct: null,
    minCartValue: null,
    notes: ''
  },
  benefit: {
    discountPercent: null,
    discountValue: null,
    freeProductCode: '',
    specialPriceCode: ''
  },
  landingTitle: '',
  landingSubtitle: '',
  landingShowFilters: true
})

const form = reactive(emptyForm())

const clientTypes = reactive({
  b2b: true,
  b2c: true
})

const customerGroupsInput = ref('')
const customersInput = ref('')
const categoriesInput = ref('')
const brandsInput = ref('')
const productListInput = ref('')

onMounted(() => {
  if (isEdit.value) {
    const promo = store.all.find((p) => p.id === Number(route.params.id))
    if (promo) {
      Object.assign(form, emptyForm(), JSON.parse(JSON.stringify(promo)))

      clientTypes.b2b = form.clientTypes.includes('B2B')
      clientTypes.b2c = form.clientTypes.includes('B2C')

      customerGroupsInput.value = (form.customerGroups || []).join(', ')
      customersInput.value = (form.customers || []).join(', ')
      categoriesInput.value = (form.categories || []).join(', ')
      brandsInput.value = (form.brands || []).join(', ')
      productListInput.value = (form.productList || []).join(', ')
    }
  }
})

const normalizeArraysBeforeSave = () => {
  const ct = []
  if (clientTypes.b2b) ct.push('B2B')
  if (clientTypes.b2c) ct.push('B2C')
  form.clientTypes = ct.length ? ct : ['B2B', 'B2C']

  const parseList = (input) =>
    input
      .split(',')
      .map((v) => v.trim())
      .filter(Boolean)

  form.customerGroups = customerGroupsInput.value ? parseList(customerGroupsInput.value) : []
  form.customers = customersInput.value ? parseList(customersInput.value) : []
  form.categories = categoriesInput.value ? parseList(categoriesInput.value) : []
  form.brands = brandsInput.value ? parseList(brandsInput.value) : []
  form.productList = productListInput.value ? parseList(productListInput.value) : []
}

const onSubmit = () => {
  normalizeArraysBeforeSave()
  store.savePromotion({ ...form })
  router.push({ name: 'admin-promotions' })
}
</script>
