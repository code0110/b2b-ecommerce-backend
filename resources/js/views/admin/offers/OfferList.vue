<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 fw-bold mb-1 text-gray-800">
          {{ activeTab === 'offers' ? 'Oferte Comerciale' : 'Cereri de Ofertă' }}
      </h1>
      <button class="btn btn-primary" @click="openCreateModal" v-if="activeTab === 'offers'">
        <i class="bi bi-plus-lg me-2"></i>
        Ofertă Nouă
      </button>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <button class="nav-link" :class="{active: activeTab === 'offers'}" @click="switchTab('offers')">
                Oferte Comerciale
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" :class="{active: activeTab === 'requests'}" @click="switchTab('requests')">
                Cereri de Ofertă
            </button>
        </li>
    </ul>

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4" v-if="activeTab === 'offers'">
      <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Status</label>
                <select v-model="filters.status" class="form-select" @change="loadOffers">
                    <option value="">Toate</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Trimisă la Client</option>
                    <option value="pending_approval">Necesită Aprobare Director</option>
                    <option value="approved">Aprobată</option>
                    <option value="negotiation">În Negociere</option>
                    <option value="accepted">Acceptată</option>
                    <option value="completed">Finalizată (Comandă)</option>
                    <option value="rejected">Respinsă</option>
                </select>
            </div>
            <div class="col-md-3">
                 <button class="btn btn-outline-secondary mt-4 w-100" @click="loadOffers">Actualizează</button>
            </div>
        </div>
      </div>
    </div>

    <!-- Table Offers -->
    <div class="card shadow-sm border-0" v-if="activeTab === 'offers'">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">#ID</th>
                            <th>Client</th>
                            <th>Agent</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th class="text-end pe-4">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                            </td>
                        </tr>
                        <tr v-else-if="offers.length === 0">
                            <td colspan="7" class="text-center py-5 text-muted">
                                Nu există oferte înregistrate.
                            </td>
                        </tr>
                        <tr v-for="offer in offers" :key="offer.id">
                            <td class="ps-4 fw-bold">#{{ offer.id }}</td>
                            <td>
                                <div>{{ offer.customer?.name || 'Client Necunoscut' }}</div>
                                <div class="small text-muted">{{ offer.customer?.cif }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ offer.agent?.name }}</span>
                            </td>
                            <td class="fw-bold">{{ formatPrice(offer.total_amount) }}</td>
                            <td>
                                <span class="badge" :class="statusBadge(offer.status)">
                                    {{ statusLabel(offer.status) }}
                                </span>
                                <div v-if="offer.requires_director_approval" class="mt-1">
                                    <span class="badge bg-danger">Necesită Derogare</span>
                                </div>
                            </td>
                            <td class="small text-muted">{{ formatDate(offer.created_at) }}</td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary me-2" @click="viewOffer(offer)" title="Vezi Detalii">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button 
                                    v-if="['accepted', 'sent', 'approved'].includes(offer.status)" 
                                    class="btn btn-sm btn-outline-success me-2" 
                                    @click="convertToOrder(offer)"
                                    title="Transformă în Comandă"
                                >
                                    <i class="bi bi-cart-check"></i>
                                </button>
                                <button v-if="offer.requires_director_approval && canApprove" class="btn btn-sm btn-success" @click="approveDerogation(offer)">
                                    Aprobă Derogare
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
         <div class="card-footer bg-white border-top-0 py-3" v-if="meta && meta.last_page > 1">
            <!-- Pagination logic here -->
             <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                        <button class="page-link" @click="changePage(meta.current_page - 1)">«</button>
                    </li>
                    <li class="page-item disabled"><span class="page-link">Pagina {{ meta.current_page }} din {{ meta.last_page }}</span></li>
                    <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                        <button class="page-link" @click="changePage(meta.current_page + 1)">»</button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    
    <!-- Table Requests -->
    <div class="card shadow-sm border-0" v-if="activeTab === 'requests'">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">#ID</th>
                            <th>Client</th>
                            <th>Notițe Client</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th class="text-end pe-4">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                            </td>
                        </tr>
                        <tr v-else-if="requests.length === 0">
                            <td colspan="6" class="text-center py-5 text-muted">
                                Nu există cereri de ofertă.
                            </td>
                        </tr>
                        <tr v-for="req in requests" :key="req.id">
                            <td class="ps-4 fw-bold">#{{ req.id }}</td>
                            <td>
                                <div>{{ req.customer?.name || 'Client Necunoscut' }}</div>
                                <div class="small text-muted">{{ req.customer?.cif }}</div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 300px;">{{ req.customer_notes }}</div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ req.status }}</span>
                            </td>
                            <td class="small text-muted">{{ formatDate(req.created_at) }}</td>
                            <td class="text-end pe-4">
                                <button v-if="!req.offer" class="btn btn-sm btn-primary" @click="convertToOffer(req)" :disabled="req.status === 'completed' || req.status === 'offered'">
                                    Creează Ofertă
                                </button>
                                <button v-else class="btn btn-sm btn-outline-primary" @click="viewOffer(req.offer)">
                                    Vezi Ofertă #{{ req.offer.id }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { adminApi } from '@/services/http';
import { useAuthStore } from '@/store/auth';
import { useToast } from 'vue-toastification';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const toast = useToast();

const activeTab = ref('offers');
const offers = ref([]);
const requests = ref([]);
const loading = ref(false);
const meta = ref({});
const filters = ref({
    status: '',
    page: 1
});

const canApprove = computed(() => authStore.role === 'admin' || authStore.role === 'sales_director');

const loadOffers = async () => {
    loading.value = true;
    try {
        const { data } = await adminApi.get('/offers', { params: filters.value });
        offers.value = data.data;
        meta.value = data; // Laravel pagination object
    } catch (e) {
        console.error(e);
        toast.error('Eroare la încărcarea ofertelor');
    } finally {
        loading.value = false;
    }
};

const loadRequests = async () => {
    loading.value = true;
    try {
        const { data } = await adminApi.get('/quotes');
        requests.value = data.data;
    } catch (e) {
        console.error(e);
        toast.error('Eroare la încărcarea cererilor');
    } finally {
        loading.value = false;
    }
};

const switchTab = (tab) => {
    activeTab.value = tab;
    // Update query param without reloading
    router.replace({ query: { ...route.query, tab } });
    if (tab === 'offers') loadOffers();
    else loadRequests();
};

const changePage = (page) => {
    filters.value.page = page;
    loadOffers();
};

const formatPrice = (val) => new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(val);
const formatDate = (val) => new Date(val).toLocaleDateString('ro-RO');

const statusLabel = (s) => {
    const map = {
        'draft': 'Draft',
        'sent': 'Trimisă',
        'pending_approval': 'În Așteptare Aprobare',
        'approved': 'Aprobată',
        'negotiation': 'Negociere',
        'rejected': 'Respinsă',
        'accepted': 'Acceptată',
        'completed': 'Finalizată (Comandă)'
    };
    return map[s] || s;
};

const statusBadge = (s) => {
    const map = {
        'draft': 'bg-secondary',
        'sent': 'bg-primary',
        'pending_approval': 'bg-warning text-dark',
        'approved': 'bg-success',
        'negotiation': 'bg-info text-dark',
        'rejected': 'bg-danger',
        'accepted': 'bg-success',
        'completed': 'bg-secondary'
    };
    return map[s] || 'bg-secondary';
};

const getRouteName = (base) => {
    if (authStore.role === 'admin') return `admin-offers${base ? '-' + base : ''}`;
    // If we are in account section, use account routes
    return `account-offers${base ? '-' + base : '-list'}`;
};

const viewOffer = (offer) => {
    router.push({ name: getRouteName('edit'), params: { id: offer.id } });
};

const convertToOrder = async (offer) => {
    if (!confirm('Ești sigur că vrei să transformi această ofertă în comandă?')) return;
    try {
        const { data } = await adminApi.post(`/offers/${offer.id}/convert-to-order`);
        toast.success('Oferta a fost transformată în comandă cu succes!');
        
        if (data.order_id) {
            const routeName = authStore.role === 'admin' ? 'admin-order-details' : 'account-order-details';
            router.push({ name: routeName, params: { id: data.order_id } });
        } else {
            loadOffers();
        }
    } catch (e) {
        console.error(e);
        toast.error(e.response?.data?.message || 'Eroare la transformarea în comandă');
    }
};

const convertToOffer = (req) => {
    router.push({ name: getRouteName('new'), query: { customer_id: req.customer_id, request_id: req.id } });
};

const approveDerogation = async (offer) => {
    if (!confirm('Confirmi aprobarea acestei oferte cu derogare?')) return;
    try {
        await adminApi.post(`/offers/${offer.id}/status`, { status: 'approved' });
        toast.success('Oferta a fost aprobată!');
        loadOffers();
    } catch (e) {
        toast.error('Eroare la aprobare');
    }
};

const openCreateModal = () => {
    router.push({ name: getRouteName('new') });
};

onMounted(() => {
    if (route.query.tab) {
        activeTab.value = route.query.tab;
    }
    if (route.query.status) {
        filters.value.status = route.query.status;
    }
    
    if (activeTab.value === 'offers') loadOffers();
    else loadRequests();
});
</script>
