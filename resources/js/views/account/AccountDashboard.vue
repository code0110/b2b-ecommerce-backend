<template>
  <div class="bg-light border-bottom py-3 mb-4">
    <div class="container">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h1 class="h4 mb-1">Contul meu</h1>
          <p class="text-muted mb-0">
            Rezumat situație comenzi, facturi, credit și acces rapid la toate secțiunile contului.
          </p>
        </div>
        <div class="text-end small">
          <div class="fw-semibold">
            Bun venit, {{ overview?.user?.name || 'Client' }}
          </div>
          <div class="text-muted">
            {{ overview?.customer?.type === 'b2b' ? 'Client B2B' : 'Client B2C' }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container mb-5">
    <div class="row">
      <!-- Sidebar cont client -->
      <aside class="col-lg-3 mb-4 mb-lg-0">
        <div class="card mb-3 shadow-sm">
          <div class="card-body d-flex">
            <div
              class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
              style="width: 48px; height: 48px;"
            >
              <span class="fw-semibold">
                {{ initials }}
              </span>
            </div>
            <div class="flex-grow-1">
              <div class="fw-semibold">
                {{ overview?.user?.name || '-' }}
              </div>
              <div class="small text-muted">
                {{ overview?.user?.email }}
              </div>
              <div class="small mt-1" v-if="overview?.customer">
                <span class="badge bg-light text-dark border">
                  {{ overview.customer.name }}
                </span>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white border-top small text-muted" v-if="overview?.customer">
            <div class="d-flex justify-content-between">
              <span>Tip client</span>
              <span class="fw-semibold text-uppercase">
                {{ overview.customer.type }}
              </span>
            </div>
          </div>
        </div>

        <nav class="card shadow-sm">
          <div class="card-header bg-white fw-semibold">
            Meniu cont
          </div>
          <div class="list-group list-group-flush small">
            <RouterLink
              :to="{ name: 'account-dashboard' }"
              class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
              :class="{ active: route.name === 'account-dashboard' }"
            >
              <span>
                <i class="bi bi-speedometer2 me-2"></i>
                Dashboard
              </span>
            </RouterLink>

            <RouterLink
              :to="{ name: 'account-orders' }"
              class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
              :class="{ active: route.name === 'account-orders' }"
            >
              <span>
                <i class="bi bi-basket2 me-2"></i>
                Comenzi
              </span>
              <span class="badge bg-light text-dark" v-if="overview?.orders">
                {{ overview.orders.total }}
              </span>
            </RouterLink>

            <RouterLink
              :to="{ name: 'account-offers' }"
              class="list-group-item list-group-item-action"
              :class="{ active: route.name === 'account-offers' }"
            >
              <i class="bi bi-tags me-2"></i>
              Oferte & negocieri
            </RouterLink>

            <RouterLink
              :to="{ name: 'account-documents' }"
              class="list-group-item list-group-item-action"
              :class="{ active: route.name === 'account-documents' }"
            >
              <i class="bi bi-receipt me-2"></i>
              Documente financiare
            </RouterLink>

            <RouterLink
              :to="{ name: 'account-recurring-orders' }"
              class="list-group-item list-group-item-action"
              :class="{ active: route.name === 'account-recurring-orders' }"
            >
              <i class="bi bi-arrow-repeat me-2"></i>
              Comenzi recurente
            </RouterLink>

            <RouterLink
              :to="{ name: 'account-company-users' }"
              class="list-group-item list-group-item-action"
              :class="{ active: route.name === 'account-company-users' }"
            >
              <i class="bi bi-people me-2"></i>
              Utilizatori companie
            </RouterLink>

            <RouterLink
              :to="{ name: 'account-addresses' }"
              class="list-group-item list-group-item-action"
              :class="{ active: route.name === 'account-addresses' }"
            >
              <i class="bi bi-geo-alt me-2"></i>
              Adrese livrare / facturare
            </RouterLink>

            <RouterLink
              :to="{ name: 'account-tickets' }"
              class="list-group-item list-group-item-action"
              :class="{ active: route.name === 'account-tickets' }"
            >
              <i class="bi bi-life-preserver me-2"></i>
              Tichete suport
            </RouterLink>

            <RouterLink
              :to="{ name: 'account-notifications' }"
              class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
              :class="{ active: route.name === 'account-notifications' }"
            >
              <span>
                <i class="bi bi-bell me-2"></i>
                Notificări
              </span>
              <span class="badge bg-danger" v-if="unreadNotifications > 0">
                {{ unreadNotifications }}
              </span>
            </RouterLink>
          </div>
        </nav>
      </aside>

      <!-- Conținut principal dashboard -->
      <section class="col-lg-9">
        <!-- Loading / error -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary mb-3" role="status"></div>
          <div class="text-muted">Se încarcă datele contului...</div>
        </div>

        <div v-else-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <div v-else>
          <!-- KPIs -->
          <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small">Comenzi totale</div>
                    <i class="bi bi-basket2 fs-5 text-primary"></i>
                  </div>
                  <div class="h4 mb-0">
                    {{ overview?.orders?.total ?? 0 }}
                  </div>
                  <div class="small text-muted mt-1">
                    {{ overview?.orders?.open ?? 0 }} în derulare
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6 col-xl-3">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small">Sold & credit</div>
                    <i class="bi bi-credit-card-2-front fs-5 text-success"></i>
                  </div>
                  <div class="small text-muted">
                    Limită credit
                  </div>
                  <div class="fw-semibold">
                    {{ formatMoney(overview?.customer?.credit_limit) }}
                  </div>
                  <div class="small text-muted mt-1">
                    Sold curent: <span class="fw-semibold">
                      {{ formatMoney(overview?.customer?.current_sold) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6 col-xl-3">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small">Facturi recente</div>
                    <i class="bi bi-receipt fs-5 text-warning"></i>
                  </div>
                  <div class="h4 mb-0">
                    {{ invoicesCount }}
                  </div>
                  <div class="small text-muted mt-1">
                    Ultimele 30 de zile
                  </div>
                  <RouterLink
                    :to="{ name: 'account-documents' }"
                    class="small mt-2 d-inline-block text-decoration-none"
                  >
                    Vezi toate facturile →
                  </RouterLink>
                </div>
              </div>
            </div>

            <div class="col-md-6 col-xl-3">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small">Notificări</div>
                    <i class="bi bi-bell fs-5 text-danger"></i>
                  </div>
                  <div class="h4 mb-0">
                    {{ unreadNotifications }}
                  </div>
                  <div class="small text-muted mt-1">
                    necitite în cont
                  </div>
                  <RouterLink
                    :to="{ name: 'account-notifications' }"
                    class="small mt-2 d-inline-block text-decoration-none"
                  >
                    Mergi la notificări →
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>

          <!-- Comenzi recente + facturi recente -->
          <div class="row g-3">
            <div class="col-xl-7">
              <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                  <div class="fw-semibold">
                    Comenzi recente
                  </div>
                  <RouterLink
                    :to="{ name: 'account-orders' }"
                    class="small text-decoration-none"
                  >
                    Vezi toate →
                  </RouterLink>
                </div>
                <div class="card-body p-0">
                  <div v-if="!overview?.orders?.recent?.length" class="p-3 text-muted small">
                    Nu aveți comenzi înregistrate încă.
                  </div>
                  <div v-else class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>#</th>
                          <th>Data</th>
                          <th>Total</th>
                          <th>Status</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="order in overview.orders.recent"
                          :key="order.id"
                        >
                          <td class="small">
                            {{ order.order_number || ('ORD-' + order.id) }}
                          </td>
                          <td class="small">
                            {{ formatDate(order.created_at) }}
                          </td>
                          <td class="small fw-semibold">
                            {{ formatMoney(order.total) }}
                          </td>
                          <td class="small">
                            <span class="badge" :class="statusBadgeClass(order.status)">
                              {{ order.status }}
                            </span>
                          </td>
                          <td class="text-end">
                            <RouterLink
                              :to="{ name: 'account-order-details', params: { id: order.id } }"
                              class="btn btn-link btn-sm text-decoration-none"
                            >
                              Detalii
                            </RouterLink>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-5">
              <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                  <div class="fw-semibold">
                    Facturi recente
                  </div>
                  <RouterLink
                    :to="{ name: 'account-documents' }"
                    class="small text-decoration-none"
                  >
                    Vezi toate →
                  </RouterLink>
                </div>
                <div class="card-body p-0">
                  <div v-if="!overview?.invoices?.recent?.length" class="p-3 text-muted small">
                    Nu aveți facturi disponibile încă.
                  </div>
                  <div v-else class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>Factură</th>
                          <th>Data</th>
                          <th>Total</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="invoice in overview.invoices.recent"
                          :key="invoice.id"
                        >
                          <td class="small">
                            {{ invoice.series }} {{ invoice.number }}
                          </td>
                          <td class="small">
                            {{ formatDate(invoice.issue_date) }}
                          </td>
                          <td class="small fw-semibold">
                            {{ formatMoney(invoice.total) }}
                          </td>
                          <td class="small">
                            <span class="badge bg-light text-dark">
                              {{ invoice.status }}
                            </span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="card-footer bg-white small text-muted">
                  Pentru detaliu și descărcare PDF, accesați secțiunea
                  <RouterLink :to="{ name: 'account-documents' }">
                    Documente financiare
                  </RouterLink>.
                </div>
              </div>
            </div>
          </div>

          <!-- B2B specific (credit & condiții comerciale) -->
          <div
            v-if="overview?.customer && overview.customer.type === 'b2b'"
            class="card shadow-sm mt-4 border-0"
          >
            <div class="card-header bg-white fw-semibold">
              Condiții comerciale B2B
            </div>
            <div class="card-body small">
              <div class="row">
                <div class="col-md-4 mb-3">
                  <div class="text-muted mb-1">Termen de plată</div>
                  <div class="fw-semibold">
                    {{ overview.customer.payment_terms || 'Conform contractului' }}
                  </div>
                </div>
                <div class="col-md-4 mb-3">
                  <div class="text-muted mb-1">Limită credit</div>
                  <div class="fw-semibold">
                    {{ formatMoney(overview.customer.credit_limit) }}
                  </div>
                </div>
                <div class="col-md-4 mb-3">
                  <div class="text-muted mb-1">Sold restant</div>
                  <div class="fw-semibold">
                    {{ formatMoney(overview.customer.current_sold) }}
                  </div>
                </div>
              </div>
              <div class="alert alert-info mb-0 small">
                Pentru modificarea condițiilor comerciale sau creșterea limitei de credit,
                vă rugăm să contactați reprezentantul de vânzări alocat.
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { RouterLink } from 'vue-router';
import { fetchAccountOverview, fetchUnreadNotificationsCount } from '@/services/account/account';

const route = useRoute();

const loading = ref(false);
const error = ref('');
const overview = ref(null);
const unreadNotifications = ref(0);

const loadData = async () => {
  loading.value = true;
  error.value = '';

  try {
    const [dashboardData, notifData] = await Promise.all([
      fetchAccountOverview(),
      fetchUnreadNotificationsCount(),
    ]);

    overview.value = dashboardData;
    unreadNotifications.value = notifData.unread ?? 0;
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca datele pentru dashboard-ul de client.';
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);

// Derivate / helpers
const initials = computed(() => {
  const name = overview.value?.user?.name || '';
  if (!name) return '?';
  const parts = name.split(' ').filter(Boolean);
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
});

const invoicesCount = computed(() => {
  return overview.value?.invoices?.recent?.length ?? 0;
});

const formatMoney = (value) => {
  if (!value && value !== 0) return '-';
  return new Intl.NumberFormat('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value) + ' RON';
};

const formatDate = (value) => {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('ro-RO', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
};

const statusBadgeClass = (status) => {
  if (!status) return 'bg-light text-dark';

  const s = status.toLowerCase();
  if (['pending', 'in_review'].includes(s)) return 'bg-warning text-dark';
  if (['processing', 'approved'].includes(s)) return 'bg-info text-dark';
  if (['completed', 'delivered'].includes(s)) return 'bg-success';
  if (['cancelled', 'rejected'].includes(s)) return 'bg-danger';

  return 'bg-light text-dark';
};
</script>
