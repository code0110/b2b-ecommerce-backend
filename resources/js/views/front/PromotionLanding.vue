<template>
  <div class="py-3">
    <div class="container">
      <RouterLink to="/promotii" class="small text-muted text-decoration-none">
        ← Înapoi la promoții
      </RouterLink>

      <div class="row mt-2">
        <div class="col-md-8">
          <h1 class="h4 mb-1">
            {{ promotion?.name || 'Promoție' }}
          </h1>
          <p class="text-muted small mb-2">
            {{ promotion?.short_description }}
          </p>
          <div class="small mb-2">
            <strong>Perioadă:</strong>
            {{ formatPeriod(promotion) }}
          </div>
          <div class="small text-muted mb-3">
            Segment: {{ formatCustomerType(promotion?.customer_type) }}
          </div>
          <p v-if="promotion?.description" class="mb-3">
            {{ promotion.description }}
          </p>
        </div>
      </div>

      <hr class="my-3" />

      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h6 mb-0">Produse incluse în promoție</h2>
      </div>

      <div v-if="loading" class="text-muted small py-3">
        Se încarcă promoția...
      </div>
      <div v-else-if="error" class="alert alert-danger small py-2">
        {{ error }}
      </div>

      <div v-else class="row g-3">
        <div
          v-for="p in products.data"
          :key="p.id"
          class="col-lg-3 col-md-4 col-sm-6"
        >
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <div class="small text-muted mb-1">
                {{ p.main_category?.name || 'Categorie' }}
              </div>
              <h3 class="h6 mb-1">{{ p.name }}</h3>
              <div class="small text-muted mb-2">
                {{ p.code || p.sku }}
              </div>
              <div class="mt-auto">
                <div class="small text-muted" v-if="p.promo_price">
                  <span class="text-decoration-line-through me-1">
                    {{ formatMoney(p.price || p.list_price || 0) }}
                  </span>
                  <span class="fw-semibold">
                    {{ formatMoney(p.promo_price) }} RON
                  </span>
                </div>
                <div v-else class="fw-semibold mb-1">
                  {{ formatMoney(p.price || p.list_price || 0) }} RON
                </div>
                <RouterLink
                  :to="`/produs/${p.slug}`"
                  class="btn btn-outline-primary btn-sm"
                >
                  Detalii produs
                </RouterLink>
              </div>
            </div>
          </div>
        </div>

        <div v-if="!products.data.length" class="col-12">
          <div class="alert alert-light border small mb-0">
            Nu există produse legate de această promoție.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { fetchPromotionLanding } from '@/services/promotions';

const route = useRoute();

const loading = ref(false);
const error = ref('');
const promotion = ref(null);
const products = reactive({
  data: [],
  meta: null,
});

const formatMoney = (value) =>
  (Number(value) || 0).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

const formatPeriod = (promo) => {
  if (!promo) return '';
  if (!promo.start_at && !promo.end_at) return 'fără perioadă';
  const start = promo.start_at
    ? new Date(promo.start_at).toLocaleDateString('ro-RO')
    : '–';
  const end = promo.end_at
    ? new Date(promo.end_at).toLocaleDateString('ro-RO')
    : '–';
  return `${start} – ${end}`;
};

const formatCustomerType = (t) => {
  if (t === 'b2b') return 'clienți B2B';
  if (t === 'b2c') return 'clienți B2C';
  return 'toți clienții';
};

const load = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchPromotionLanding(route.params.slug);

    promotion.value = data.promotion;
    products.data = data.products?.data || [];
    products.meta = data.products?.meta || null;
  } catch (e) {
    console.error(e);
    error.value = 'Promoția nu a putut fi încărcată.';
  } finally {
    loading.value = false;
  }
};

onMounted(load);
</script>
