<template>
  <div>
    <div v-if="promotion" class="dd-page-header py-3 mb-3">
      <div class="container">
        <div class="row align-items-center g-3">
          <div class="col-md-7">
            <h1 class="h4 mb-1">{{ promotion.name }}</h1>
            <p class="text-muted mb-2">
              {{ promotion.short_description }}
            </p>
            <p class="small mb-1" v-if="promotion.period">
              <strong>Perioadă:</strong> {{ promotion.period }}
            </p>
            <p class="small text-muted mb-3">
              Segment: {{ promotion.segmentLabel }}
            </p>
            <RouterLink
              to="/promotii"
              class="btn btn-outline-secondary btn-sm"
            >
              ← Înapoi la lista de promoții
            </RouterLink>
          </div>
          <div class="col-md-5">
            <div v-if="promotion.hero_image" class="ratio ratio-16x9 bg-light rounded overflow-hidden">
              <img
                :src="promotion.hero_image"
                :alt="promotion.name"
                class="w-100 h-100 object-fit-cover"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container pb-4">
      <div v-if="error" class="alert alert-danger py-2 mb-3">
        {{ error }}
      </div>

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border spinner-border-sm text-orange" role="status" />
        <div class="small text-muted mt-2">Se încarcă promoția...</div>
      </div>

      <div v-else-if="promotion">

      <!-- Descriere detaliată -->
      <div
        v-if="promotion.description"
        class="card mb-4"
      >
        <div class="card-body">
          <h2 class="h6 mb-3">Detalii campanie</h2>
          <div
            class="small"
            v-html="promotion.description"
          />
        </div>
      </div>

      <!-- Produse incluse în promoție -->
      <div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h6 mb-0">Produse incluse în promoție</h2>
          <span class="small text-muted">
            {{ products.length }} produs{{ products.length === 1 ? '' : 'e' }}
          </span>
        </div>

        <div v-if="products.length === 0" class="alert alert-info py-2">
          <span v-if="promotion.applies_to === 'all'">
            Această promoție se aplică la nivelul întregului coș sau unei game extinse, conform condițiilor.
          </span>
          <span v-else>
            Nu sunt produse asociate acestei promoții în acest moment.
          </span>
        </div>

        <div class="row g-3">
          <div
            v-for="product in products"
            :key="product.id"
            class="col-md-3 col-sm-6"
          >
            <div class="card h-100 dd-product-card">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <div class="small text-muted">
                    {{ product.category || 'Categorie nespecificată' }}
                  </div>
                  <span
                    v-if="product.is_new"
                    class="badge bg-success"
                  >
                    Nou
                  </span>
                </div>
                <h3 class="h6 mb-1 fw-bold line-clamp-2">{{ product.name }}</h3>
                <div class="small text-muted mb-2">{{ product.code }}</div>
                <div class="mt-auto">
                  <div class="mb-1">
                    <span v-if="product.list_price && product.list_price > (product.promo_price || product.price)" class="text-decoration-line-through text-muted small me-2">
                      {{ formatPrice(product.list_price) }}
                    </span>
                    <span :class="(product.list_price > (product.promo_price || product.price)) ? 'text-danger fw-bold' : 'fw-bold'">
                      {{ formatPrice(product.promo_price || product.price) }} RON
                    </span>
                  </div>
                  <RouterLink
                    :to="`/produs/${product.slug}`"
                    class="btn btn-orange btn-sm w-100"
                  >
                    Detalii produs
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

      <div v-else-if="!loading" class="alert alert-warning py-2">
        Promoția nu a fost găsită.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchPromotionDetails } from '@/services/promotions';

const route = useRoute();
const router = useRouter();

const promotion = ref(null);
const products = ref([]);

const loading = ref(false);
const error = ref('');

const loadPromotion = async () => {
  const slug = route.params.slug;

  if (!slug) {
    router.replace({ name: 'promotions' });
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const data = await fetchPromotionDetails(slug);
    promotion.value = data.promotion ?? null;
    products.value = data.products ?? [];
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-a putut încărca promoția.';
  } finally {
    loading.value = false;
  }
};

const formatPrice = (value) => {
  if (typeof value !== 'number') return value;
  return value.toLocaleString('ro-RO', { minimumFractionDigits: 2 });
};

onMounted(loadPromotion);

// reîncarcă dacă se schimbă slug-ul
watch(
  () => route.params.slug,
  () => loadPromotion()
);
</script>
