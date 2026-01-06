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

    <!-- Mobile-first Offers List -->
    <div v-if="activeTab === 'offers'">
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
        <div v-else-if="offers.length === 0" class="text-center py-5 text-muted">
            Nu există oferte înregistrate.
        </div>
        <div v-else class="row g-3">
            <div v-for="offer in offers" :key="offer.id" class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm border h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="fw-bold text-primary">#{{ offer.id }}</span>
                            <span class="badge" :class="statusBadge(offer.status)">
                                {{ statusLabel(offer.status) }}
                            </span>
                        </div>
                        
                        <h6 class="card-title mb-1 text-truncate">{{ offer.customer?.name || 'Client Necunoscut' }}</h6>
                        <div class="small text-muted mb-2">{{ offer.customer?.cif }}</div>
                        
                        <div class="mb-2">
                            <span class="badge bg-light text-dark border">{{ offer.agent?.name || 'Fără agent' }}</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted">Total:</span>
                            <span class="fw-bold">{{ formatPrice(offer.total_amount) }}</span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small text-muted">Data:</span>
                            <span class="small">{{ formatDate(offer.created_at) }}</span>
                        </div>

                        <div v-if="offer.requires_director_approval" class="alert alert-warning py-1 px-2 small mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i> Necesită Derogare
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-sm btn-outline-primary" @click="viewOffer(offer)">
                                <i class="bi bi-eye me-1"></i> Vezi Detalii
                            </button>
                            
                            <button 
                                v-if="['accepted', 'sent', 'approved'].includes(offer.status)" 
                                class="btn btn-sm btn-outline-success" 
                                @click="convertToOrder(offer)"
                            >
                                <i class="bi bi-cart-check me-1"></i> Transformă în Comandă
                            </button>
                            
                            <button v-if="offer.requires_director_approval && canApprove" class="btn btn-sm btn-success" @click="approveDerogation(offer)">
                                Aprobă Derogare
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4" v-if="meta && meta.last_page > 1">
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
    
    <!-- Mobile-first Requests List -->
    <div v-if="activeTab === 'requests'">
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
        <div v-else-if="requests.length === 0" class="text-center py-5 text-muted">
            Nu există cereri de ofertă.
        </div>
        <div v-else class="row g-3">
            <div v-for="req in requests" :key="req.id" class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm border h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="fw-bold text-primary">#{{ req.id }}</span>
                            <span class="badge bg-secondary">{{ req.status }}</span>
                        </div>
                        
                        <h6 class="card-title mb-1 text-truncate">{{ req.customer?.name || 'Client Necunoscut' }}</h6>
                        <div class="small text-muted mb-2">{{ req.customer?.cif }}</div>
                        
                        <div class="bg-light p-2 rounded small mb-3 text-truncate" style="max-width: 100%;">
                            {{ req.customer_notes || 'Fără notițe' }}
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small text-muted">Data:</span>
                            <span class="small">{{ formatDate(req.created_at) }}</span>
                        </div>

                        <div class="d-grid gap-2">
                            <button v-if="!req.offer" class="btn btn-sm btn-primary" @click="convertToOffer(req)" :disabled="req.status === 'completed' || req.status === 'offered'">
                                <i class="bi bi-file-earmark-plus me-1"></i> Creează Ofertă
                            </button>
                            <button v-else class="btn btn-sm btn-outline-primary" @click="viewOffer(req.offer)">
                                <i class="bi bi-eye me-1"></i> Vezi Ofertă #{{ req.offer.id }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { adminApi } from '@/services/http';
import { useAuthStore } from '@/store/auth';
import { useTrackingStore } from '@/store/tracking';
import { useToast } from 'vue-toastification';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const trackingStore = useTrackingStore();
const toast = useToast();

const isMounted = ref(true);

onUnmounted(() => {
    isMounted.value = false;
});

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
    if (!isMounted.value) return;
    loading.value = true;
    try {
        const { data } = await adminApi.get('/offers', { params: filters.value });
        if (!isMounted.value) return;
        offers.value = data.data;
        meta.value = data; // Laravel pagination object
    } catch (e) {
        if (!isMounted.value) return;
        console.error(e);
        // Only show toast if not aborted/cancelled
        if (e.code !== 'ERR_CANCELED' && e.message !== 'canceled') {
            toast.error('Eroare la încărcarea ofertelor');
        }
    } finally {
        if (isMounted.value) {
            loading.value = false;
        }
    }
};

const loadRequests = async () => {
    if (!isMounted.value) return;
    loading.value = true;
    try {
        const { data } = await adminApi.get('/quotes');
        if (!isMounted.value) return;
        requests.value = data.data;
    } catch (e) {
        if (!isMounted.value) return;
        console.error(e);
        toast.error('Eroare la încărcarea cererilor');
    } finally {
        if (isMounted.value) {
            loading.value = false;
        }
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

onMounted(async () => {
    if (['sales_agent', 'sales_director'].includes(authStore.role)) {
        if (!trackingStore.isShiftActive) {
            try {
                await trackingStore.checkStatus();
            } catch (e) {
                console.error('Check status failed', e);
            }
            
            if (!isMounted.value) return;

            if (!trackingStore.isShiftActive) {
                 toast.error('Trebuie să începeți programul de lucru pentru a vedea ofertele!');
                 router.push({ name: 'agent-dashboard' });
                 return;
            }
        }
    }

    if (!isMounted.value) return;

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
