<template>
  <div class="container py-4" v-if="!loading && promotion">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <RouterLink to="/">Acasă</RouterLink>
        </li>
        <li class="breadcrumb-item">
          <RouterLink to="/promotii">Promoții</RouterLink>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
          {{ promotion.name || promotion.title }}
        </li>
      </ol>
    </nav>

    <!-- Header promoție -->
    <div class="row mb-4">
      <div class="col-md-8 mb-3 mb-md-0">
        <h1 class="h4 mb-2">
          {{ promotion.name || promotion.title }}
        </h1>
        <p v-if="promotion.short_description" class="text-muted mb-2">
          {{ promotion.short_description }}
        </p>
        <p class="small mb-1">
          <strong>Perioadă:</strong>
          <span v-if="promotion.start_at || promotion.end_at">
            {{ promotion.start_at }} – {{ promotion.end_at || 'nelimitat' }}
          </span>
          <span v-else>Fără perioadă definită</span>
        </p>
        <p class="small text-muted mb-1">
          <span v-if="promotion.customer_type">
            Tip client: {{ promotion.customer_type.toUpperCase() }}.
          </span>
          <span v-if="promotion.logged_in_only" class="ms-2">
            (Doar pentru utilizatori logați)
          </span>
        </p>
        <div
          v-if="promotion.conditions"
          class="small text-muted"
        >
          {{ promotion.conditions }}
        </div>
      </div>
      <div class="col-md-4">
        <!-- Imagine / banner promoție (simplificat) -->
        <div
          class="border rounded bg-light d-flex align-items-center justify-content-center"
          style="min-height: 180px;"
        >
          <span class="text-muted small">
            Banner promoție (hero_image / banner_image)
          </span>
        </div>
      </div>
    </div>

    <!-- Descriere detaliată (landing content) -->
    <div class="mb-4">
      <h2 class="h5 mb-2">Descriere campanie</h2>
      <p v-if="promotion.description" class="mb-0">
        {{ promotion.description }}
      </p>
      <p v-else class="text-muted small mb-0">
        Nu există o descriere detaliată configurată pentru această promoție.
      </p>
    </div>

    <!-- Produse incluse în promoție -->
    <section>
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 mb-0">Produse incluse în promoție</h2>
      </div>

      <div v-if="!products.length" class="text-muted small">
        Nu sunt produse listate explicit pentru această promoție sau promoția
        se aplică la nivel de categorie/brand.
      </div>

      <div class="row g-3">
        <div
          v-for="product in products"
          :key="product.slug || product.id"
          class="col-md-3 col-sm-6"
        >
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <div class="small text-muted mb-1">
                {{ product.brand?.name || product.brand || '—' }}
              </div>
              <h3 class="h6 mb-1">{{ product.name }}</h3>
              <div class="small text-muted mb-2">
                {{ product.internal_code || product.code }}
              </div>
              <div class="mt-auto">
                <div
                  class="small text-muted"
                  v-if="product.promo_price || product.promoPrice"
                >
                  <span class="text-decoration-line-through me-1">
                    {{ formatPrice(product.price ?? product.list_price) }}
                  </span>
                  <span class="fw-semibold">
                    {{ formatPrice(product.promo_price ?? product.promoPrice) }} RON
                  </span>
                </div>
                <div v-else class="fw-semibold mb-1">
                  {{ formatPrice(product.final_price ?? product.price ?? product.list_price) }} RON
                </div>
                <RouterLink
                  v-if="product.slug"
                  :to="`/produs/${product.slug}`"
                  class="btn btn-outline-primary btn-sm"
                >
                  Detalii produs
                </RouterLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- Loading / 404 -->
  <div v-else-if="loading" class="container py-4">
    <div class="alert alert-info">
      Se încarcă detaliile promoției...
    </div>
  </div>
  <div v-else class="container py-4">
    <div class="alert alert-warning">
      Promoția nu a fost găsită.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { fetchPromotionBySlug } from '@/services/promotions';

const route = useRoute();

const loading = ref(false);
const error = ref('');

const promotion = ref(null);
const products = ref([]);

const formatPrice = (value) => {
  if (value == null) return '-';
  const num = Number(value);
  if (Number.isNaN(num)) return String(value);
  return num.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const loadPromotion = async () => {
  loading.value = true;
  error.value = '';
  promotion.value = null;
  products.value = [];

  const slug = route.params.slug;

  try {
    const data = await fetchPromotionBySlug(slug);

    // adaptare la structura backend-ului
    promotion.value = data.promotion ?? data;
    products.value =
      data.products ??
      data.promotion_products ??
      data.items ??
      [];
  } catch (e) {
    console.error('Promotion load error', e);
    if (e.response?.status === 404) {
      error.value = 'Promoția nu a fost găsită.';
    } else {
      error.value =
        e.response?.data?.message ||
        'Nu s-au putut încărca detaliile promoției.';
    }
  } finally {
    loading.value = false;
  }
};

watch(
  () => route.params.slug,
  () => {
    loadPromotion();
  },
);

onMounted(() => {
  loadPromotion();
});
</script>
