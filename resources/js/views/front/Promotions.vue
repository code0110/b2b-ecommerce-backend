<template>
  <div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Promoții active</h1>
        <p class="text-muted mb-0 small">
          Campanii promoționale pentru clienți B2B și B2C.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
        <select
          v-model="scope"
          class="form-select form-select-sm w-auto"
          @change="loadPromotions"
        >
          <option value="current">Active acum</option>
          <option value="upcoming">În curând</option>
          <option value="all">Toate</option>
        </select>

        <select
          v-model="customerType"
          class="form-select form-select-sm w-auto"
          @change="loadPromotions"
        >
          <option value="">B2B & B2C</option>
          <option value="b2c">Doar B2C</option>
          <option value="b2b">Doar B2B</option>
        </select>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger py-2 mb-3">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border spinner-border-sm" role="status" />
      <div class="small text-muted mt-2">Se încarcă promoțiile...</div>
    </div>

    <div v-else>
      <div v-if="promotions.length === 0" class="alert alert-info py-2">
        Nu există promoții pentru filtrarea selectată.
      </div>

      <div class="row g-3">
        <div
          v-for="promo in promotions"
          :key="promo.slug"
          class="col-md-4 col-sm-6"
        >
          <div class="card h-100 shadow-sm">
            <div
              v-if="promo.hero_image"
              class="ratio ratio-16x9"
            >
              <img
                :src="promo.hero_image"
                :alt="promo.name"
                class="card-img-top object-fit-cover"
              />
            </div>
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-danger">{{ promo.badge }}</span>
                <span class="badge bg-light text-muted border">
                  {{ promo.segmentLabel }}
                </span>
              </div>
              <h2 class="h6 mb-1">
                {{ promo.name }}
              </h2>
              <p class="small text-muted mb-2">
                {{ promo.short_description }}
              </p>
              <p v-if="promo.period" class="small mb-3">
                <strong>Perioadă:</strong> {{ promo.period }}
              </p>

              <div class="mt-auto d-flex gap-2">
                <RouterLink
                  :to="`/promotii/${promo.slug}`"
                  class="btn btn-outline-primary btn-sm flex-grow-1"
                >
                  Vezi detalii
                </RouterLink>
                <button
                  class="btn btn-primary btn-sm flex-grow-1"
                  @click="addPromoToCart(promo)"
                  :disabled="addingPromo === promo.id"
                >
                  <span v-if="addingPromo === promo.id" class="spinner-border spinner-border-sm me-1"></span>
                  Adaugă în coș
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Dacă vrei paginare mai târziu, aici e locul -->
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fetchPromotions } from '@/services/promotions';
import { addPromotionToCart } from '@/services/cart';
import { useToast } from 'vue-toastification';

const toast = useToast();
const promotions = ref([]);
const loading = ref(false);
const error = ref('');
const addingPromo = ref(null);

const scope = ref('current');       // current | upcoming | all
const customerType = ref('');       // '' | b2b | b2c

const loadPromotions = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchPromotions({
      scope: scope.value,
      customer_type: customerType.value || undefined,
    });

    promotions.value = data.data ?? [];
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca promoțiile.';
  } finally {
    loading.value = false;
  }
};

const addPromoToCart = async (promo) => {
  addingPromo.value = promo.id;
  try {
    await addPromotionToCart(promo.id);
    toast.success('Promoția a fost adăugată în coș!');
  } catch (e) {
    console.error(e);
    const msg = e.response?.data?.message || 'Nu s-a putut adăuga promoția în coș.';
    toast.error(msg);
  } finally {
    addingPromo.value = null;
  }
};

onMounted(loadPromotions);
</script>
