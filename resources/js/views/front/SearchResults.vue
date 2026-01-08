<template>
  <div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Rezultate căutare</h1>
        <p class="small text-muted mb-0">
          Căutare după: <strong>"{{ query || '' }}"</strong>
        </p>
      </div>
      <div v-if="total > 0" class="small text-muted mt-2 mt-md-0">
        {{ total }} rezultat{{ total === 1 ? '' : 'e' }}
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-2" @submit.prevent="handleSubmit">
          <div class="col-md-10">
            <input
              v-model="searchInput"
              type="text"
              class="form-control form-control-sm"
              placeholder="Caută după denumire, cod intern, cod de bare..."
            />
          </div>
          <div class="col-md-2 text-md-end">
            <button class="btn btn-primary btn-sm w-100" type="submit">
              Caută
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger py-2 mb-3">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border spinner-border-sm" role="status" />
      <div class="small text-muted mt-2">Se caută produse...</div>
    </div>

    <div v-else>
      <div v-if="!query" class="alert alert-info py-2">
        Introdu un termen de căutare pentru a vedea rezultate.
      </div>

      <div v-else-if="products.length === 0" class="alert alert-warning py-2">
        Nu am găsit produse pentru termenul introdus.
      </div>

      <div v-else class="row g-3">
        <div
          v-for="product in products"
          :key="product.id"
          class="col-md-3 col-sm-6"
        >
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <div class="small text-muted mb-1">
                {{ product.category || 'Categorie nespecificată' }}
              </div>
              <h2 class="h6 mb-1">{{ product.name }}</h2>
              <div class="small text-muted mb-2">{{ product.code }}</div>
              <div class="mt-auto">
                <div v-if="product.promoPrice || product.promo_price || product.price">
                  <span v-if="product.list_price && product.list_price > (product.promoPrice || product.promo_price || product.price)" class="text-decoration-line-through text-muted small d-block">
                    {{ formatPrice(product.list_price) }}
                  </span>
                  <div class="fw-semibold mb-1" :class="{'text-danger': (product.list_price > (product.promoPrice || product.promo_price || product.price))}">
                    {{ formatPrice(product.promoPrice || product.promo_price || product.price) }} RON
                  </div>
                </div>
                <div v-else class="fw-semibold mb-1">
                  {{ formatPrice(product.price || 0) }} RON
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
      </div>

      <!-- Paginare minimală (dacă vrei să o folosești mai târziu) -->
      <nav
        v-if="meta.last_page > 1"
        class="mt-3"
        aria-label="Pagini rezultate căutare"
      >
        <ul class="pagination pagination-sm mb-0">
          <li
            class="page-item"
            :class="{ disabled: meta.current_page <= 1 }"
          >
            <button
              class="page-link"
              type="button"
              @click="changePage(meta.current_page - 1)"
            >
              «
            </button>
          </li>

          <li
            v-for="page in pages"
            :key="page"
            class="page-item"
            :class="{ active: page === meta.current_page }"
          >
            <button
              class="page-link"
              type="button"
              @click="changePage(page)"
            >
              {{ page }}
            </button>
          </li>

          <li
            class="page-item"
            :class="{ disabled: meta.current_page >= meta.last_page }"
          >
            <button
              class="page-link"
              type="button"
              @click="changePage(meta.current_page + 1)"
            >
              »
            </button>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { searchProducts } from '@/services/catalog';

const route = useRoute();
const router = useRouter();

const query = ref(route.query.q || '');
const searchInput = ref(query.value);

const products = ref([]);
const loading = ref(false);
const error = ref('');

const meta = ref({
  total: 0,
  current_page: 1,
  last_page: 1,
});

const total = computed(() => meta.value.total);

const pages = computed(() => {
  const last = meta.value.last_page || 1;
  const current = meta.value.current_page || 1;

  const result = [];
  const start = Math.max(1, current - 2);
  const end = Math.min(last, current + 2);

  for (let p = start; p <= end; p += 1) {
    result.push(p);
  }
  return result;
});

const loadResults = async () => {
  const q = query.value?.toString().trim();

  if (!q) {
    products.value = [];
    meta.value = { total: 0, current_page: 1, last_page: 1 };
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const data = await searchProducts({
      q,
      page: meta.value.current_page,
    });

    products.value = data.data ?? [];
    meta.value = data.meta ?? {
      total: 0,
      current_page: 1,
      last_page: 1,
    };
  } catch (e) {
    console.error(e);
    error.value = 'A apărut o eroare la căutare.';
  } finally {
    loading.value = false;
  }
};

const handleSubmit = () => {
  query.value = searchInput.value;
  meta.value.current_page = 1;
  router.replace({
    name: 'search-results',
    query: { q: query.value || undefined },
  });
  loadResults();
};

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return;
  meta.value.current_page = page;
  loadResults();
};

const formatPrice = (value) => {
  if (typeof value !== 'number') return value;
  return value.toLocaleString('ro-RO', { minimumFractionDigits: 2 });
};

// inițial
onMounted(loadResults);

// reîncarcă dacă se schimbă q în URL
watch(
  () => route.query.q,
  (newQ) => {
    query.value = newQ || '';
    searchInput.value = query.value;
    meta.value.current_page = 1;
    loadResults();
  }
);
</script>
