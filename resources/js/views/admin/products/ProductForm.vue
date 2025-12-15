<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">
          {{ isEdit ? 'Editare produs' : 'Produs nou' }}
        </h1>
        <p class="text-muted mb-0">
          Administrare detalii produs, prețuri, stoc, imagini, variante și produse asociate.
        </p>
      </div>
      <div class="d-flex gap-2">
        <RouterLink
          :to="{ name: 'admin-products' }"
          class="btn btn-outline-secondary btn-sm"
        >
          Înapoi la listă
        </RouterLink>
        <button
          type="button"
          class="btn btn-primary btn-sm"
          :disabled="saving"
          @click="submit"
        >
          {{ saving ? 'Se salvează...' : 'Salvează produsul' }}
        </button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-5">
      Se încarcă datele produsului...
    </div>

    <div v-else class="card">
      <div class="card-header pb-0 border-bottom-0">
        <ul class="nav nav-tabs card-header-tabs">
          <li class="nav-item" v-for="tab in tabs" :key="tab.key">
            <button
              class="nav-link"
              :class="{ active: activeTab === tab.key }"
              type="button"
              @click="activeTab = tab.key"
            >
              {{ tab.label }}
            </button>
          </li>
        </ul>
      </div>

      <div class="card-body">
        <!-- TAB GENERAL -->
        <div v-if="activeTab === 'general'">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Denumire produs *</label>
              <input
                v-model="form.name"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': fieldError('name') }"
              />
              <div class="invalid-feedback" v-if="fieldError('name')">
                {{ fieldError('name') }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Slug (URL) *</label>
              <input
                v-model="form.slug"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': fieldError('slug') }"
              />
              <div class="invalid-feedback" v-if="fieldError('slug')">
                {{ fieldError('slug') }}
              </div>
              <div class="form-text">
                Va fi folosit în URL: /produs/{{ form.slug || 'exemplu' }}
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Cod intern</label>
              <input
                v-model="form.internal_code"
                type="text"
                class="form-control"
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">Barcode</label>
              <input
                v-model="form.barcode"
                type="text"
                class="form-control"
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">ERP ID</label>
              <input
                v-model="form.erp_id"
                type="text"
                class="form-control"
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select
                v-model="form.status"
                class="form-select"
                :class="{ 'is-invalid': fieldError('status') }"
              >
                <option value="published">Publicat</option>
                <option value="draft">Draft</option>
                <option value="hidden">Ascuns</option>
              </select>
              <div class="invalid-feedback" v-if="fieldError('status')">
                {{ fieldError('status') }}
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Categorie principală *</label>
              <select
                v-model="form.main_category_id"
                class="form-select"
                :class="{ 'is-invalid': fieldError('main_category_id') }"
              >
                <option :value="null" disabled>Selectează categorie</option>
                <option
                  v-for="cat in flatCategories"
                  :key="cat.id"
                  :value="cat.id"
                >
                  {{ cat.indented_name }}
                </option>
              </select>
              <div class="invalid-feedback" v-if="fieldError('main_category_id')">
                {{ fieldError('main_category_id') }}
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Categorii secundare</label>
              <select
                v-model="form.category_ids"
                class="form-select"
                multiple
              >
                <option
                  v-for="cat in flatCategories"
                  :key="cat.id"
                  :value="cat.id"
                >
                  {{ cat.indented_name }}
                </option>
              </select>
              <div class="form-text">
                Ține Ctrl/Cmd apăsat pentru selecție multiplă.
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Brand</label>
              <select
                v-model="form.brand_id"
                class="form-select"
              >
                <option :value="null">Fără brand</option>
                <option
                  v-for="brand in brands"
                  :key="brand.id"
                  :value="brand.id"
                >
                  {{ brand.name }}
                </option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Descriere scurtă</label>
              <textarea
                v-model="form.short_description"
                rows="2"
                class="form-control"
              ></textarea>
            </div>

            <div class="col-12">
              <label class="form-label">Descriere lungă (rich text simplu)</label>
              <textarea
                v-model="form.long_description"
                rows="5"
                class="form-control"
              ></textarea>
              <div class="form-text">
                Într-o versiune ulterioară se poate integra un editor WYSIWYG.
              </div>
            </div>

            <div class="col-12 d-flex flex-wrap gap-3 mt-2">
              <div class="form-check">
                <input
                  id="flagNew"
                  v-model="form.is_new"
                  class="form-check-input"
                  type="checkbox"
                  :true-value="true"
                  :false-value="false"
                />
                <label class="form-check-label" for="flagNew">
                  Marcat ca „nou”
                </label>
              </div>
              <div class="form-check">
                <input
                  id="flagPromo"
                  v-model="form.is_promo"
                  class="form-check-input"
                  type="checkbox"
                  :true-value="true"
                  :false-value="false"
                />
                <label class="form-check-label" for="flagPromo">
                  Marcat ca „promoțional”
                </label>
              </div>
              <div class="form-check">
                <input
                  id="flagBest"
                  v-model="form.is_best_seller"
                  class="form-check-input"
                  type="checkbox"
                  :true-value="true"
                  :false-value="false"
                />
                <label class="form-check-label" for="flagBest">
                  Marcat ca „best seller”
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB PREȚURI -->
        <div v-if="activeTab === 'pricing'">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Preț de listă *</label>
              <input
                v-model.number="form.list_price"
                type="number"
                step="0.01"
                min="0"
                class="form-control"
                :class="{ 'is-invalid': fieldError('list_price') }"
              />
              <div class="invalid-feedback" v-if="fieldError('list_price')">
                {{ fieldError('list_price') }}
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">PRP (preț recomandat producător)</label>
              <input
                v-model.number="form.rrp_price"
                type="number"
                step="0.01"
                min="0"
                class="form-control"
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">TVA (%)</label>
              <input
                v-model.number="form.vat_rate"
                type="number"
                step="0.01"
                min="0"
                class="form-control"
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">Override preț</label>
              <input
                v-model.number="form.price_override"
                type="number"
                step="0.01"
                min="0"
                class="form-control"
              />
              <div class="form-text">
                Dacă este setat, are prioritate față de prețul din ERP.
              </div>
            </div>
          </div>
        </div>

        <!-- TAB STOC -->
        <div v-if="activeTab === 'stock'">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Status stoc</label>
              <select
                v-model="form.stock_status"
                class="form-select"
              >
                <option :value="null">Nespecificat</option>
                <option value="in_stock">În stoc</option>
                <option value="limited">Stoc limitat</option>
                <option value="out_of_stock">Epuizat</option>
                <option value="on_order">La comandă</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Cantitate stoc</label>
              <input
                v-model.number="form.stock_qty"
                type="number"
                min="0"
                class="form-control"
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">Stoc furnizor</label>
              <input
                v-model.number="form.supplier_stock_qty"
                type="number"
                min="0"
                class="form-control"
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">Termen livrare (zile)</label>
              <input
                v-model.number="form.lead_time_days"
                type="number"
                min="0"
                class="form-control"
              />
            </div>
          </div>
        </div>

        <!-- TAB IMAGINI -->
        <div v-if="activeTab === 'images'">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Galerie imagini</h5>
            <button
              type="button"
              class="btn btn-outline-primary btn-sm"
              @click="addImage"
            >
              Adaugă imagine
            </button>
          </div>
          <p class="text-muted small">
            În acest demo, imaginile sunt gestionate prin path-uri. Într-o versiune completă
            se poate integra upload cu gestionare fișiere.
          </p>

          <div
            v-if="form.images.length === 0"
            class="text-muted small"
          >
            Nu există imagini adăugate.
          </div>

          <div
            v-for="(img, index) in form.images"
            :key="index"
            class="border rounded p-2 mb-2"
          >
            <div class="row g-2 align-items-center">
              <div class="col-md-6">
                <label class="form-label mb-1">Path imagine</label>
                <input
                  v-model="img.path"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="/storage/products/..."
                />
              </div>
              <div class="col-md-2">
                <label class="form-label mb-1">Principală</label>
                <div class="form-check">
                  <input
                    :id="`imgMain${index}`"
                    v-model="img.is_main"
                    class="form-check-input"
                    type="checkbox"
                    :true-value="true"
                    :false-value="false"
                  />
                  <label
                    class="form-check-label small"
                    :for="`imgMain${index}`"
                  >
                    Imagine principală
                  </label>
                </div>
              </div>
              <div class="col-md-2">
                <label class="form-label mb-1">Ordine</label>
                <input
                  v-model.number="img.sort_order"
                  type="number"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-md-2 text-end">
                <button
                  type="button"
                  class="btn btn-outline-danger btn-sm mt-3 mt-md-0"
                  @click="removeImage(index)"
                >
                  Șterge
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB VARIANTE -->
        <div v-if="activeTab === 'variants'">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Variante produs</h5>
            <button
              type="button"
              class="btn btn-outline-primary btn-sm"
              @click="addVariant"
            >
              Adaugă variantă
            </button>
          </div>
          <p class="text-muted small">
            Exemplu de modelare variante: culoare, dimensiune, etc. Atributele
            sunt păstrate într-un obiect JSON (color, size...), util pentru
            filtre și afișare în front.
          </p>

          <div
            v-if="form.variants.length === 0"
            class="text-muted small"
          >
            Nu există variante definite; produsul este tratat ca „simplu”.
          </div>

          <div
            v-for="(variant, index) in form.variants"
            :key="index"
            class="border rounded p-2 mb-2"
          >
            <div class="row g-2">
              <div class="col-md-3">
                <label class="form-label mb-1">SKU *</label>
                <input
                  v-model="variant.sku"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-md-3">
                <label class="form-label mb-1">Barcode</label>
                <input
                  v-model="variant.barcode"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-md-2">
                <label class="form-label mb-1">Preț</label>
                <input
                  v-model.number="variant.price"
                  type="number"
                  step="0.01"
                  min="0"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-md-2">
                <label class="form-label mb-1">Stoc</label>
                <input
                  v-model.number="variant.stock_qty"
                  type="number"
                  min="0"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-md-2">
                <label class="form-label mb-1">Slug</label>
                <input
                  v-model="variant.slug"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>

              <div class="col-md-4">
                <label class="form-label mb-1">Culoare</label>
                <input
                  v-model="variant.attributes.color"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="ex. roșu"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label mb-1">Mărime</label>
                <input
                  v-model="variant.attributes.size"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="ex. M, L, XL"
                />
              </div>

              <div class="col-md-4 d-flex align-items-end justify-content-end">
                <button
                  type="button"
                  class="btn btn-outline-danger btn-sm"
                  @click="removeVariant(index)"
                >
                  Șterge variantă
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB PRODUSE ASOCIATE -->
        <div v-if="activeTab === 'related'">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Produse asociate</h5>
            <button
              type="button"
              class="btn btn-outline-primary btn-sm"
              @click="addRelatedProduct"
            >
              Adaugă produs asociat
            </button>
          </div>
          <p class="text-muted small">
            Simplu demo: introduci ID-ul produsului asociat și tipul relației
            (similar, cross_sell, up_sell). Într-o versiune completă se poate
            integra un selector cu căutare produse.
          </p>

          <div
            v-if="form.related_products.length === 0"
            class="text-muted small"
          >
            Nu există produse asociate definite.
          </div>

          <div
            v-for="(rel, index) in form.related_products"
            :key="index"
            class="border rounded p-2 mb-2"
          >
            <div class="row g-2 align-items-center">
              <div class="col-md-3">
                <label class="form-label mb-1">ID produs asociat</label>
                <input
                  v-model.number="rel.related_id"
                  type="number"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-md-3">
                <label class="form-label mb-1">Tip relație</label>
                <select
                  v-model="rel.type"
                  class="form-select form-select-sm"
                >
                  <option value="similar">Similar</option>
                  <option value="cross_sell">Cross-sell</option>
                  <option value="up_sell">Up-sell</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label mb-1">Produs (info)</label>
                <div class="small">
                  <span v-if="rel.related && rel.related.name">
                    {{ rel.related.name }} (ID: {{ rel.related.id }})
                  </span>
                  <span v-else class="text-muted">
                    Info produs asociat disponibilă după salvare/refresh.
                  </span>
                </div>
              </div>
              <div class="col-md-2 text-end">
                <button
                  type="button"
                  class="btn btn-outline-danger btn-sm mt-3 mt-md-0"
                  @click="removeRelatedProduct(index)"
                >
                  Șterge
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB DOCUMENTE -->
        <div v-if="activeTab === 'documents'">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Documente atașate</h5>
            <button
              type="button"
              class="btn btn-outline-primary btn-sm"
              @click="addDocument"
            >
              Adaugă document
            </button>
          </div>
          <p class="text-muted small">
            Fișiere precum fișe tehnice, certificate, manuale. Acum se
            gestionează ca path-uri; în producție se poate integra upload
            cu restricționare acces.
          </p>

          <div
            v-if="form.documents.length === 0"
            class="text-muted small"
          >
            Nu există documente atașate.
          </div>

          <div
            v-for="(doc, index) in form.documents"
            :key="index"
            class="border rounded p-2 mb-2"
          >
            <div class="row g-2 align-items-center">
              <div class="col-md-5">
                <label class="form-label mb-1">Path document</label>
                <input
                  v-model="doc.path"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="/storage/products/doc.pdf"
                />
              </div>
              <div class="col-md-3">
                <label class="form-label mb-1">Tip</label>
                <input
                  v-model="doc.type"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="ex. spec_sheet, manual, certificat"
                />
              </div>
              <div class="col-md-2">
                <label class="form-label mb-1">Vizibilitate</label>
                <select
                  v-model="doc.visibility"
                  class="form-select form-select-sm"
                >
                  <option value="public">Public</option>
                  <option value="customers_only">Doar clienți</option>
                  <option value="by_request">Doar la cerere</option>
                </select>
              </div>
              <div class="col-md-2 text-end">
                <button
                  type="button"
                  class="btn btn-outline-danger btn-sm mt-3 mt-md-0"
                  @click="removeDocument(index)"
                >
                  Șterge
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- FOOTER -->
        <div class="mt-4 d-flex justify-content-between align-items-center border-top pt-3">
          <div class="small text-muted">
            câmpurile marcate cu * sunt obligatorii.
          </div>
          <button
            type="button"
            class="btn btn-primary btn-sm"
            :disabled="saving"
            @click="submit"
          >
            {{ saving ? 'Se salvează...' : 'Salvează produsul' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  reactive,
  onMounted,
  computed,
} from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  fetchProduct,
  createProduct,
  updateProduct,
} from '@/services/admin/products';
import { fetchCategories } from '@/services/admin/categories';
import { fetchBrands } from '@/services/admin/brands';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => !!route.params.id);

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const errors = ref({});

const categories = ref([]);
const brands = ref([]);

const activeTab = ref('general');

const tabs = [
  { key: 'general', label: 'General' },
  { key: 'pricing', label: 'Prețuri' },
  { key: 'stock', label: 'Stoc' },
  { key: 'images', label: 'Imagini' },
  { key: 'variants', label: 'Variante' },
  { key: 'related', label: 'Produse asociate' },
  { key: 'documents', label: 'Documente' },
];

const form = reactive({
  id: null,
  name: '',
  slug: '',
  internal_code: '',
  barcode: '',
  erp_id: '',
  short_description: '',
  long_description: '',
  main_category_id: null,
  brand_id: null,
  status: 'published',
  sort_order: 0,

  list_price: 0,
  rrp_price: null,
  vat_rate: null,
  price_override: null,

  stock_status: null,
  stock_qty: 0,
  supplier_stock_qty: 0,
  lead_time_days: null,

  is_new: false,
  is_promo: false,
  is_best_seller: false,

  category_ids: [],

  images: [],
  variants: [],
  related_products: [],
  documents: [],
});

const fieldError = (field) => {
  return errors.value[field]?.[0] || '';
};

const loadMeta = async () => {
  try {
    const cats = await fetchCategories({ per_page: 1000 });
    categories.value = cats.data || cats;

    const br = await fetchBrands({ per_page: 1000 });
    brands.value = br.data || br;
  } catch (e) {
    console.error('Meta load error', e);
  }
};

const loadProduct = async () => {
  if (!isEdit.value) return;

  loading.value = true;
  error.value = '';
  errors.value = {};

  try {
    const data = await fetchProduct(route.params.id);

    form.id = data.id;
    form.name = data.name;
    form.slug = data.slug;
    form.internal_code = data.internal_code;
    form.barcode = data.barcode;
    form.erp_id = data.erp_id;
    form.short_description = data.short_description;
    form.long_description = data.long_description;
    form.main_category_id = data.main_category_id;
    form.brand_id = data.brand_id;
    form.status = data.status || 'published';
    form.sort_order = data.sort_order ?? 0;

    form.list_price = data.list_price ?? 0;
    form.rrp_price = data.rrp_price;
    form.vat_rate = data.vat_rate;
    form.price_override = data.price_override;

    form.stock_status = data.stock_status;
    form.stock_qty = data.stock_qty ?? 0;
    form.supplier_stock_qty = data.supplier_stock_qty ?? 0;
    form.lead_time_days = data.lead_time_days;

    form.is_new = !!data.is_new;
    form.is_promo = !!data.is_promo;
    form.is_best_seller = !!data.is_best_seller;

    form.category_ids = data.category_ids || [];

    form.images = (data.images || []).map((img) => ({
      id: img.id ?? null,
      path: img.path || '',
      is_main: !!img.is_main,
      sort_order: img.sort_order ?? 0,
    }));

    form.variants = (data.variants || []).map((v) => ({
      id: v.id ?? null,
      sku: v.sku || '',
      barcode: v.barcode || '',
      erp_id: v.erp_id || '',
      price: v.price ?? null,
      stock_qty: v.stock_qty ?? 0,
      slug: v.slug || '',
      attributes: v.attributes || {},
    }));

    form.related_products = (data.related_products || []).map((rel) => ({
      id: rel.id ?? null,
      related_id: rel.related_id,
      type: rel.type || 'similar',
      related: rel.related || null,
    }));

    form.documents = (data.documents || []).map((doc) => ({
      id: doc.id ?? null,
      path: doc.path || '',
      type: doc.type || '',
      visibility: doc.visibility || 'public',
    }));
  } catch (e) {
    console.error('Load product error', e);
    error.value = 'Nu s-au putut încărca datele produsului.';
  } finally {
    loading.value = false;
  }
};

const buildPayload = () => {
  return {
    name: form.name,
    slug: form.slug,
    internal_code: form.internal_code,
    barcode: form.barcode,
    erp_id: form.erp_id,
    short_description: form.short_description,
    long_description: form.long_description,
    main_category_id: form.main_category_id,
    brand_id: form.brand_id,
    status: form.status,
    sort_order: form.sort_order,

    list_price: form.list_price,
    rrp_price: form.rrp_price,
    vat_rate: form.vat_rate,
    price_override: form.price_override,

    stock_status: form.stock_status,
    stock_qty: form.stock_qty,
    supplier_stock_qty: form.supplier_stock_qty,
    lead_time_days: form.lead_time_days,

    is_new: form.is_new,
    is_promo: form.is_promo,
    is_best_seller: form.is_best_seller,

    category_ids: form.category_ids,

    images: form.images.map((img) => ({
      id: img.id ?? null,
      path: img.path,
      is_main: !!img.is_main,
      sort_order: img.sort_order ?? 0,
    })),

    variants: form.variants.map((v) => ({
      id: v.id ?? null,
      sku: v.sku,
      barcode: v.barcode,
      erp_id: v.erp_id,
      price: v.price,
      stock_qty: v.stock_qty,
      slug: v.slug,
      attributes: v.attributes || {},
    })),

    related_products: form.related_products.map((rel) => ({
      id: rel.id ?? null,
      related_id: rel.related_id,
      type: rel.type || 'similar',
    })),

    documents: form.documents.map((doc) => ({
      id: doc.id ?? null,
      path: doc.path,
      type: doc.type,
      visibility: doc.visibility || 'public',
    })),
  };
};

const submit = async () => {
  saving.value = true;
  error.value = '';
  errors.value = {};

  const payload = buildPayload();

  try {
    if (isEdit.value) {
      await updateProduct(form.id, payload);
    } else {
      const created = await createProduct(payload);
      form.id = created.id;
    }

    router.push({ name: 'admin-products' });
  } catch (e) {
    console.error('Save product error', e);

    if (e.response && e.response.status === 422) {
      errors.value = e.response.data.errors || {};
      error.value = e.response.data.message || 'Date invalide.';
    } else {
      error.value = 'A apărut o eroare la salvarea produsului.';
    }
  } finally {
    saving.value = false;
  }
};

const addImage = () => {
  form.images.push({
    id: null,
    path: '',
    is_main: form.images.length === 0,
    sort_order: form.images.length + 1,
  });
};

const removeImage = (index) => {
  form.images.splice(index, 1);
};

const addVariant = () => {
  form.variants.push({
    id: null,
    sku: '',
    barcode: '',
    erp_id: '',
    price: null,
    stock_qty: 0,
    slug: '',
    attributes: {
      color: '',
      size: '',
    },
  });
};

const removeVariant = (index) => {
  form.variants.splice(index, 1);
};

const addRelatedProduct = () => {
  form.related_products.push({
    id: null,
    related_id: null,
    type: 'similar',
    related: null,
  });
};

const removeRelatedProduct = (index) => {
  form.related_products.splice(index, 1);
};

const addDocument = () => {
  form.documents.push({
    id: null,
    path: '',
    type: '',
    visibility: 'public',
  });
};

const removeDocument = (index) => {
  form.documents.splice(index, 1);
};

const flatCategories = computed(() => {
  const result = [];

  const build = (nodes, level = 0) => {
    nodes.forEach((cat) => {
      result.push({
        id: cat.id,
        indented_name: `${'— '.repeat(level)}${cat.name}`,
      });

      if (cat.children && cat.children.length) {
        build(cat.children, level + 1);
      }
    });
  };

  build(categories.value);
  return result;
});

onMounted(async () => {
  await loadMeta();
  await loadProduct();
});
</script>
