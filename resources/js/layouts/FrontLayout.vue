<template>
  <div class="front-layout d-flex flex-column min-vh-100 bg-light">
    <!-- Active Visit Banner (When NOT impersonating) -->
    <div v-if="visitStore.hasActiveVisit && !impersonatingClient" class="bg-primary text-white py-2 px-3 text-center d-flex justify-content-center align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-geo-alt-fill"></i>
            <span class="fw-bold">Vizită activă:</span>
            <span>{{ visitStore.activeVisit.customer?.name || 'Client necunoscut' }}</span>
            <span class="small opacity-75 ms-1">({{ new Date(visitStore.activeVisit.start_time).toLocaleTimeString() }})</span>
        </div>
        <button @click="handleEndVisit" class="btn btn-sm btn-light text-primary d-flex align-items-center gap-2 border-0" :disabled="visitStore.loading">
            <span v-if="visitStore.loading" class="spinner-border spinner-border-sm"></span>
            <i v-else class="bi bi-stop-circle"></i> 
            Încheie
        </button>
    </div>

    <!-- Impersonation Banner -->
    <div v-if="impersonatingClient" class="bg-warning text-dark py-2 px-3 text-center d-flex justify-content-center align-items-center gap-3">
      <strong><i class="bi bi-exclamation-triangle-fill"></i> Mod Impersonare:</strong>
      <span>Comandați în numele clientului <strong>{{ impersonatingClientName }}</strong></span>
      
      <span v-if="visitStore.activeVisit" class="badge bg-danger ms-2 animate-pulse">
          <i class="bi bi-geo-alt-fill"></i> Vizită Activă
      </span>

      <button @click="stopImpersonation" class="btn btn-sm btn-dark ms-2">
        Revenire la Dashboard
      </button>
    </div>

    <!-- HEADER -->
    <header class="bg-white border-bottom">
      <div class="container py-2 d-flex align-items-center justify-content-between gap-3">
        <!-- Brand -->
        <div class="d-flex align-items-center gap-2">
          <div
            class="rounded-circle d-flex align-items-center justify-content-center"
            style="width: 36px; height: 36px; background: #111827; color: #ffffff; font-weight: 600;"
          >
            MB
          </div>
          <div class="d-flex flex-column lh-1">
            <span class="small text-muted">B2B materiale profesionale</span>
            <span class="fw-semibold">MB2B</span>
          </div>
        </div>

        <!-- Catalog + search (desktop) -->
        <div class="d-none d-lg-flex align-items-center flex-grow-1 gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1"
            @click="openCatalog"
          >
            <span style="font-size: 1.1rem;">☰</span>
            <span>Catalog</span>
          </button>

          <div class="flex-grow-1 position-relative">
            <input
              v-model="searchQuery"
              type="text"
              class="form-control form-control-sm ps-3"
              placeholder="Căutați produs, SKU sau categorie…"
              @keyup.enter="goToSearch"
            />
            <button
              type="button"
              class="btn btn-link btn-sm position-absolute top-50 end-0 translate-middle-y me-1 text-muted"
              @click="goToSearch"
            >
              🔍
            </button>
          </div>

          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-light btn-sm">
              RON
            </button>
            <div class="form-check form-switch mb-0">
              <input
                class="form-check-input"
                type="checkbox"
                id="tvaSwitch"
                v-model="showVat"
              />
              <label class="form-check-label small" for="tvaSwitch">
                TVA
              </label>
            </div>
            <RouterLink
              :to="{ name: 'become-partner' }"
              class="btn btn-outline-secondary btn-sm d-none d-xl-inline-flex"
            >
              RFQ
            </RouterLink>
          </div>
        </div>

        <!-- Right: portal + coș -->
        <div class="d-flex align-items-center gap-2">
          <!-- Notificări -->
          <NotificationsDropdown v-if="authStore.isAuthenticated" />

          <RouterLink
            :to="accountLink"
            class="btn btn-outline-secondary btn-sm"
          >
            Portal
          </RouterLink>
          <RouterLink
            :to="{ name: 'cart' }"
            class="btn btn-dark btn-sm d-flex align-items-center gap-1"
          >
            <span>🛒</span>
            <span>Coș</span>
          </RouterLink>
        </div>
      </div>

      <!-- Bara de navigație secundară -->
      <div class="border-top bg-light small">
        <div class="container py-1 d-flex flex-wrap align-items-center gap-2">
          <span class="text-muted me-2">NAVIGAȚIE</span>

          <RouterLink
            :to="{ name: 'static-page', params: { slug: 'despre-noi' } }"
            class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1"
          >
            Despre
          </RouterLink>
          <RouterLink
            :to="{ name: 'static-page', params: { slug: 'contact' } }"
            class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1"
          >
            Contact
          </RouterLink>
          <RouterLink
            :to="{ name: 'blog-list' }"
            class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1"
          >
            Blog
          </RouterLink>

          <div class="ms-auto text-muted d-none d-md-block">
            Suport clienți: 09–18
          </div>
        </div>
      </div>
    </header>

    <!-- CONȚINUT -->
    <main class="flex-grow-1">
      <RouterView />
    </main>

    <!-- FOOTER -->
    <footer class="border-top bg-white small text-muted py-3 mt-4">
      <div class="container d-flex flex-wrap justify-content-between gap-2">
        <div>
          &copy; {{ currentYear }} MB2B – demo B2B/B2C e-commerce.
        </div>
        <div class="d-flex gap-3">
          <RouterLink
            :to="{ name: 'static-page', params: { slug: 'termeni-conditii' } }"
          >
            Termeni &amp; condiții
          </RouterLink>
          <RouterLink
            :to="{ name: 'static-page', params: { slug: 'gdpr' } }"
          >
            GDPR
          </RouterLink>
        </div>
      </div>
    </footer>

    <!-- Catalog overlay -->
    <CategoryMegaModal
      v-if="showCatalog"
      @close="showCatalog = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/store/auth';
import { useVisitStore } from '@/store/visit';
import CategoryMegaModal from '@/components/catalog/CategoryMegaModal.vue';
import NotificationsDropdown from '@/components/common/NotificationsDropdown.vue';

const router = useRouter();
const authStore = useAuthStore();
const visitStore = useVisitStore();

const showCatalog = ref(false);
const searchQuery = ref('');
const showVat = ref(true);

const impersonatingClient = ref(!!localStorage.getItem('impersonated_client_id'));
const impersonatingClientName = ref(localStorage.getItem('impersonated_client_name'));

const handleEndVisit = async () => {
  if (confirm('Sigur doriți să încheiați vizita?')) {
    try {
      await visitStore.endVisit();
    } catch (e) {
      console.error(e);
    }
  }
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

const currentYear = new Date().getFullYear();

const accountLink = computed(() => {
  return authStore.isAuthenticated
    ? { name: 'account-dashboard' }
    : { name: 'login', query: { redirect: '/cont' } };
});

const openCatalog = () => {
  showCatalog.value = true;
};

const goToSearch = () => {
  if (!searchQuery.value) return;
  router.push({
    name: 'search-results',
    query: { q: searchQuery.value },
  });
};

// Permitem și altor componente (ex. Home) să deschidă catalogul
const handleOpenCatalogEvent = () => {
  openCatalog();
};

onMounted(() => {
  visitStore.checkActiveVisit();
  window.addEventListener('mb2b:open-catalog', handleOpenCatalogEvent);
});

onBeforeUnmount(() => {
  window.removeEventListener('mb2b:open-catalog', handleOpenCatalogEvent);
});
</script>

<style scoped>
.front-layout {
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
</style>
