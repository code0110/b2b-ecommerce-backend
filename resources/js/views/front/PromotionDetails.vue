<template>
  <div class="container py-4" v-if="!loading && product">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <RouterLink to="/">Acasă</RouterLink>
        </li>
        <li class="breadcrumb-item">
          <RouterLink to="/produse">Catalog</RouterLink>
        </li>
        <li
          v-if="product.main_category"
          class="breadcrumb-item"
        >
          <RouterLink
            :to="`/categorie/${product.main_category.slug}`"
          >
            {{ product.main_category.name }}
          </RouterLink>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
          {{ product.name }}
        </li>
      </ol>
    </nav>

    <!-- Header produs -->
    <div class="row mb-4">
      <div class="col-md-6 mb-3 mb-md-0">
        <div
          class="border rounded bg-light d-flex align-items-center justify-content-center"
          style="min-height: 280px;"
        >
          <span class="text-muted small">
            Galerie imagini (de integrat cu câmpurile din DB)
          </span>
        </div>
      </div>

      <div class="col-md-6">
        <h1 class="h4 mb-1">{{ product.name }}</h1>
        <p class="small text-muted mb-2">
          Cod intern: {{ product.internal_code || product.code || '—' }}
        </p>
        <p class="small text-muted mb-2" v-if="product.brand">
          Brand: <strong>{{ product.brand.name || product.brand }}</strong>
        </p>

        <!-- Preț & promo -->
        <div class="mb-3">
          <div v-if="hasPromo" class="small text-muted">
            <span class="text-decoration-line-through me-2">
              {{ formatPrice(basePrice) }} RON
            </span>
            <span class="fw-bold h5 mb-0">
              {{ formatPrice(promoPrice) }} RON
            </span>
          </div>
          <div v-else class="fw-bold h5 mb-0">
            {{ formatPrice(basePrice) }} RON
          </div>
          <div class="small text-muted mt-1">
            Prețurile pot fi diferite pentru clienți B2B în funcție de condițiile comerciale.
          </div>
        </div>

        <!-- Stoc -->
        <div class="mb-3 small">
          <strong>Disponibilitate:</strong>
          <span v-if="product.stock_status === 'in_stock'">În stoc</span>
          <span v-else-if="product.stock_status === 'on_order'">La comandă</span>
          <span v-else-if="product.stock_status === 'limited'">Stoc limitat</span>
          <span v-else>Disponibilitate necunoscută</span>

          <span v-if="product.stock_qty != null" class="ms-2 text-muted">
            ({{ product.stock_qty }} buc. disponibile)
          </span>
        </div>

        <!-- CTA: cantitate + butoane -->
        <div class="d-flex align-items-center mb-3">
          <label class="me-2 small mb-0">Cantitate</label>
          <input
            type="number"
            v-model.number="quantity"
            min="1"
            class="form-control form-control-sm"
            style="max-width: 100px;"
          />
        </div>

        <div class="d-flex flex-wrap gap-2 mb-2">
          <button
            type="button"
            class="btn btn-primary btn-sm"
            :disabled="adding"
            @click="addToCart"
          >
            <span v-if="adding">Se adaugă...</span>
            <span v-else>Adaugă în coș</span>
          </button>
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
          >
            Adaugă la favorite
          </button>
          <button
            type="button"
            class="btn btn-outline-primary btn-sm"
          >
            Solicită ofertă
          </button>
        </div>

        <div v-if="addMessage" class="small mt-1" :class="addMessageClass">
          {{ addMessage }}
        </div>

        <!-- Descriere scurtă -->
        <div class="mt-3">
          <h2 class="h6">Descriere scurtă</h2>
          <p class="small mb-0">
            {{ product.short_description || 'Nu există o descriere scurtă configurată.' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Taburi: descriere, atribute, documente -->
    <div class="row mb-4">
      <div class="col-md-8">
        <div class="card mb-3">
          <div class="card-body">
            <h2 class="h6 mb-2">Descriere detaliată</h2>
            <p class="small mb-0">
              {{ product.description || 'Nu există o descriere detaliată configurată.' }}
            </p>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-body">
            <h2 class="h6 mb-2">Atribute produs</h2>
            <div v-if="(product.attributes || []).length === 0" class="small text-muted">
              Nu sunt definite atribute pentru acest produs.
            </div>
            <table
              v-else
              class="table table-sm mb-0"
            >
              <tbody>
                <tr
                  v-for="attr in product.attributes"
                  :key="attr.id || attr.name"
                >
                  <th class="small" style="width: 40%;">
                    {{ attr.name }}
                  </th>
                  <td class="small">
                    {{ attr.value }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <h2 class="h6 mb-2">Documente atașate</h2>
            <div
              v-if="(product.documents || []).length === 0"
              class="small text-muted"
            >
              Nu există documente atașate acestui produs.
            </div>
            <ul
              v-else
              class="list-unstyled mb-0 small"
            >
              <li
                v-for="doc in product.documents"
                :key="doc.id"
                class="mb-1"
              >
                <span class="me-1">📄</span>
                <a
                  :href="doc.url"
                  target="_blank"
                  rel="noopener"
                >
                  {{ doc.name }}
                </a>
                <span class="text-muted ms-1">
                  ({{ doc.type_label || doc.type }})
                </span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Col dreapta: similare + complementare -->
      <div class="col-md-4">
        <div
          v-if="relatedProducts.length"
          class="card mb-3"
        >
          <div class="card-body">
            <h2 class="h6 mb-2">Produse similare</h2>
            <ul class="list-unstyled mb-0 small">
              <li
                v-for="p in relatedProducts"
                :key="p.slug || p.id"
                class="mb-1"
              >
                <RouterLink
                  :to="`/produs/${p.slug}`"
                  class="text-decoration-none"
                >
                  {{ p.name }}
                </RouterLink>
              </li>
            </ul>
          </div>
        </div>

        <div
          v-if="complementaryProducts.length"
          class="card"
        >
          <div class="card-body">
            <h2 class="h6 mb-2">Produse complementare</h2>
            <ul class="list-unstyled mb-0 small">
              <li
                v-for="p in complementaryProducts"
                :key="p.slug || p.id"
                class="mb-1"
              >
                <RouterLink
                  :to="`/produs/${p.slug}`"
                  class="text-decoration-none"
                >
                  {{ p.name }}
                </RouterLink>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading / 404 -->
  <div v-else-if="loading" class="container py-4">
    <div class="alert alert-info">
      Se încarcă detaliile produsului...
    </div>
  </div>
  <div v-else class="container py-4">
    <div class="alert alert-warning">
      Produsul nu a fost găsit.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { fetchProductBySlug } from '@/services/catalog';
import { addCartItem } from '@/services/cart';
import { setTitle, setMeta, setMetaProperty, setCanonical, setJsonLd } from '@/utils/seo';

const route = useRoute();

const loading = ref(false);
const error = ref('');
const product = ref(null);
const relatedProducts = ref([]);
const complementaryProducts = ref([]);

const quantity = ref(1);
const adding = ref(false);
const addMessage = ref('');
const addMessageType = ref('info');

const formatPrice = (value) => {
  if (value == null) return '-';
  const num = Number(value);
  if (Number.isNaN(num)) return String(value);
  return num.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const basePrice = computed(() => {
  if (!product.value) return 0;
  return (
    product.value.list_price ??
    product.value.price ??
    product.value.final_price ??
    0
  );
});

const promoPrice = computed(() => {
  if (!product.value) return null;
  return (
    product.value.promo_price ??
    product.value.final_price ??
    null
  );
});

const hasPromo = computed(() => {
  if (!product.value) return false;
  const base = Number(basePrice.value);
  const promo = Number(promoPrice.value);
  return promo && base && promo < base;
});

const addMessageClass = computed(() => {
  return addMessageType.value === 'error'
    ? 'text-danger'
    : 'text-success';
});

const loadProduct = async () => {
  loading.value = true;
  error.value = '';
  product.value = null;
  relatedProducts.value = [];
  complementaryProducts.value = [];
  addMessage.value = '';

  const slug = route.params.slug;

  try {
    const data = await fetchProductBySlug(slug);

    product.value = data.product ?? data;
    relatedProducts.value =
      data.related_products ??
      data.similar_products ??
      [];
    complementaryProducts.value =
      data.complementary_products ??
      data.cross_sell_products ??
      [];
    applySeo();
  } catch (e) {
    console.error('Product load error', e);
    if (e.response?.status === 404) {
      error.value = 'Produsul nu a fost găsit.';
    } else {
      error.value =
        e.response?.data?.message ||
        'Nu s-au putut încărca detaliile produsului.';
    }
  } finally {
    loading.value = false;
  }
};

const addToCart = async () => {
  if (!product.value) return;
  if (quantity.value < 1) quantity.value = 1;

  adding.value = true;
  addMessage.value = '';
  addMessageType.value = 'info';

  try {
    await addCartItem({
      product_id: product.value.id,
      quantity: Number(quantity.value),
    });

    addMessageType.value = 'success';
    addMessage.value = 'Produsul a fost adăugat în coș.';
  } catch (e) {
    console.error('Add to cart error', e);
    addMessageType.value = 'error';
    addMessage.value =
      e.response?.data?.message ||
      'Nu s-a putut adăuga produsul în coș.';
  } finally {
    adding.value = false;
  }
};

watch(
  () => route.params.slug,
  () => {
    loadProduct();
  },
);

onMounted(() => {
  loadProduct();
});

const applySeo = () => {
  if (!product.value) return;
  const title = (product.value.meta_title || product.value.name || '') + ' | ' + (document?.querySelector('meta[name=\"application-name\"]')?.getAttribute('content') || '');
  const desc = product.value.meta_description || product.value.short_description || '';
  const url = window.location.origin + (location.pathname || '');
  const image = product.value.image_url || product.value.main_image_url || '';
  const brand = product.value.brand?.name || product.value.brand || '';
  const sku = product.value.sku || product.value.internal_code || '';
  const price = parseFloat(product.value.price_override || product.value.promo_price || product.value.price || 0);
  const availability = (product.value.stock_status === 'in_stock' || (product.value.stock_qty && product.value.stock_qty > 0)) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
  setTitle(title);
  setMeta('description', desc);
  setMetaProperty('og:type', 'product');
  setMetaProperty('og:title', title);
  setMetaProperty('og:description', desc);
  setMetaProperty('og:url', url);
  if (image) setMetaProperty('og:image', image);
  setCanonical(url);
  const breadcrumb = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    'itemListElement': [
      { '@type': 'ListItem', position: 1, name: 'Acasă', item: window.location.origin + '/' },
      { '@type': 'ListItem', position: 2, name: product.value.main_category?.name || 'Categorie produse', item: window.location.origin + (product.value.main_category ? '/categorie/' + product.value.main_category.slug : '/produse') },
      { '@type': 'ListItem', position: 3, name: product.value.name, item: url }
    ]
  };
  const productJson = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    'name': product.value.name,
    'image': image ? [image] : undefined,
    'description': desc,
    'sku': sku || undefined,
    'brand': brand ? { '@type': 'Brand', 'name': brand } : undefined,
    'offers': {
      '@type': 'Offer',
      'priceCurrency': 'RON',
      'price': price,
      'availability': availability,
      'url': url
    }
  };
  setJsonLd({ '@graph': [breadcrumb, productJson] })
}
</script>
