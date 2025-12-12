<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">
        {{ isEdit ? 'Editează produs' : 'Produs nou' }}
      </h1>
      <RouterLink
        :to="{ name: 'admin-products' }"
        class="btn btn-outline-secondary btn-sm"
      >
        &larr; Înapoi la listă
      </RouterLink>
    </div>

    <div v-if="loading" class="text-muted">
      Se încarcă datele produsului...
    </div>

    <div v-else>
      <form class="card" @submit.prevent="onSubmit">
        <div class="card-body">
          <div v-if="error" class="alert alert-danger py-2 px-3 small">
            {{ error }}
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Denumire produs</label>
              <input
                v-model="form.name"
                type="text"
                class="form-control form-control-sm"
                required
                @blur="maybeGenerateSlug"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label">Slug (URL)</label>
              <input
                v-model="form.slug"
                type="text"
                class="form-control form-control-sm"
                required
              />
              <div class="form-text small">
                ex: ciment-55kg. Se poate genera automat din denumire.
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Cod intern</label>
              <input
                v-model="form.internal_code"
                type="text"
                class="form-control form-control-sm"
                required
              />
            </div>
            <div class="col-md-4">
              <label class="form-label">Barcode</label>
              <input
                v-model="form.barcode"
                type="text"
                class="form-control form-control-sm"
              />
            </div>
            <div class="col-md-4">
              <label class="form-label">ERP ID</label>
              <input
                v-model="form.erp_id"
                type="text"
                class="form-control form-control-sm"
              />
            </div>

            <div class="col-md-6">
              <label class="form-label">Categorie principală</label>
              <select
                v-model="form.main_category_id"
                class="form-select form-select-sm"
                required
              >
                <option value="">— selectează —</option>
                <option
                  v-for="cat in categories"
                  :key="cat.id"
                  :value="cat.id"
                >
                  {{ cat.name }}
                </option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Brand</label>
              <select
                v-model="form.brand_id"
                class="form-select form-select-sm"
              >
                <option :value="null">— fără brand —</option>
                <option
                  v-for="brand in brands"
                  :key="brand.id"
                  :value="brand.id"
                >
                  {{ brand.name }}
                </option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select
                v-model="form.status"
                class="form-select form-select-sm"
                required
              >
                <option value="published">Publicat</option>
                <option value="hidden">Ascuns</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Ordine sortare</label>
              <input
                v-model.number="form.sort_order"
                type="number"
                class="form-control form-control-sm"
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">Preț listă</label>
              <input
                v-model.number="form.list_price"
                type="number"
                step="0.01"
                min="0"
                class="form-control form-control-sm"
                required
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">RRP (preț recomandat)</label>
              <input
                v-model.number="form.rrp_price"
                type="number"
                step="0.01"
                min="0"
                class="form-control form-control-sm"
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">TVA (%)</label>
              <input
                v-model.number="form.vat_rate"
                type="number"
                step="0.01"
                min="0"
                class="form-control form-control-sm"
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">Preț override (opțional)</label>
              <input
                v-model.number="form.price_override"
                type="number"
                step="0.01"
                min="0"
                class="form-control form-control-sm"
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">Status stoc</label>
              <input
                v-model="form.stock_status"
                type="text"
                class="form-control form-control-sm"
                placeholder="ex: in_stock, la_comanda"
                required
              />
            </div>

            <div class="col-md-3">
              <label class="form-label">Cantitate stoc</label>
              <input
                v-model.number="form.stock_qty"
                type="number"
                class="form-control form-control-sm"
              />
            </div>

            <div class="col-md-6">
              <label class="form-label">Descriere scurtă</label>
              <textarea
                v-model="form.short_description"
                class="form-control form-control-sm"
                rows="2"
              />
            </div>

            <div class="col-md-6">
              <label class="form-label">Descriere lungă</label>
              <textarea
                v-model="form.long_description"
                class="form-control form-control-sm"
                rows="4"
              />
            </div>

            <div class="col-md-4 d-flex align-items-center">
              <div class="form-check mt-3">
                <input
                  v-model="form.is_new"
                  class="form-check-input"
                  type="checkbox"
                  id="is_new"
                />
                <label class="form-check-label" for="is_new">
                  Marcat ca „Nou”
                </label>
              </div>
            </div>
            <div class="col-md-4 d-flex align-items-center">
              <div class="form-check mt-3">
                <input
                  v-model="form.is_promo"
                  class="form-check-input"
                  type="checkbox"
                  id="is_promo"
                />
                <label class="form-check-label" for="is_promo">
                  În promoție
                </label>
              </div>
            </div>
            <div class="col-md-4 d-flex align-items-center">
              <div class="form-check mt-3">
                <input
                  v-model="form.is_best_seller"
                  class="form-check-input"
                  type="checkbox"
                  id="is_best_seller"
                />
                <label class="form-check-label" for="is_best_seller">
                  Best seller
                </label>
              </div>
            </div>
          </div>

          <!-- Aici ulterior putem adăuga tab-uri pentru imagini, variante, atribute, documente, cross-sell etc. -->
        </div>

        <div class="card-footer d-flex justify-content-end gap-2 py-2">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            @click="goBack"
          >
            Anulează
          </button>
          <button
            type="submit"
            class="btn btn-primary btn-sm"
            :disabled="saveLoading"
          >
            <span
              v-if="saveLoading"
              class="spinner-border spinner-border-sm me-1"
            />
            Salvează
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  fetchAdminProduct,
  createAdminProduct,
  updateAdminProduct
} from '@/services/admin/products';
import { fetchAdminCategories } from '@/services/admin/categories';
import { fetchAdminBrands } from '@/services/admin/brands';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const saveLoading = ref(false);
const error = ref('');

const categories = ref([]);
const brands = ref([]);

const form = ref({
  name: '',
  slug: '',
  internal_code: '',
  barcode: '',
  erp_id: '',
  short_description: '',
  long_description: '',
  main_category_id: '',
  brand_id: null,
  status: 'published',
  sort_order: null,
  list_price: 0,
  rrp_price: null,
  vat_rate: null,
  price_override: null,
  stock_status: 'in_stock',
  stock_qty: null,
  is_new: false,
  is_promo: false,
  is_best_seller: false
});

const isEdit = computed(() => !!route.params.id);

const loadFiltersData = async () => {
  [categories.value, brands.value] = await Promise.all([
    fetchAdminCategories(),
    fetchAdminBrands()
  ]);
};

const loadProduct = async () => {
  if (!isEdit.value) return;
  const data = await fetchAdminProduct(route.params.id);

  form.value = {
    name: data.name ?? '',
    slug: data.slug ?? '',
    internal_code: data.internal_code ?? '',
    barcode: data.barcode ?? '',
    erp_id: data.erp_id ?? '',
    short_description: data.short_description ?? '',
    long_description: data.long_description ?? '',
    main_category_id: data.main_category_id ?? '',
    brand_id: data.brand_id ?? null,
    status: data.status ?? 'published',
    sort_order: data.sort_order ?? null,
    list_price: Number(data.list_price ?? 0),
    rrp_price: data.rrp_price != null ? Number(data.rrp_price) : null,
    vat_rate: data.vat_rate != null ? Number(data.vat_rate) : null,
    price_override:
      data.price_override != null ? Number(data.price_override) : null,
    stock_status: data.stock_status ?? 'in_stock',
    stock_qty: data.stock_qty != null ? Number(data.stock_qty) : null,
    is_new: !!data.is_new,
    is_promo: !!data.is_promo,
    is_best_seller: !!data.is_best_seller
  };
};

const slugify = (value) =>
  value
    .toString()
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');

const maybeGenerateSlug = () => {
  if (!form.value.slug && form.value.name) {
    form.value.slug = slugify(form.value.name);
  }
};

const onSubmit = async () => {
  saveLoading.value = true;
  error.value = '';

  const payload = {
    name: form.value.name,
    slug: form.value.slug,
    internal_code: form.value.internal_code,
    barcode: form.value.barcode || null,
    erp_id: form.value.erp_id || null,
    short_description: form.value.short_description || null,
    long_description: form.value.long_description || null,
    main_category_id: form.value.main_category_id,
    brand_id: form.value.brand_id || null,
    status: form.value.status,
    sort_order: form.value.sort_order ?? null,
    list_price: Number(form.value.list_price),
    rrp_price: form.value.rrp_price != null ? Number(form.value.rrp_price) : null,
    vat_rate: form.value.vat_rate != null ? Number(form.value.vat_rate) : null,
    price_override:
      form.value.price_override != null
        ? Number(form.value.price_override)
        : null,
    stock_status: form.value.stock_status,
    stock_qty: form.value.stock_qty != null ? Number(form.value.stock_qty) : null,
    is_new: !!form.value.is_new,
    is_promo: !!form.value.is_promo,
    is_best_seller: !!form.value.is_best_seller
  };

  try {
    if (isEdit.value) {
      await updateAdminProduct(route.params.id, payload);
    } else {
      await createAdminProduct(payload);
    }
    router.push({ name: 'admin-products' });
  } catch (e) {
    console.error('Save product error', e);
    if (e.response?.data?.message) {
      error.value = e.response.data.message;
    } else if (e.response?.status === 422) {
      error.value = 'Date invalide pentru produs (verifică câmpurile obligatorii).';
    } else {
      error.value = 'A apărut o eroare la salvarea produsului.';
    }
  } finally {
    saveLoading.value = false;
  }
};

const goBack = () => {
  router.push({ name: 'admin-products' });
};

onMounted(async () => {
  loading.value = true;
  try {
    await loadFiltersData();
    await loadProduct();
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca datele pentru formular.';
  } finally {
    loading.value = false;
  }
});
</script>
