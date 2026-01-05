<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">Dashboard Director</h1>
      <button class="btn btn-primary" @click="fetchData">
        <i class="bi bi-arrow-clockwise me-1"></i> Actualizează
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4 g-3">
      <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0 h-100" :class="{ 'border-warning': summary.pending_approvals > 0 }">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small">Aprobări Necesare</h6>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h2 class="fw-bold mb-0" :class="summary.pending_approvals > 0 ? 'text-warning' : 'text-secondary'">{{ summary.pending_approvals }}</h2>
                <button v-if="summary.pending_approvals > 0" class="btn btn-sm btn-warning text-dark" @click="goToApprovals">
                    Vezi <i class="bi bi-arrow-right"></i>
                </button>
            </div>
            <small class="text-muted">Oferte ce necesită derogare</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small">Vânzări Astăzi</h6>
            <h2 class="fw-bold mb-0 text-primary">{{ formatPrice(summary.today_sales) }}</h2>
            <small class="text-muted">De la {{ summary.agents_count }} agenți</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small">Vânzări Luna Curentă</h6>
            <h2 class="fw-bold mb-0">{{ formatPrice(summary.month_sales) }}</h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small">Vizite Astăzi</h6>
            <h2 class="fw-bold mb-0">{{ summary.today_visits }}</h2>
            <small class="text-success" v-if="summary.active_visits > 0">{{ summary.active_visits }} în desfășurare</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small">Status Echipă</h6>
            <div class="d-flex align-items-center mt-2">
                <span class="badge bg-success me-2">{{ summary.active_visits }} Activi</span>
                <span class="badge bg-secondary">{{ summary.agents_count - summary.active_visits }} Inactivi</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Team Status Table -->
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Status Echipă în Timp Real</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Agent</th>
              <th>Status</th>
              <th>Activitate Curentă</th>
              <th>Locație</th>
              <th>Vânzări Astăzi</th>
              <th>Vizite Astăzi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="agent in teamStatus" :key="agent.id">
              <td class="fw-bold">{{ agent.name }}</td>
              <td>
                <span v-if="agent.status === 'in_visit'" class="badge bg-success rounded-pill px-3">
                    <i class="bi bi-geo-alt-fill me-1"></i> În Vizită
                </span>
                <span v-else class="badge bg-secondary rounded-pill px-3">Idle</span>
              </td>
              <td>
                <div v-if="agent.status === 'in_visit'">
                    <div class="fw-bold text-dark">{{ agent.current_customer }}</div>
                    <small class="text-muted">Început la {{ formatTime(agent.visit_start_time) }}</small>
                </div>
                <div v-else-if="agent.last_seen">
                    <small class="text-muted">Ultima vizită: {{ formatTime(agent.last_seen) }}</small>
                </div>
                <span v-else class="text-muted">-</span>
              </td>
              <td>
                <div v-if="agent.latitude && agent.longitude">
                    <a :href="`https://www.google.com/maps/search/?api=1&query=${agent.latitude},${agent.longitude}`" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-map me-1"></i> Vezi Harta
                    </a>
                    <div v-if="agent.is_off_site" class="mt-1">
                        <span class="badge bg-danger" title="Distanță mare față de client">Off-site</span>
                    </div>
                </div>
                <span v-else class="text-muted">-</span>
              </td>
              <td>{{ formatPrice(agent.today_sales) }}</td>
              <td>{{ agent.today_visits }}</td>
            </tr>
            <tr v-if="teamStatus.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">Nu există agenți asignați.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { adminApi } from '@/services/http';

const router = useRouter();

const summary = ref({
    today_sales: 0,
    month_sales: 0,
    today_visits: 0,
    active_visits: 0,
    agents_count: 0,
    pending_approvals: 0
});

const teamStatus = ref([]);
let refreshInterval = null;

const formatPrice = (value) => {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(value || 0);
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit' });
};

const goToApprovals = () => {
    router.push({ name: 'account-offers-list', query: { status: 'pending_approval' } });
};

const fetchData = async () => {
    try {
        const [summaryRes, teamRes] = await Promise.all([
            adminApi.get('/director/dashboard/summary'),
            adminApi.get('/director/dashboard/team-status')
        ]);
        
        summary.value = summaryRes.data;
        teamStatus.value = teamRes.data;
    } catch (e) {
        console.error('Failed to load director dashboard', e);
    }
};

onMounted(() => {
    fetchData();
    // Refresh every 60 seconds
    refreshInterval = setInterval(fetchData, 60000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>
