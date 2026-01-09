<template>
  <div>
    <div class="dd-page-header py-3 mb-3">
      <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2">
          <div>
            <h1 class="h4 mb-1">Rezultate căutare</h1>
            <p class="small text-muted mb-0">
              Căutare după: <strong>"{{ query || '' }}"</strong>
            </p>
          </div>
          <div v-if="total > 0" class="small text-muted">
            {{ total }} rezultat{{ total === 1 ? '' : 'e' }}
          </div>
        </div>

        <form class="mt-3" @submit.prevent="handleSubmit">
          <div class="input-group">
            <input
              v-model="searchInput"
              type="text"
              class="form-control"
              placeholder="Caută după denumire, cod intern, cod de bare..."
            />
            <button class="btn btn-orange px-4" type="submit">
              <i class="bi bi-search me-2"></i>Caută
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="container pb-4">

      <div v-if="error" class="alert alert-danger py-2 mb-3">
        {{ error }}
      </div>

      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border spinner-border-sm text-orange" role="status" />
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
            class="col-xl-3 col-lg-3 col-md-4 col-sm-6 col-6"
          >
            <div class="card h-100 bg-white dd-product-card position-relative">
              <div class="ratio ratio-4x3 bg-white border-bottom position-relative">
                <img
                  v-if="product.main_image_url || product.image_url"
                  :src="product.main_image_url || product.image_url"
                  :alt="product.name"
                  class="w-100 h-100 object-fit-contain p-3"
                  loading="lazy"
                >
                <div v-else class="d-flex align-items-center justify-content-center text-muted small w-100 h-100 bg-light">
                  Fără imagine
                </div>

                <span
                  v-if="hasDiscount(product)"
                  class="position-absolute top-0 start-0 m-2 badge bg-danger rounded-pill shadow-sm"
                >
                  Promo
                </span>
              </div>

              <div class="card-body d-flex flex-column">
                <div class="small text-muted mb-1">
                  {{ product.category || 'Categorie' }}
                </div>
                <h2 class="h6 mb-1 fw-bold text-truncate-2">
                  <RouterLink
                    v-if="product.slug"
                    :to="`/produs/${product.slug}`"
                    class="text-decoration-none text-dark stretched-link"
                  >
                    {{ product.name }}
                  </RouterLink>
                  <span v-else>
                    {{ product.name }}
                  </span>
                </h2>
                <div class="small text-muted mb-2">
                  {{ product.code || product.internal_code || product.sku || '' }}
                </div>

                <div class="mt-auto pt-2 border-top">
                  <div v-if="getUnitPrice(product) !== null">
                    <span
                      v-if="getListPrice(product) !== null && getListPrice(product) > getUnitPrice(product)"
                      class="text-decoration-line-through text-muted small d-block"
                    >
                      {{ formatPrice(getListPrice(product)) }}
                    </span>
                    <div
                      class="fw-semibold mb-2"
                      :class="getListPrice(product) !== null && getListPrice(product) > getUnitPrice(product) ? 'text-danger' : ''"
                    >
                      {{ formatPrice(getUnitPrice(product)) }} RON
                    </div>
                  </div>
                  <div v-else class="text-muted small mb-2">
                    Preț indisponibil
                  </div>

                  <div class="d-grid gap-2">
                    <button
                      type="button"
                      class="btn btn-orange btn-sm"
                      :disabled="!product.id || addLoading === product.id"
                      @click.prevent="addToCart(product)"
                    >
                      <span v-if="addLoading === product.id" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                      <i v-else class="bi bi-cart-plus me-2"></i>
                      Adaugă în coș
                    </button>
                    <RouterLink
                      v-if="product.slug"
                      :to="`/produs/${product.slug}`"
                      class="btn btn-outline-secondary btn-sm"
                    >
                      Detalii
                    </RouterLink>
                  </div>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { searchProducts } from '@/services/catalog';
import { useCartStore } from '@/store/cart';
import { addCartItem } from '@/services/cart';
import { useToast } from 'vue-toastification';

const route = useRoute();
const router = useRouter();
const cartStore = useCartStore();
const toast = useToast();

const query = ref(route.query.q || '');
const searchInput = ref(query.value);

const products = ref([]);
const loading = ref(false);
const error = ref('');
const addLoading = ref(null);

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

const getUnitPrice = (product) => {
  const price = product?.promoPrice ?? product?.promo_price ?? product?.price ?? null;
  const num = Number(price);
  return Number.isFinite(num) ? num : null;
};

const getListPrice = (product) => {
  const price = product?.list_price ?? null;
  const num = Number(price);
  return Number.isFinite(num) ? num : null;
};

const hasDiscount = (product) => {
  const list = getListPrice(product);
  const unit = getUnitPrice(product);
  return list !== null && unit !== null && list > unit;
};

const formatPrice = (value) => {
  const num = Number(value);
  if (!Number.isFinite(num)) return '';
  return num.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const addToCart = async (product) => {
  if (!product?.id) return;
  addLoading.value = product.id;
  try {
    const cart = await addCartItem({
      product_id: product.id,
      quantity: 1,
      unit: 'buc',
    });
    if (cart) {
      cartStore.setCartData(cart);
    } else {
      await cartStore.fetchCart();
    }
    toast.success('Produs adăugat în coș');
  } catch (e) {
    toast.error('Nu s-a putut adăuga produsul în coș');
  } finally {
    addLoading.value = null;
  }
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
