<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Promoții active și în curând</h1>
    </div>

    <!-- Mesaje -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>
    <div v-if="loading" class="alert alert-info">
      Se încarcă promoțiile...
    </div>

    <!-- Filtre simple -->
    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-3" @submit.prevent="reloadPromotions">
          <div class="col-md-4">
            <label class="form-label small">Căutare</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="Nume promoție..."
            />
          </div>
          <div class="col-md-3">
            <label class="form-label small">Tip client</label>
            <select
              v-model="filters.customer_type"
              class="form-select form-select-sm"
            >
              <option value="">Toți</option>
              <option value="b2c">B2C</option>
              <option value="b2b">B2B</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Status</label>
            <select
              v-model="filters.status"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="active">Active</option>
              <option value="upcoming">În curând</option>
              <option value="expired">Expirate</option>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
              Aplică filtre
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Listă promoții -->
    <div v-if="!loading && !promotions.length" class="text-muted">
      Nu există promoții pentru filtrele curente.
    </div>

    <div class="row g-3">
      <div
        v-for="promo in promotions"
        :key="promo.slug || promo.id"
        class="col-md-4"
      >
        <div class="card h-100 shadow-sm">
          <div class="card-body d-flex flex-column">
            <div class="mb-2">
              <span
                v-if="promo.status"
                class="badge"
                :class="badgeClass(promo.status)"
              >
                {{ statusLabel(promo.status) }}
              </span>
              <span
                v-if="promo.customer_type"
                class="badge bg-light text-muted border ms-1"
              >
                {{ promo.customer_type.toUpperCase() }}
              </span>
            </div>
            <h2 class="h6 mb-1">
              {{ promo.name || promo.title }}
            </h2>
            <p class="small text-muted mb-2">
              {{ promo.short_description || promo.teaser }}
            </p>
            <p class="small mb-2">
              <strong>Perioadă:</strong>
              <span v-if="promo.start_at || promo.end_at">
                {{ promo.start_at }} – {{ promo.end_at || 'nelimitat' }}
              </span>
              <span v-else>
                Fără perioadă definită
              </span>
            </p>
            <p v-if="promo.logged_in_only" class="small text-muted mb-3">
              Disponibilă doar pentru utilizatori logați.
            </p>
            <div class="mt-auto">
              <RouterLink
                v-if="promo.slug"
                :to="`/promotii/${promo.slug}`"
                class="btn btn-outline-primary btn-sm"
              >
                Vezi detalii
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Paginare simplă -->
    <div
      v-if="meta.last_page > 1"
      class="d-flex justify-content-between align-items-center mt-3"
    >
      <div class="text-muted small">
        Pagina {{ meta.current_page }} din {{ meta.last_page }} –
        {{ meta.total }} promoții
      </div>
      <div class="btn-group">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="meta.current_page === 1"
          @click="changePage(meta.current_page - 1)"
        >
          « Anterioară
        </button>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="meta.current_page === meta.last_page"
          @click="changePage(meta.current_page + 1)"
        >
          Următoarea »
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { fetchPromotions } from '@/services/promotions';

const loading = ref(false);
const error = ref('');

const promotions = ref([]);
const meta = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

const filters = ref({
  search: '',
  customer_type: '',
  status: '',
});

const loadPromotions = async (page = 1) => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      page,
      search: filters.value.search || undefined,
      customer_type: filters.value.customer_type || undefined,
      status: filters.value.status || undefined,
    };

    const { items, meta: m } = await fetchPromotions(params);
    promotions.value = items;
    meta.value = m;
  } catch (e) {
    console.error('Promotions load error', e);
    error.value =
      e.response?.data?.message || 'Nu s-au putut încărca promoțiile.';
  } finally {
    loading.value = false;
  }
};

const reloadPromotions = () => {
  loadPromotions(1);
};

const changePage = (page) => {
  loadPromotions(page);
};

const badgeClass = (status) => {
  switch (status) {
    case 'active':
      return 'bg-success';
    case 'upcoming':
      return 'bg-info';
    case 'expired':
      return 'bg-secondary';
    default:
      return 'bg-light text-muted';
  }
};

const statusLabel = (status) => {
  switch (status) {
    case 'active':
      return 'Activă';
    case 'upcoming':
      return 'În curând';
    case 'expired':
      return 'Expirată';
    default:
      return status;
  }
};

onMounted(() => {
  loadPromotions(1);
});
</script>
