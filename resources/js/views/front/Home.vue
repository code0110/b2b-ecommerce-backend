<template>
  <div class="home-page">
    <div class="py-5 mb-4" style="background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);">
      <div class="container">
        <div class="row align-items-center g-4">
          <div class="col-lg-7">
            <h1 class="display-6 fw-semibold mb-2">
              {{ heroTitle }}
            </h1>
            <p class="text-muted mb-3">
              {{ heroSubtitle }}
            </p>
            <div class="d-flex flex-wrap gap-2">
              <button type="button" class="btn btn-primary" @click="openCatalog">
                {{ heroCtaText }}
              </button>
              <RouterLink :to="{ name: 'become-partner' }" class="btn btn-outline-primary">
                Devino partener B2B
              </RouterLink>
              <RouterLink :to="{ name: 'sales-representatives' }" class="btn btn-outline-secondary">
                Găsește un reprezentant
              </RouterLink>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="card border-0 shadow-sm" v-if="featuresList.length">
              <div class="card-body small">
                <div class="fw-semibold mb-2">{{ contentStore.getBlock('home_features_title') || 'De ce noi?' }}</div>
                <ul class="mb-0 text-muted ps-3">
                  <li v-for="(feature, idx) in featuresList" :key="idx" class="mb-1">
                    <strong>{{ feature.title }}:</strong> {{ feature.description }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container mb-5">
      <section class="mb-4" v-if="featuredCategories.length">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 mb-0">{{ contentStore.getBlock('home_categories_title') || 'Categorii populare' }}</h2>
          <button type="button" class="btn btn-link btn-sm text-decoration-none" @click="openCatalog">
            Deschide catalogul →
          </button>
        </div>
        <div class="row g-3">
          <div v-for="cat in featuredCategories" :key="cat.slug || cat.id" class="col-6 col-md-3 col-lg-2">
            <RouterLink :to="`/categorie/${cat.slug}`" class="text-decoration-none">
              <div class="card h-100 border-0 shadow-sm">
                <div class="ratio ratio-1x1 bg-light d-flex align-items-center justify-content-center">
                  <i class="bi bi-grid text-muted" style="font-size: 1.6rem;"></i>
                </div>
                <div class="card-body py-2">
                  <div class="fw-semibold small text-dark">{{ cat.name }}</div>
                </div>
              </div>
            </RouterLink>
          </div>
        </div>
      </section>
      <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 mb-0">{{ contentStore.getBlock('home_promotions_title') || 'Promoții active' }}</h2>
          <RouterLink
            to="/promotii"
            class="btn btn-link btn-sm text-decoration-none"
          >
            Vezi toate promoțiile →
          </RouterLink>
        </div>
        <div v-if="loading" class="row g-3">
          <div v-for="n in 3" :key="'promo-skel-' + n" class="col-md-4">
            <div class="card h-100 shadow-sm">
              <div class="ratio ratio-16x9 bg-light"></div>
              <div class="card-body">
                <div class="placeholder-wave">
                  <span class="placeholder col-3 me-2"></span>
                  <span class="placeholder col-6"></span>
                </div>
                <div class="placeholder-wave mt-2">
                  <span class="placeholder col-9"></span>
                </div>
              </div>
            </div>
          </div>
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
              <div class="ratio ratio-16x9 bg-light" v-if="promo.image_url">
                <img :src="promo.image_url" :alt="promo.title" class="img-fluid rounded-top object-fit-cover" loading="lazy" />
              </div>
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

      <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 mb-0">{{ contentStore.getBlock('home_new_products_title') || 'Produse noi' }}</h2>
          <RouterLink
            to="/noutati"
            class="btn btn-link btn-sm text-decoration-none"
          >
            Vezi toate noutățile →
          </RouterLink>
        </div>
        <div v-if="loading" class="row g-3">
          <div v-for="n in 8" :key="'new-skel-' + n" class="col-md-3 col-sm-6">
            <div class="card h-100">
              <div class="ratio ratio-4x3 bg-light"></div>
              <div class="card-body">
                <div class="placeholder-wave">
                  <span class="placeholder col-8"></span>
                </div>
                <div class="placeholder-wave mt-2">
                  <span class="placeholder col-5"></span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="row g-3">
          <div
            v-for="product in newProducts"
            :key="product.slug || product.id"
            class="col-md-3 col-sm-6"
          >
            <div class="card h-100 border-0 shadow-sm">
              <div class="ratio ratio-4x3 bg-light">
                <img v-if="product.main_image_url" :src="product.main_image_url" :alt="product.name" class="card-img-top object-fit-cover rounded-top" loading="lazy" />
                <div v-else class="d-flex align-items-center justify-content-center text-muted small">Fără imagine</div>
              </div>
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <div class="small text-muted">
                    {{ product.category?.name || product.category || 'Categorie' }}
                  </div>
                  <span v-if="product.discountPercent || product.discount_percent" class="badge bg-success">
                    -{{ product.discountPercent || product.discount_percent }}%
                  </span>
                </div>
                <h3 class="h6 mb-1">{{ product.name }}</h3>
                <div class="small text-muted mb-2">{{ product.code || product.sku }}</div>
                <div class="mt-auto">
                  <div v-if="product.promoPrice || product.promo_price" class="small text-muted">
                    <span class="text-decoration-line-through me-1">
                      {{ formatMoney(product.price || product.list_price || 0) }}
                    </span>
                    <span class="fw-semibold">
                      {{ formatMoney(product.promoPrice || product.promo_price) }} RON
                    </span>
                  </div>
                  <div v-else class="fw-semibold mb-1">
                    {{ formatMoney(product.price || product.list_price || 0) }} RON
                  </div>
                  <RouterLink :to="`/produs/${product.slug}`" class="btn btn-outline-secondary btn-sm">
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

      <section>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 mb-0">{{ contentStore.getBlock('home_recommended_title') || 'Produse recomandate' }}</h2>
          <RouterLink
            to="/reduceri"
            class="btn btn-link btn-sm text-decoration-none"
          >
            Vezi produsele în promoție →
          </RouterLink>
        </div>
        <div v-if="loading" class="row g-3">
          <div v-for="n in 8" :key="'rec-skel-' + n" class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
              <div class="ratio ratio-4x3 bg-light"></div>
              <div class="card-body">
                <div class="placeholder-wave">
                  <span class="placeholder col-8"></span>
                </div>
                <div class="placeholder-wave mt-2">
                  <span class="placeholder col-5"></span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row g-3" v-else>
          <div
            v-for="product in recommendedProducts"
            :key="product.slug || product.id"
            class="col-md-3 col-sm-6"
          >
            <div class="card h-100 border-0 shadow-sm">
              <div class="ratio ratio-4x3 bg-light">
                <img v-if="product.main_image_url" :src="product.main_image_url" :alt="product.name" class="card-img-top object-fit-cover rounded-top" loading="lazy" />
                <div v-else class="d-flex align-items-center justify-content-center text-muted small">Fără imagine</div>
              </div>
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
      <section class="mb-5" v-if="brands.length">
        <h2 class="h5 mb-3">{{ contentStore.getBlock('home_brands_title') || 'Branduri partenere' }}</h2>
        <div class="row g-4 align-items-center justify-content-center">
          <div v-for="brand in brands" :key="brand.id" class="col-4 col-md-3 col-lg-2 text-center">
            <RouterLink :to="`/brand/${brand.slug}`" class="text-decoration-none">
              <img 
                v-if="brand.logo_url" 
                :src="brand.logo_url" 
                :alt="brand.name" 
                class="img-fluid opacity-75 hover-opacity-100 transition" 
                style="max-height: 50px; filter: grayscale(100%);"
              />
              <span v-else class="text-muted fw-bold">{{ brand.name }}</span>
            </RouterLink>
          </div>
        </div>
      </section>

      <!-- Latest Blog Posts -->
      <section class="mb-5" v-if="blogPosts.length">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 mb-0">{{ contentStore.getBlock('home_blog_title') || 'Noutăți de pe blog' }}</h2>
          <RouterLink to="/blog" class="btn btn-link btn-sm text-decoration-none">
            Vezi toate articolele →
          </RouterLink>
        </div>
        <div class="row g-4">
          <div v-for="post in blogPosts" :key="post.id" class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
              <div class="ratio ratio-16x9 bg-light rounded-top">
                <img v-if="post.image_url" :src="post.image_url" :alt="post.title" class="card-img-top object-fit-cover" loading="lazy" />
              </div>
              <div class="card-body">
                <div class="small text-muted mb-2">{{ new Date(post.published_at || post.created_at).toLocaleDateString('ro-RO') }}</div>
                <h3 class="h6 mb-2 card-title">
                  <RouterLink :to="`/blog/${post.slug}`" class="text-decoration-none text-dark stretched-link">
                    {{ post.title }}
                  </RouterLink>
                </h3>
                <p class="card-text small text-muted line-clamp-3">
                  {{ post.excerpt || post.content?.substring(0, 100) + '...' }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Testimonials Section -->
      <section class="mb-5 py-5 bg-light rounded-3" v-if="testimonialsList.length">
        <div class="container">
          <h2 class="h5 mb-4 text-center">{{ contentStore.getBlock('home_testimonials_title') || 'Ce spun clienții noștri' }}</h2>
          <div class="row g-4">
            <div class="col-md-4" v-for="(testimonial, idx) in testimonialsList" :key="idx">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                  <div class="mb-3 text-warning">
                    <i class="bi bi-star-fill" v-for="n in (testimonial.rating || 5)" :key="n"></i>
                  </div>
                  <p class="card-text text-muted">"{{ testimonial.text }}"</p>
                  <div class="d-flex align-items-center mt-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                      {{ testimonial.name.charAt(0) }}
                    </div>
                    <div>
                      <h6 class="mb-0 small">{{ testimonial.name }}</h6>
                      <small class="text-muted">{{ testimonial.company }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useContentStore } from '@/stores/content';
import { fetchHomeData, fetchCategoryTree, fetchBrands } from '@/services/catalog';
import { fetchBlogList } from '@/services/content';

const loading = ref(false);
const error = ref('');

const contentStore = useContentStore();

const homePromotions = ref([]);
const newProducts = ref([]);
const recommendedProducts = ref([]);
const categories = ref([]);
const featuredCategories = ref([]);
const brands = ref([]);
const blogPosts = ref([]);

// Content Blocks Computed Properties
const heroTitle = computed(() => contentStore.getBlock('home_hero_title') || 'Platformă B2B pentru materiale și produse');
const heroSubtitle = computed(() => contentStore.getBlock('home_hero_subtitle') || 'Explorați catalogul, promoțiile active și prețurile contractuale. Interfață rapidă, modernă și 100% responsivă.');
const heroCtaText = computed(() => contentStore.getBlock('home_hero_cta_text') || 'Deschide catalogul');
const featuresList = computed(() => contentStore.getBlock('home_features_list') || []);
const testimonialsList = computed(() => contentStore.getBlock('home_testimonials_list') || []);

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
    if (Object.keys(contentStore.blocks).length === 0) {
      await contentStore.fetchBlocks();
    }
    const data = await fetchHomeData();

    homePromotions.value = data.promotions ?? data.home_promotions ?? [];
    newProducts.value = data.new_products ?? [];
    recommendedProducts.value = data.recommended_products ?? [];
    const tree = await fetchCategoryTree().catch(() => ({ items: [] }));
    const items = tree.items ?? tree ?? [];
    categories.value = Array.isArray(items) ? items : [];
    featuredCategories.value = categories.value
      .filter(c => !c.parent_id)
      .slice(0, 12);
    
    // Fetch brands
    const brandsData = await fetchBrands().catch(() => []);
    brands.value = Array.isArray(brandsData) ? brandsData.slice(0, 12) : (brandsData.data || []).slice(0, 12);

    // Fetch blog posts
    const blogData = await fetchBlogList({ limit: 3 }).catch(() => ({ data: [] }));
    blogPosts.value = blogData.data || [];
    
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
