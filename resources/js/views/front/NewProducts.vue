<template>
  <div class="py-3">
    <div class="container">
      <h1 class="h4 mb-2">Produse noi</h1>
      <p class="text-muted small mb-3">
        Listare produse introduse recent în platformă.
      </p>

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
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <div class="small text-muted mb-1">
                {{ p.main_category?.name || 'Categorie' }}
              </div>
              <h2 class="h6 mb-1">{{ p.name }}</h2>
              <div class="small text-muted mb-2">
                {{ p.code || p.sku }}
              </div>
              <div class="mt-auto">
                <div v-if="p.promoPrice || p.promo_price || p.price">
                  <span v-if="p.list_price && p.list_price > (p.promoPrice || p.promo_price || p.price)" class="text-decoration-line-through text-muted small d-block">
                    {{ formatMoney(p.list_price) }}
                  </span>
                  <div class="fw-semibold mb-1" :class="{'text-danger': (p.list_price > (p.promoPrice || p.promo_price || p.price))}">
                    {{ formatMoney(p.promoPrice || p.promo_price || p.price) }} RON
                  </div>
                </div>
                <div v-else class="fw-semibold mb-1">
                  {{ formatMoney(p.price || p.list_price || 0) }} RON
                </div>
                <RouterLink
                  :to="`/produs/${p.slug}`"
                  class="btn btn-outline-primary btn-sm"
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
import { ref, reactive, onMounted } from 'vue';
import { fetchNewProductsPage } from '@/services/catalog';

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
