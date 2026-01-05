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
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Agent</th>
                            <th class="text-end">Target Vânzări (RON)</th>
                            <th class="text-end">Target Vizite</th>
                            <th class="text-end">Target Clienți Noi</th>
                            <th class="text-end">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="agent in agents" :key="agent.id">
                            <td>
                                <div class="fw-bold">{{ agent.first_name }} {{ agent.last_name }}</div>
                                <small class="text-muted">{{ agent.email }}</small>
                            </td>
                            <td class="text-end">
                                <div v-if="editingId === agent.id">
                                    <input type="number" v-model="tempTarget.target_sales_amount" class="form-control form-control-sm text-end" min="0" step="100">
                                </div>
                                <span v-else>{{ formatPrice(getTarget(agent.id)?.target_sales_amount) }}</span>
                            </td>
                            <td class="text-end">
                                <div v-if="editingId === agent.id">
                                    <input type="number" v-model="tempTarget.target_visits_count" class="form-control form-control-sm text-end" min="0">
                                </div>
                                <span v-else>{{ getTarget(agent.id)?.target_visits_count || 0 }}</span>
                            </td>
                            <td class="text-end">
                                <div v-if="editingId === agent.id">
                                    <input type="number" v-model="tempTarget.target_new_customers" class="form-control form-control-sm text-end" min="0">
                                </div>
                                <span v-else>{{ getTarget(agent.id)?.target_new_customers || 0 }}</span>
                            </td>
                            <td class="text-end">
                                <div v-if="editingId === agent.id" class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-success" @click="saveTarget(agent.id)" :disabled="saving">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" @click="cancelEdit">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <button v-else class="btn btn-sm btn-outline-primary" @click="editTarget(agent)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="editingId && agents.find(a => a.id === editingId)" :key="'details-' + editingId" class="table-light">
                            <td colspan="5">
                                <div class="p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0">Targeturi pe Categorii</h6>
                                        <button class="btn btn-sm btn-outline-primary" @click="addTargetItem">
                                            <i class="bi bi-plus-lg"></i> Adaugă Categorie
                                        </button>
                                    </div>
                                    <div v-if="tempTarget.items && tempTarget.items.length > 0" class="row g-2">
                                        <div v-for="(item, index) in tempTarget.items" :key="index" class="col-md-6">
                                            <div class="card card-body p-2 bg-white">
                                                <div class="d-flex gap-2 align-items-center">
                                                    <select v-model="item.target_id" class="form-select form-select-sm">
                                                        <option value="" disabled>Selectează Categoria</option>
                                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                                            {{ cat.name }}
                                                        </option>
                                                    </select>
                                                    <input type="number" v-model="item.target_amount" class="form-control form-control-sm" placeholder="Sumă (RON)" min="0" step="100" style="width: 120px;">
                                                    <button class="btn btn-sm btn-outline-danger" @click="removeTargetItem(index)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-muted small fst-italic">
                                        Nu sunt definite targeturi specifice pe categorii.
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="agents.length === 0">
                            <td colspan="5" class="text-center py-4 text-muted">Nu există agenți asignați.</td>
                        </tr>
                    </tbody>
                </table>
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
