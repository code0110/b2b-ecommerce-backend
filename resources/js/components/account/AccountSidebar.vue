<template>
  <div class="account-sidebar">
    <!-- Card info utilizator -->
    <div class="card mb-3 shadow-sm">
      <div class="card-body p-3">
        <div class="d-flex align-items-center">
          <div
            class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2"
            style="width: 36px; height: 36px;"
          >
            <span class="fw-semibold">
              {{ initials }}
            </span>
          </div>
          <div class="flex-grow-1">
            <div class="fw-semibold small mb-0">
              {{ displayName }}
            </div>
            <div class="text-muted small">
              {{ roleLabel }}
            </div>
          </div>
        </div>
        
        <!-- Toggle Start/Stop Program -->
        <div v-if="isAgentOrDirector" class="mt-3 pt-2 border-top">
             <div v-if="trackingStore.loading" class="text-center small text-muted">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Se actualizează...
             </div>
             <button 
                v-else-if="!trackingStore.isShiftActive"
                @click="startDay"
                class="btn btn-sm btn-success w-100 d-flex align-items-center justify-content-center"
             >
                <i class="bi bi-play-circle me-2"></i> Start Program
             </button>
             <button 
                v-else
                @click="endDay"
                class="btn btn-sm btn-danger w-100 d-flex align-items-center justify-content-center"
             >
                <i class="bi bi-stop-circle me-2"></i> Încheie Program
             </button>
             
             <div v-if="trackingStore.isShiftActive" class="text-center mt-1">
                <small class="text-success fw-bold" style="font-size: 0.7rem;">
                    <i class="bi bi-broadcast me-1"></i> Monitorizare Activă
                </small>
             </div>
        </div>
      </div>
    </div>

    <!-- Meniu principal cont -->
    <div class="card mb-3 shadow-sm">
      <div class="card-header py-2">
        <span class="fw-semibold small">Contul meu</span>
      </div>
      <div class="list-group list-group-flush small">
        <RouterLink
          :to="{ name: 'account-dashboard' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-dashboard') }"
        >
          <span>
            <i class="bi bi-speedometer2 me-1"></i>
            Dashboard
          </span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'quick-order' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('quick-order') }"
        >
          <span>
            <i class="bi bi-lightning-charge me-1"></i>
            Comandă Rapidă
          </span>
        </RouterLink>

        <RouterLink
          v-if="isAgentOrDirector"
          :to="{ name: 'agent-dashboard' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('agent-dashboard') }"
        >
          <span>
            <i class="bi bi-briefcase me-1"></i>
            Panou Agent
          </span>
        </RouterLink>

        <RouterLink
          v-if="isAgentOrDirector"
          :to="{ name: 'account-agent-routes' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-agent-routes') }"
        >
          <span>
            <i class="bi bi-map me-1"></i>
            Planificare Rute
          </span>
        </RouterLink>

        <RouterLink
          v-if="isDirector || authStore.role === 'admin'"
          :to="{ name: 'account-route-history' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-route-history') }"
        >
          <span>
            <i class="bi bi-clock-history me-1"></i>
            Istoric Rute
          </span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'account-orders' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-orders') || isActive('account-order-details') }"
        >
          <span>
            <i class="bi bi-bag-check me-1"></i>
            Comenzi
          </span>
        </RouterLink>

        <RouterLink
          :to="{ name: isAgentOrDirector ? 'account-offers-list' : 'account-offers' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-offers') || isActive('account-offers-list') }"
        >
          <span>
            <i class="bi bi-file-earmark-text me-1"></i>
            Oferte & cereri
          </span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'account-request-quote' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-request-quote') }"
        >
          <span>
            <i class="bi bi-pencil-square me-1"></i>
            Cere Ofertă
          </span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'account-documents' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-documents') }"
        >
          <span>
            <i class="bi bi-receipt me-1"></i>
            Documente fiscale
          </span>
        </RouterLink>
      </div>
    </div>

    <!-- Meniu Director -->
    <div v-if="isDirector" class="card mb-3 shadow-sm">
      <div class="card-header py-2">
        <span class="fw-semibold small">Management Vânzări</span>
      </div>
      <div class="list-group list-group-flush small">
        <RouterLink
          :to="{ name: 'account-director-dashboard' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-director-dashboard') }"
        >
          <span>
            <i class="bi bi-person-workspace me-1"></i>
            Dashboard Director
          </span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'account-reports' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-reports') }"
        >
          <span>
            <i class="bi bi-bar-chart-line me-1"></i>
            Raport Performanță
          </span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'account-locations-report' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-locations-report') }"
        >
          <span>
            <i class="bi bi-geo-alt-fill me-1"></i>
            Raport Locații
          </span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'account-targets' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-targets') }"
        >
          <span>
            <i class="bi bi-bullseye me-1"></i>
            Obiective KPI
          </span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'account-routes-management' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-routes-management') }"
        >
          <span>
            <i class="bi bi-map-fill me-1"></i>
            Management Rute
          </span>
        </RouterLink>
      </div>
    </div>

    <!-- Meniu adresă & companie -->
    <div class="card mb-3 shadow-sm">
      <div class="card-header py-2">
        <span class="fw-semibold small">Date & adresă</span>
      </div>
      <div class="list-group list-group-flush small">
        <RouterLink
          :to="{ name: 'account-addresses' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-addresses') }"
        >
          <span>
            <i class="bi bi-geo-alt me-1"></i>
            Adrese
          </span>
        </RouterLink>

        <RouterLink
          v-if="isCompanyAccount"
          :to="{ name: 'account-company-users' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-company-users') }"
        >
          <span>
            <i class="bi bi-people me-1"></i>
            Utilizatori companie
          </span>
        </RouterLink>

        <RouterLink
          v-if="isCompanyAccount"
          :to="{ name: 'account-recurring-orders' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-recurring-orders') }"
        >
          <span>
            <i class="bi bi-arrow-repeat me-1"></i>
            Comenzi recurente
          </span>
        </RouterLink>
      </div>
    </div>

    <!-- Meniu suport & notificări -->
    <div class="card mb-3 shadow-sm">
      <div class="card-header py-2">
        <span class="fw-semibold small">Suport & notificări</span>
      </div>
      <div class="list-group list-group-flush small">
        <RouterLink
          :to="{ name: 'account-tickets' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-tickets') }"
        >
          <span>
            <i class="bi bi-life-preserver me-1"></i>
            Tichete suport
          </span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'account-notifications' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-notifications') }"
        >
          <span>
            <i class="bi bi-bell me-1"></i>
            Notificări
          </span>
          <span
            v-if="unreadNotifications > 0"
            class="badge rounded-pill bg-danger"
          >
            {{ unreadNotifications }}
          </span>
        </RouterLink>
      </div>
    </div>

    <!-- Buton logout -->
    <button
      type="button"
      class="btn btn-outline-secondary btn-sm w-100"
      @click="logout"
    >
      <i class="bi bi-box-arrow-right me-1"></i>
      Delogare
    </button>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/store/auth';
import { useTrackingStore } from '@/store/tracking';
import  api  from '@/services/http';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const trackingStore = useTrackingStore();

const unreadNotifications = ref(0);

const startDay = async () => {
    try {
        await trackingStore.startDay();
    } catch (e) {
        alert('Eroare la pornirea programului.');
    }
};

const endDay = async () => {
    if (!confirm('Ești sigur că vrei să închei programul?')) return;
    try {
        await trackingStore.endDay();
    } catch (e) {
        alert('Eroare la încheierea programului.');
    }
};

const displayName = computed(() => {
  return authStore.user?.name || authStore.user?.full_name || 'Utilizator';
});

const initials = computed(() => {
  const name = displayName.value;
  const parts = name.split(' ').filter(Boolean);
  if (parts.length === 0) return 'U';
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
  return (parts[0][0] + parts[1][0]).toUpperCase();
});

const isCompanyAccount = computed(() => {
  // Verificare mai robustă, în caz că nu avem customerType setat explicit
  const roles = (authStore.user?.roles || []).map(r => r.slug || r.code);
  return authStore.customerType === 'b2b' || authStore.role === 'customer_b2b' || roles.includes('company_owner');
});

const isAgentOrDirector = computed(() => {
  const roles = (authStore.user?.roles || []).map(r => r.slug || r.code);
  return roles.includes('sales_agent') || roles.includes('sales_director');
});

const isDirector = computed(() => {
  const roles = (authStore.user?.roles || []).map(r => r.slug || r.code);
  return roles.includes('sales_director') || authStore.role === 'sales_director';
});

const roleLabel = computed(() => {
  if (authStore.role === 'admin') return 'Administrator';
  if (authStore.role === 'customer_b2b') return 'Client B2B';
  if (authStore.role === 'customer_b2c') return 'Client B2C';
  return 'Utilizator';
});

const isActive = (name) => {
  return route.name === name;
};

const loadUnreadNotifications = async () => {
  try {
    const { data } = await api.get('/notifications/unread-count');
    unreadNotifications.value = data.count ?? 0;
  } catch (e) {
    console.warn('Cannot load unread notifications count', e);
  }
};

const logout = async () => {
  try {
    await authStore.logout();
    router.push({ name: 'home' });
  } catch (e) {
    console.error('Logout error', e);
  }
};

onMounted(() => {
  if (authStore.isAuthenticated) {
    loadUnreadNotifications();
    
    if (isAgentOrDirector.value) {
        trackingStore.checkStatus();
    }
  }
});
</script>

<style scoped>
.account-sidebar .list-group-item.active {
  background-color: #0d6efd;
  border-color: #0d6efd;
  color: #fff;
}
.account-sidebar .list-group-item.active .text-muted {
  color: rgba(255, 255, 255, 0.75) !important;
}
</style>
