<template>
  <div class="row">
    <!-- Director Selector -->
    <div class="col-12 mb-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-3 bg-white rounded d-flex align-items-center gap-3">
          <div class="flex-grow-1">
            <label class="form-label small fw-bold text-muted text-uppercase mb-1">Selectează Director Vânzări</label>
            <select v-model="selectedDirectorId" class="form-select" @change="loadData">
              <option :value="null">-- Alege un director --</option>
              <option v-for="d in directors" :key="d.id" :value="d.id">
                {{ d.name }} ({{ d.email }})
              </option>
            </select>
          </div>
          <div class="pt-3">
            <button 
              class="btn btn-primary shadow-sm" 
              @click="saveChanges" 
              :disabled="saving || !isDirty || !selectedDirectorId"
            >
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-save me-2"></i>
              Salvează Modificările
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="selectedDirectorId" class="row g-4">
      <!-- Available Agents -->
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-bold text-muted text-uppercase">
              Agenți Disponibili
              <span class="badge bg-light text-dark ms-2">{{ filteredAvailableAgents.length }}</span>
            </h6>
            <div class="small text-muted mt-1">Agenți care nu au un director asignat</div>
          </div>
          <div class="card-body p-0 overflow-auto" style="height: 600px;">
             <!-- Search -->
            <div class="p-3 border-bottom sticky-top bg-white">
              <input 
                v-model="searchQuery" 
                type="text" 
                class="form-control form-control-sm" 
                placeholder="Caută agent..."
              >
            </div>
            
            <div class="list-group list-group-flush">
              <div 
                v-for="agent in filteredAvailableAgents" 
                :key="agent.id" 
                class="list-group-item d-flex justify-content-between align-items-center action-hover"
              >
                <div>
                  <div class="fw-bold text-dark">{{ agent.name }}</div>
                  <div class="small text-muted">{{ agent.email }}</div>
                </div>
                <button class="btn btn-sm btn-outline-primary" @click="assignAgent(agent)">
                  <i class="bi bi-arrow-right"></i>
                  Alocă
                </button>
              </div>
              <div v-if="filteredAvailableAgents.length === 0" class="p-4 text-center text-muted small">
                Nu am găsit agenți disponibili.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Assigned Agents -->
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-primary-subtle">
          <div class="card-header bg-primary bg-opacity-10 border-bottom py-3">
            <h6 class="mb-0 fw-bold text-primary text-uppercase">
              Agenți Subordonați
              <span class="badge bg-primary ms-2">{{ assignedAgents.length }}</span>
            </h6>
            <div class="small text-primary text-opacity-75 mt-1">Echipa directorului selectat</div>
          </div>
          <div class="card-body p-0 overflow-auto" style="height: 600px;">
            <div v-if="assignedAgents.length === 0" class="p-5 text-center text-muted">
              <i class="bi bi-people fs-1 mb-3 d-block opacity-25"></i>
              Niciun agent asignat.
              <br>Adaugă din lista din stânga.
            </div>
            
            <div v-else class="list-group list-group-flush">
              <div 
                v-for="(agent, index) in assignedAgents" 
                :key="agent.id" 
                class="list-group-item d-flex align-items-center justify-content-between"
              >
                <div class="d-flex align-items-center gap-3">
                  <div class="fw-bold text-muted" style="width: 20px;">{{ index + 1 }}.</div>
                  <div>
                    <div class="fw-bold text-dark">{{ agent.name }}</div>
                    <div class="small text-muted">{{ agent.email }}</div>
                  </div>
                </div>
                
                <button 
                  class="btn btn-sm btn-outline-danger" 
                  @click="unassignAgent(agent)"
                  title="Elimină din echipă"
                >
                  <i class="bi bi-trash"></i>
                  Elimină
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="col-12 text-center py-5">
      <div class="text-muted">Selectează un director pentru a gestiona echipa.</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { fetchUsers, updateUser } from '@/services/admin/users';
import { useToast } from 'vue-toastification';

const toast = useToast();

const directors = ref([]);
const allAgents = ref([]); // All agents in system
const selectedDirectorId = ref(null);
const loading = ref(false);
const saving = ref(false);
const isDirty = ref(false);
const searchQuery = ref('');

// State for current selection
const assignedAgents = ref([]);
const availableAgents = ref([]);

const loadDirectors = async () => {
  try {
    const data = await fetchUsers({ role: 'sales_director', per_page: 100 });
    directors.value = data.data || data.items || [];
  } catch (e) {
    console.error('loadDirectors error', e);
  }
};

const loadAgents = async () => {
  try {
    // We fetch ALL agents to sort them into available/assigned
    const data = await fetchUsers({ role: 'sales_agent', per_page: 500 });
    allAgents.value = data.data || data.items || [];
  } catch (e) {
    console.error('loadAgents error', e);
  }
};

const loadData = () => {
  if (!selectedDirectorId.value) return;
  
  // Reset lists based on current allAgents and selectedDirectorId
  assignedAgents.value = allAgents.value.filter(a => a.director_id === selectedDirectorId.value);
  
  // Available are those with NO director OR assigned to current director (which goes to assigned)
  // Actually available should be those with director_id === null
  availableAgents.value = allAgents.value.filter(a => !a.director_id);
  
  isDirty.value = false;
};

const filteredAvailableAgents = computed(() => {
  if (!searchQuery.value) return availableAgents.value;
  const q = searchQuery.value.toLowerCase();
  return availableAgents.value.filter(a => 
    a.name.toLowerCase().includes(q) || 
    a.email.toLowerCase().includes(q)
  );
});

const assignAgent = (agent) => {
  // Move from available to assigned
  availableAgents.value = availableAgents.value.filter(a => a.id !== agent.id);
  assignedAgents.value.push({ ...agent, director_id: selectedDirectorId.value }); // Optimistic update
  isDirty.value = true;
};

const unassignAgent = (agent) => {
  // Move from assigned to available
  assignedAgents.value = assignedAgents.value.filter(a => a.id !== agent.id);
  availableAgents.value.push({ ...agent, director_id: null });
  isDirty.value = true;
};

const saveChanges = async () => {
  if (!selectedDirectorId.value) return;
  saving.value = true;

  try {
    const promises = [];

    // 1. Update newly assigned agents
    // Agents currently in assignedAgents list should have director_id set to selectedDirectorId
    // We only need to update those who didn't have this director_id before
    for (const agent of assignedAgents.value) {
       // Check against original source of truth or just update all? 
       // Updating all is safer but more requests. 
       // Optimization: check if agent.director_id was already selectedDirectorId
       // However, we modified local objects in assignAgent. 
       // Let's rely on the original list 'allAgents' to check changes if we want optimization.
       
       // Simple approach: Update all in assigned list to have this director
       promises.push(updateUser(agent.id, { director_id: selectedDirectorId.value }));
    }

    // 2. Update unassigned agents
    // Agents currently in availableAgents list who HAD this director before need to be set to null
    // We can just iterate availableAgents and if their director_id == selectedDirectorId (from original fetch), set to null.
    // But 'agent' object in availableAgents has 'director_id' null if we moved it from assigned? 
    // Wait, in unassignAgent we did: { ...agent, director_id: null }
    // So we can just check if we need to save 'null'.
    
    // Better approach:
    // We know exactly what changed if we tracked it, but here we can just "Sync".
    // But we don't have a "Sync" endpoint for One-to-Many on User model from frontend easily.
    // So we update individually.
    
    // Find agents that WERE assigned to this director but are NOT anymore.
    const originallyAssigned = allAgents.value.filter(a => a.director_id === selectedDirectorId.value);
    const currentlyAssignedIds = assignedAgents.value.map(a => a.id);
    
    const toRemove = originallyAssigned.filter(a => !currentlyAssignedIds.includes(a.id));
    
    for (const agent of toRemove) {
        promises.push(updateUser(agent.id, { director_id: null }));
    }

    await Promise.all(promises);
    
    toast.success('Asignările au fost salvate!');
    
    // Refresh data
    await loadAgents();
    loadData();

  } catch (e) {
    console.error('Save error', e);
    toast.error('Eroare la salvare.');
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  loading.value = true;
  await Promise.all([loadDirectors(), loadAgents()]);
  loading.value = false;
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