<template>
  <!-- Hero de sus -->
  <div class="bg-light border-bottom py-4 mb-4">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-7">
          <h1 class="h3 mb-2">Platformă e-commerce B2B / B2C (demo)</h1>
          <p class="text-muted mb-3">
            Exemplu de homepage cu secțiuni de promoții, produse noi și recomandări,
            gândit pentru clienți finali și parteneri B2B.
          </p>
          <div class="d-flex flex-wrap gap-2">
            <RouterLink to="/promotii" class="btn btn-primary btn-sm">
              Vezi promoțiile active
            </RouterLink>
            <RouterLink to="/devino-partener" class="btn btn-outline-primary btn-sm">
              Devino partener B2B
            </RouterLink>
            <RouterLink to="/reprezentanti-vanzari" class="btn btn-outline-secondary btn-sm">
              Găsește un reprezentant
            </RouterLink>
          </div>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0 small">
          <div class="border rounded p-3 bg-white shadow-sm">
            <div class="fw-semibold mb-1">Segmentare demo</div>
            <ul class="mb-0 text-muted ps-3">
              <li>Clienți B2C – retail, persoane fizice;</li>
              <li>Clienți B2B – companii cu termene de plată și limită de credit;</li>
              <li>Agenți / directori – pot lucra în numele clienților.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container mb-4">
    <!-- Mesaje globale -->
    <div v-if="error" class="alert alert-danger mb-3">
      {{ error }}
    </div>
    <div v-if="loading" class="alert alert-info mb-3">
      Se încarcă conținutul homepage-ului...
    </div>

    <!-- Secțiune promoții -->
    <section class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 mb-0">Promoții</h2>
        <RouterLink to="/promotii" class="btn btn-link btn-sm text-decoration-none">
          Vezi toate promoțiile →
        </RouterLink>
      </div>

      <div v-if="!homePromotions.length && !loading" class="text-muted small">
        Momentan nu există promoții active.
      </div>

      <div v-else class="row g-3">
        <div
          v-for="promo in homePromotions"
          :key="promo.slug || promo.id"
          class="col-md-4"
        >
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <span
                v-if="promo.badge"
                class="badge bg-danger mb-2"
              >
                {{ promo.badge }}
              </span>
              <h3 class="h6">
                {{ promo.title || 'Campanie promoțională' }}
              </h3>
              <p class="small text-muted mb-2">
                {{ promo.teaser || promo.short_description }}
              </p>
              <p v-if="promo.period" class="small mb-1">
                <strong>Perioadă:</strong>
                {{ promo.period }}
              </p>
              <p v-if="promo.segmentLabel" class="small text-muted mb-3">
                Segment: {{ promo.segmentLabel }}
              </p>
              <RouterLink
                v-if="promo.slug"
                :to="`/promotii/${promo.slug}`"
                class="btn btn-outline-primary btn-sm"
              >
                Detalii promoție
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Secțiune produse noi -->
    <section class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 mb-0">Produse noi</h2>
        <RouterLink to="/noutati" class="btn btn-link btn-sm text-decoration-none">
          Vezi toate noutățile →
        </RouterLink>
      </div>

      <div v-if="!newProducts.length && !loading" class="text-muted small">
        Momentan nu există produse noi.
      </div>

      <div v-else class="row g-3">
        <div
          v-for="product in newProducts"
          :key="product.slug || product.id"
          class="col-md-3 col-sm-6"
        >
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <div class="small text-muted mb-1">
                {{ product.category?.name || product.category || '—' }}
              </div>
              <h3 class="h6 mb-1">{{ product.name }}</h3>
              <div class="small text-muted mb-2">
                {{ product.internal_code || product.code }}
              </div>
              <div class="mt-auto">
                <div class="fw-semibold mb-1">
                  {{ formatPrice(product.price ?? product.final_price ?? product.list_price) }}
                  <span v-if="product.price || product.final_price || product.list_price">
                    RON
                  </span>
                </div>
                <RouterLink
                  v-if="product.slug"
                  :to="`/produs/${product.slug}`"
                  class="btn btn-outline-secondary btn-sm"
                >
                  Detalii produs
                </RouterLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Secțiune recomandate -->
    <section class="mb-5">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 mb-0">Produse recomandate</h2>
        <RouterLink to="/reduceri" class="btn btn-link btn-sm text-decoration-none">
          Vezi toate produsele în promoție →
        </RouterLink>
      </div>

      <div v-if="!recommendedProducts.length && !loading" class="text-muted small">
        Momentan nu există produse recomandate.
      </div>

      <div v-else class="row g-3">
        <div
          v-for="product in recommendedProducts"
          :key="product.slug || product.id"
          class="col-md-3 col-sm-6"
        >
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="small text-muted">
                  {{ product.category?.name || product.category || '—' }}
                </div>
                <span
                  v-if="product.hasDiscount || product.discountPercent"
                  class="badge bg-success"
                >
                  -{{ product.discountPercent ?? product.discount_percent ?? 0 }}%
                </span>
              </div>
              <h3 class="h6 mb-1">{{ product.name }}</h3>
              <div class="small text-muted mb-2">
                {{ product.internal_code || product.code }}
              </div>
              <div class="mt-auto">
                <!-- cu discount -->
                <div
                  class="small text-muted"
                  v-if="product.hasDiscount || product.promoPrice || product.promo_price"
                >
                  <span class="text-decoration-line-through me-1">
                    {{ formatPrice(product.price ?? product.list_price) }}
                  </span>
                  <span class="fw-semibold">
                    {{ formatPrice(product.promoPrice ?? product.promo_price ?? product.final_price) }}
                    RON
                  </span>
                </div>
                <!-- fără discount -->
                <div
                  class="fw-semibold mb-1"
                  v-else
                >
                  {{ formatPrice(product.price ?? product.final_price ?? product.list_price) }}
                  <span v-if="product.price || product.final_price || product.list_price">
                    RON
                  </span>
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
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { fetchHomeData } from '@/services/catalog';

const loading = ref(false);
const error = ref('');

// colecțiile folosite în template
const homePromotions = ref([]);        // promoțiile de pe home
const newProducts = ref([]);           // produse noi
const recommendedProducts = ref([]);   // produse recomandate

const formatPrice = (value) => {
  if (value == null) return '-';
  const num = Number(value);
  if (Number.isNaN(num)) return String(value);
  return num.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const loadHome = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchHomeData();

    // adaptează la structura reală a răspunsului /api/home
    homePromotions.value = data.promotions ?? data.home_promotions ?? [];
    newProducts.value = data.new_products ?? [];
    recommendedProducts.value = data.recommended_products ?? [];
  } catch (e) {
    console.error('Home data error', e);
    error.value = 'Nu s-au putut încărca datele pentru homepage.';
  } finally {
    loading.value = false;
  }
};

onMounted(loadHome);
</script>
`
::contentReference[oaicite:0]{index=0}
