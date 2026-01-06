<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">Obiective Vânzări (KPI)</h1>
      <div class="d-flex gap-2">
         <select v-model="selectedYear" class="form-select" style="width: 100px;">
             <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
         </select>
         <select v-model="selectedMonth" class="form-select" style="width: 150px;">
             <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
         </select>
         <button class="btn btn-primary" @click="fetchTargets">Actualizează</button>
      </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <div v-if="agents.length === 0" class="text-center text-muted py-4">
                Nu există agenți asignați.
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                <div v-for="agent in agents" :key="agent.id" class="col">
                    <div class="card h-100 border shadow-sm" :class="{'border-primary': editingId === agent.id}">
                        <div class="card-body">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0">{{ agent.first_name }} {{ agent.last_name }}</h6>
                                    <small class="text-muted">{{ agent.email }}</small>
                                </div>
                                <div v-if="editingId !== agent.id">
                                    <button class="btn btn-sm btn-outline-primary rounded-circle" @click="editTarget(agent)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- View Mode -->
                            <div v-if="editingId !== agent.id">
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Target Vânzări</span>
                                    <span class="fw-bold">{{ formatPrice(getTarget(agent.id)?.target_sales_amount) }}</span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Target Vizite</span>
                                    <span class="fw-bold">{{ getTarget(agent.id)?.target_visits_count || 0 }}</span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Target Clienți Noi</span>
                                    <span class="fw-bold">{{ getTarget(agent.id)?.target_new_customers || 0 }}</span>
                                </div>
                            </div>

                            <!-- Edit Mode -->
                            <div v-else>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Target Vânzări (RON)</label>
                                    <input type="number" v-model="tempTarget.target_sales_amount" class="form-control form-control-sm" min="0" step="100">
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Vizite</label>
                                        <input type="number" v-model="tempTarget.target_visits_count" class="form-control form-control-sm" min="0">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Clienți Noi</label>
                                        <input type="number" v-model="tempTarget.target_new_customers" class="form-control form-control-sm" min="0">
                                    </div>
                                </div>

                                <hr class="my-3">
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label small fw-bold mb-0">Categorii</label>
                                    <button class="btn btn-xs btn-outline-primary py-0" @click="addTargetItem" style="font-size: 0.75rem;">
                                        <i class="bi bi-plus-lg"></i> Adaugă
                                    </button>
                                </div>

                                <div v-if="tempTarget.items && tempTarget.items.length > 0" class="d-flex flex-column gap-2 mb-3">
                                    <div v-for="(item, index) in tempTarget.items" :key="index" class="bg-light p-2 rounded border">
                                        <select v-model="item.target_id" class="form-select form-select-sm mb-2">
                                            <option value="" disabled>Categorie</option>
                                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                                {{ cat.name }}
                                            </option>
                                        </select>
                                        <div class="d-flex gap-2">
                                            <input type="number" v-model="item.target_amount" class="form-control form-control-sm" placeholder="Sumă" min="0" step="100">
                                            <button class="btn btn-sm btn-outline-danger" @click="removeTargetItem(index)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-muted small fst-italic mb-3">
                                    Fără target pe categorii.
                                </div>

                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-success flex-grow-1" @click="saveTarget(agent.id)" :disabled="saving">
                                        <i class="bi bi-check-lg me-1"></i> Salvează
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary flex-grow-1" @click="cancelEdit">
                                        <i class="bi bi-x-lg me-1"></i> Anulează
                                    </button>
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
import { useToast } from 'vue-toastification';

const toast = useToast();

const selectedYear = ref(new Date().getFullYear());
const selectedMonth = ref(new Date().getMonth() + 1);
const years = [2025, 2026, 2027];
const months = ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];

const agents = ref([]);
const targets = ref([]);
const categories = ref([]);
const editingId = ref(null);
const tempTarget = ref({});
const saving = ref(false);

const getTarget = (userId) => {
    return targets.value.find(t => t.user_id === userId) || {};
};

const formatPrice = (value) => {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(value || 0);
};

const fetchAgents = async () => {
    try {
        const res = await adminApi.get('/users?role=sales_agent&per_page=100');
        agents.value = res.data.data;
    } catch (e) {
        console.error(e);
        toast.error('Eroare la încărcarea agenților');
    }
};

const fetchTargets = async () => {
    try {
        const res = await adminApi.get('/sales-targets', {
            params: {
                year: selectedYear.value,
                month: selectedMonth.value
            }
        });
        targets.value = res.data;
    } catch (e) {
        console.error(e);
        toast.error('Eroare la încărcarea obiectivelor');
    }
};

const fetchCategories = async () => {
    try {
        const res = await adminApi.get('/categories?per_page=100');
        categories.value = res.data.data || res.data;
    } catch (e) {
        console.error('Eroare încărcare categorii', e);
    }
};

const editTarget = (agent) => {
    if (!agent || !agent.id) return;
    
    const existing = getTarget(agent.id);
    tempTarget.value = {
        target_sales_amount: existing.target_sales_amount || 0,
        target_visits_count: existing.target_visits_count || 0,
        target_new_customers: existing.target_new_customers || 0,
        items: existing.items ? existing.items.map(i => ({
            target_type: 'category',
            target_id: i.target_id,
            target_amount: i.target_amount
        })) : []
    };
    editingId.value = agent.id;
};

const addTargetItem = () => {
    if (!tempTarget.value.items) {
        tempTarget.value.items = [];
    }
    tempTarget.value.items.push({
        target_type: 'category',
        target_id: '',
        target_amount: 0
    });
};

const removeTargetItem = (index) => {
    if (tempTarget.value.items) {
        tempTarget.value.items.splice(index, 1);
    }
};

const cancelEdit = () => {
    editingId.value = null;
    tempTarget.value = {};
};

const saveTarget = async (userId) => {
    saving.value = true;
    try {
        const payload = {
            user_id: userId,
            year: selectedYear.value,
            month: selectedMonth.value,
            ...tempTarget.value
        };
        
        const res = await adminApi.post('/sales-targets', payload);
        
        // Update local list
        const idx = targets.value.findIndex(t => t.user_id === userId);
        if (idx !== -1) {
            targets.value[idx] = res.data;
        } else {
            targets.value.push(res.data);
        }
        
        editingId.value = null;
        toast.success('Obiectiv salvat cu succes!');
    } catch (e) {
        console.error(e);
        toast.error('Nu s-a putut salva obiectivul.');
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchAgents();
    fetchTargets();
    fetchCategories();
});
</script>
