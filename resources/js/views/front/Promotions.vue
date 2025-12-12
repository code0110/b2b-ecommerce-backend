<template>
  <div class="py-3">
    <div class="container">
      <h1 class="h4 mb-2">Promoții</h1>
      <p class="text-muted small mb-3">
        Promoții active și campanii în derulare pentru clienți B2B și B2C.
      </p>

      <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <select
          v-model="filters.customer_type"
          class="form-select form-select-sm"
          style="max-width: 220px"
          @change="reload"
        >
          <option value="">Toți clienții</option>
          <option value="b2c">Doar B2C</option>
          <option value="b2b">Doar B2B</option>
        </select>

        <select
          v-model="filters.type"
          class="form-select form-select-sm"
          style="max-width: 220px"
          @change="reload"
        >
          <option value="">Toate tipurile</option>
          <option value="discount">Discount</option>
          <option value="free">Gratuitate</option>
        </select>
      </div>

      <div v-if="loading" class="text-muted small py-3">
        Se încarcă promoțiile...
      </div>
      <div v-else-if="error" class="alert alert-danger small py-2">
        {{ error }}
      </div>

      <div v-else class="row g-3">
        <div
          v-for="promo in promotions.data"
          :key="promo.id"
          class="col-md-4"
        >
          <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
              <span class="badge bg-danger mb-2">Promoție</span>
              <h2 class="h6 mb-1">
                {{ promo.name || promo.title }}
              </h2>
              <p class="small text-muted mb-2">
                {{ promo.short_description || promo.description }}
              </p>
              <p class="small mb-2">
                <strong>Perioadă:</strong>
                {{ formatPeriod(promo) }}
              </p>
              <p class="small text-muted mb-3">
                Segment: {{ formatCustomerType(promo.customer_type) }}
              </p>
              <RouterLink
                :to="`/promotii/${promo.slug}`"
                class="btn btn-outline-primary btn-sm mt-auto"
              >
                Detalii promoție
              </RouterLink>
            </div>
          </div>
        </div>

        <div v-if="!promotions.data.length" class="col-12">
          <div class="alert alert-light border small mb-0">
            Nu există promoții pentru filtrele selectate.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { fetchPromotionsList } from '@/services/promotions';

const loading = ref(false);
const error = ref('');
const promotions = reactive({
  data: [],
  meta: null,
});

const filters = reactive({
  customer_type: '',
  type: '',
});

const formatPeriod = (promo) => {
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
    const data = await fetchPromotionsList({
      customer_type: filters.customer_type || undefined,
      type: filters.type || undefined,
    });

    promotions.data = data.data || [];
    promotions.meta = data.meta || null;
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca promoțiile.';
  } finally {
    loading.value = false;
  }
};

const reload = () => {
  load();
};

onMounted(load);
</script>
