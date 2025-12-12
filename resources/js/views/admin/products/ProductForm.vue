<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  fetchAdminProduct,
  createAdminProduct,
  updateAdminProduct,
} from '@/services/admin/products';
import { fetchAdminCategories } from '@/services/admin/categories';
import { fetchAdminBrands } from '@/services/admin/brands';

const route = useRoute();
const router = useRouter();

const productId = computed(() => route.params.id ?? null);
const isEdit = computed(() => !!productId.value);

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const successMessage = ref('');

// formularul de produs – acum include slug și main_category_id
const form = ref({
  name: '',
  slug: '',
  internal_code: '',
  erp_id: '',
  barcode: '',
  status: 'published', // published / hidden
  sort_order: 0,

  main_category_id: null,
  brand_id: null,

  list_price: null,
  recommended_price: null,
  vat: 19,
  override_price: null,

  stock_status: 'in_stock', // in_stock / low_stock / out_of_stock / on_demand
  stock_qty: 0,
  supplier_stock: null,
  delivery_eta_days: null,

  short_description: '',
  long_description: '',
});

// dropdowns
const categories = ref([]);
const brands = ref([]);

// helper pentru slug
const slugify = (value) => {
  return String(value || '')
    .toLowerCase()
    .trim()
    .replace(/[\s\_]+/g, '-') // spații și underscore -> -
    .replace(/[^a-z0-9\-]/g, '') // elimină caractere non-alfanumerice
    .replace(/\-+/g, '-'); // compactează multiple '-'
};

// dacă numele se schimbă și slug-ul e gol (sau suntem pe create), generăm automat un slug
watch(
  () => form.value.name,
  (newVal) => {
    if (!isEdit.value && !form.value.slug) {
      form.value.slug = slugify(newVal);
    }
  },
);

// încărcăm categorii și branduri pentru select
const loadOptions = async () => {
  try {
    const [cats, brs] = await Promise.all([
      fetchAdminCategories({ per_page: 999 }),
      fetchAdminBrands({ per_page: 999 }),
    ]);
    categories.value = cats;
    brands.value = brs;
  } catch (e) {
    console.error('Load categories/brands error', e);
  }
};

const loadProduct = async () => {
  if (!isEdit.value) return;
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchAdminProduct(productId.value);

    form.value = {
      name: data.name ?? '',
      slug: data.slug ?? '',
      internal_code: data.internal_code ?? '',
      erp_id: data.erp_id ?? '',
      barcode: data.barcode ?? '',
      status: data.status ?? 'published',
      sort_order: data.sort_order ?? 0,

      main_category_id: data.main_category_id ?? null,
      brand_id: data.brand_id ?? null,

      list_price: data.list_price ?? null,
      recommended_price: data.recommended_price ?? null,
      vat: data.vat ?? 19,
      override_price: data.override_price ?? null,

      stock_status: data.stock_status ?? 'in_stock',
      stock_qty: data.stock_qty ?? 0,
      supplier_stock: data.supplier_stock ?? null,
      delivery_eta_days: data.delivery_eta_days ?? null,

      short_description: data.short_description ?? '',
      long_description: data.long_description ?? '',
    };
  } catch (e) {
    console.error('Load product error', e);
    error.value = 'Nu s-a putut încărca produsul pentru editare.';
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  error.value = '';
  successMessage.value = '';
  saving.value = true;

  try {
    const payload = { ...form.value };

    if (isEdit.value) {
      await updateAdminProduct(productId.value, payload);
      successMessage.value = 'Produsul a fost actualizat.';
    } else {
      await createAdminProduct(payload);
      successMessage.value = 'Produsul a fost creat.';
      // după creare, mergem la listă (sau poți rămâne pe form)
      router.push({ name: 'admin-products' });
      return;
    }
  } catch (e) {
    console.error('Save product error', e);

    if (e.response?.data?.errors) {
      // concatenăm mesajele de validare într-un singur string
      const errs = e.response.data.errors;
      error.value = Object.values(errs).flat().join(' ');
    } else {
      error.value =
        e.response?.data?.message ||
        'Salvarea produsului a eșuat. Verifică datele și încearcă din nou.';
    }
  } finally {
    saving.value = false;
  }
};

const cancel = () => {
  router.push({ name: 'admin-products' });
};

onMounted(async () => {
  await loadOptions();
  await loadProduct();
});
</script>

<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h4 mb-0">
        {{ isEdit ? 'Editează produs' : 'Adaugă produs nou' }}
      </h1>
      <button type="button" class="btn btn-outline-secondary" @click="cancel">
        Înapoi la listă
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>
    <div v-if="successMessage" class="alert alert-success">
      {{ successMessage }}
    </div>

    <div class="card">
      <div class="card-body">
        <div v-if="loading">Se încarcă datele produsului...</div>

        <form v-else @submit.prevent="handleSubmit" class="row g-3">
          <!-- Date generale -->
          <div class="col-12">
            <h5 class="mb-3">Date generale</h5>
          </div>

          <div class="col-md-6">
            <label class="form-label">Denumire produs</label>
            <input
              v-model="form.name"
              type="text"
              class="form-control"
              required
            />
          </div>

          <div class="col-md-6">
            <label class="form-label">Slug</label>
            <input
              v-model="form.slug"
              type="text"
              class="form-control"
              required
            />
            <div class="form-text">
              URL friendly (se poate genera automat din denumire, dar e editabil).
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
            <label class="form-label">ERP ID</label>
            <input
              v-model="form.erp_id"
              type="text"
              class="form-control"
            />
          </div>

          <div class="col-md-3">
            <label class="form-label">Cod de bare</label>
            <input
              v-model="form.barcode"
              type="text"
              class="form-control"
            />
          </div>

          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select v-model="form.status" class="form-select">
              <option value="published">Publicat</option>
              <option value="hidden">Ascuns</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Ordine sortare</label>
            <input
              v-model.number="form.sort_order"
              type="number"
              class="form-control"
            />
          </div>

          <!-- Categorie principală & Brand -->
          <div class="col-md-3">
            <label class="form-label">Categorie principală</label>
            <select v-model="form.main_category_id" class="form-select" required>
              <option :value="null">— Selectează categorie —</option>
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
            <label class="form-label">Brand</label>
            <select v-model="form.brand_id" class="form-select">
              <option :value="null">— Fără brand —</option>
              <option
                v-for="brand in brands"
                :key="brand.id"
                :value="brand.id"
              >
                {{ brand.name }}
              </option>
            </select>
          </div>

          <!-- Prețuri -->
          <div class="col-12 mt-4">
            <h5 class="mb-3">Prețuri</h5>
          </div>

          <div class="col-md-3">
            <label class="form-label">Preț listă</label>
            <input
              v-model.number="form.list_price"
              type="number"
              step="0.01"
              class="form-control"
            />
          </div>

          <div class="col-md-3">
            <label class="form-label">Preț recomandat (PRP)</label>
            <input
              v-model.number="form.recommended_price"
              type="number"
              step="0.01"
              class="form-control"
            />
          </div>

          <div class="col-md-2">
            <label class="form-label">TVA (%)</label>
            <input
              v-model.number="form.vat"
              type="number"
              step="0.01"
              class="form-control"
            />
          </div>

          <div class="col-md-4">
            <label class="form-label">Override preț (opțional)</label>
            <input
              v-model.number="form.override_price"
              type="number"
              step="0.01"
              class="form-control"
            />
          </div>

          <!-- Stoc -->
          <div class="col-12 mt-4">
            <h5 class="mb-3">Stoc</h5>
          </div>

          <div class="col-md-3">
            <label class="form-label">Status stoc</label>
            <select v-model="form.stock_status" class="form-select">
              <option value="in_stock">În stoc</option>
              <option value="low_stock">Stoc limitat</option>
              <option value="out_of_stock">Epuizat</option>
              <option value="on_demand">La comandă</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Cantitate stoc</label>
            <input
              v-model.number="form.stock_qty"
              type="number"
              class="form-control"
            />
          </div>

          <div class="col-md-3">
            <label class="form-label">Stoc furnizor</label>
            <input
              v-model.number="form.supplier_stock"
              type="number"
              class="form-control"
            />
          </div>

          <div class="col-md-3">
            <label class="form-label">Termen livrare (zile)</label>
            <input
              v-model.number="form.delivery_eta_days"
              type="number"
              class="form-control"
            />
          </div>

          <!-- Descrieri -->
          <div class="col-12 mt-4">
            <h5 class="mb-3">Descrieri</h5>
          </div>

          <div class="col-12">
            <label class="form-label">Descriere scurtă</label>
            <textarea
              v-model="form.short_description"
              class="form-control"
              rows="2"
            ></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Descriere lungă</label>
            <textarea
              v-model="form.long_description"
              class="form-control"
              rows="5"
            ></textarea>
          </div>

          <!-- Acțiuni -->
          <div class="col-12 mt-4 d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="cancel"
            >
              Anulează
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="saving"
            >
              <span v-if="!saving">
                {{ isEdit ? 'Salvează modificările' : 'Creează produs' }}
              </span>
              <span v-else>Se salvează...</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
