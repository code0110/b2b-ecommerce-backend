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
import ComparePage from '@/views/front/ComparePage.vue'
import SalesRepresentatives from '@/views/front/SalesRepresentatives.vue'
import Cart from '@/views/front/Cart.vue'
import Checkout from '@/views/front/Checkout.vue'
import QuickOrder from '@/views/front/QuickOrder.vue'
import BlogList from '@/views/front/content/BlogList.vue'
import BlogPost from '@/views/front/content/BlogPost.vue'
import StaticPage from '@/views/front/content/StaticPage.vue'
import BecomePartner from '@/views/front/BecomePartner.vue'
import WishlistPage from '@/views/front/WishlistPage.vue'
import SharedWishlistPage from '@/views/front/SharedWishlistPage.vue'



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
import AgentDashboard from '@/views/account/AgentDashboard.vue'
import AccountAgentRoutes from '@/views/account/AgentRoutes.vue'

// Admin
import AdminDashboard from '@/views/admin/Dashboard.vue'
import ProductList from '@/views/admin/products/ProductList.vue'
import ProductForm from '@/views/admin/products/ProductForm.vue'
import CategoryListAdmin from '@/views/admin/categories/CategoryList.vue'
import CategoryFormAdmin from '@/views/admin/categories/CategoryForm.vue'
import BrandList from '@/views/admin/brands/BrandList.vue'
import AttributeListAdmin from '@/views/admin/attributes/AttributeList.vue'
import AttributeFormAdmin from '@/views/admin/attributes/AttributeForm.vue'
import CustomerList from '@/views/admin/customers/CustomerList.vue'
import CustomerDetails from '@/views/admin/customers/CustomerDetails.vue'
import CustomerGroupList from '@/views/admin/customer-groups/CustomerGroupList.vue'
import ShippingSettings from '@/views/admin/shipping/ShippingSettings.vue'
import Shipments from '@/views/admin/shipping/Shipments.vue'
import PromotionListAdmin from '@/views/admin/promotions/PromotionList.vue'
import PromotionFormAdmin from '@/views/admin/promotions/PromotionForm.vue'
import TicketList from '@/views/admin/tickets/TicketList.vue'
import Collections from '@/views/admin/payments/Collections.vue'
import ReceiptBookList from '@/views/admin/receipt-books/ReceiptBookList.vue'
import AdminNotifications from '@/views/admin/notifications/AdminNotifications.vue'
import RolesPermissions from '@/views/admin/settings/RolesPermissions.vue'
import AuditLog from '@/views/admin/settings/AuditLog.vue'
import OfferList from '@/views/admin/offers/OfferList.vue'
import PartnerRequests from '@/views/admin/partners/PartnerRequests.vue'
import AccountLayout from '@/layouts/AccountLayout.vue';
// sus, lângă celelalte importuri Admin
import AdminOrdersList from '@/views/admin/orders/OrderList.vue';
import AdminOrderDetails from '@/views/admin/orders/OrderDetails.vue';
import AdminUsers from '@/views/admin/settings/Users.vue';
import AgentRoutes from '@/views/admin/routes/AgentRoutes.vue';

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
        { path: '', name: 'home', component: Home, meta: { public: true } },
        { path: 'promotii', name: 'promotions', component: Promotions, meta: { public: true } },
        {
          path: 'promotii/:slug',
          name: 'promotion-landing',
          component: PromotionLanding,
          meta: { public: true }
        },
        { path: 'noutati', name: 'new-products', component: NewProducts, meta: { public: true } },
        {
          path: 'reduceri',
          name: 'discounted-products',
          component: DiscountedProducts,
          meta: { public: true }
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
          path: 'comparare',
          name: 'compare-products',
          component: ComparePage
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
          path: 'blog',
          name: 'blog-list',
          component: BlogList
        },
        {
          path: 'blog/:slug',
          name: 'blog-post',
          component: BlogPost,
          props: true
        },
        {
          path: 'become-partner',
          name: 'become-partner',
          component: BecomePartner
        },
        {
          path: 'favorite',
          name: 'wishlist',
          component: WishlistPage,
          meta: { public: true }
        },
        {
          path: 'wishlist/shared/:token',
          name: 'shared-wishlist',
          component: SharedWishlistPage,
          meta: { public: true }
        },
        {
          path: ':slug',
          name: 'static-page',
          component: StaticPage,
          props: true
        }
      ]
    },

   // Cont client (layout dedicat în interiorul FrontLayout)
{
  path: '/cont',
  component: FrontLayout,
  children: [
    {
      path: '',
      component: AccountLayout,
      meta: { requiresAuth: true },
      children: [
            {
              path: '',
              redirect: { name: 'account-dashboard' }
            },
            {
              path: 'agent',
              name: 'agent-dashboard',
              component: AgentDashboard,
              meta: { requiresAuth: true }
            },
            {
              path: 'director',
              name: 'account-director-dashboard',
              component: () => import('@/views/admin/director/DirectorDashboard.vue'),
              meta: { requiresAuth: true, requiresRole: 'sales_director' }
            },
            {
              path: 'dashboard',
              name: 'account-dashboard',
              component: AccountDashboard,
              meta: { requiresAuth: true }
            },
            {
              path: 'comanda-rapida',
              name: 'quick-order',
              component: QuickOrder,
              meta: { requiresAuth: true }
            },
            {
              path: 'notificari',
              name: 'account-notifications',
              component: AccountNotifications,
              meta: { requiresAuth: true }
            },
            {
              path: 'notificari/setari',
              name: 'account-notification-settings',
              component: () => import('@/views/account/NotificationSettings.vue'),
              meta: { requiresAuth: true }
            },
            {
              path: 'comenzi',
          name: 'account-orders',
          component: Orders,
          meta: { requiresAuth: true }
        },
        {
          path: 'oferte',
          name: 'account-offers',
          component: AccountOffers,
          meta: { requiresAuth: true }
        },
        {
          path: 'oferte-agent',
          name: 'account-offers-list',
          component: () => import('@/views/admin/offers/OfferList.vue'),
          meta: { requiresAuth: true, requiresRole: ['sales_agent', 'sales_director'] }
        },
        {
          path: 'oferte-agent/noua',
          name: 'account-offers-new',
          component: () => import('@/views/admin/offers/OfferForm.vue'),
          meta: { requiresAuth: true, requiresRole: ['sales_agent', 'sales_director'] }
        },
        {
          path: 'oferte-agent/:id',
          name: 'account-offers-edit',
          component: () => import('@/views/admin/offers/OfferForm.vue'),
          meta: { requiresAuth: true, requiresRole: ['sales_agent', 'sales_director'] }
        },
        {
          path: 'cereri-oferta',
          name: 'account-quote-requests',
          component: () => import('@/views/account/AccountQuoteRequests.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'cereri-oferta/noua',
          name: 'account-quote-requests-new',
          component: () => import('@/views/account/AccountQuoteRequestNew.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'cereri-oferta/:id',
          name: 'account-quote-requests-show',
          component: () => import('@/views/account/AccountQuoteRequestDetails.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'cere-oferta',
          name: 'account-request-quote',
          component: () => import('@/views/account/RequestQuote.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'oferte/:id',
          name: 'account-offer-details',
          component: () => import('@/views/account/AccountOfferDetails.vue'),
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
        },
        {
          path: 'rute',
          name: 'account-agent-routes',
          component: AccountAgentRoutes,
          meta: { requiresAuth: true }
        },
        {
          path: 'rapoarte',
          name: 'account-reports',
          component: () => import('@/views/admin/reports/ReportsDashboard.vue'),
          meta: { requiresAuth: true, requiresRole: 'sales_director' }
        },
        {
          path: 'rapoarte/locatii',
          name: 'account-locations-report',
          component: () => import('@/views/admin/reports/LocationsReport.vue'),
          meta: { requiresAuth: true, requiresRole: 'sales_director' }
        },
        {
          path: 'rapoarte/istoric-rute',
          name: 'account-route-history',
          component: () => import('@/views/admin/reports/RouteHistory.vue'),
          meta: { requiresAuth: true, requiresRole: 'sales_director' }
        },
        {
          path: 'obiective',
          name: 'account-targets',
          component: () => import('@/views/admin/targets/SalesTargets.vue'),
          meta: { requiresAuth: true, requiresRole: 'sales_director' }
        },
        {
          path: 'management-rute',
          name: 'account-routes-management',
          component: () => import('@/views/admin/routes/AgentRoutes.vue'),
          meta: { requiresAuth: true, requiresRole: 'sales_director' }
        },
        {
          path: 'comenzi-aprobate',
          name: 'account-director-approvals',
          component: () => import('@/views/account/AccountDirectorApprovals.vue'),
          meta: { requiresAuth: true, requiresRole: 'sales_director' }
        }
      ]
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
          path: 'reports',
          name: 'admin-reports',
          component: () => import('@/views/admin/reports/ReportsDashboard.vue')
        },
        {
          path: 'reports/locations',
          name: 'admin-reports-locations',
          component: () => import('@/views/admin/reports/LocationsReport.vue')
        },
        {
          path: 'reports/route-history',
          name: 'admin-reports-route-history',
          component: () => import('@/views/admin/reports/RouteHistory.vue'),
          meta: { requiresAuth: true, requiresRole: ['admin', 'sales_director'] } // Update permissions
        },
        {
          path: 'targets',
          name: 'admin-targets',
          component: () => import('@/views/admin/targets/SalesTargets.vue')
        },
        {
          path: 'director-dashboard',
          name: 'director-dashboard',
          component: () => import('@/views/admin/director/DirectorDashboard.vue')
        },
        {
          path: 'orders',
          name: 'admin-orders',
          component: AdminOrdersList
        },
        {
          path: 'orders/quick-order',
          name: 'admin-quick-order',
          component: QuickOrder
        },
        {
          path: 'orders/:id',
  name: 'admin-order-details',
  component: AdminOrderDetails
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
          path: 'attributes',
          name: 'admin-attributes',
          component: AttributeListAdmin
        },
        {
          path: 'attributes/new',
          name: 'admin-attributes-new',
          component: AttributeFormAdmin
        },
        {
          path: 'attributes/:id',
          name: 'admin-attributes-edit',
          component: AttributeFormAdmin
        },
        {
          path: 'pages',
          name: 'admin-pages',
          component: () => import('@/views/admin/content/PageList.vue')
        },
        {
          path: 'pages/new',
          name: 'admin-pages-create',
          component: () => import('@/views/admin/content/PageForm.vue')
        },
        {
          path: 'pages/:id',
          name: 'admin-pages-edit',
          component: () => import('@/views/admin/content/PageForm.vue')
        },
        {
          path: 'content-blocks',
          name: 'admin-content-blocks',
          component: () => import('@/views/admin/content/ContentBlockList.vue')
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
          path: 'offers/new',
          name: 'admin-offers-new',
          component: () => import('@/views/admin/offers/OfferForm.vue')
        },
        {
          path: 'offers/:id',
          name: 'admin-offers-edit',
          component: () => import('@/views/admin/offers/OfferForm.vue')
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
          path: 'receipt-books',
          name: 'admin-receipt-books',
          component: ReceiptBookList
        },
        {
          path: 'routes',
          name: 'admin-agent-routes',
          component: AgentRoutes
        },
        {
          path: 'sales-representatives',
          name: 'admin-sales-reps',
          component: () => import('@/views/admin/sales-reps/SalesRepList.vue')
        },
        {
          path: 'sales-representatives/create',
          name: 'admin-sales-reps-create',
          component: () => import('@/views/admin/sales-reps/SalesRepForm.vue')
        },
        {
          path: 'sales-representatives/:id',
          name: 'admin-sales-reps-edit',
          component: () => import('@/views/admin/sales-reps/SalesRepForm.vue')
        },
        {
          path: 'settings/general',
          name: 'admin-settings-general',
          component: () => import('@/views/admin/settings/GeneralSettings.vue')
        },
        {
          path: 'settings/offers',
          name: 'admin-settings-offers',
          component: () => import('@/views/admin/settings/OfferSettings.vue')
        },
        {
          path: 'settings/financial-risk',
          name: 'admin-settings-financial-risk',
          component: () => import('@/views/admin/settings/FinancialSettings.vue')
        },
        {
          path: 'settings/users',
  name: 'admin-users',
  component: AdminUsers
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

  if (to.meta.requiresAdmin) {
    // Restrict access strictly to admin role
    if (authStore.role === 'admin') {
      return next();
    }
    
    // Utilizator logat dar fără drepturi de admin – redirecționăm către dashboard-ul corespunzător
    if (authStore.role === 'sales_director') {
      return next({ name: 'account-director-dashboard' });
    } else if (authStore.role === 'sales_agent') {
      return next({ name: 'agent-dashboard' });
    } else {
      return next({ name: 'account-dashboard' });
    }
  }

  return next()
})

export default router
