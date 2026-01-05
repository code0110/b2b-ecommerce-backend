<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1 text-gray-800">Planificare Rute</h1>
        <p class="text-muted small mb-0">Gestionează rutele săptămânale pentru agenți.</p>
      </div>
      <button 
        class="btn btn-primary shadow-sm" 
        @click="saveChanges" 
        :disabled="saving || !isDirty"
      >
        <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
        <i v-else class="bi bi-save me-2"></i>
        Salvează Modificările
      </button>
    </div>

    <!-- Agent Selector (for Admins/Directors) -->
    <div v-if="canSelectAgent" class="card border-0 shadow-sm mb-4">
      <div class="card-body p-3 bg-white rounded">
        <label class="form-label small fw-bold text-muted text-uppercase">Selectează Agent</label>
        <select v-model="selectedAgentId" class="form-select" @change="loadData">
          <option :value="null">-- Alege un agent --</option>
          <option v-for="agent in agents" :key="agent.id" :value="agent.id">
            {{ agent.name }} ({{ agent.email }})
          </option>
        </select>
      </div>
    </div>

    <div v-if="selectedAgentId" class="row">
      <!-- Days Tabs -->
      <div class="col-12 mb-4">
        <ul class="nav nav-pills bg-white p-2 rounded shadow-sm border">
          <li class="nav-item" v-for="day in days" :key="day.value">
            <button 
              class="nav-link" 
              :class="{ active: currentDay === day.value }" 
              @click="changeDay(day.value)"
            >
              {{ day.label }}
            </button>
          </li>
        </ul>
      </div>

      <!-- Available Customers -->
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-bold text-muted text-uppercase">
              Clienți Disponibili
              <span class="badge bg-light text-dark ms-2">{{ availableCustomers.length }}</span>
            </h6>
          </div>
          <div class="card-body p-0 overflow-auto" style="height: 600px;">
             <!-- Search -->
            <div class="p-3 border-bottom sticky-top bg-white">
              <input 
                v-model="searchQuery" 
                type="text" 
                class="form-control form-control-sm" 
                placeholder="Caută client..."
              >
            </div>
            
            <div class="list-group list-group-flush">
              <div 
                v-for="c in filteredAvailableCustomers" 
                :key="c.id" 
                class="list-group-item d-flex justify-content-between align-items-center action-hover"
              >
                <div>
                  <div class="fw-bold text-dark">
                    {{ c.name }}
                    <span v-if="selectedAgentId && c.agent_user_id !== selectedAgentId" class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;">Secundar</span>
                  </div>
                  <div class="small text-muted">{{ c.address || 'Fără adresă' }}</div>
                </div>
                <button class="btn btn-sm btn-outline-primary" @click="addToRoute(c)">
                  <i class="bi bi-plus-lg"></i>
                </button>
              </div>
              <div v-if="filteredAvailableCustomers.length === 0" class="p-4 text-center text-muted small">
                Nu am găsit clienți disponibili.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Route for Selected Day -->
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-primary-subtle">
          <div class="card-header bg-primary bg-opacity-10 border-bottom py-3">
            <h6 class="mb-0 fw-bold text-primary text-uppercase">
              Ruta: {{ currentDayLabel }}
              <span class="badge bg-primary ms-2">{{ routeCustomers.length }}</span>
            </h6>
          </div>
          <div class="card-body p-0 overflow-auto" style="height: 600px;">
            <div v-if="routeCustomers.length === 0" class="p-5 text-center text-muted">
              <i class="bi bi-map fs-1 mb-3 d-block opacity-25"></i>
              Nu sunt vizite planificate pentru această zi.
              <br>Adaugă clienți din lista din stânga.
            </div>
            
            <div v-else class="list-group list-group-flush">
              <div 
                v-for="(item, index) in routeCustomers" 
                :key="item.tempId || item.id" 
                class="list-group-item d-flex align-items-center gap-3"
              >
                <div class="fw-bold text-muted me-2" style="width: 20px;">{{ index + 1 }}.</div>
                <div class="flex-grow-1">
                  <div class="fw-bold text-dark">
                    {{ item.customer.name }}
                    <span v-if="selectedAgentId && item.customer.agent_user_id !== selectedAgentId" class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;">Secundar</span>
                  </div>
                  <div class="small text-muted">{{ item.customer.address || 'Fără adresă' }}</div>
                  <div class="mt-1">
                     <select v-model="item.week_type" class="form-select form-select-xs d-inline-block w-auto py-0 px-2" style="height: 24px; font-size: 0.75rem;" @change="isDirty = true" @click.stop>
                        <option value="all">Săpt. Toate</option>
                        <option value="odd">Săpt. Impare</option>
                        <option value="even">Săpt. Pare</option>
                     </select>
                  </div>
                </div>
                
                <div class="btn-group">
                  <button 
                    class="btn btn-sm" 
                    :class="visitStore.activeVisit?.customer_id === item.customer.id ? 'btn-success' : 'btn-outline-success'"
                    @click="handleStartVisit(item.customer)" 
                    :disabled="visitStore.loading || (visitStore.activeVisit?.customer_id === item.customer.id)"
                    :title="visitStore.activeVisit?.customer_id === item.customer.id ? 'Vizită în curs' : 'Începe Vizită'"
                  >
                    <i class="bi" :class="visitStore.activeVisit?.customer_id === item.customer.id ? 'bi-geo-alt-fill' : 'bi-geo-alt'"></i>
                  </button>
                  <button 
                    class="btn btn-sm btn-light border" 
                    @click="moveUp(index)" 
                    :disabled="index === 0"
                    title="Mută sus"
                  >
                    <i class="bi bi-arrow-up"></i>
                  </button>
                  <button 
                    class="btn btn-sm btn-light border" 
                    @click="moveDown(index)" 
                    :disabled="index === routeCustomers.length - 1"
                    title="Mută jos"
                  >
                    <i class="bi bi-arrow-down"></i>
                  </button>
                  <button 
                    class="btn btn-sm btn-outline-danger" 
                    @click="removeFromRoute(index)"
                    title="Elimină din rută"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="text-center py-5">
      <div class="spinner-border text-primary" v-if="loadingAgents"></div>
      <div v-else class="text-muted">Selectează un agent pentru a începe planificarea.</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '@/store/auth';
import { fetchUsers } from '@/services/admin/users';
import { fetchCustomers } from '@/services/admin/customers';
import { fetchRoutes, createRoute, updateRoute, deleteRoute } from '@/services/admin/agentRoutes';
import { useVisitStore } from '@/store/visit';
import { useToast } from 'vue-toastification';

const authStore = useAuthStore();
const visitStore = useVisitStore();
const toast = useToast();

const days = [
  { value: 'Monday', label: 'Luni' },
  { value: 'Tuesday', label: 'Marți' },
  { value: 'Wednesday', label: 'Miercuri' },
  { value: 'Thursday', label: 'Joi' },
  { value: 'Friday', label: 'Vineri' },
  { value: 'Saturday', label: 'Sâmbătă' },
  { value: 'Sunday', label: 'Duminică' },
];

const currentDay = ref(new Date().toLocaleDateString('en-US', { weekday: 'long' }));
const selectedAgentId = ref(null);
const agents = ref([]);
const customers = ref([]);
const routes = ref([]); // All routes for the agent
const loadingAgents = ref(false);
const loadingData = ref(false);
const saving = ref(false);
const isDirty = ref(false);
const searchQuery = ref('');

// Helper for UI uniqueness
let tempIdCounter = 0;

const canSelectAgent = computed(() => {
  return authStore.role === 'admin' || authStore.role === 'sales_director';
});

const currentDayLabel = computed(() => {
  return days.find(d => d.value === currentDay.value)?.label || currentDay.value;
});

// Customers assigned to agent but NOT in current day's route
// Note: A customer CAN be visited multiple days, so we check if they are in CURRENT day route.
const availableCustomers = computed(() => {
  const currentRouteCustomerIds = routeCustomers.value.map(r => r.customer.id);
  return customers.value.filter(c => !currentRouteCustomerIds.includes(c.id));
});

const filteredAvailableCustomers = computed(() => {
  if (!searchQuery.value) return availableCustomers.value;
  const q = searchQuery.value.toLowerCase();
  return availableCustomers.value.filter(c => 
    c.name.toLowerCase().includes(q) || 
    (c.cif && c.cif.includes(q))
  );
});

// The current day's route items
const routeCustomers = ref([]); 

const loadAgents = async () => {
  loadingAgents.value = true;
  try {
    if (authStore.role === 'sales_agent') {
      selectedAgentId.value = authStore.user.id;
      // No need to fetch list
    } else {
      // Fetch agents
      const data = await fetchUsers({ role: 'sales_agent', per_page: 100 });
      agents.value = data.data || data.items || [];
    }
  } catch (e) {
    console.error('Error loading agents', e);
  } finally {
    loadingAgents.value = false;
  }
};

const loadData = async () => {
  if (!selectedAgentId.value) return;
  
  loadingData.value = true;
  isDirty.value = false;
  try {
    // 1. Fetch Agent's Customers
    // Need to ensure backend supports filtering by agent_id if I am admin
    // Or if I am agent, it returns mine.
    // The fetchCustomers service usually supports filters.
    const customersData = await fetchCustomers({ 
      agent_user_id: selectedAgentId.value,
      per_page: 500 // Assuming max 500 customers per agent for now
    });
    customers.value = customersData.data || [];

    // 2. Fetch Routes
    const routesData = await fetchRoutes({ 
      agent_id: selectedAgentId.value 
    });
    routes.value = routesData;

    // 3. Set current day route
    updateCurrentDayRoute();

  } catch (e) {
    console.error('Error loading data', e);
    toast.error('Eroare la încărcarea datelor.');
  } finally {
    loadingData.value = false;
  }
};

const updateCurrentDayRoute = () => {
  // Filter routes for current day
  const dayRoutes = routes.value.filter(r => r.day_of_week === currentDay.value);
  // Sort by sort_order
  dayRoutes.sort((a, b) => a.sort_order - b.sort_order);
  
  // Map to local structure
  routeCustomers.value = dayRoutes.map(r => ({
    ...r,
    // Ensure we have customer object. API returns it.
    customer: r.customer || customers.value.find(c => c.id === r.customer_id) || { name: 'Unknown', id: r.customer_id }
  }));
};

const changeDay = (day) => {
  if (isDirty.value) {
    if (!confirm('Ai modificări nesalvate. Dacă schimbi ziua, le vei pierde. Continui?')) {
      return;
    }
  }
  currentDay.value = day;
  isDirty.value = false;
  // Refresh from 'routes' (which holds the server state)
  updateCurrentDayRoute();
};

const addToRoute = (customer) => {
  routeCustomers.value.push({
    tempId: `new_${++tempIdCounter}`,
    customer_id: customer.id,
    customer: customer,
    day_of_week: currentDay.value,
    week_type: 'all',
    agent_id: selectedAgentId.value
  });
  isDirty.value = true;
};

const removeFromRoute = (index) => {
  routeCustomers.value.splice(index, 1);
  isDirty.value = true;
};

const moveUp = (index) => {
  if (index === 0) return;
  const item = routeCustomers.value[index];
  routeCustomers.value.splice(index, 1);
  routeCustomers.value.splice(index - 1, 0, item);
  isDirty.value = true;
};

const moveDown = (index) => {
  if (index === routeCustomers.value.length - 1) return;
  const item = routeCustomers.value[index];
  routeCustomers.value.splice(index, 1);
  routeCustomers.value.splice(index + 1, 0, item);
  isDirty.value = true;
};

import { useRouter } from 'vue-router'

const router = useRouter()

const handleStartVisit = async (customer) => {
  if (visitStore.activeVisit) {
    if (!confirm('Aveți deja o vizită activă. Doriți să o încheiați și să începeți una nouă?')) return
  }
  
  try {
    await visitStore.startVisit(customer.id)
    toast.success(`Vizită începută pentru ${customer.name}`)
    // Redirect to customer details
    router.push({ name: 'admin-customer-details', params: { id: customer.id } })
  } catch (e) {
    toast.error('Eroare la începerea vizitei: ' + (e.response?.data?.message || e.message))
  }
}

const saveChanges = async () => {
  if (!selectedAgentId.value) return;
  saving.value = true;

  try {
    // 1. Identify items to delete
    // Items that were in 'routes' for this day, but are not in 'routeCustomers' (by id)
    const originalDayRoutes = routes.value.filter(r => r.day_of_week === currentDay.value);
    
    // We need to match by ID. New items don't have ID.
    const currentIds = routeCustomers.value
      .filter(r => r.id) // only those with existing ID
      .map(r => r.id);
      
    const toDelete = originalDayRoutes.filter(r => !currentIds.includes(r.id));
    
    // 2. Identify items to update (reorder) or create
    // We iterate through routeCustomers and update their sort_order
    const promises = [];

    // Delete first
    for (const r of toDelete) {
      promises.push(deleteRoute(r.id));
    }

    // Wait for deletes to finish? Or run all parallel?
    // Parallel is fine.
    
    await Promise.all(promises);
    
    // Now create/update
    // We process sequentially or parallel? Parallel is faster but sort_order might need care?
    // sort_order is explicitly sent.
    
    const upsertPromises = routeCustomers.value.map((item, index) => {
      const payload = {
        agent_id: selectedAgentId.value,
        customer_id: item.customer_id,
        day_of_week: currentDay.value,
        week_type: item.week_type || 'all',
        sort_order: index + 1
      };

      if (item.id) {
        // Update
        return updateRoute(item.id, payload);
      } else {
        // Create
        return createRoute(payload);
      }
    });

    await Promise.all(upsertPromises);

    toast.success('Modificările au fost salvate!');
    isDirty.value = false;
    
    // Reload everything to get fresh IDs and state
    await loadData();

  } catch (e) {
    console.error('Save error', e);
    toast.error('Eroare la salvare.');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadAgents();
});

watch(selectedAgentId, (newVal) => {
  if (newVal) {
    loadData();
  } else {
    routes.value = [];
    customers.value = [];
    routeCustomers.value = [];
  }
});
</script>

<style scoped>
.action-hover:hover {
  background-color: #f8f9fa;
}
.list-group-item {
  transition: all 0.2s;
}
</style>
