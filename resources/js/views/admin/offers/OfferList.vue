<template>
  <div class="container-fluid py-4">
    <!-- Header Section with Actions -->
    <div class="row align-items-center justify-content-between mb-4 g-3">
      <div class="col-12 col-md-auto">
        <h1 class="h3 fw-bold mb-1 text-gray-800">
          {{ activeTab === 'offers' ? 'Oferte Comerciale' : 'Cereri de Ofertă' }}
        </h1>
        <p class="text-muted small mb-0">Gestionează ofertele și cererile clienților</p>
      </div>
      <div class="col-12 col-md-auto d-flex gap-2">
        <!-- Desktop Action Button -->
        <button 
          v-if="activeTab === 'offers'"
          class="btn btn-primary d-none d-md-flex align-items-center shadow-sm px-4 py-2" 
          @click="openCustomerSelectionModal"
        >
          <i class="bi bi-plus-circle-fill me-2 fs-5"></i>
          <span class="fw-semibold">Ofertă Nouă</span>
        </button>
      </div>
    </div>

    <!-- Mobile Floating Action Button -->
    <button 
        v-if="activeTab === 'offers'"
        class="btn btn-primary rounded-circle shadow-lg position-fixed bottom-0 end-0 m-4 d-md-none d-flex align-items-center justify-content-center z-3"
        style="width: 60px; height: 60px;"
        @click="openCustomerSelectionModal"
    >
        <i class="bi bi-plus-lg fs-3"></i>
    </button>

    <!-- Tabs -->
    <ul class="nav nav-tabs nav-fill mb-4 border-bottom-0">
        <li class="nav-item">
            <button 
                class="nav-link border rounded-top" 
                :class="{active: activeTab === 'offers', 'fw-bold': activeTab === 'offers', 'text-muted': activeTab !== 'offers'}" 
                @click="switchTab('offers')"
            >
                <i class="bi bi-file-earmark-text me-2"></i>Oferte Comerciale
            </button>
        </li>
        <li class="nav-item">
            <button 
                class="nav-link border rounded-top ms-1" 
                :class="{active: activeTab === 'requests', 'fw-bold': activeTab === 'requests', 'text-muted': activeTab !== 'requests'}" 
                @click="switchTab('requests')"
            >
                <i class="bi bi-inbox me-2"></i>Cereri de Ofertă
            </button>
        </li>
    </ul>

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4 bg-white" v-if="activeTab === 'offers'">
      <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase text-muted">Status Ofertă</label>
                <select v-model="filters.status" class="form-select bg-light border-0" @change="loadOffers">
                    <option value="">Toate Statusurile</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Trimisă la Client</option>
                    <option value="pending_approval">Necesită Aprobare</option>
                    <option value="approved">Aprobată</option>
                    <option value="negotiation">În Negociere</option>
                    <option value="accepted">Acceptată</option>
                    <option value="completed">Finalizată</option>
                    <option value="rejected">Respinsă</option>
                </select>
            </div>
            <div class="col-md-auto ms-auto">
                 <button class="btn btn-light text-primary border-0" @click="loadOffers">
                    <i class="bi bi-arrow-clockwise me-1"></i> Actualizează
                 </button>
            </div>
        </div>
      </div>
    </div>

    <!-- Customer Selection Modal -->
    <div v-if="showCustomerModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Selectează Clientul</h5>
                    <button type="button" class="btn-close" @click="closeCustomerModal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="input-group mb-4">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input 
                            type="text" 
                            class="form-control bg-light border-start-0" 
                            placeholder="Caută client după nume, CIF sau oraș..." 
                            v-model="customerSearch"
                            @input="debouncedSearch"
                        >
                    </div>
                    
                    <div v-if="loadingCustomers" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    
                    <div v-else-if="customersList.length === 0" class="text-center py-4 text-muted">
                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                        Nu am găsit clienți conform criteriilor.
                    </div>
                    
                    <div v-else class="list-group list-group-flush">
                        <button 
                            v-for="customer in customersList" 
                            :key="customer.id"
                            class="list-group-item list-group-item-action p-3 border rounded mb-2 d-flex justify-content-between align-items-center"
                            @click="selectCustomer(customer)"
                        >
                            <div>
                                <h6 class="mb-1 fw-bold text-primary">{{ customer.name }}</h6>
                                <div class="small text-muted">
                                    <span class="me-3"><i class="bi bi-building me-1"></i> {{ customer.cif }}</span>
                                    <span><i class="bi bi-geo-alt me-1"></i> {{ customer.city || 'Nespecificat' }}</span>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </button>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" @click="closeCustomerModal">Anulează</button>
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

// Customer Selection Modal Logic
const showCustomerModal = ref(false);
const customerSearch = ref('');
const customersList = ref([]);
const loadingCustomers = ref(false);
let searchTimeout = null;

const openCustomerSelectionModal = async () => {
    showCustomerModal.value = true;
    customerSearch.value = '';
    customersList.value = [];
    // Load initial list (first 10)
    await performSearch('');
};

const closeCustomerModal = () => {
    showCustomerModal.value = false;
};

const debouncedSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch(customerSearch.value);
    }, 300);
};

const performSearch = async (query) => {
    loadingCustomers.value = true;
    try {
        const { data } = await adminApi.get('/customers', {
            params: {
                search: query,
                per_page: 10,
                sort_by: 'name',
                sort_dir: 'asc'
            }
        });
        customersList.value = data.data || [];
    } catch (e) {
        console.error('Error searching customers', e);
        toast.error('Eroare la căutarea clienților');
    } finally {
        loadingCustomers.value = false;
    }
};

const selectCustomer = (customer) => {
    closeCustomerModal();
    router.push({ 
        name: getRouteName('new'), 
        query: { customer_id: customer.id } 
    });
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
