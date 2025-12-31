<template>
  <div class="admin-layout d-flex">
    <aside class="bg-dark text-white p-3 min-vh-100" style="width: 260px;">
      <div class="d-flex align-items-center mb-4">
        <span class="fw-bold">Admin Panel</span>
      </div>

      <nav class="nav flex-column small">
        <span class="text-uppercase text-muted mb-2">General</span>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-dashboard' }">
          Dashboard
        </RouterLink>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-notifications' }">
          Notificări
        </RouterLink>

        <li class="nav-item">
  <RouterLink
    :to="{ name: 'admin-orders' }"
    class="nav-link"
    active-class="active"
  >
    <i class="bi bi-bag-check me-2"></i>
    <span>Comenzi</span>
  </RouterLink>
</li>


        <span class="text-uppercase text-muted mt-3 mb-2">Catalog</span>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-products' }">
          Produse
        </RouterLink>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-categories' }">
          Categorii
        </RouterLink>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-brands' }">
          Mărci
        </RouterLink>

        <span class="text-uppercase text-muted mt-3 mb-2">Clienți</span>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-customers' }">
          Clienți
        </RouterLink>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-customer-groups' }">
          Grupuri clienți
        </RouterLink>

        <span class="text-uppercase text-muted mt-3 mb-2">Campanii</span>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-promotions' }">
          Promoții & discounturi
        </RouterLink>

        <span class="text-uppercase text-muted mt-3 mb-2">Ofertare B2B</span>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-offers' }">
          Oferte & negocieri
        </RouterLink>

        <span class="text-uppercase text-muted mt-3 mb-2">Operațional</span>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-shipping' }">
          Livrare & transport
        </RouterLink>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-shipments' }">
          AWB & expedieri
        </RouterLink>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-tickets' }">
          Tichete suport
        </RouterLink>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-collections' }">
          Încasări
        </RouterLink>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-receipt-books' }">
          Chitanțiere
        </RouterLink>


        <li class="nav-item">
  <RouterLink
    :to="{ name: 'admin-users' }"
    class="nav-link"
    active-class="active"
  >
    <i class="bi bi-people me-2"></i>
    <span>Utilizatori</span>
  </RouterLink>
</li>

<li class="nav-item">
  <RouterLink
    :to="{ name: 'admin-roles-permissions' }"
    class="nav-link"
    active-class="active"
  >
    <i class="bi bi-shield-lock me-2"></i>
    <span>Roluri & permisiuni</span>
  </RouterLink>
</li>


        <span class="text-uppercase text-muted mt-3 mb-2">Setări</span>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-roles-permissions' }">
          Roluri & permisiuni
        </RouterLink>
        <RouterLink class="nav-link text-white" :to="{ name: 'admin-audit-log' }">
          Audit log
        </RouterLink>
      </nav>
    </aside>

    <div class="flex-grow-1 d-flex flex-column">
      <header class="border-bottom bg-white py-2 px-3 d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Admin / {{ currentRouteName }}
        </div>
        <div class="d-flex align-items-center gap-3">
          <NotificationBell
            :count="adminUnreadCount"
            :to="{ name: 'admin-notifications' }"
            label="Notificări"
          />
          <span class="small text-muted">Utilizator demo (Administrator)</span>
          <button class="btn btn-outline-secondary btn-sm" type="button">
            Logout
          </button>
        </div>
      </header>

      <main class="p-3 flex-grow-1">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useNotificationsStore } from '@/store/notifications'
import NotificationBell from '@/components/common/NotificationBell.vue'

const route = useRoute()
const currentRouteName = route.name ?? 'Dashboard'

const notificationsStore = useNotificationsStore()
const adminUnreadCount = computed(() => notificationsStore.adminUnreadCount)

onMounted(() => {
  notificationsStore.fetchAdminUnreadCount()
})
</script>

<style scoped>
.nav-link {
  padding-left: 0;
}
.nav-link.router-link-active {
  font-weight: 600;
}
</style>
