<template>
  <div class="admin-layout d-flex min-vh-100">
    <!-- Sidebar -->
    <aside class="sidebar bg-dark text-white d-flex flex-column" :class="{ 'sidebar-collapsed': isCollapsed }" style="transition: all 0.3s;">
      <div class="sidebar-header p-3 d-flex align-items-center justify-content-between border-bottom border-secondary">
        <div class="d-flex align-items-center">
          <div class="brand-badge me-2">MB</div>
          <span class="fw-bold fs-5 tracking-tight sidebar-title">Admin Panel</span>
        </div>
        <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-1" @click="toggleSidebar" title="Comută sidebar">
          <i :class="isCollapsed ? 'bi bi-chevron-double-right' : 'bi bi-chevron-double-left'"></i>
        </button>
      </div>

      <div class="sidebar-content flex-grow-1 overflow-auto custom-scrollbar p-2">
        <nav class="nav flex-column gap-1">
          
          <div class="nav-section-title text-uppercase text-muted fw-bold small mt-3 mb-2 px-2">General</div>
          
          <RouterLink :to="{ name: 'admin-dashboard' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Dashboard">
            <i class="bi bi-speedometer2 me-2"></i> <span class="link-text">Dashboard</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-notifications' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Notificări">
            <i class="bi bi-bell me-2"></i> <span class="link-text">Notificări</span>
            <span v-if="adminUnreadCount > 0" class="badge bg-danger ms-auto rounded-pill sidebar-badge">{{ adminUnreadCount }}</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-orders' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Comenzi">
            <i class="bi bi-bag-check me-2"></i> <span class="link-text">Comenzi</span>
          </RouterLink>

          <RouterLink :to="{ name: 'admin-reports' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Rapoarte">
            <i class="bi bi-bar-chart-line me-2"></i> <span class="link-text">Rapoarte</span>
          </RouterLink>

          <RouterLink :to="{ name: 'admin-reports-locations' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Raport Locații">
            <i class="bi bi-geo-alt me-2"></i> <span class="link-text">Raport Locații</span>
          </RouterLink>

          <RouterLink :to="{ name: 'admin-reports-route-history' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Istoric Rute">
            <i class="bi bi-clock-history me-2"></i> <span class="link-text">Istoric Rute</span>
          </RouterLink>

          <RouterLink :to="{ name: 'admin-targets' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Obiective KPI">
            <i class="bi bi-bullseye me-2"></i> <span class="link-text">Obiective KPI</span>
          </RouterLink>

          <div class="nav-section-title text-uppercase text-muted fw-bold small mt-4 mb-2 px-2">Catalog</div>
          
          <RouterLink :to="{ name: 'admin-products' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Produse">
            <i class="bi bi-box-seam me-2"></i> <span class="link-text">Produse</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-categories' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Categorii">
            <i class="bi bi-grid me-2"></i> <span class="link-text">Categorii</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-brands' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Mărci">
            <i class="bi bi-tag me-2"></i> <span class="link-text">Mărci</span>
          </RouterLink>

          <div class="nav-section-title text-uppercase text-muted fw-bold small mt-4 mb-2 px-2">Clienți & Vânzări</div>
          
          <RouterLink :to="{ name: 'admin-customers' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Clienți">
            <i class="bi bi-people me-2"></i> <span class="link-text">Clienți</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-customer-groups' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Grupuri clienți">
            <i class="bi bi-people-fill me-2"></i> <span class="link-text">Grupuri clienți</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-promotions' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Promoții">
            <i class="bi bi-percent me-2"></i> <span class="link-text">Promoții</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-offers' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Oferte B2B">
            <i class="bi bi-file-earmark-text me-2"></i> <span class="link-text">Oferte B2B</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-agent-routes' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Planificare Rute">
            <i class="bi bi-map me-2"></i> <span class="link-text">Planificare Rute</span>
          </RouterLink>

          <div class="nav-section-title text-uppercase text-muted fw-bold small mt-4 mb-2 px-2">Conținut</div>
          <RouterLink :to="{ name: 'admin-pages' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Pagini">
            <i class="bi bi-file-richtext me-2"></i> <span class="link-text">Pagini</span>
          </RouterLink>
          <RouterLink :to="{ name: 'admin-content-blocks' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Blocuri Conținut">
            <i class="bi bi-layout-text-window-reverse me-2"></i> <span class="link-text">Blocuri Conținut</span>
          </RouterLink>

          <div class="nav-section-title text-uppercase text-muted fw-bold small mt-4 mb-2 px-2">Operațional</div>
          
          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-shipping' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Livrare & Config">
            <i class="bi bi-truck me-2"></i> <span class="link-text">Livrare & Config</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-shipments' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="AWB & Expedieri">
            <i class="bi bi-upc-scan me-2"></i> <span class="link-text">AWB & Expedieri</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-tickets' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Tichete Suport">
            <i class="bi bi-chat-left-text me-2"></i> <span class="link-text">Tichete Suport</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-collections' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Încasări">
            <i class="bi bi-cash-stack me-2"></i> <span class="link-text">Încasări</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-receipt-books' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Chitanțiere">
            <i class="bi bi-receipt me-2"></i> <span class="link-text">Chitanțiere</span>
          </RouterLink>

          <div v-if="authStore.role === 'admin'" class="nav-section-title text-uppercase text-muted fw-bold small mt-4 mb-2 px-2">Sistem & Utilizatori</div>
          
          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-settings-offers' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Configurare Oferte">
            <i class="bi bi-sliders me-2"></i> <span class="link-text">Configurare Oferte</span>
          </RouterLink>
          
          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-users' }" exact-active-class="active-link" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" :class="{ 'active-link': route.name === 'admin-users' && !route.query.role }" title="Toți Utilizatorii">
            <i class="bi bi-person-badge me-2"></i> <span class="link-text">Toți Utilizatorii</span>
          </RouterLink>
          
          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-users', query: { role: 'sales_agent' } }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Agenți Vânzări">
            <i class="bi bi-person-workspace me-2"></i> <span class="link-text">Agenți Vânzări</span>
          </RouterLink>
          
          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-users', query: { role: 'sales_director' } }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Directori Vânzări">
            <i class="bi bi-person-video me-2"></i> <span class="link-text">Directori Vânzări</span>
          </RouterLink>
          
          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-roles-permissions' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Roluri & Permisiuni">
            <i class="bi bi-shield-lock me-2"></i> <span class="link-text">Roluri & Permisiuni</span>
          </RouterLink>
          
          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-audit-log' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Audit Log">
            <i class="bi bi-journal-text me-2"></i> <span class="link-text">Audit Log</span>
          </RouterLink>

        </nav>
      </div>
      
      <div class="sidebar-footer p-3 border-top border-secondary bg-dark-darker">
        <div class="d-flex align-items-center">
            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                {{ (authStore.user?.first_name?.[0] || 'A').toUpperCase() }}
            </div>
            <div class="flex-grow-1 overflow-hidden footer-info">
                <div class="small fw-bold text-truncate">{{ authStore.user?.first_name }} {{ authStore.user?.last_name }}</div>
                <div class="text-muted extra-small text-truncate">Administrator</div>
            </div>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-grow-1 d-flex flex-column bg-light" style="height: 100vh; overflow: hidden;">
      
      <!-- Top Header -->
      <header class="bg-white border-bottom shadow-sm px-4 py-3 d-flex justify-content-between align-items-center z-10">
        <div class="d-flex align-items-center">
            <h5 class="mb-0 text-dark fw-semibold">{{ formatRouteName(currentRouteName) }}</h5>
        </div>
        
        <div class="d-flex align-items-center gap-3">
          <NotificationBell
            :count="adminUnreadCount"
            :to="{ name: 'admin-notifications' }"
            label="Notificări"
          />
          <div class="vr mx-2"></div>
          <button @click="logout" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-2 transition-all hover-scale">
            <i class="bi bi-box-arrow-right"></i>
            <span>Deconectare</span>
          </button>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-grow-1 overflow-auto p-4 custom-scrollbar">
        <div class="container-fluid p-0">
             <RouterView />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useNotificationsStore } from '@/store/notifications'
import { useAuthStore } from '@/store/auth'
import NotificationBell from '@/components/common/NotificationBell.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()

const currentRouteName = computed(() => route.name)
const adminUnreadCount = computed(() => notificationsStore.adminUnreadCount)

const isCollapsed = ref(localStorage.getItem('adminSidebarCollapsed') === '1')
const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value
  localStorage.setItem('adminSidebarCollapsed', isCollapsed.value ? '1' : '0')
}

const logout = async () => {
  try {
    await authStore.logout()
    router.push('/login')
  } catch (e) {
    console.error('Logout error:', e)
    // Force redirect even on error
    router.push('/login')
  }
}

const formatRouteName = (name) => {
    if (!name) return 'Dashboard';
    // Remove 'admin-' prefix and capitalize
    const cleanName = String(name).replace('admin-', '').replace('-', ' ');
    return cleanName.charAt(0).toUpperCase() + cleanName.slice(1);
}

onMounted(() => {
  notificationsStore.fetchAdminUnreadCount()
  visitStore.checkActiveVisit()
})
</script>

<style scoped>
.sidebar {
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    z-index: 1000;
    width: 260px;
    background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
}

.bg-dark-darker {
    background-color: rgba(0,0,0,0.2);
}

.nav-link {
    transition: all 0.2s ease;
    opacity: 0.85;
    font-size: 0.9rem;
    position: relative;
    border-radius: 8px;
}

.nav-link:hover {
    background-color: rgba(255,255,255,0.08);
    opacity: 1;
    transform: translateX(4px);
}

.active-link {
    background-color: var(--bs-primary) !important;
    color: white !important;
    opacity: 1;
    box-shadow: 0 4px 6px rgba(0,0,0,0.15);
}

.active-link::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: rgba(255,255,255,0.9);
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
}

.nav-section-title {
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    color: #adb5bd !important;
}

.extra-small {
    font-size: 0.75rem;
}

.tracking-tight {
    letter-spacing: -0.5px;
}

.sidebar-collapsed {
    width: 80px;
}

.sidebar-collapsed .sidebar-title,
.sidebar-collapsed .nav-section-title,
.sidebar-collapsed .link-text,
.sidebar-collapsed .footer-info,
.sidebar-collapsed .sidebar-badge {
    display: none !important;
}

.sidebar-collapsed .nav-link {
    justify-content: center;
}

.brand-badge {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #10b981);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    letter-spacing: 0.5px;
}

/* Scrollbar Styling */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.sidebar .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.hover-scale:hover {
    transform: scale(1.05);
}
</style>
