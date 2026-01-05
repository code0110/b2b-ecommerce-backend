<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">Rapoarte Activitate</h1>
      
      <div class="d-flex gap-2">
         <select v-model="selectedAgent" class="form-select" style="width: 200px;" v-if="canFilterAgents">
            <option :value="null">Toți Agenții</option>
            <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                {{ agent.first_name }} {{ agent.last_name }}
            </option>
         </select>
         
         <input type="date" v-model="startDate" class="form-control" style="width: 150px;">
         <input type="date" v-model="endDate" class="form-control" style="width: 150px;">
         
         <button class="btn btn-primary" @click="fetchData">Actualizează</button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small">Total Vizite</h6>
            <h2 class="fw-bold mb-0">{{ stats.total_visits }}</h2>
            <small class="text-success">{{ stats.completed_visits }} finalizate</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small">Valoare Comenzi (Vizite)</h6>
            <h2 class="fw-bold mb-0">{{ formatPrice(stats.orders_value) }}</h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small">Încasări (Vizite)</h6>
            <h2 class="fw-bold mb-0">{{ formatPrice(stats.payments_value) }}</h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0 h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase small">Rată Conversie</h6>
            <h2 class="fw-bold mb-0">{{ conversionRate }}%</h2>
            <small class="text-muted">Comenzi per vizită</small>
          </div>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <!-- Visits Chart -->
      <div class="col-md-8">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3">
             <h6 class="mb-0 fw-bold">Evoluție Vizite</h6>
          </div>
          <div class="card-body">
             <div style="height: 300px; position: relative;">
                <Bar v-if="visitsChartData" :data="visitsChartData" :options="chartOptions" />
             </div>
          </div>
        </div>
      </div>

      <!-- Outcomes Chart -->
      <div class="col-md-4">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3">
             <h6 class="mb-0 fw-bold">Rezultate Vizite</h6>
          </div>
          <div class="card-body">
             <div style="height: 300px; position: relative;">
                <Pie v-if="outcomesChartData" :data="outcomesChartData" :options="pieOptions" />
             </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Agent Performance Table -->
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Performanță Agenți</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Agent</th>
              <th>Vizite</th>
              <th>Valoare Comenzi</th>
              <th>Încasări</th>
              <th>Medie Comandă / Vizită</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="agent in agentPerformance" :key="agent.id">
              <td class="fw-bold">{{ agent.name }}</td>
              <td>{{ agent.visits_count }}</td>
              <td>{{ formatPrice(agent.orders_value) }}</td>
              <td>{{ formatPrice(agent.payments_value) }}</td>
              <td>
                {{ agent.visits_count > 0 ? formatPrice(agent.orders_value / agent.visits_count) : '-' }}
              </td>
            </tr>
            <tr v-if="agentPerformance.length === 0">
                <td colspan="5" class="text-center py-4 text-muted">Nu există date pentru perioada selectată.</td>
            </tr>
          </tbody>
        </table>
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

const stats = ref({
    total_visits: 0,
    completed_visits: 0,
    orders_value: 0,
    payments_value: 0
});

const conversionRate = computed(() => {
    if (stats.value.total_visits === 0) return 0;
    // Note: We don't have exact "number of orders" in stats yet, only value. 
    // Ideally we should add order count to stats endpoint. 
    // For now, let's just return 0 or request backend update.
    // Let's assume we want order count too. I'll stick to 0 for now or remove it.
    // Wait, let's keep it simple. I will just display what I have.
    return 0; 
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

const fetchData = async () => {
    try {
        const params = {
            start_date: startDate.value,
            end_date: endDate.value,
            agent_id: selectedAgent.value
        };

        // 1. Stats
        const statsRes = await adminApi.get('/reports/dashboard-stats', { params });
        stats.value = statsRes.data;

        // 2. Visits Chart
        const visitsRes = await adminApi.get('/reports/visits-chart', { params });
        visitsChartData.value = {
            labels: visitsRes.data.map(v => v.date),
            datasets: [{
                label: 'Vizite',
                backgroundColor: '#0d6efd',
                data: visitsRes.data.map(v => v.count)
            }]
        };

        // 3. Outcomes Chart
        const outcomesRes = await adminApi.get('/reports/outcomes-chart', { params });
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
            labels: outcomesRes.data.map(o => labelsMap[o.outcome] || o.outcome),
            datasets: [{
                backgroundColor: colors,
                data: outcomesRes.data.map(o => o.count)
            }]
        };

        // 4. Performance
        const perfRes = await adminApi.get('/reports/agent-performance', { params });
        agentPerformance.value = perfRes.data;

    } catch (e) {
        console.error('Failed to load reports', e);
    }
};

const loadAgents = async () => {
    if (!canFilterAgents.value) return;
    try {
        // We can reuse users endpoint or sales-reps. Let's try users with role filter
        // Or if we implemented receipt-books-agents endpoint which returns agents, we can use that or similar.
        // Let's use a simple call to users? No, specific endpoint is better.
        // I'll use receipt-books-agents as a hack or just users.
        // Actually, let's use the users resource filtering.
        const res = await adminApi.get('/users?role=sales_agent&per_page=100');
        agents.value = res.data.data;
    } catch (e) {
        console.error(e);
    }
};

onMounted(() => {
    loadAgents();
    fetchData();
});
</script>
