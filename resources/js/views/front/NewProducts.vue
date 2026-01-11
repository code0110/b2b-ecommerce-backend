<template>
  <div>
    <div class="dd-page-header py-3 mb-3">
      <div class="container">
        <h1 class="h4 mb-1">Produse noi</h1>
        <p class="text-muted small mb-0">
          Listare produse introduse recent în platformă.
        </p>
      </div>
    </div>

    <div class="container pb-4">

      <div v-if="loading" class="text-muted small py-3">
        Se încarcă produsele...
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
          <div class="card h-100 dd-product-card">
<<<<<<< HEAD
            <div class="ratio ratio-4x3 bg-white border-bottom position-relative">
              <WishlistButton :product="p" />
              <CompareButton 
                :product="p" 
                custom-class="position-absolute top-0 end-0 me-2 mt-5 shadow-sm"
                :round="true"
              />
=======
            <div class="ratio ratio-4x3 bg-white border-bottom">
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
              <img
                v-if="p.main_image_url || p.image_url"
                :src="p.main_image_url || p.image_url"
                :alt="p.name"
                class="w-100 h-100 object-fit-contain p-3"
                loading="lazy"
              >
              <div v-else class="d-flex align-items-center justify-content-center text-muted small w-100 h-100 bg-light">
                Fără imagine
              </div>
            </div>
            <div class="card-body d-flex flex-column">
              <div class="small text-muted mb-1">
                {{ p.main_category?.name || 'Categorie' }}
              </div>
              <h2 class="h6 mb-1 fw-bold line-clamp-2">{{ p.name }}</h2>
              <div class="small text-muted mb-2">
                {{ p.code || p.sku }}
              </div>
              <div class="mt-auto">
                <div v-if="p.promoPrice || p.promo_price || p.price">
                  <span v-if="p.list_price && p.list_price > (p.promoPrice || p.promo_price || p.price)" class="text-decoration-line-through text-muted small d-block">
                    {{ formatPriceGlobal(p.list_price, p.vat_rate, p.vat_included) }}
                  </span>
                  <div class="fw-semibold mb-1" :class="{'text-danger': (p.list_price > (p.promoPrice || p.promo_price || p.price))}">
                    {{ formatPriceGlobal(p.promoPrice || p.promo_price || p.price, p.vat_rate, p.vat_included) }}
                  </div>
                </div>
                <div v-else class="fw-semibold mb-1">
                  {{ formatPriceGlobal(p.price || p.list_price || 0, p.vat_rate, p.vat_included) }}
                </div>
                <RouterLink
                  :to="`/produs/${p.slug}`"
                  class="btn btn-orange btn-sm w-100"
                >
                  Detalii
                </RouterLink>
              </div>
            </div>
          </div>
        </div>

        <div v-if="!products.data.length" class="col-12">
          <div class="alert alert-light border small mb-0">
            Nu există produse noi în acest moment.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { fetchNewProductsPage } from '@/services/catalog';
import WishlistButton from '@/components/common/WishlistButton.vue';
import CompareButton from '@/components/common/CompareButton.vue';
import { useAuthStore } from '@/store/auth';
import { usePrice } from '@/composables/usePrice';

const authStore = useAuthStore();
const { formatPrice: formatPriceGlobal } = usePrice();

const showNumericStock = computed(() => {
  const roles = (authStore.user?.roles || []).map(r => (r.slug || r.code || '').toLowerCase());
  return roles.some(r => ['admin', 'sales_agent', 'sales_director', 'operator', 'manager'].includes(r));
});

const loading = ref(false);
const error = ref('');
const products = reactive({
  data: [],
  meta: null,
});

const formatMoney = (value) =>
  (Number(value) || 0).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

const load = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchNewProductsPage();
    products.data = data.data || [];
    products.meta = data.meta || null;
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca produsele noi.';
  } finally {
    loading.value = false;
  }
};

onMounted(load);
</script>
