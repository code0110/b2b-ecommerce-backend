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
          :to="{ name: 'account-offers' }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
          :class="{ active: isActive('account-offers') }"
        >
          <span>
            <i class="bi bi-file-earmark-text me-1"></i>
            Oferte & cereri
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
import  api  from '@/services/http';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const unreadNotifications = ref(0);

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
    // optional: ignori eroarea la meniu
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
