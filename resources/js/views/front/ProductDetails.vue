<template>
  <div class="container py-4" v-if="!loading && product">
    <!-- Breadcrumbs simple -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <RouterLink to="/">Acasă</RouterLink>
        </li>
        <li class="breadcrumb-item">
          <RouterLink to="/produse">Catalog</RouterLink>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
          {{ product.name }}
        </li>
      </ol>
    </nav>

    <div class="row">
      <!-- Imagine / galerie -->
      <div class="col-md-6 mb-3">
        <div
          class="border rounded p-3 h-100 d-flex align-items-center justify-content-center bg-white"
          style="min-height: 280px;"
        >
          <span class="text-muted small">
            [ Galerie imagini – de integrat ulterior cu câmpurile reale ]
          </span>
        </div>
      </div>

      <!-- Detalii produs + buton coș -->
      <div class="col-md-6 mb-3">
        <h1 class="h4 mb-1">{{ product.name }}</h1>

        <p class="small text-muted mb-1">
          Cod intern: {{ product.internal_code || product.code || '-' }}
        </p>

        <p class="small text-muted mb-2" v-if="product.brand">
          Brand: <strong>{{ product.brand.name || product.brand }}</strong>
        </p>

        <!-- Preț -->
        <div class="mb-3">
          <span class="h5 me-2">
            {{ displayPrice }} RON
          </span>
          <span class="badge bg-success" v-if="hasPromo">
            Promoție
          </span>
        </div>

        <!-- Cantitate -->
        <div class="mb-3">
          <label class="form-label small">Cantitate</label>
          <div class="input-group input-group-sm" style="max-width: 180px;">
            <button
              class="btn btn-outline-secondary"
              type="button"
              @click="decreaseQty"
            >
              -
            </button>
            <input
              type="number"
              class="form-control text-center"
              v-model.number="quantity"
              min="1"
            />
            <button
              class="btn btn-outline-secondary"
              type="button"
              @click="increaseQty"
            >
              +
            </button>
          </div>
        </div>

        <!-- BUTONUL DE COȘ – aici era problema: fără @click -->
        <div class="d-flex flex-wrap gap-2 mb-3">
          <button
            type="button"
            class="btn btn-primary btn-sm"
            :disabled="addLoading"
            @click="addToCart"
          >
            <span
              v-if="addLoading"
              class="spinner-border spinner-border-sm me-1"
            />
            Adaugă în coș
          </button>

          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
          >
            Adaugă la favorite
          </button>
        </div>

        <!-- Mesaje după adăugare -->
        <div v-if="addMessage" class="alert alert-success py-2 px-3 small">
          {{ addMessage }}
        </div>
        <div v-if="addError" class="alert alert-danger py-2 px-3 small">
          {{ addError }}
        </div>

        <hr />

        <!-- Info stoc simplificată -->
        <p class="small text-muted mb-1" v-if="product.stock_status">
          Disponibilitate: {{ product.stock_status }}
        </p>
        <p class="small text-muted mb-0" v-if="product.main_category">
          Categorie: {{ product.main_category.name }}
        </p>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-md-8">
        <h2 class="h6">Descriere produs</h2>
        <p class="small text-muted" v-if="product.short_description">
          {{ product.short_description }}
        </p>
        <div
          v-if="product.description"
          class="small"
          v-html="product.description"
        />
      </div>
    </div>
  </div>

  <!-- Loading -->
  <div class="container py-5" v-else-if="loading">
    <div class="text-center text-muted">
      Se încarcă detaliile produsului...
    </div>
  </div>

  <!-- Not found -->
  <div class="container py-5" v-else>
    <div class="alert alert-warning">
      Produsul nu a fost găsit.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { fetchProductBySlug } from '@/services/catalog';
import { addCartItem } from '@/services/cart';

const route = useRoute();

const loading = ref(false);
const product = ref(null);
const error = ref('');

// stare add-to-cart
const quantity = ref(1);
const addLoading = ref(false);
const addMessage = ref('');
const addError = ref('');

// încarcă produsul după slug din URL
const loadProduct = async () => {
  loading.value = true;
  error.value = '';
  addMessage.value = '';
  addError.value = '';

  try {
    const slug = route.params.slug;
    const data = await fetchProductBySlug(slug);

    // backend-ul poate întoarce direct produsul sau { product: {...} }
    product.value = data.product ?? data;
  } catch (e) {
    console.error('Product load error', e);
    error.value = 'Nu s-au putut încărca detaliile produsului.';
  } finally {
    loading.value = false;
  }
};

onMounted(loadProduct);

watch(
  () => route.params.slug,
  () => {
    loadProduct();
  }
);

// calcul preț pentru afișare
const basePrice = computed(() => {
  if (!product.value) return 0;

  const p = product.value;

  return (
    p.price_override ??
    p.list_price ??
    p.final_price ??
    p.price ??
    0
  );
});

const promoPrice = computed(() => {
  if (!product.value) return null;

  const p = product.value;

  return p.promo_price ?? null;
});

const hasPromo = computed(() => {
  if (!promoPrice.value) return false;
  return Number(promoPrice.value) < Number(basePrice.value);
});

const displayPrice = computed(() => {
  const val = hasPromo.value ? promoPrice.value : basePrice.value;
  const num = Number(val || 0);

  return num.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
});

// quantity controls
const increaseQty = () => {
  quantity.value += 1;
};

const decreaseQty = () => {
  if (quantity.value > 1) {
    quantity.value -= 1;
  }
};

// FUNCTIA APELATĂ DE BUTON
const addToCart = async () => {
  if (!product.value) return;

  addLoading.value = true;
  addMessage.value = '';
  addError.value = '';

  try {
    const payload = {
      product_id: product.value.id,
      quantity: Number(quantity.value) || 1,
      // dacă vei avea variante: product_variant_id: selectedVariantId.value || null,
    };

    const cartData = await addCartItem(payload);
    console.debug('Cart after add', cartData);

    addMessage.value = 'Produsul a fost adăugat în coș.';
  } catch (e) {
    console.error('Add to cart error', e);
    if (e.response?.data?.message) {
      addError.value = e.response.data.message;
    } else if (e.response?.status === 422) {
      addError.value = 'Date invalide pentru adăugarea în coș.';
    } else {
      addError.value = 'Nu s-a putut adăuga produsul în coș.';
    }
  } finally {
    addLoading.value = false;
  }
};
</script>
