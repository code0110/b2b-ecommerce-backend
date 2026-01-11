<template>
  <div class="agent-routes-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h4 mb-1">Ruta Mea</h1>
        <p class="text-muted small mb-0">
            Săptămâna {{ currentWeekType === 'odd' ? 'Impară' : 'Pară' }} (Nr. {{ currentWeekNumber }})
            <span class="mx-1">•</span>
            {{ formatDate(selectedDate) }}
        </p>
      </div>
      <!-- KPI Card -->
      <div class="card bg-white border-0 shadow-sm">
        <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
             <div class="text-center">
                <div class="h5 mb-0 fw-bold text-success">{{ kpi.visited }}</div>
                <div class="small text-muted">Vizitați</div>
             </div>
             <div class="vr"></div>
             <div class="text-center">
                <div class="h5 mb-0 fw-bold text-orange">{{ kpi.total }}</div>
                <div class="small text-muted">Total</div>
             </div>
             <div class="vr"></div>
             <div class="text-center">
                <div class="h5 mb-0 fw-bold text-warning">{{ kpi.pending }}</div>
                <div class="small text-muted">Rămași</div>
             </div>
        </div>
      </div>
    </div>

    <!-- Day Selector -->
    <ul class="nav nav-pills bg-white p-2 rounded shadow-sm border mb-4 overflow-auto flex-nowrap">
        <li class="nav-item" v-for="day in days" :key="day.value">
        <button 
            class="nav-link text-nowrap" 
            :class="{ active: currentDay === day.value }" 
            @click="changeDay(day.value)"
        >
            {{ day.label }}
        </button>
        </li>
    </ul>

    <!-- Route List -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-orange"></div>
            </div>
            <div v-else-if="filteredRouteCustomers.length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-calendar-check fs-1 mb-3 d-block opacity-25"></i>
                Nu sunt vizite planificate pentru această zi.
            </div>
            <div v-else class="list-group list-group-flush">
                <div v-for="(item, index) in filteredRouteCustomers" :key="item.id" 
                    class="list-group-item d-flex align-items-center p-3"
                    :class="{'bg-light': item.visitStatus === 'completed', 'border-start border-5 border-success': item.visitStatus === 'completed', 'border-start border-5 border-orange': item.visitStatus === 'active'}"
                >
                    <div class="me-3 text-muted fw-bold" style="width: 25px;">{{ index + 1 }}.</div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div v-if="item.customer">
                                <h6 class="mb-1 fw-bold text-dark">{{ item.customer.name }}</h6>
                                <div class="small text-muted">{{ item.customer.address || 'Fără adresă' }}</div>
                            </div>
                            <div v-else>
                                <h6 class="mb-1 fw-bold text-danger">Client Indisponibil</h6>
                            </div>
                            <div class="text-end ms-2">
                                <span v-if="item.visitStatus === 'completed'" class="badge bg-success mb-1 d-block"><i class="bi bi-check-lg"></i> Vizitat</span>
                                <span v-else-if="item.visitStatus === 'active'" class="badge bg-orange mb-1 d-block"><i class="bi bi-geo-alt-fill"></i> Activ</span>
                                
                                <span class="badge bg-light text-dark border" v-if="item.week_type !== 'all'">
                                    {{ item.week_type === 'odd' ? 'Săpt. Impară' : 'Săpt. Pară' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="ms-3">
                         <button 
                            class="btn btn-sm"
                            :class="item.visitStatus === 'active' ? 'btn-orange' : (item.visitStatus === 'completed' ? 'btn-outline-secondary' : 'btn-outline-secondary')"
                            @click="handleStartVisit(item.customer)"
                            :disabled="visitStore.loading || !item.customer || (visitStore.activeVisit && item.customer && visitStore.activeVisit.customer_id !== item.customer.id)"
                         >
                            <i class="bi" :class="item.visitStatus === 'active' ? 'bi-eye' : 'bi-geo-alt'"></i>
                            {{ item.visitStatus === 'active' ? 'Vezi' : 'Vizită' }}
                         </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '@/store/auth';
import { useTrackingStore } from '@/store/tracking';
import { fetchRoutes } from '@/services/admin/agentRoutes';
import { fetchVisits } from '@/services/admin/customerVisits';
import { useVisitStore } from '@/store/visit';
import { useToast } from 'vue-toastification';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const trackingStore = useTrackingStore();
const visitStore = useVisitStore();
const toast = useToast();
const router = useRouter();

const days = [
  { value: 'Monday', label: 'Luni' },
  { value: 'Tuesday', label: 'Marți' },
  { value: 'Wednesday', label: 'Miercuri' },
  { value: 'Thursday', label: 'Joi' },
  { value: 'Friday', label: 'Vineri' },
  { value: 'Saturday', label: 'Sâmbătă' },
  { value: 'Sunday', label: 'Duminică' },
];

const currentDay = ref('Monday');
const selectedDate = ref(new Date());
const routes = ref([]);
const visits = ref([]);
const loading = ref(false);

const getWeekNumber = (d) => {
    d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay()||7));
    var yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
    var weekNo = Math.ceil(( ( (d - yearStart) / 86400000) + 1)/7);
    return weekNo;
};

const currentWeekNumber = computed(() => getWeekNumber(selectedDate.value));
const currentWeekType = computed(() => (currentWeekNumber.value % 2 === 0) ? 'even' : 'odd');

const formatDate = (date) => {
    return date.toLocaleDateString('ro-RO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
};

// Map day index (0-6) to name
const getDayName = (date) => {
    const daysMap = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return daysMap[date.getDay()];
};

const setToday = () => {
    const today = new Date();
    selectedDate.value = today;
    currentDay.value = getDayName(today);
};

const changeDay = (dayValue) => {
    currentDay.value = dayValue;
    // Update selectedDate to match the selected day within the CURRENT week
    const today = new Date();
    const currentDayIndex = today.getDay() || 7; // 1-7 (Mon-Sun)
    
    const targetDayIndex = days.findIndex(d => d.value === dayValue) + 1; // 1-7
    
    const diff = targetDayIndex - currentDayIndex;
    const newDate = new Date(today);
    newDate.setDate(today.getDate() + diff);
    selectedDate.value = newDate;
    
    loadData();
};

const loadData = async () => {
    loading.value = true;
    try {
        if (!authStore.user) {
            await authStore.refreshUser();
            if (!authStore.user) {
                 throw new Error('User not authenticated');
            }
        }

        // 1. Fetch Routes for me
        const routesData = await fetchRoutes({ 
            agent_id: authStore.user.id,
            day_of_week: currentDay.value
        });
        
        // 2. Fetch Visits for this date
        const dateStr = selectedDate.value.toISOString().split('T')[0];
        const visitsData = await fetchVisits({
            agent_id: authStore.user.id,
            date: dateStr,
            per_page: 100
        });
        
        routes.value = routesData;
        visits.value = visitsData.data || [];
        
    } catch (e) {
        console.error(e);
        const errorMsg = e.response?.data?.message || e.message || 'Eroare la încărcarea datelor.';
        toast.error(errorMsg);
    } finally {
        loading.value = false;
    }
};

const filteredRouteCustomers = computed(() => {
    // Filter by week type (All + Current)
    const list = routes.value.filter(r => r.week_type === 'all' || r.week_type === currentWeekType.value);
    
    // Sort
    list.sort((a, b) => a.sort_order - b.sort_order);
    
    // Enrich with status
    return list.map(r => {
        const visit = visits.value.find(v => v.customer_id === r.customer_id && v.status !== 'cancelled');
        let status = 'pending';
        if (visit) {
            status = visit.status === 'in_progress' ? 'active' : 'completed';
        }
        // Also check global active visit
        if (visitStore.activeVisit?.customer_id === r.customer_id) {
            status = 'active';
        }
        
        return {
            ...r,
            visitStatus: status
        };
    });
});

const kpi = computed(() => {
    const total = filteredRouteCustomers.value.length;
    const visited = filteredRouteCustomers.value.filter(r => r.visitStatus === 'completed').length;
    const active = filteredRouteCustomers.value.filter(r => r.visitStatus === 'active').length;
    return {
        total,
        visited,
        pending: total - visited - active
    };
});

const handleStartVisit = async (customer) => {
    if (visitStore.activeVisit) {
        if (visitStore.activeVisit.customer_id === customer.id) {
             // Already active, just go to dashboard or details
             router.push({ name: 'agent-dashboard' });
             return;
        }
        if (!confirm('Aveți deja o vizită activă. Doriți să o încheiați și să începeți una nouă?')) return;
        await visitStore.endVisit();
    }
    
    try {
        await visitStore.startVisit(customer.id);
        toast.success(`Vizită începută cu ${customer.name}`);
        router.push({ name: 'agent-dashboard' });
    } catch (e) {
        toast.error('Eroare la începerea vizitei: ' + (e.response?.data?.message || e.message));
    }
};

const createOffer = (customer) => {
    router.push({ 
        name: 'admin-offers-new', 
        query: { customer_id: customer.id } 
    });
};

onMounted(async () => {
    if (!trackingStore.isShiftActive) {
        await trackingStore.checkStatus();
        if (!trackingStore.isShiftActive) {
             toast.error('Trebuie să începeți programul de lucru pentru a vedea rutele!');
             router.push({ name: 'agent-dashboard' });
             return;
        }
    }

    setToday();
    loadData();
});

watch(currentDay, () => {
   // loadData is called by changeDay
});

</script>

<style scoped>
.nav-link {
    cursor: pointer;
}
.route-card {
    transition: transform 0.2s;
}
.route-card.active {
    border: 1px solid var(--dd-orange) !important;
}
.route-card.completed {
    background-color: #f8f9fa;
    opacity: 0.85;
}
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.6; }
    100% { opacity: 1; }
}
.animate-pulse {
    animation: pulse 2s infinite;
}
</style>
