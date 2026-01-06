<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1">{{ isEdit ? 'Editare Ofertă' : 'Ofertă Nouă' }}</h1>
        <p class="text-muted small mb-0">Completează detaliile ofertei comerciale.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="router.back()">Anulează</button>
        <button class="btn btn-primary" @click="saveOffer" :disabled="saving || !isValid">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
          {{ isEdit ? 'Salvează Modificările' : 'Creează Ofertă' }}
        </button>
      </div>
    </div>

    <div class="row g-4">
      <!-- Left Column: Customer & Details -->
      <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Informații Client</h6>
            <span v-if="currentStatus" class="badge" :class="statusBadge(currentStatus)">{{ statusLabel(currentStatus) }}</span>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">Client</label>
              <CustomerSelector @select="selectCustomer" ref="customerSelectorRef" :disabled="isCustomerLocked" />
              <div v-if="form.customer" class="mt-2 p-2 bg-light rounded small">
                 <div class="fw-bold">{{ form.customer.name }}</div>
                 <div>{{ form.customer.cif }}</div>
                 <div>{{ form.customer.address }}</div>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">Valabilitate până la</label>
              <input type="date" class="form-control" v-model="form.valid_until">
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">Note interne / Mesaj</label>
              <textarea class="form-control" rows="4" v-model="form.notes" placeholder="Detalii suplimentare..."></textarea>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 mb-4" v-if="isEdit">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Acțiuni & Status</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button v-if="currentStatus === 'draft'" class="btn btn-primary" @click="changeStatus('sent')">
                        <i class="bi bi-send me-2"></i> Trimite la Client
                    </button>
                    
                    <div v-if="currentStatus === 'pending_approval' && canApprove" class="d-grid gap-2">
                        <button class="btn btn-success" @click="changeStatus('approved')">
                            <i class="bi bi-check-lg me-2"></i> Aprobă Derogare
                        </button>
                        <button class="btn btn-danger" @click="changeStatus('rejected')">
                            <i class="bi bi-x-lg me-2"></i> Respinge
                        </button>
                    </div>

                    <div v-if="['sent', 'approved', 'accepted'].includes(currentStatus)" class="d-grid gap-2 mb-2">
                        <button class="btn btn-success" @click="convertToOrder">
                            <i class="bi bi-cart-check me-2"></i> Transformă în Comandă
                        </button>
                    </div>

                    <div v-if="['sent', 'negotiation', 'approved'].includes(currentStatus)" class="d-grid gap-2">
                        <button class="btn btn-outline-primary" @click="changeStatus('completed')">
                            <i class="bi bi-check-circle me-2"></i> Marchează Finalizat (Manual)
                        </button>
                        <button class="btn btn-outline-danger" @click="changeStatus('rejected')">
                            Anulează Oferta
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Sumar Ofertă</h6>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Total Brut:</span>
              <span class="fw-bold">{{ formatPrice(totals.gross) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2 text-success">
              <span class="text-muted">Discount Total:</span>
              <span>-{{ formatPrice(totals.discount) }}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-0 h5">
              <span>Total Net:</span>
              <span class="fw-bold text-primary">{{ formatPrice(totals.net) }}</span>
            </div>
            
            <div v-if="requiresDerogation" class="alert alert-warning mt-3 mb-0 small">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Atenție!</strong> Această ofertă conține discount-uri > {{ config.approvalThreshold }}% și va necesita aprobarea Directorului de Vânzări.
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Items -->
      <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Produse</h6>
                <div class="d-flex gap-2">
                     <button class="btn btn-outline-secondary btn-sm" @click="recalculateAllPrices" :disabled="form.items.length === 0 || calculating" title="Recalculează prețurile conform sistemului (resetează modificările manuale)">
                        <i class="bi bi-arrow-clockwise" :class="{'spinner-border spinner-border-sm': calculating}"></i>
                        <span class="d-none d-md-inline ms-1">Recalculează Promoții</span>
                    </button>
                    <div style="width: 300px;">
                        <ProductSelector @select="addProduct" />
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Produs</th>
                                <th style="width: 100px;">Cant.</th>
                                <th style="width: 120px;">Preț Unitar</th>
                                <th style="width: 100px;">Discount %</th>
                                <th class="text-end pe-4">Total</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="form.items.length === 0">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    Adaugă produse folosind căutarea de mai sus.
                                </td>
                            </tr>
                            <tr v-for="(item, index) in form.items" :key="index">
                                <td class="ps-4">
                                    <div class="fw-bold">{{ item.product_name }}</div>
                                    <div class="small text-muted">{{ item.product_code }}</div>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" v-model.number="item.quantity" min="1" @input="calculateLine(item)">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" v-model.number="item.unit_price" step="0.01" @input="calculateLine(item)">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" v-model.number="item.discount_percent" min="0" :max="config.maxDiscount" @input="calculateLine(item)" :class="{'text-danger fw-bold': item.discount_percent > config.approvalThreshold}">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </td>
                                <td class="text-end pe-4 fw-bold">
                                    <div v-if="item.calculating" class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span v-else>{{ formatPrice(item.final_total) }}</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm text-danger" @click="removeItem(index)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Chat Section -->
        <div class="card shadow-sm border-0" v-if="isEdit">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Negociere / Discuție</h6>
            </div>
            <div class="card-body">
                 <div class="mb-3 p-3 bg-light rounded border" style="max-height: 400px; overflow-y: auto;" ref="messagesContainer">
                    <div v-if="messages.length === 0" class="text-center text-muted py-4 small">
                        Nu există mesaje.
                    </div>
                    <div v-for="msg in sortedMessages" :key="msg.id" class="mb-3 d-flex flex-column" :class="{'align-items-end': isMe(msg.user_id), 'align-items-start': !isMe(msg.user_id)}">
                        <div class="p-2 rounded shadow-sm" style="max-width: 80%;" :class="isMe(msg.user_id) ? 'bg-primary text-white' : 'bg-white border text-dark'">
                            <div class="small fw-bold mb-1" v-if="!isMe(msg.user_id)">{{ msg.user?.name }}</div>
                            <div class="text-break">{{ msg.message }}</div>
                        </div>
                         <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                            {{ formatDate(msg.created_at, true) }}
                            <span v-if="msg.is_internal" class="badge bg-secondary ms-1">Intern</span>
                        </div>
                    </div>
                </div>
                
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Scrie un mesaj..." v-model="newMessage" @keydown.enter="sendMessage">
                    <button class="btn btn-outline-secondary" type="button" @click="isInternalMessage = !isInternalMessage" :class="{'active': isInternalMessage, 'btn-warning': isInternalMessage}">
                        <i class="bi bi-eye-slash"></i> Intern
                    </button>
                    <button class="btn btn-primary" type="button" @click="sendMessage" :disabled="!newMessage.trim()">
                        <i class="bi bi-send"></i> Trimite
                    </button>
                </div>
                <div class="form-text small" v-if="isInternalMessage">
                    Mesajele interne sunt vizibile doar pentru staff.
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/store/auth';
import { useVisitStore } from '@/store/visit';
import { useTrackingStore } from '@/store/tracking';
import { useToast } from 'vue-toastification';
import api, { adminApi } from '@/services/http';
import CustomerSelector from '@/components/admin/CustomerSelector.vue';
import ProductSelector from '@/components/admin/ProductSelector.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const visitStore = useVisitStore();
const trackingStore = useTrackingStore();
const toast = useToast();

const isEdit = computed(() => !!route.params.id);
const saving = ref(false);
const customerSelectorRef = ref(null);
const currentStatus = ref('draft');
const messages = ref([]);
const newMessage = ref('');
const isInternalMessage = ref(false);
const messagesContainer = ref(null);
const calculating = ref(false);
const isCustomerLocked = ref(false);

// Settings
const config = reactive({
    approvalThreshold: 15,
    maxDiscount: 20
});

const loadConfig = async () => {
    try {
        const { data } = await api.get('/config');
        if (data.offer_discount_threshold_approval) {
            config.approvalThreshold = parseInt(data.offer_discount_threshold_approval);
        }
        if (data.offer_discount_max) {
            config.maxDiscount = parseInt(data.offer_discount_max);
        }
    } catch (e) {
        console.warn('Could not load public config', e);
    }
};

const canApprove = computed(() => ['admin', 'sales_director'].includes(authStore.role));
const isMe = (userId) => authStore.user?.id === userId;



const sortedMessages = computed(() => {
    return [...messages.value].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
});

const form = reactive({
    customer: null,
    customer_id: null,
    quote_request_id: null,
    valid_until: null,
    notes: '',
    items: []
});

const loadRequestData = async (requestId) => {
    try {
        const { data } = await adminApi.get(`/quotes/${requestId}`);
        if (data) {
            // Set customer
            if (data.customer) {
                selectCustomer(data.customer);
                if (customerSelectorRef.value) {
                    customerSelectorRef.value.searchQuery = data.customer.name;
                }
            }
            
            // Set items
            if (data.items && data.items.length > 0) {
                form.items = data.items.map(item => {
                    const product = item.product || {};
                    const unitPrice = parseFloat(item.list_price || product.list_price || product.price || 0);
                    const qty = parseFloat(item.quantity || 1);
                    
                    return {
                        product_id: item.product_id,
                        product_name: product.name || 'Produs necunoscut',
                        product_code: product.internal_code || '',
                        quantity: qty,
                        unit_price: unitPrice,
                        discount_percent: 0,
                        final_total: qty * unitPrice
                    };
                });
            }
            
            // Set quote request ID
            form.quote_request_id = parseInt(requestId);
            
            // Set notes if available
            if (data.internal_notes) {
                form.notes = `Referință Cerere Ofertă #${requestId}\n\n${data.internal_notes}`;
            } else {
                form.notes = `Referință Cerere Ofertă #${requestId}`;
            }
            
            toast.success('Datele din cererea de ofertă au fost încărcate.');
        }
    } catch (e) {
        console.error('Error loading request data:', e);
        toast.error('Nu am putut încărca datele din cererea de ofertă.');
    }
};

const totals = computed(() => {
    let gross = 0;
    let net = 0;
    
    form.items.forEach(item => {
        const qty = parseFloat(item.quantity) || 0;
        const price = parseFloat(item.unit_price) || 0;
        const discount = parseFloat(item.discount_percent) || 0;
        
        gross += qty * price;
        net += qty * price * (1 - discount / 100);
    });
    
    return {
        gross,
        net,
        discount: gross - net
    };
});

const requiresDerogation = computed(() => {
    return form.items.some(item => item.discount_percent > config.approvalThreshold);
});

const hasErrors = computed(() => {
    return form.items.some(item => item.discount_percent > config.maxDiscount);
});

const isValid = computed(() => {
    return form.customer_id && form.items.length > 0 && !hasErrors.value;
});

const selectCustomer = (customer) => {
    form.customer = customer;
    form.customer_id = customer ? customer.id : null;
};

const addProduct = async (product) => {
    // Check if already exists
    const existing = form.items.find(i => i.product_id === product.id);
    if (existing) {
        existing.quantity++;
        calculateLine(existing);
        toast.info('Cantitatea produsului a fost actualizată.');
        return;
    }

    const newItem = reactive({
        product_id: product.id,
        product_name: product.name,
        product_code: product.internal_code || product.sku,
        quantity: 1,
        unit_price: parseFloat(product.list_price || product.price || 0), // Base price
        discount_percent: 0,
        final_total: parseFloat(product.list_price || product.price || 0),
        calculating: false
    });
    
    form.items.push(newItem);
    
    // Calculate system price immediately
    await calculateItemSystemPrice(newItem);
};

const calculateItemSystemPrice = async (item) => {
    if (!form.customer_id) return;
    
    try {
        item.calculating = true;
        const payload = {
            customer_id: form.customer_id,
            items: [{
                product_id: item.product_id,
                quantity: item.quantity,
                price_override: null, 
                discount_override: null
            }]
        };
        
        // Use the account route which maps to Admin\QuickOrderController@calculate
        const { data } = await api.post('/account/quick-order/calculate', payload);
        if (data && data.items && data.items[0]) {
            const result = data.items[0];
            item.unit_price = result.unit_base_price;
            item.discount_percent = result.discount_percent;
            item.final_total = result.line_total;
        }
    } catch (e) {
        console.error("Pricing error", e);
    } finally {
        item.calculating = false;
    }
};

const recalculateAllPrices = async () => {
    if (!form.customer_id || form.items.length === 0) return;
    
    if (!confirm('Această acțiune va recalcula toate prețurile conform sistemului și va suprascrie modificările manuale. Continui?')) return;
    
    calculating.value = true;
    try {
        const payload = {
            customer_id: form.customer_id,
            items: form.items.map(i => ({
                product_id: i.product_id,
                quantity: i.quantity,
                price_override: null,
                discount_override: null
            }))
        };
        
        const { data } = await api.post('/account/quick-order/calculate', payload);
        
        data.items.forEach(resItem => {
             const item = form.items.find(i => i.product_id === resItem.product_id);
             if (item) {
                 item.unit_price = resItem.unit_base_price;
                 item.discount_percent = resItem.discount_percent;
                 item.final_total = resItem.line_total;
             }
        });
        
        toast.success('Prețurile au fost actualizate conform promoțiilor active.');
    } catch (e) {
        console.error("Bulk pricing error", e);
        toast.error('Eroare la recalcularea prețurilor.');
    } finally {
        calculating.value = false;
    }
};

const calculateLine = (item) => {
    const price = parseFloat(item.unit_price) || 0;
    const qty = parseInt(item.quantity) || 1;
    const discount = parseFloat(item.discount_percent) || 0;
    
    const total = price * qty * (1 - discount / 100);
    item.final_total = total;
};

const applyPromo = (item) => {
    if (item.promo_info) {
        item.discount_percent = item.promo_info.discount;
        calculateLine(item);
        toast.success('Promoție aplicată!');
    }
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const formatPrice = (val) => new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(val || 0);
const formatDate = (val, time = false) => {
    if (!val) return '';
    const d = new Date(val);
    return time ? d.toLocaleString('ro-RO') : d.toLocaleDateString('ro-RO');
};

const statusLabel = (s) => {
    const map = {
        'draft': 'Draft',
        'sent': 'Trimisă',
        'pending_approval': 'Așteptare Aprobare',
        'approved': 'Aprobată',
        'negotiation': 'Negociere',
        'rejected': 'Respinsă',
        'accepted': 'Acceptată',
        'completed': 'Finalizată'
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
        'completed': 'bg-success'
    };
    return map[s] || 'bg-secondary';
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    try {
        const { data } = await adminApi.post(`/offers/${route.params.id}/messages`, {
            message: newMessage.value,
            is_internal: isInternalMessage.value
        });
        messages.value.push(data);
        newMessage.value = '';
        scrollToBottom();
    } catch (e) {
        toast.error('Eroare la trimiterea mesajului.');
    }
};

const changeStatus = async (status) => {
    if (!confirm('Ești sigur că vrei să schimbi statusul?')) return;
    try {
        await adminApi.post(`/offers/${route.params.id}/status`, { status });
        toast.success('Status actualizat!');
        currentStatus.value = status;
        // Reload to get latest state if needed
    } catch (e) {
        toast.error('Eroare la actualizarea statusului.');
    }
};

const convertToOrder = async () => {
    if (!confirm('Ești sigur că vrei să transformi această ofertă în comandă?')) return;
    try {
        const { data } = await adminApi.post(`/offers/${route.params.id}/convert-to-order`);
        toast.success('Oferta a fost transformată în comandă cu succes!');
        
        if (data.order_id) {
            const routeName = authStore.role === 'admin' ? 'admin-order-details' : 'account-order-details';
            router.push({ name: routeName, params: { id: data.order_id } });
        } else {
            router.push({ name: getRouteName() }); // Back to list
        }
    } catch (e) {
        console.error(e);
        toast.error(e.response?.data?.message || 'Eroare la transformarea în comandă');
    }
};

const deleteOffer = async () => {
    if (!confirm('Ești sigur că vrei să ștergi această ofertă definitv?')) return;
    try {
        await adminApi.delete(`/offers/${route.params.id}`);
        toast.success('Oferta a fost ștearsă.');
        
        // Go back to list
        const routeName = authStore.role === 'admin' ? 'admin-offers' : 'account-offers-list';
        router.push({ name: routeName });
    } catch (e) {
        console.error(e);
        toast.error(e.response?.data?.message || 'Eroare la ștergerea ofertei');
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const getRouteName = (base) => {
    if (authStore.role === 'admin') return `admin-offers${base ? '-' + base : ''}`;
    return `account-offers${base ? '-' + base : '-list'}`;
};

const saveOffer = async () => {
    if (!isValid.value) return;
    
    saving.value = true;
    try {
        const payload = {
            customer_id: form.customer_id,
            customer_visit_id: visitStore.activeVisit?.id,
            quote_request_id: form.quote_request_id,
            valid_until: form.valid_until,
            notes: form.notes,
            items: form.items.map(i => ({
                product_id: i.product_id,
                quantity: i.quantity,
                unit_price: i.unit_price,
                discount_percent: i.discount_percent
            }))
        };

        if (isEdit.value) {
            await adminApi.put(`/offers/${route.params.id}`, payload);
            toast.success('Ofertă actualizată cu succes!');
        } else {
            await adminApi.post('/offers', payload);
            toast.success('Ofertă creată cu succes!');
        }
        
        router.push({ name: getRouteName() });
    } catch (e) {
        console.error(e);
        toast.error('Eroare la salvarea ofertei.');
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await loadConfig();

    // Check visit restriction for Agents/Directors creating NEW offers
    if (!isEdit.value && ['sales_agent', 'sales_director'].includes(authStore.role)) {
         // Check Shift Status first
         if (!trackingStore.isShiftActive) {
            await trackingStore.checkStatus();
            if (!trackingStore.isShiftActive) {
                 toast.error('Trebuie să începeți programul de lucru pentru a crea oferte!');
                 router.push({ name: 'agent-dashboard' });
                 return;
            }
         }

         if (!visitStore.activeVisit) {
             toast.error('Trebuie să aveți o vizită activă pentru a crea o ofertă!');
             router.push({ name: 'agent-dashboard' });
             return;
         } else {
             // Lock to visited customer
             selectCustomer(visitStore.activeVisit.customer);
             isCustomerLocked.value = true;
             
             nextTick(() => {
                 if (customerSelectorRef.value) {
                     customerSelectorRef.value.searchQuery = visitStore.activeVisit.customer.name;
                 }
             });
         }
    }

    if (isEdit.value) {
        // Load offer data
        try {
            const { data } = await adminApi.get(`/offers/${route.params.id}`);
            form.customer_id = data.customer_id;
            form.customer = data.customer;
            form.valid_until = data.valid_until;
            form.notes = data.notes;
            currentStatus.value = data.status;
            messages.value = data.messages || [];
            scrollToBottom();
            
            // Map items
            form.items = data.items.map(i => ({
                product_id: i.product_id,
                product_name: i.product.name,
                product_code: i.product.internal_code,
                quantity: i.quantity,
                unit_price: i.unit_price,
                discount_percent: i.discount_percent,
                final_total: i.final_price * i.quantity
            }));
            
            // Pre-fill customer selector search if needed (optional)
             if (customerSelectorRef.value) {
                 customerSelectorRef.value.searchQuery = data.customer.name;
             }
        } catch (e) {
            toast.error('Nu am putut încărca oferta.');
            router.push({ name: getRouteName() });
        }
    } else {
        if (route.query.customer_id) {
            try {
                const { data } = await adminApi.get(`/customers/${route.query.customer_id}`);
                selectCustomer(data);
            } catch (e) {
                console.error('Error fetching customer:', e);
            }
        }
        
        if (route.query.request_id) {
            await loadRequestData(route.query.request_id);
        }
    }
});
</script>
