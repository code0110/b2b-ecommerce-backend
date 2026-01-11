<template>
  <div class="bg-light border-bottom py-3 mb-4">
    <div class="container">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h1 class="h4 mb-1">Dashboard</h1>
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
      <!-- Conținut principal dashboard -->
      <section class="col-12">
        <!-- Loading / error -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-orange mb-3" role="status"></div>
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
                    <i class="bi bi-basket2 fs-5 text-dd-blue"></i>
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
                    <i class="bi bi-receipt fs-5 text-orange"></i>
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
                  <div v-else class="list-group list-group-flush">
                    <div
                      class="list-group-item"
                      v-for="order in overview.orders.recent"
                      :key="order.id"
                    >
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div class="fw-semibold small">
                            {{ order.order_number || ('ORD-' + order.id) }}
                          </div>
                          <div class="small text-muted">
                            {{ formatDate(order.created_at) }}
                          </div>
                        </div>
                        <div class="text-end">
                          <div class="small fw-bold">
                            {{ formatMoney(order.total) }}
                          </div>
                          <span class="badge small" :class="statusBadgeClass(order.status)">
                            {{ order.status }}
                          </span>
                        </div>
                      </div>
                      <div class="mt-2 text-end">
                        <RouterLink
                          :to="{ name: 'account-order-details', params: { id: order.id } }"
                          class="btn btn-link btn-sm text-decoration-none"
                        >
                          Detalii
                        </RouterLink>
                      </div>
                    </div>
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
                  <div v-else class="list-group list-group-flush">
                    <div
                      class="list-group-item d-flex justify-content-between align-items-start"
                      v-for="invoice in overview.invoices.recent"
                      :key="invoice.id"
                    >
                      <div>
                        <div class="fw-semibold small">
                          {{ invoice.series }} {{ invoice.number }}
                        </div>
                        <div class="small text-muted">
                          {{ formatDate(invoice.issue_date) }}
                        </div>
                      </div>
                      <div class="text-end">
                        <div class="small fw-bold">
                          {{ formatMoney(invoice.total) }}
                        </div>
                        <span class="badge bg-light text-dark small">
                          {{ invoice.status }}
                        </span>
                      </div>
                    </div>
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
  if (['pending', 'in_review'].includes(s)) return 'bg-orange text-white';
  if (['processing', 'approved'].includes(s)) return 'bg-dd-blue text-white';
  if (['completed', 'delivered'].includes(s)) return 'bg-success text-white';
  if (['cancelled', 'rejected'].includes(s)) return 'bg-danger text-white';

  return 'bg-light text-dark';
};
</script>
