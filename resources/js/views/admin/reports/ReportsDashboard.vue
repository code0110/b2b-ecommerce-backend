<template>
  <div class="container-fluid py-3">
    <!-- Header & Filters -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div>
             <h1 class="h4 mb-1">Rapoarte Activitate</h1>
             <p class="text-muted small mb-0">Statistici și performanță echipă vânzări</p>
          </div>
          
          <div class="d-flex flex-column flex-md-row gap-2">
             <select v-model="selectedAgent" class="form-select form-select-sm" v-if="canFilterAgents">
                <option :value="null">Toți Agenții</option>
                <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                    {{ agent.first_name }} {{ agent.last_name }}
                </option>
             </select>
             
             <div class="d-flex gap-2">
                 <input type="date" v-model="startDate" class="form-control form-control-sm">
                 <input type="date" v-model="endDate" class="form-control form-control-sm">
             </div>
             
             <button class="btn btn-primary btn-sm" @click="fetchData" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-arrow-clockwise me-1"></i> Actualizează
             </button>
         </div>
        </div>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger border-0 shadow-sm mb-4">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ error }}
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
      <!-- Total Vizite -->
      <div class="col-6 col-lg-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-center mb-2">
                <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-2">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <h6 class="text-muted text-uppercase small mb-0">Vizite</h6>
            </div>
            <h2 class="fw-bold mb-0 h4">
              <span v-if="loading" class="placeholder-glow"><span class="placeholder col-6"></span></span>
              <span v-else>{{ stats.total_visits }}</span>
            </h2>
            <div class="d-flex flex-column small text-muted mt-1">
                <span>
                  <span v-if="loading" class="placeholder-glow"><span class="placeholder col-6"></span></span>
                  <span v-else>{{ stats.completed_visits }} finalizate</span>
                </span>
                <span class="text-success">
                  <span v-if="loading" class="placeholder-glow"><span class="placeholder col-6"></span></span>
                  <span v-else>{{ completionRate }}% rată fin.</span>
                </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Comenzi -->
      <div class="col-6 col-lg-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-center mb-2">
                <div class="bg-success bg-opacity-10 text-success rounded p-2 me-2">
                    <i class="bi bi-cart-check-fill"></i>
                </div>
                <h6 class="text-muted text-uppercase small mb-0">
                  Comenzi (
                  <span v-if="loading" class="placeholder-glow"><span class="placeholder col-6"></span></span>
                  <span v-else>{{ stats.orders_count }}</span>
                  )
                </h6>
            </div>
            <h2 class="fw-bold mb-0 h4">
              <span v-if="loading" class="placeholder-glow"><span class="placeholder col-6"></span></span>
              <span v-else>{{ formatPrice(stats.orders_value) }}</span>
            </h2>
            <div class="small text-muted mt-1">
                <span v-if="loading" class="placeholder-glow"><span class="placeholder col-6"></span></span>
                <span v-else>{{ stats.visits_with_orders }} vizite cu comandă</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Încasări -->
      <div class="col-6 col-lg-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-center mb-2">
                <div class="bg-info bg-opacity-10 text-info rounded p-2 me-2">
                    <i class="bi bi-wallet2"></i>
                </div>
                <h6 class="text-muted text-uppercase small mb-0">Încasări</h6>
            </div>
            <h2 class="fw-bold mb-0 h4">
              <span v-if="loading" class="placeholder-glow"><span class="placeholder col-6"></span></span>
              <span v-else>{{ formatPrice(stats.payments_value) }}</span>
            </h2>
             <div class="small text-muted mt-1">
                <span v-if="loading" class="placeholder-glow"><span class="placeholder col-6"></span></span>
                <span v-else>{{ stats.visits_with_payments }} vizite cu încasare</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Conversie -->
      <div class="col-6 col-lg-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-center mb-2">
                <div class="bg-warning bg-opacity-10 text-warning rounded p-2 me-2">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h6 class="text-muted text-uppercase small mb-0">Conversie</h6>
            </div>
            <h2 class="fw-bold mb-0 h4">
              <span v-if="loading" class="placeholder-glow"><span class="placeholder col-6"></span></span>
              <span v-else>{{ conversionRate }}%</span>
            </h2>
            <div class="small text-success mt-1">
                din vizitele efectuate
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <!-- Visits Chart -->
      <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white py-3">
             <h6 class="mb-0 fw-bold">Evoluție Vizite</h6>
          </div>
          <div class="card-body">
             <div style="height: 300px; position: relative;">
                <div v-if="loading" class="d-flex h-100 align-items-center justify-content-center">
                  <div class="spinner-border text-primary" role="status"></div>
                </div>
                <Bar v-else-if="visitsChartData" :data="visitsChartData" :options="chartOptions" />
                <div v-else class="h-100 d-flex align-items-center justify-content-center text-muted">
                  Fără date pentru perioada selectată
                </div>
             </div>
          </div>
        </div>
      </div>

      <!-- Outcomes Chart -->
      <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white py-3">
             <h6 class="mb-0 fw-bold">Rezultate Vizite</h6>
          </div>
          <div class="card-body">
             <div style="height: 300px; position: relative;">
                <div v-if="loading" class="d-flex h-100 align-items-center justify-content-center">
                  <div class="spinner-border text-primary" role="status"></div>
                </div>
                <Pie v-else-if="outcomesChartData" :data="outcomesChartData" :options="pieOptions" />
                <div v-else class="h-100 d-flex align-items-center justify-content-center text-muted">
                  Fără date pentru perioada selectată
                </div>
             </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Performanță Agenți</h6>
      </div>
      
      <div class="card-body p-3">
        <div v-if="loading" class="text-center text-muted py-4">
          <div class="spinner-border text-primary mb-2" role="status"></div>
          Se încarcă datele...
        </div>
        <div v-else-if="agentPerformance.length === 0" class="text-center text-muted py-4">
            Nu există date pentru perioada selectată.
        </div>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            <div v-for="agent in agentPerformance" :key="agent.id" class="col">
                <div class="card h-100 border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-primary mb-0">{{ agent.name }}</h6>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                <i class="bi bi-wallet2 me-1"></i> {{ formatPrice(agent.payments_value) }}
                            </span>
                        </div>
                        
                        <!-- Vizite -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted"><i class="bi bi-geo-alt me-1"></i>Vizite</span>
                                <span class="fw-semibold">{{ agent.visits_count }} <span class="text-muted fw-normal" v-if="agent.target_visits">/ {{ agent.target_visits }}</span></span>
                            </div>
                            <div class="progress" style="height: 6px;" v-if="agent.target_visits > 0">
                                <div class="progress-bar" :class="getProgressBarClass(agent.visits_count, agent.target_visits)" 
                                     role="progressbar" 
                                     :style="{ width: Math.min((agent.visits_count / agent.target_visits) * 100, 100) + '%' }">
                                </div>
                            </div>
                        </div>

                        <!-- Vânzări -->
                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted"><i class="bi bi-cart me-1"></i>Vânzări</span>
                                <span class="fw-semibold">{{ formatPrice(agent.orders_value) }} <span class="text-muted fw-normal" v-if="agent.target_sales">/ {{ formatPrice(agent.target_sales) }}</span></span>
                            </div>
                            <div class="progress" style="height: 6px;" v-if="agent.target_sales > 0">
                                <div class="progress-bar" :class="getProgressBarClass(agent.orders_value, agent.target_sales)" 
                                     role="progressbar" 
                                     :style="{ width: Math.min((agent.orders_value / agent.target_sales) * 100, 100) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { adminApi } from '@/services/http';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement
} from 'chart.js';
import { Bar, Pie } from 'vue-chartjs';
import { useAuthStore } from '@/store/auth';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend);

const authStore = useAuthStore();
const canFilterAgents = computed(() => authStore.hasRole('admin') || authStore.hasRole('sales_director'));

const startDate = ref(new Date().toISOString().slice(0, 8) + '01'); // First day of current month
const endDate = ref(new Date().toISOString().slice(0, 10)); // Today
const selectedAgent = ref(null);
const agents = ref([]);
const loading = ref(false);
const error = ref('');

const stats = ref({
    total_visits: 0,
    completed_visits: 0,
    visits_with_orders: 0,
    visits_with_payments: 0,
    orders_value: 0,
    orders_count: 0,
    payments_value: 0
});

const conversionRate = computed(() => {
    if (!stats.value || !stats.value.total_visits) return 0;
    return Math.round(((stats.value.visits_with_orders || 0) / stats.value.total_visits) * 100);
});

const completionRate = computed(() => {
    if (!stats.value || !stats.value.total_visits) return 0;
    return Math.round(((stats.value.completed_visits || 0) / stats.value.total_visits) * 100);
});

const visitsChartData = ref(null);
const outcomesChartData = ref(null);
const agentPerformance = ref([]);

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
      legend: { display: false }
  }
};

const pieOptions = {
  responsive: true,
  maintainAspectRatio: false,
};

const formatPrice = (value) => {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(value || 0);
};

const getProgressBarClass = (current, target) => {
    if (!target) return 'bg-secondary';
    const percent = (current / target) * 100;
    if (percent >= 100) return 'bg-success';
    if (percent >= 80) return 'bg-info';
    if (percent >= 50) return 'bg-warning';
    return 'bg-danger';
};

const fetchData = async () => {
    loading.value = true;
    error.value = '';
    try {
        const params = {
            start_date: startDate.value,
            end_date: endDate.value,
            agent_id: selectedAgent.value
        };

        const [statsRes, visitsRes, outcomesRes, perfRes] = await Promise.all([
          adminApi.get('/reports/dashboard-stats', { params }),
          adminApi.get('/reports/visits-chart', { params }),
          adminApi.get('/reports/outcomes-chart', { params }),
          adminApi.get('/reports/agent-performance', { params }),
        ]);

        stats.value = statsRes.data;

        visitsChartData.value = {
            labels: (visitsRes.data || []).map(v => v.date),
            datasets: [{
                label: 'Vizite',
                backgroundColor: '#0d6efd',
                data: (visitsRes.data || []).map(v => v.count)
            }]
        };

        const labelsMap = {
            'order_placed': 'Comandă',
            'payment_collected': 'Încasare',
            'stock_check': 'Stoc',
            'presentation': 'Prezentare',
            'no_interest': 'Refuz',
            'client_closed': 'Închis',
            'other': 'Altele'
        };
        const colors = ['#198754', '#0dcaf0', '#ffc107', '#0d6efd', '#dc3545', '#6c757d', '#212529'];
        
        outcomesChartData.value = {
            labels: (outcomesRes.data || []).map(o => labelsMap[o.outcome] || o.outcome),
            datasets: [{
                backgroundColor: colors,
                data: (outcomesRes.data || []).map(o => o.count)
            }]
        };

        agentPerformance.value = perfRes.data || [];

    } catch (e) {
        console.error('Failed to load reports', e);
        error.value = 'Nu s-au putut încărca rapoartele pentru perioada selectată.';
    } finally {
        loading.value = false;
    }
};

const loadAgents = async () => {
    if (!canFilterAgents.value) return;
    try {
        let loadedAgents = [];
        
        if (authStore.hasRole('admin')) {
            // Fetch agents and directors for admin
            const [agentsRes, directorsRes] = await Promise.all([
                adminApi.get('/users?role=sales_agent&per_page=100'),
                adminApi.get('/users?role=sales_director&per_page=100')
            ]);
            
            const agentsList = agentsRes.data.data || [];
            const directorsList = directorsRes.data.data || [];
            
            // Merge and dedup by ID
            const map = new Map();
            [...agentsList, ...directorsList].forEach(u => map.set(u.id, u));
            loadedAgents = Array.from(map.values());
            
        } else {
            // For Director: fetch without role param to get subordinates + self
            // (Assuming backend UserController logic handles the filtering based on authenticated user)
            const res = await adminApi.get('/users?per_page=100');
            loadedAgents = res.data.data || [];
        }
        
        // Sort by name
        loadedAgents.sort((a, b) => {
            const nameA = (a.first_name + ' ' + (a.last_name || '')).toLowerCase();
            const nameB = (b.first_name + ' ' + (b.last_name || '')).toLowerCase();
            return nameA.localeCompare(nameB);
        });

        agents.value = loadedAgents;
    } catch (e) {
        console.error(e);
    }
};

onMounted(() => {
    loadAgents();
    fetchData();
});
</script>
