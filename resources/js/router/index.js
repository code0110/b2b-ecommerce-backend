import { createRouter, createWebHistory } from 'vue-router'

import FrontLayout from '@/layouts/FrontLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'

// Auth
import Login from '@/views/auth/Login.vue'
import Register from '@/views/auth/Register.vue'

// Front
import Home from '@/views/front/Home.vue'
import Promotions from '@/views/front/Promotions.vue'
import PromotionLanding from '@/views/front/PromotionLanding.vue'
import NewProducts from '@/views/front/NewProducts.vue'
import DiscountedProducts from '@/views/front/DiscountedProducts.vue'
import Category from '@/views/front/Category.vue'
import ProductDetails from '@/views/front/ProductDetails.vue'
import SearchResults from '@/views/front/SearchResults.vue'
import CompareProducts from '@/views/front/CompareProducts.vue'
import SalesRepresentatives from '@/views/front/SalesRepresentatives.vue'
import Cart from '@/views/front/Cart.vue'
import Checkout from '@/views/front/Checkout.vue'
import QuickOrder from '@/views/front/QuickOrder.vue'
import BlogList from '@/views/front/content/BlogList.vue'
import BlogPost from '@/views/front/content/BlogPost.vue'
import StaticPage from '@/views/front/content/StaticPage.vue'
import BecomePartner from '@/views/front/BecomePartner.vue'

// Account
import AccountDashboard from '@/views/account/AccountDashboard.vue'
import Orders from '@/views/account/Orders.vue'
import OrderDetails from '@/views/account/OrderDetails.vue'
import Addresses from '@/views/account/Addresses.vue'
import BillingDocuments from '@/views/account/BillingDocuments.vue'
import CompanyUsers from '@/views/account/CompanyUsers.vue'
import RecurringOrders from '@/views/account/RecurringOrders.vue'
import AccountTickets from '@/views/account/AccountTickets.vue'
import AccountOffers from '@/views/account/AccountOffers.vue'
import AccountNotifications from '@/views/account/AccountNotifications.vue'

// Admin
import AdminDashboard from '@/views/admin/Dashboard.vue'
import ProductList from '@/views/admin/products/ProductList.vue'
import ProductForm from '@/views/admin/products/ProductForm.vue'
import CategoryListAdmin from '@/views/admin/categories/CategoryList.vue'
import CategoryFormAdmin from '@/views/admin/categories/CategoryForm.vue'
import BrandList from '@/views/admin/brands/BrandList.vue'
import CustomerList from '@/views/admin/customers/CustomerList.vue'
import CustomerDetails from '@/views/admin/customers/CustomerDetails.vue'
import CustomerGroupList from '@/views/admin/customer-groups/CustomerGroupList.vue'
import ShippingSettings from '@/views/admin/shipping/ShippingSettings.vue'
import Shipments from '@/views/admin/shipping/Shipments.vue'
import PromotionListAdmin from '@/views/admin/promotions/PromotionList.vue'
import PromotionFormAdmin from '@/views/admin/promotions/PromotionForm.vue'
import TicketList from '@/views/admin/tickets/TicketList.vue'
import Collections from '@/views/admin/payments/Collections.vue'
import AdminNotifications from '@/views/admin/notifications/AdminNotifications.vue'
import RolesPermissions from '@/views/admin/settings/RolesPermissions.vue'
import AuditLog from '@/views/admin/settings/AuditLog.vue'
import OfferList from '@/views/admin/offers/OfferList.vue'
import PartnerRequests from '@/views/admin/partners/PartnerRequests.vue'

import { useAuthStore } from '@/store/auth'

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior() {
    return { top: 0 }
  },
  routes: [
    // Autentificare
    {
      path: '/login',
      component: AuthLayout,
      children: [
        {
          path: '',
          name: 'login',
          component: Login,
          meta: { public: true }
        }
      ]
    },
    {
      path: '/register',
      component: AuthLayout,
      children: [
        {
          path: '',
          name: 'register',
          component: Register,
          meta: { public: true }
        }
      ]
    },

    // Front-office public + cont client
    {
      path: '/',
      component: FrontLayout,
      children: [
        { path: '', name: 'home', component: Home },
        { path: 'promotii', name: 'promotions', component: Promotions },
        {
          path: 'promotii/:slug',
          name: 'promotion-landing',
          component: PromotionLanding
        },
        { path: 'noutati', name: 'new-products', component: NewProducts },
        {
          path: 'reduceri',
          name: 'discounted-products',
          component: DiscountedProducts
        },
        {
      path: 'produse',
      name: 'products-list',
      component: Category,
      meta: { public: true }
    },
        {
          path: 'categorie/:slug',
          name: 'category',
          component: Category
        },

        {
          path: 'produs/:slug',
          name: 'product-details',
          component: ProductDetails
        },
        {
          path: 'cautare',
          name: 'search-results',
          component: SearchResults
        },
        {
          path: 'compara',
          name: 'compare-products',
          component: CompareProducts
        },
        {
          path: 'reprezentanti-vanzari',
          name: 'sales-representatives',
          component: SalesRepresentatives
        },
        {
          path: 'cos',
          name: 'cart',
          component: Cart
        },
        {
          path: 'checkout',
          name: 'checkout',
          component: Checkout
        },
        {
          path: 'comanda-rapida',
          name: 'quick-order',
          component: QuickOrder
        },
        {
          path: 'blog',
          name: 'blog-list',
          component: BlogList
        },
        {
          path: 'blog/:slug',
          name: 'blog-post',
          component: BlogPost
        },
        {
          path: 'pagina/:slug',
          name: 'static-page',
          component: StaticPage
        },
        {
          path: 'devino-partener',
          name: 'become-partner',
          component: BecomePartner
        }
      ]
    },

    // Cont client (se afișează în același layout front)
    {
      path: '/cont',
      component: FrontLayout,
      children: [
        {
          path: '',
          name: 'account-dashboard',
          component: AccountDashboard,
          meta: { requiresAuth: true }
        },
        {
          path: 'notificari',
          name: 'account-notifications',
          component: AccountNotifications,
          meta: { requiresAuth: true }
        },
        {
          path: 'comenzi',
          name: 'account-orders',
          component: Orders,
          meta: { requiresAuth: false }
        },
        {
          path: 'oferte',
          name: 'account-offers',
          component: AccountOffers,
          meta: { requiresAuth: true }
        },
        {
          path: 'comenzi/:id',
          name: 'account-order-details',
          component: OrderDetails,
          meta: { requiresAuth: true }
        },
        {
          path: 'adrese',
          name: 'account-addresses',
          component: Addresses,
          meta: { requiresAuth: true }
        },
        {
          path: 'documente',
          name: 'account-documents',
          component: BillingDocuments,
          meta: { requiresAuth: true }
        },
        {
          path: 'tichete',
          name: 'account-tickets',
          component: AccountTickets,
          meta: { requiresAuth: true }
        },
        {
          path: 'comenzi-recurente',
          name: 'account-recurring-orders',
          component: RecurringOrders,
          meta: { requiresAuth: true }
        },
        {
          path: 'utilizatori',
          name: 'account-company-users',
          component: CompanyUsers,
          meta: { requiresAuth: true }
        }
      ]
    },

    // Admin
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: AdminDashboard
        },
        {
          path: 'notificari',
          name: 'admin-notifications',
          component: AdminNotifications
        },
        {
          path: 'products',
          name: 'admin-products',
          component: ProductList
        },
        {
          path: 'products/new',
          name: 'admin-products-new',
          component: ProductForm
        },
        {
          path: 'products/:id',
          name: 'admin-products-edit',
          component: ProductForm
        },
        {
          path: 'categories',
          name: 'admin-categories',
          component: CategoryListAdmin
        },
        {
          path: 'categories/new',
          name: 'admin-categories-new',
          component: CategoryFormAdmin
        },
        {
          path: 'categories/:id',
          name: 'admin-categories-edit',
          component: CategoryFormAdmin
        },
        {
          path: 'brands',
          name: 'admin-brands',
          component: BrandList
        },
        {
          path: 'customers',
          name: 'admin-customers',
          component: CustomerList
        },
        {
          path: 'customers/:id',
          name: 'admin-customer-details',
          component: CustomerDetails
        },
        {
          path: 'customer-groups',
          name: 'admin-customer-groups',
          component: CustomerGroupList
        },
        {
          path: 'partner-requests',
          name: 'admin-partner-requests',
          component: PartnerRequests
        },
        {
          path: 'shipping',
          name: 'admin-shipping',
          component: ShippingSettings
        },
        {
          path: 'shipments',
          name: 'admin-shipments',
          component: Shipments
        },
        {
          path: 'promotions',
          name: 'admin-promotions',
          component: PromotionListAdmin
        },
        {
          path: 'promotions/new',
          name: 'admin-promotions-new',
          component: PromotionFormAdmin
        },
        {
          path: 'promotions/:id',
          name: 'admin-promotions-edit',
          component: PromotionFormAdmin
        },
        {
          path: 'offers',
          name: 'admin-offers',
          component: OfferList
        },
        {
          path: 'tickets',
          name: 'admin-tickets',
          component: TicketList
        },
        {
          path: 'collections',
          name: 'admin-collections',
          component: Collections
        },
        {
          path: 'settings/roles-permissions',
          name: 'admin-roles-permissions',
          component: RolesPermissions
        },
        {
          path: 'settings/audit-log',
          name: 'admin-audit-log',
          component: AuditLog
        }
      ]
    }
  ]
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.public) {
    return next()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({
      name: 'login',
      query: { redirect: to.fullPath }
    })
  }

  if (to.meta.requiresAdmin && authStore.role !== 'admin') {
    // Utilizator logat dar fără drepturi de admin – redirecționăm către dashboard-ul de client.
    return next({ name: 'account-dashboard' })
  }

  return next()
})

export default router
