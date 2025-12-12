<template>
  <div class="home-page">
    <!-- HERO -->
    <div class="bg-white border-bottom py-4 mb-4">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-7">
            <h1 class="h3 h2-md fw-semibold mb-2">
              Starter B2B materiale
            </h1>
            <p class="text-muted mb-3">
              Selectați o categorie sau utilizați meniul de sus pentru a explora
              catalogul de produse, promoțiile active și prețurile contractuale.
            </p>
            <div class="d-flex flex-wrap gap-2">
              <button
                type="button"
                class="btn btn-primary btn-sm"
                @click="openCatalog"
              >
                Deschide catalogul
              </button>
              <RouterLink
                :to="{ name: 'become-partner' }"
                class="btn btn-outline-primary btn-sm"
              >
                Devino partener B2B
              </RouterLink>
              <RouterLink
  :to="{ name: 'sales-representatives' }"
  class="btn btn-outline-secondary btn-sm"
>
  Găsește un reprezentant
</RouterLink>

            </div>
          </div>
          <div class="col-lg-5 mt-3 mt-lg-0">
            <div class="border rounded-4 p-3 bg-light small">
              <div class="fw-semibold mb-2">
                Scenarii acoperite de platformă (demo):
              </div>
              <ul class="mb-0 text-muted ps-3">
                <li>Comenzi B2C punctuale pentru proiecte mici.</li>
                <li>
                  Clienți B2B cu termene de plată, limite de credit și prețuri
                  contractuale.
                </li>
                <li>
                  Agenți / directori care plasează comenzi în numele
                  portofoliului de clienți.
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CONȚINUT -->
    <div class="container mb-5">
      <!-- Promoții -->
      <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 mb-0">Promoții active</h2>
          <RouterLink
            to="/promotii"
            class="btn btn-link btn-sm text-decoration-none"
          >
            Vezi toate promoțiile →
          </RouterLink>
        </div>

        <div v-if="loading" class="text-muted small py-3">
          Se încarcă datele pentru homepage...
        </div>
        <div v-else-if="error" class="alert alert-danger py-2 small mb-3">
          {{ error }}
        </div>

        <div v-else class="row g-3">
          <div
            v-for="promo in homePromotions"
            :key="promo.slug || promo.id"
            class="col-md-4"
          >
            <div class="card h-100 shadow-sm">
              <div class="card-body">
                <span class="badge bg-danger mb-2">
                  {{ promo.badge || 'Promoție' }}
                </span>
                <h3 class="h6 mb-1">
                  {{ promo.title }}
                </h3>
                <p class="small text-muted mb-2">
                  {{ promo.teaser || promo.short_description }}
                </p>
                <p class="small mb-2" v-if="promo.period || promo.start_at">
                  <strong>Perioadă:</strong>
                  {{ promo.period || formatPeriod(promo) }}
                </p>
                <RouterLink
                  :to="`/promotii/${promo.slug}`"
                  class="btn btn-outline-primary btn-sm"
                >
                  Detalii promoție
                </RouterLink>
              </div>
            </div>
          </div>

          <div v-if="!homePromotions.length" class="col-12">
            <div class="alert alert-light border small mb-0">
              Nu există promoții active în acest moment.
            </div>
          </div>
        </div>
      </section>

      <!-- Produse noi -->
      <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 mb-0">Produse noi</h2>
          <RouterLink
            to="/noutati"
            class="btn btn-link btn-sm text-decoration-none"
          >
            Vezi toate noutățile →
          </RouterLink>
        </div>

        <div class="row g-3">
          <div
            v-for="product in newProducts"
            :key="product.slug || product.id"
            class="col-md-3 col-sm-6"
          >
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <div class="small text-muted mb-1">
                  {{ product.category?.name || product.category || 'Categorie' }}
                </div>
                <h3 class="h6 mb-1">
                  {{ product.name }}
                </h3>
                <div class="small text-muted mb-2">
                  {{ product.code || product.sku }}
                </div>
                <div class="mt-auto">
                  <div class="fw-semibold mb-1">
                    {{ formatMoney(product.price || product.list_price || 0) }}
                    RON
                  </div>
                  <RouterLink
                    :to="`/produs/${product.slug}`"
                    class="btn btn-outline-secondary btn-sm"
                  >
                    Detalii produs
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>

          <div v-if="!newProducts.length" class="col-12">
            <div class="alert alert-light border small mb-0">
              Nu există produse noi în acest moment.
            </div>
          </div>
        </div>
      </section>

      <!-- Produse recomandate -->
      <section>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 mb-0">Produse recomandate</h2>
          <RouterLink
            to="/reduceri"
            class="btn btn-link btn-sm text-decoration-none"
          >
            Vezi produsele în promoție →
          </RouterLink>
        </div>

        <div class="row g-3">
          <div
            v-for="product in recommendedProducts"
            :key="product.slug || product.id"
            class="col-md-3 col-sm-6"
          >
            <div class="card h-100 border-0 shadow-sm">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <div class="small text-muted">
                    {{ product.category?.name || product.category || 'Categorie' }}
                  </div>
                  <span
                    v-if="product.discountPercent || product.discount_percent"
                    class="badge bg-success"
                  >
                    -{{ product.discountPercent || product.discount_percent }}%
                  </span>
                </div>
                <h3 class="h6 mb-1">
                  {{ product.name }}
                </h3>
                <div class="small text-muted mb-2">
                  {{ product.code || product.sku }}
                </div>
                <div class="mt-auto">
                  <div
                    v-if="product.promoPrice || product.promo_price"
                    class="small text-muted"
                  >
                    <span class="text-decoration-line-through me-1">
                      {{ formatMoney(product.price || product.list_price || 0) }}
                    </span>
                    <span class="fw-semibold">
                      {{ formatMoney(product.promoPrice || product.promo_price) }}
                      RON
                    </span>
                  </div>
                  <div v-else class="fw-semibold mb-1">
                    {{ formatMoney(product.price || product.list_price || 0) }}
                    RON
                  </div>
                  <RouterLink
                    :to="`/produs/${product.slug}`"
                    class="btn btn-outline-primary btn-sm"
                  >
                    Detalii produs
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>

          <div v-if="!recommendedProducts.length" class="col-12">
            <div class="alert alert-light border small mb-0">
              Nu există încă produse recomandate configurate pentru homepage.
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fetchHomeData } from '@/services/catalog';

const loading = ref(false);
const error = ref('');

const homePromotions = ref([]);
const newProducts = ref([]);
const recommendedProducts = ref([]);

const formatMoney = (value) => {
  return (Number(value) || 0).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const formatPeriod = (promo) => {
  if (!promo.start_at && !promo.end_at) return '';
  const start = promo.start_at
    ? new Date(promo.start_at).toLocaleDateString('ro-RO')
    : '–';
  const end = promo.end_at
    ? new Date(promo.end_at).toLocaleDateString('ro-RO')
    : '–';
  return `${start} – ${end}`;
};

const loadHome = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchHomeData();

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

const openCatalog = () => {
  // trimitem un eveniment global la FrontLayout
  window.dispatchEvent(new CustomEvent('mb2b:open-catalog'));
};

onMounted(loadHome);
</script>

<style scoped>
.home-page {
  min-height: 100%;
}
</style>
