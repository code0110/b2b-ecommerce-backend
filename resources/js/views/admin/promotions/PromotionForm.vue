<!-- resources/js/views/admin/promotions/PromotionForm.vue -->
<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">
          {{ isEdit ? 'Editează promoție' : 'Promoție nouă' }}
        </h1>
        <div class="text-muted small">
          Configurare regulă de promoție și segmentare.
        </div>
      </div>
    </div>

    <div v-if="loading" class="alert alert-info small py-2">
      Se încarcă datele promoției...
    </div>
    <div v-else-if="error" class="alert alert-danger small py-2">
      {{ error }}
    </div>

    <form v-if="!loading" @submit.prevent="submit">
      <div class="row g-3 mb-3">
        <div class="col-lg-8">
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <span class="small text-uppercase text-muted">Date generale</span>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <label class="form-label form-label-sm">Denumire promoție</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': validationErrors.name }"
                />
                <div v-if="validationErrors.name" class="invalid-feedback">
                  {{ validationErrors.name[0] }}
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label form-label-sm">Slug (URL)</label>
                <input
                  v-model="form.slug"
                  type="text"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': validationErrors.slug }"
                />
                <div v-if="validationErrors.slug" class="invalid-feedback">
                  {{ validationErrors.slug[0] }}
                </div>
                <div class="form-text small">
                  Folosit în URL: /promotii/{{ form.slug || 'campanie' }}
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label form-label-sm">Descriere scurtă</label>
                <textarea
                  v-model="form.short_description"
                  rows="2"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': validationErrors.short_description }"
                />
                <div
                  v-if="validationErrors.short_description"
                  class="invalid-feedback"
                >
                  {{ validationErrors.short_description[0] }}
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label form-label-sm">Descriere detaliată</label>
                <textarea
                  v-model="form.description"
                  rows="4"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': validationErrors.description }"
                />
                <div
                  v-if="validationErrors.description"
                  class="invalid-feedback"
                >
                  {{ validationErrors.description[0] }}
                </div>
              </div>

              <div class="row">
                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">Data start</label>
                  <input
                    v-model="form.start_at"
                    type="date"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.start_at }"
                  />
                  <div v-if="validationErrors.start_at" class="invalid-feedback">
                    {{ validationErrors.start_at[0] }}
                  </div>
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">Data stop</label>
                  <input
                    v-model="form.end_at"
                    type="date"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.end_at }"
                  />
                  <div v-if="validationErrors.end_at" class="invalid-feedback">
                    {{ validationErrors.end_at[0] }}
                  </div>
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">Status</label>
                  <select
                    v-model="form.status"
                    class="form-select form-select-sm"
                    :class="{ 'is-invalid': validationErrors.status }"
                  >
                    <option value="draft">Draft</option>
                    <option value="active">Activă</option>
                    <option value="inactive">Inactivă</option>
                  </select>
                  <div v-if="validationErrors.status" class="invalid-feedback">
                    {{ validationErrors.status[0] }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card shadow-sm">
            <div class="card-header py-2">
              <span class="small text-uppercase text-muted">Reguli promoție</span>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">Tip bonus</label>
                  <select
                    v-model="form.bonus_type"
                    class="form-select form-select-sm"
                    :class="{ 'is-invalid': validationErrors.bonus_type }"
                  >
                    <option value="discount_percent">Discount procentual</option>
                    <option value="discount_value">Discount valoric</option>
                    <option value="free_item">Produs gratuit</option>
                  </select>
                  <div
                    v-if="validationErrors.bonus_type"
                    class="invalid-feedback"
                  >
                    {{ validationErrors.bonus_type[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">Valoare min. coș</label>
                  <input
                    v-model.number="form.min_cart_total"
                    type="number"
                    class="form-control form-control-sm"
                    min="0"
                    step="0.01"
                    :class="{ 'is-invalid': validationErrors.min_cart_total }"
                  />
                  <div
                    v-if="validationErrors.min_cart_total"
                    class="invalid-feedback"
                  >
                    {{ validationErrors.min_cart_total[0] }}
                  </div>
                </div>

                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">Cantitate min. / produs</label>
                  <input
                    v-model.number="form.min_qty_per_product"
                    type="number"
                    min="0"
                    step="1"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': validationErrors.min_qty_per_product }"
                  />
                  <div
                    v-if="validationErrors.min_qty_per_product"
                    class="invalid-feedback"
                  >
                    {{ validationErrors.min_qty_per_product[0] }}
                  </div>
                </div>
              </div>

              <div class="row mb-2">
                <div class="col-md-4">
                  <div class="form-check form-switch">
                    <input
                      v-model="form.is_exclusive"
                      class="form-check-input"
                      type="checkbox"
                      id="promo-exclusive"
                    />
                    <label
                      class="form-check-label small"
                      for="promo-exclusive"
                    >
                      Promoție exclusivă
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check form-switch">
                    <input
                      v-model="form.is_iterative"
                      class="form-check-input"
                      type="checkbox"
                      id="promo-iterative"
                    />
                    <label
                      class="form-check-label small"
                      for="promo-iterative"
                    >
                      Promoție cumulabilă
                    </label>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm">Tip client</label>
                  <select
                    v-model="form.customer_type"
                    class="form-select form-select-sm"
                    :class="{ 'is-invalid': validationErrors.customer_type }"
                  >
                    <option value="b2b">B2B</option>
                    <option value="b2c">B2C</option>
                    <option value="both">B2B + B2C</option>
                  </select>
                  <div
                    v-if="validationErrors.customer_type"
                    class="invalid-feedback"
                  >
                    {{ validationErrors.customer_type[0] }}
                  </div>
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label form-label-sm d-block">Utilizatori</label>
                  <div class="form-check form-switch">
                    <input
                      v-model="form.logged_in_only"
                      class="form-check-input"
                      type="checkbox"
                      id="promo-logged-in-only"
                    />
                    <label
                      class="form-check-label small"
                      for="promo-logged-in-only"
                    >
                      Doar utilizatori logați
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Segmentare -->
        <div class="col-lg-4">
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <span class="small text-uppercase text-muted">Segmentare clienți</span>
            </div>
            <div class="card-body small">
              <div class="mb-2">
                <label class="form-label form-label-sm">Grupuri de clienți</label>
                <select
                  v-model="form.customer_group_ids"
                  class="form-select form-select-sm"
                  multiple
                >
                  <option
                    v-for="group in groups"
                    :key="group.id"
                    :value="group.id"
                  >
                    {{ group.name }} ({{ group.type.toUpperCase() }})
                  </option>
                </select>
                <div class="form-text">
                  Dacă nu selectezi nimic, promoția se aplică tuturor grupurilor
                  compatibile cu tipul de client.
                </div>
              </div>
            </div>
          </div>

          <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
              <span class="small text-uppercase text-muted">Segmentare catalog</span>
            </div>
            <div class="card-body small">
              <div class="mb-2">
                <label class="form-label form-label-sm">Categorii</label>
                <select
                  v-model="form.category_ids"
                  class="form-select form-select-sm"
                  multiple
                >
                  <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                  >
                    {{ category.name }}
                  </option>
                </select>
              </div>

              <div class="mb-2">
                <label class="form-label form-label-sm">Branduri</label>
                <select
                  v-model="form.brand_ids"
                  class="form-select form-select-sm"
                  multiple
                >
                  <option
                    v-for="brand in brands"
                    :key="brand.id"
                    :value="brand.id"
                  >
                    {{ brand.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <div
            v-if="generalError"
            class="alert alert-danger small py-2 mb-2"
          >
            {{ generalError }}
          </div>

          <div class="d-flex justify-content-between">
            <RouterLink
              :to="{ name: 'admin-promotions' }"
              class="btn btn-outline-secondary btn-sm"
            >
              « Înapoi la listă
            </RouterLink>

            <button
              type="submit"
              class="btn btn-primary btn-sm"
              :disabled="saving"
            >
              <span
                v-if="saving"
                class="spinner-border spinner-border-sm me-1"
              />
              {{ isEdit ? 'Salvează promoția' : 'Creează promoția' }}
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  fetchPromotion,
  createPromotion,
  updatePromotion
} from '@/services/admin/promotions';
import { fetchCustomerGroups } from '@/services/admin/customerGroups';
import { fetchAdminCategories } from '@/services/admin/categories';
import { fetchAdminBrands } from '@/services/admin/brands';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => route.name === 'admin-promotions-edit');
const promotionId = computed(() => route.params.id);

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const generalError = ref('');
const validationErrors = ref({});

const groups = ref([]);
const categories = ref([]);
const brands = ref([]);

const form = ref({
  name: '',
  slug: '',
  short_description: '',
  description: '',
  hero_image: '',
  banner_image: '',
  mobile_image: '',
  start_at: '',
  end_at: '',
  status: 'draft',
  is_exclusive: false,
  is_iterative: false,
  bonus_type: 'discount_percent',
  min_cart_total: null,
  min_qty_per_product: null,
  customer_type: 'both',
  logged_in_only: false,
  customer_group_ids: [],
  category_ids: [],
  brand_ids: []
});

const loadLookups = async () => {
  const [g, c, b] = await Promise.all([
    fetchCustomerGroups(),
    fetchAdminCategories(),
    fetchAdminBrands()
  ]);
  groups.value = g;
  categories.value = c;
  brands.value = b;
};

const loadPromotion = async () => {
  if (!isEdit.value) return;

  try {
    const data = await fetchPromotion(promotionId.value);

    form.value = {
      ...form.value,
      name: data.name || '',
      slug: data.slug || '',
      short_description: data.short_description || '',
      description: data.description || '',
      hero_image: data.hero_image || '',
      banner_image: data.banner_image || '',
      mobile_image: data.mobile_image || '',
      start_at: data.start_at ? data.start_at.substring(0, 10) : '',
      end_at: data.end_at ? data.end_at.substring(0, 10) : '',
      status: data.status || 'draft',
      is_exclusive: !!data.is_exclusive,
      is_iterative: !!data.is_iterative,
      bonus_type: data.bonus_type || 'discount_percent',
      min_cart_total: data.min_cart_total,
      min_qty_per_product: data.min_qty_per_product,
      customer_type: data.customer_type || 'both',
      logged_in_only: !!data.logged_in_only,
      customer_group_ids:
        (data.customer_groups || data.customerGroups || []).map(g => g.id),
      category_ids: (data.categories || []).map(c => c.id),
      brand_ids: (data.brands || []).map(b => b.id)
    };
  } catch (e) {
    console.error('Promotion load error', e);
    error.value = 'Nu s-au putut încărca datele promoției.';
  }
};

const submit = async () => {
  saving.value = true;
  validationErrors.value = {};
  generalError.value = '';

  const payload = {
    name: form.value.name,
    slug: form.value.slug,
    short_description: form.value.short_description,
    description: form.value.description,
    hero_image: form.value.hero_image || null,
    banner_image: form.value.banner_image || null,
    mobile_image: form.value.mobile_image || null,
    start_at: form.value.start_at || null,
    end_at: form.value.end_at || null,
    status: form.value.status,
    is_exclusive: form.value.is_exclusive,
    is_iterative: form.value.is_iterative,
    bonus_type: form.value.bonus_type,
    min_cart_total: form.value.min_cart_total,
    min_qty_per_product: form.value.min_qty_per_product,
    customer_type: form.value.customer_type,
    logged_in_only: form.value.logged_in_only,
    customer_group_ids: form.value.customer_group_ids,
    category_ids: form.value.category_ids,
    brand_ids: form.value.brand_ids
  };

  try {
    if (isEdit.value) {
      await updatePromotion(promotionId.value, payload);
    } else {
      await createPromotion(payload);
    }

    router.push({ name: 'admin-promotions' });
  } catch (e) {
    console.error('Promotion save error', e);
    if (e.response && e.response.status === 422) {
      validationErrors.value = e.response.data.errors || {};
    } else {
      generalError.value = 'A apărut o eroare la salvarea promoției.';
    }
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  loading.value = true;
  try {
    await loadLookups();
    await loadPromotion();
  } finally {
    loading.value = false;
  }
});
</script>
