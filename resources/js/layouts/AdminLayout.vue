<template>
  <div class="admin-layout d-flex min-vh-100" data-bs-theme="light">
    <!-- Sidebar -->
    <aside class="sidebar bg-dark text-white d-flex flex-column" :class="{ 'sidebar-collapsed': isCollapsed }" style="transition: all 0.3s;">
      <div class="sidebar-header p-3 d-flex align-items-center justify-content-between border-bottom border-secondary">
        <div class="d-flex align-items-center">
          <img 
            v-if="siteConfig.config.site_logo"
            :src="siteConfig.config.site_logo" 
            :alt="siteConfig.config.site_name" 
            style="height: 32px; width: auto; object-fit: contain;"
            class="me-2"
          >
          <div v-else class="brand-badge me-2">{{ (siteConfig.config.site_name || 'MB').substring(0, 2).toUpperCase() }}</div>
          <span class="fw-bold fs-5 tracking-tight sidebar-title">Admin Panel</span>
        </div>
        <button class="btn btn-sm btn-outline-light d-flex align-items-center gap-1" @click="toggleSidebar" title="Comută sidebar">
          <i :class="isCollapsed ? 'bi bi-chevron-double-right' : 'bi bi-chevron-double-left'"></i>
        </button>
      </div>

      <div class="sidebar-content flex-grow-1 overflow-auto custom-scrollbar p-2">
        <nav class="nav flex-column gap-1">
          
          <div class="nav-section-title text-uppercase text-muted fw-bold small mt-3 mb-2 px-2">General</div>
          
          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-dashboard' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Dashboard">
            <i class="bi bi-speedometer2 me-2"></i> <span class="link-text">Dashboard</span>
          </RouterLink>
          
          <RouterLink :to="{ name: 'admin-notifications' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Notificări">
            <i class="bi bi-bell me-2"></i> <span class="link-text">Notificări</span>
            <span v-if="adminUnreadCount > 0" class="badge bg-danger ms-auto rounded-pill sidebar-badge">{{ adminUnreadCount }}</span>
          </RouterLink>
          
          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-orders' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Comenzi">
            <i class="bi bi-bag-check me-2"></i> <span class="link-text">Comenzi</span>
          </RouterLink>

          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-reports' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Rapoarte">
            <i class="bi bi-bar-chart-line me-2"></i> <span class="link-text">Rapoarte</span>
          </RouterLink>

          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-reports-locations' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Raport Locații">
            <i class="bi bi-geo-alt me-2"></i> <span class="link-text">Raport Locații</span>
          </RouterLink>

          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-reports-route-history' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Istoric Rute">
            <i class="bi bi-clock-history me-2"></i> <span class="link-text">Istoric Rute</span>
          </RouterLink>

          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-targets' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Obiective KPI">
            <i class="bi bi-bullseye me-2"></i> <span class="link-text">Obiective KPI</span>
          </RouterLink>

          <div v-if="isAdmin || isAgentOrDirector" class="nav-section-title text-uppercase text-muted fw-bold small mt-4 mb-2 px-2">Catalog</div>
          
          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-products' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Produse">
            <i class="bi bi-box-seam me-2"></i> <span class="link-text">Produse</span>
          </RouterLink>
          
          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-categories' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Categorii">
            <i class="bi bi-grid me-2"></i> <span class="link-text">Categorii</span>
          </RouterLink>
          
          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-brands' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Mărci">
            <i class="bi bi-tag me-2"></i> <span class="link-text">Mărci</span>
          </RouterLink>

          <div v-if="isAdmin || isAgentOrDirector" class="nav-section-title text-uppercase text-muted fw-bold small mt-4 mb-2 px-2">Clienți & Vânzări</div>
          
          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-users' }" exact-active-class="active-link" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" :class="{ 'active-link': route.name === 'admin-users' }" title="Utilizatori & Clienți">
            <i class="bi bi-person-badge me-2"></i> <span class="link-text">Utilizatori & Clienți</span>
          </RouterLink>
          
<<<<<<< HEAD
          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-sales-reps' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Reprezentanți">
            <i class="bi bi-person-badge me-2"></i> <span class="link-text">Reprezentanți</span>
          </RouterLink>
          
=======
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-customer-groups' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Grupuri clienți">
            <i class="bi bi-people-fill me-2"></i> <span class="link-text">Grupuri clienți</span>
          </RouterLink>
          
          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-promotions' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Promoții">
            <i class="bi bi-percent me-2"></i> <span class="link-text">Promoții</span>
          </RouterLink>
          
          <RouterLink v-if="isAdmin || isAgentOrDirector" :to="{ name: 'admin-offers' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Oferte B2B">
            <i class="bi bi-file-earmark-text me-2"></i> <span class="link-text">Oferte B2B</span>
          </RouterLink>
          
          <RouterLink v-if="isAgentOrDirector || isAdmin" :to="{ name: 'admin-agent-routes' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Planificare Rute">
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
          
          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-settings-general' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Setări Generale">
            <i class="bi bi-gear me-2"></i> <span class="link-text">Setări Generale</span>
          </RouterLink>
<<<<<<< HEAD

          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-settings-offers' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Reguli Discount">
            <i class="bi bi-sliders me-2"></i> <span class="link-text">Reguli Discount</span>
          </RouterLink>

          <RouterLink v-if="authStore.role === 'admin'" :to="{ name: 'admin-settings-financial-risk' }" class="nav-link text-white rounded d-flex align-items-center px-3 py-2" active-class="active-link" title="Setări Risc Financiar">
            <i class="bi bi-cash-coin me-2"></i> <span class="link-text">Risc Financiar</span>
          </RouterLink>
=======
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
          
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
                <div class="text-muted extra-small text-truncate">{{ roleLabel }}</div>
            </div>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main flex-grow-1 d-flex flex-column" style="height: 100vh; overflow: hidden;">
      
      <!-- Top Header -->
      <header class="admin-topbar px-4 py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
          <button class="btn btn-sm btn-light border d-flex align-items-center justify-content-center topbar-icon-btn" type="button" @click="toggleSidebar" title="Meniu">
            <i class="bi bi-list fs-5"></i>
          </button>

          <div class="admin-search d-none d-md-flex align-items-center gap-2 flex-grow-1">
            <i class="bi bi-search text-muted"></i>
            <input
              v-model="topbarSearch"
              class="form-control form-control-sm border-0 shadow-none bg-transparent"
              type="search"
              placeholder="Caută..."
              aria-label="Caută"
            />
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-light border d-flex align-items-center justify-content-center topbar-icon-btn" type="button" title="Comenzi rapide">
            <i class="bi bi-grid"></i>
          </button>

          <NotificationBell
            :count="adminUnreadCount"
            :to="{ name: 'admin-notifications' }"
            label="Notificări"
          />

          <div class="dropdown">
            <button
              class="btn btn-sm btn-light border d-flex align-items-center gap-2 px-2"
              type="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <span class="user-avatar d-inline-flex align-items-center justify-content-center">
                {{ userInitials }}
              </span>
              <span class="d-none d-md-inline text-truncate" style="max-width: 180px;">
                {{ displayName }}
              </span>
              <i class="bi bi-chevron-down small text-muted"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
              <li class="px-3 py-2 small text-muted">
                {{ authStore.role === 'admin' ? 'Administrator' : (authStore.role || 'Utilizator') }}
              </li>
              <li><hr class="dropdown-divider" /></li>
              <li>
                <button class="dropdown-item d-flex align-items-center gap-2" type="button" @click="logout">
                  <i class="bi bi-box-arrow-right"></i>
                  Deconectare
                </button>
              </li>
            </ul>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main 
        class="flex-grow-1"
        :class="[
          isFullscreenPage ? 'd-flex flex-column overflow-hidden p-0' : 'overflow-auto p-4 custom-scrollbar'
        ]"
      >
        <div :class="isFullscreenPage ? 'flex-grow-1 d-flex flex-column' : 'container-fluid p-0'">
             <RouterView :key="$route.fullPath" />
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
import { useVisitStore } from '@/store/visit'
import { useSiteConfigStore } from '@/store/siteConfig'
import NotificationBell from '@/components/common/NotificationBell.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()
const visitStore = useVisitStore()
const siteConfig = useSiteConfigStore()

const currentRouteName = computed(() => route.name)
const isFullscreenPage = computed(() => ['admin-quick-order'].includes(route.name))
const adminUnreadCount = computed(() => notificationsStore.adminUnreadCount)

const isImpersonating = computed(() => !!authStore.impersonatedCustomer || !!localStorage.getItem('impersonated_client_id') || !!sessionStorage.getItem('impersonating'));

const isCustomer = computed(() => {
  if (!authStore.user) return false;
  
  // 1. Explicit checks for customer indicators
  if (authStore.user.customer_id || authStore.user.customer) return true;
  
  // Impersonation acts as customer
  if (isImpersonating.value) return true;

  // Role checks
  const currentRole = String(authStore.role || '').toLowerCase();
  if (['customer', 'customer_b2b', 'customer_b2c', 'b2b', 'b2c'].includes(currentRole)) return true;
  if (currentRole.includes('customer')) return true;

  const roles = (authStore.user.roles || []).map(r => (r.slug || r.code || '').toLowerCase());
  if (roles.some(r => r.includes('customer') || r === 'b2b' || r === 'b2c')) return true;

  // 2. Check for staff roles
  const hasStaffRole = roles.some(r => ['admin', 'sales_agent', 'sales_director', 'operator', 'manager'].includes(r));
  
  if (hasStaffRole) return false;

  // 3. Fallback: if not staff, assume customer
  return true;
});

const isAgentOrDirector = computed(() => {
  if (isCustomer.value) return false;
  if (authStore.user?.customer_id || authStore.user?.customer) return false;
  
  // Check impersonation again to be sure
  if (isImpersonating.value) return false;

  const roles = (authStore.user?.roles || []).map(r => (r.slug || r.code || '').toLowerCase());
  return roles.includes('sales_agent') || roles.includes('sales_director') || roles.includes('admin') || authStore.role === 'admin';
});

const roleLabel = computed(() => {
  if (authStore.role === 'admin') return 'Administrator';
  if (isAgentOrDirector.value) return 'Agent Vânzări';
  if (isCustomer.value) return 'Client B2B';
  return 'Utilizator';
});

const topbarSearch = ref('')

const isCollapsed = ref(localStorage.getItem('adminSidebarCollapsed') === '1')
const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value
  localStorage.setItem('adminSidebarCollapsed', isCollapsed.value ? '1' : '0')
}

const displayName = computed(() => {
  return authStore.user?.name || `${authStore.user?.first_name || ''} ${authStore.user?.last_name || ''}`.trim() || 'Utilizator'
})

const userInitials = computed(() => {
  const name = displayName.value
  const parts = String(name).split(' ').filter(Boolean)
  if (parts.length === 0) return 'U'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[1][0]).toUpperCase()
})

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
  siteConfig.fetchConfig()
  notificationsStore.fetchAdminUnreadCount()
  visitStore.checkActiveVisit()
})
</script>

<style scoped>
.admin-layout {
    --bs-primary: #7367f0;
    --bs-primary-rgb: 115, 103, 240;
    --vx-bg: #f8f7fa;
    --vx-card-bg: #ffffff;
    --vx-card-radius: 0.6rem;
    --vx-card-shadow: 0 6px 24px rgba(47, 43, 61, 0.08);
    background: var(--vx-bg);
}

.admin-main {
    background: var(--vx-bg);
}

.admin-topbar {
    position: sticky;
    top: 0;
    z-index: 1020;
    background: rgba(248, 247, 250, 0.9);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(47, 43, 61, 0.08);
}

.topbar-icon-btn {
    width: 38px;
    height: 38px;
    border-radius: 0.5rem;
}

.admin-search {
    max-width: 520px;
    padding: 0.35rem 0.75rem;
    border-radius: 0.6rem;
    border: 1px solid rgba(47, 43, 61, 0.08);
    background: rgba(255, 255, 255, 0.9);
}

.user-avatar {
    width: 28px;
    height: 28px;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #ffffff;
    background: linear-gradient(135deg, rgba(115, 103, 240, 1), rgba(0, 207, 232, 1));
}

.sidebar {
    position: relative;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    z-index: 1000;
    width: 260px;
    background: linear-gradient(180deg, #283046 0%, #1f2437 100%);
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
    cursor: pointer;
    z-index: 10;
}

.active-link {
    background: linear-gradient(118deg, #7367f0, rgba(115, 103, 240, 0.7));
    box-shadow: 0 0 10px 1px rgba(115, 103, 240, 0.7);
    opacity: 1;
    font-weight: 600;
    color: #fff !important;
}

.nav-link:hover:not(.active-link) {
    background: rgba(255, 255, 255, 0.05);
    transform: translateX(3px);
    opacity: 1;
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
