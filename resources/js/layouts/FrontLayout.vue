<template>
  <div class="front-layout d-flex flex-column min-vh-100 bg-light">
    <!-- Top Bar (Secondary Links) -->
    <div class="top-bar py-1 px-3 d-none d-md-block text-white" style="background-color: #555; font-size: 0.8rem;">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex gap-3">
                <a href="#" class="text-white text-decoration-none hover-underline"><i class="bi bi-headset me-1"></i>Suport Clienți</a>
                <a href="#" class="text-white text-decoration-none hover-underline"><i class="bi bi-shop me-1"></i>Magazine</a>
                <a href="#" class="text-white text-decoration-none hover-underline"><i class="bi bi-tools me-1"></i>Servicii</a>
            </div>
<<<<<<< HEAD
            <div class="d-flex gap-3 align-items-center">
                <!-- VAT Toggle -->
                <div class="form-check form-switch d-flex align-items-center gap-2 mb-0 me-3" title="Schimbă modul de afișare al prețurilor">
                    <input 
                        class="form-check-input" 
                        type="checkbox" 
                        role="switch" 
                        id="vatToggle" 
                        :checked="preferencesStore.showVat" 
                        @change="preferencesStore.toggleVat"
                        style="cursor: pointer;"
                    >
                    <label class="form-check-label text-white small user-select-none" for="vatToggle" style="cursor: pointer;">
                        {{ preferencesStore.showVat ? 'Prețuri cu TVA' : 'Prețuri fără TVA' }}
                    </label>
                </div>

=======
            <div class="d-flex gap-3">
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
                <a href="#" class="text-white text-decoration-none hover-underline">Retur</a>
                <a href="#" class="text-white text-decoration-none hover-underline">Contact</a>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="bg-white sticky-top shadow-sm" style="z-index: 1020;">
      <div class="container py-3">
        <div class="row align-items-center g-3">
            <!-- Brand -->
            <div class="col-6 col-lg-2">
                <RouterLink to="/" class="d-flex align-items-center text-decoration-none gap-2">
<<<<<<< HEAD
                    <img 
                      v-if="siteConfig.config.site_logo"
                      :src="siteConfig.config.site_logo" 
                      :alt="siteConfig.config.site_name || 'MB2B'" 
                      style="height: 48px; object-fit: contain;"
                    >
                    <div v-else class="rounded d-flex align-items-center justify-content-center fw-bold text-white fs-3" 
                         style="width: 48px; height: 48px; background: #0060aa;">
                        {{ (siteConfig.config.site_name || 'MB2B').charAt(0) }}
                    </div>
                    <div v-if="!siteConfig.config.site_logo" class="d-flex flex-column lh-1">
                        <span class="fw-bold text-dark fs-4 tracking-tight" style="color: #0060aa;">{{ siteConfig.config.site_name || 'MB2B' }}</span>
=======
                    <div class="rounded d-flex align-items-center justify-content-center fw-bold text-white fs-3" 
                         style="width: 48px; height: 48px; background: #0060aa;">
                        M
                    </div>
                    <div class="d-flex flex-column lh-1">
                        <span class="fw-bold text-dark fs-4 tracking-tight" style="color: #0060aa;">MB2B</span>
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
                        <span class="text-muted" style="font-size: 0.6rem; letter-spacing: 1px; text-transform: uppercase;">Industry</span>
                    </div>
                </RouterLink>
            </div>

            <!-- Search (Desktop) -->
            <div class="col-12 col-lg-6 order-3 order-lg-2">
<<<<<<< HEAD
                <SearchAutocomplete />
=======
                <div class="input-group input-group-lg">
                    <input 
                        v-model="searchQuery" 
                        @keyup.enter="goToSearch"
                        type="text" 
                        class="form-control border-end-0 shadow-none fs-6" 
                        placeholder="Caută produse, branduri..." 
                        aria-label="Search"
                        style="border-color: #ced4da; border-radius: 4px 0 0 4px;"
                    >
                    <button 
                        class="btn btn-orange text-white fw-bold px-4" 
                        type="button"
                        @click="goToSearch"
                        style="border-radius: 0 4px 4px 0;"
                    >
                        <i class="bi bi-search"></i>
                    </button>
                </div>
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
            </div>

            <!-- Actions -->
            <div class="col-6 col-lg-4 order-2 order-lg-3 d-flex align-items-center justify-content-end gap-4">
                <!-- User Account -->
                <RouterLink :to="accountLink" class="d-flex flex-column align-items-center text-decoration-none text-dark gap-1 header-action">
                    <div class="position-relative">
                        <i class="bi bi-person fs-4 text-secondary"></i>
                        <span v-if="authStore.isAuthenticated" class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle"></span>
                    </div>
                    <div class="d-none d-xl-block lh-1 text-center">
                        <div class="small fw-semibold">{{ authStore.user?.name ? 'Contul meu' : 'Intră în cont' }}</div>
                    </div>
                </RouterLink>

<<<<<<< HEAD
                <!-- Favorites -->
                <RouterLink :to="{ name: 'wishlist' }" class="d-flex flex-column align-items-center text-decoration-none text-dark gap-1 header-action position-relative">
                    <div class="position-relative">
                        <i class="bi bi-heart fs-4 text-secondary"></i>
                        <span v-if="wishlistStore.count > 0" 
                              class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                           {{ wishlistStore.count }}
                        </span>
                    </div>
                    <div class="d-none d-xl-block lh-1 text-center">
                        <div class="small fw-semibold">Favorite</div>
                    </div>
                </RouterLink>
=======
                <!-- Favorites (Placeholder) -->
                <a href="#" class="d-flex flex-column align-items-center text-decoration-none text-dark gap-1 header-action">
                    <i class="bi bi-heart fs-4 text-secondary"></i>
                    <div class="d-none d-xl-block lh-1 text-center">
                        <div class="small fw-semibold">Favorite</div>
                    </div>
                </a>
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76

                <!-- Cart -->
                <RouterLink :to="{ name: 'cart' }" class="d-flex flex-column align-items-center text-decoration-none text-dark gap-1 position-relative header-action">
                    <div class="position-relative">
                         <i class="bi bi-cart3 fs-4 text-secondary"></i>
                         <span v-if="cartStore.itemCount > 0" 
                               class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ cartStore.itemCount }}
                         </span>
                    </div>
                    <div class="d-none d-xl-block lh-1 text-center">
                        <div class="small fw-semibold">Coșul meu</div>
                        <!-- <div class="fw-bold text-orange">{{ formatPrice(cartStore.totalValue) }}</div> -->
                    </div>
                </RouterLink>
            </div>
        </div>
      </div>

      <!-- Navigation Bar -->
      <div class="border-top" style="background-color: #ffffff; border-bottom: 1px solid #e5e5e5;">
          <div class="container">
              <div class="d-flex d-md-none align-items-center gap-2 py-2">
                <button
                  class="btn btn-orange flex-grow-1 d-flex align-items-center justify-content-center gap-2 fw-bold text-white border-0"
                  type="button"
                  @click="openCatalog"
                >
                  <i class="bi bi-list fs-5"></i>
                  Produse
                </button>
                <button
                  class="btn btn-outline-secondary d-flex align-items-center justify-content-center"
                  type="button"
                  data-bs-toggle="offcanvas"
                  data-bs-target="#frontMenu"
                  aria-controls="frontMenu"
                >
                  <i class="bi bi-list"></i>
                </button>
              </div>

              <div class="d-none d-md-flex align-items-center">
                  <div class="position-relative">
                      <button 
                        class="btn btn-orange rounded-0 d-flex align-items-center gap-3 px-4 py-2 fw-bold text-white border-0"
                        @click="openCatalog"
                        style="min-width: 240px; font-size: 1rem;"
                      >
                          <i class="bi bi-list fs-4"></i>
                          <span>PRODUSE</span>
                      </button>
                  </div>

                  <nav class="d-none d-md-flex ms-4 gap-4">
                      <RouterLink :to="{ name: 'promotions' }" class="text-decoration-none text-dark fw-bold small hover-orange text-uppercase">Promoții</RouterLink>
                      <RouterLink :to="{ name: 'new-products' }" class="text-decoration-none text-dark fw-bold small hover-orange text-uppercase">Noutăți</RouterLink>
                      <RouterLink :to="{ name: 'discounted-products' }" class="text-decoration-none text-dark fw-bold small hover-orange text-uppercase">Reduceri</RouterLink>
                      <RouterLink :to="{ name: 'blog-list' }" class="text-decoration-none text-dark fw-bold small hover-orange text-uppercase">Blog</RouterLink>
                      <RouterLink :to="{ name: 'sales-representatives' }" class="text-decoration-none text-dark fw-bold small hover-orange text-uppercase">Suport</RouterLink>
                  </nav>

<<<<<<< HEAD
                  <div v-if="authStore.isAuthenticated && (visitStore.activeVisit || impersonatingClient)" class="ms-auto d-flex align-items-center gap-2 py-1">
                       <span v-if="visitStore.activeVisit" class="badge bg-danger animate-pulse d-flex align-items-center gap-1">
                          <i class="bi bi-geo-alt-fill"></i> Vizită: {{ visitStore.client?.name || visitStore.activeVisit.customer?.name || 'Client' }}
=======
                  <div v-if="visitStore.activeVisit || impersonatingClient" class="ms-auto d-flex align-items-center gap-2 py-1">
                       <span v-if="visitStore.activeVisit" class="badge bg-danger animate-pulse d-flex align-items-center gap-1">
                          <i class="bi bi-geo-alt-fill"></i> Vizită: {{ visitStore.client?.name }}
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
                      </span>
                      <button v-if="impersonatingClient" @click="stopImpersonation" class="btn btn-sm btn-outline-dark ms-2">
                        Stop Impersonare
                      </button>
                  </div>
              </div>
          </div>
      </div>
    </header>

    <!-- CONȚINUT -->
    <main class="flex-grow-1">
      <RouterView />
    </main>

    <!-- FOOTER -->
    <Footer />

    <div
      class="offcanvas offcanvas-start"
      tabindex="-1"
      id="frontMenu"
      aria-labelledby="frontMenuLabel"
      style="width: 320px;"
    >
      <div class="offcanvas-header border-bottom">
        <div id="frontMenuLabel" class="fw-bold">Meniu</div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body p-0">
        <div class="list-group list-group-flush">
          <button
            type="button"
            class="list-group-item list-group-item-action d-flex align-items-center gap-2"
            data-bs-dismiss="offcanvas"
            @click="openCatalog"
          >
            <i class="bi bi-grid"></i>
            Produse
          </button>
          <RouterLink :to="{ name: 'promotions' }" class="list-group-item list-group-item-action d-flex align-items-center gap-2" data-bs-dismiss="offcanvas">
            <i class="bi bi-tags"></i>
            Promoții
          </RouterLink>
          <RouterLink :to="{ name: 'new-products' }" class="list-group-item list-group-item-action d-flex align-items-center gap-2" data-bs-dismiss="offcanvas">
            <i class="bi bi-stars"></i>
            Noutăți
          </RouterLink>
          <RouterLink :to="{ name: 'discounted-products' }" class="list-group-item list-group-item-action d-flex align-items-center gap-2" data-bs-dismiss="offcanvas">
            <i class="bi bi-percent"></i>
            Reduceri
          </RouterLink>
          <RouterLink :to="{ name: 'blog-list' }" class="list-group-item list-group-item-action d-flex align-items-center gap-2" data-bs-dismiss="offcanvas">
            <i class="bi bi-journal-text"></i>
            Blog
          </RouterLink>
          <RouterLink :to="{ name: 'sales-representatives' }" class="list-group-item list-group-item-action d-flex align-items-center gap-2" data-bs-dismiss="offcanvas">
            <i class="bi bi-headset"></i>
            Reprezentanți
          </RouterLink>
          <RouterLink :to="{ name: 'become-partner' }" class="list-group-item list-group-item-action d-flex align-items-center gap-2" data-bs-dismiss="offcanvas">
            <i class="bi bi-building-add"></i>
            Devino partener
          </RouterLink>
          <div class="border-top"></div>
          <RouterLink :to="{ name: 'cart' }" class="list-group-item list-group-item-action d-flex align-items-center gap-2" data-bs-dismiss="offcanvas">
            <i class="bi bi-cart3"></i>
            Coș
            <span v-if="cartStore.itemCount > 0" class="ms-auto badge rounded-pill bg-danger">{{ cartStore.itemCount }}</span>
          </RouterLink>
          <RouterLink :to="accountLink" class="list-group-item list-group-item-action d-flex align-items-center gap-2" data-bs-dismiss="offcanvas">
            <i class="bi bi-person"></i>
            Cont
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Catalog overlay -->
    <CategoryMegaModal
      v-if="showCatalog"
      @close="showCatalog = false"
    />
    <CompareFloatingBar />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/store/auth';
import { useVisitStore } from '@/store/visit';
import { useCartStore } from '@/store/cart';
import { useWishlistStore } from '@/store/wishlist';
import { useCompareStore } from '@/store/compare';
import { usePreferencesStore } from '@/store/preferences';
import CategoryMegaModal from '@/components/catalog/CategoryMegaModal.vue';
<<<<<<< HEAD
import CompareFloatingBar from '@/components/common/CompareFloatingBar.vue';
=======
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
import Footer from '@/components/common/Footer.vue';
import SearchAutocomplete from '@/components/common/SearchAutocomplete.vue';

import { useSiteConfigStore } from '@/store/siteConfig';

const siteConfig = useSiteConfigStore();
const router = useRouter();
const authStore = useAuthStore();
const visitStore = useVisitStore();
const cartStore = useCartStore();
const wishlistStore = useWishlistStore();
const compareStore = useCompareStore();
const preferencesStore = usePreferencesStore();

const showCatalog = ref(false);
<<<<<<< HEAD
// const searchQuery = ref('');
=======
const searchQuery = ref('');
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76

const impersonatingClient = ref(!!localStorage.getItem('impersonated_client_id'));

const formatPrice = (value) => {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(value || 0);
};

const stopImpersonation = async () => {
  if (visitStore.activeVisit) {
      if(confirm('Doriți să încheiați și vizita curentă?')) {
          try {
              await visitStore.endVisit();
          } catch(e) {
              console.error(e);
          }
      }
  }

  localStorage.removeItem('impersonated_client_id');
  localStorage.removeItem('impersonated_client_name');
  window.location.href = '/cont/agent';
};

const accountLink = computed(() => {
  return authStore.isAuthenticated
    ? { name: 'account-dashboard' }
    : { name: 'login', query: { redirect: '/cont' } };
});

const openCatalog = () => {
  showCatalog.value = true;
};

// const goToSearch = () => {
//   if (!searchQuery.value) return;
//   router.push({
//     name: 'search-results',
//     query: { q: searchQuery.value },
//   });
// };

const handleOpenCatalogEvent = () => {
  openCatalog();
};

onMounted(() => {
<<<<<<< HEAD
  siteConfig.fetchConfig();
=======
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
  if (authStore.isAuthenticated) {
    visitStore.checkActiveVisit();
  }
  cartStore.fetchCart();
  compareStore.loadComparison();
  window.addEventListener('mb2b:open-catalog', handleOpenCatalogEvent);
});

onBeforeUnmount(() => {
  window.removeEventListener('mb2b:open-catalog', handleOpenCatalogEvent);
});
</script>

<style scoped>
.front-layout {
  font-family: 'Instrument Sans', system-ui, sans-serif;
}
.hover-underline:hover {
    text-decoration: underline !important;
}
.hover-orange:hover {
    color: var(--dd-orange) !important;
}
.header-action:hover i {
    color: var(--dd-orange) !important;
}
.header-action:hover .fw-semibold {
    color: var(--dd-orange) !important;
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .5; }
}
</style>
