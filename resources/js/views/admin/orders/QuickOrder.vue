<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1">Comandă Rapidă</h1>
        <p class="text-muted small mb-0">Creează rapid comenzi pentru clienți.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-primary" @click="submitOrder" :disabled="submitting || items.length === 0">
          <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
          Finalizează Comanda
        </button>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4">
      <li class="nav-item">
        <a class="nav-link" :class="{ active: currentTab === 'order' }" href="#" @click.prevent="currentTab = 'order'">
          <i class="bi bi-cart-plus me-2"></i>Produse & Comandă
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" :class="{ active: currentTab === 'client' }" href="#" @click.prevent="currentTab = 'client'">
          <i class="bi bi-person-lines-fill me-2"></i>Date Client
        </a>
      </li>
    </ul>

    <div class="row g-4">
      <!-- Left Column: Content based on Tab -->
      <div class="col-lg-8">
        
        <!-- Tab: Order (Products) -->
        <div v-if="currentTab === 'order'">
            <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Adaugă Produse</h6>
            </div>
            <div class="card-body">
                <div class="mb-4">
                <label class="form-label small fw-bold text-muted">Caută și selectează produse</label>
                <!-- Enhanced Product Selector with Filters -->
                <ProductSelector @select="addProduct" :enableFilters="true" />
                </div>

                <!-- Active Promotions Section -->
                <div v-if="customerPromotions.length > 0" class="card shadow-sm border-0 mb-4 bg-light">
                  <div class="card-header bg-transparent py-2 border-0">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-gift-fill me-2"></i>Promoții Active Disponibile</h6>
                  </div>
                  <div class="card-body pt-0">
                    <div v-for="promo in customerPromotions" :key="promo.id" class="mb-3 bg-white p-3 rounded border shadow-sm">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">{{ promo.name }}</div>
                                <div class="small text-muted">{{ promo.description }}</div>
                                <div class="badge bg-info text-dark mt-1" v-if="promo.bonus_type === 'free_item'">Produs Gratuit</div>
                                <div class="badge bg-success mt-1" v-else-if="promo.discount_percent > 0">-{{ promo.discount_percent }}%</div>
                            </div>
                        </div>
                        
                        <!-- Associated Products -->
                        <div v-if="promo.products && promo.products.length > 0" class="mt-2">
                            <div class="small fw-bold text-muted mb-1">Produse incluse:</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0 align-middle">
                                    <tbody>
                                        <tr v-for="prod in promo.products" :key="prod.id">
                                            <td>
                                                <div class="small fw-semibold">{{ prod.name }}</div>
                                                <div class="text-muted" style="font-size: 0.75rem">{{ prod.sku }}</div>
                                            </td>
                                            <td class="text-end">
                                                <div v-if="prod.promo_price < prod.base_price">
                                                    <span class="text-decoration-line-through text-muted small">{{ formatPrice(prod.base_price) }}</span>
                                                    <span class="text-success fw-bold ms-1">{{ formatPrice(prod.promo_price) }}</span>
                                                </div>
                                                <div v-else class="fw-bold">{{ formatPrice(prod.base_price) }}</div>
                                            </td>
                                            <td class="text-end" style="width: 50px">
                                                <button class="btn btn-sm btn-outline-primary py-0 px-2" @click="addPromoProduct(prod, promo)" title="Adaugă în comandă">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                  </div>
                </div>

                <div v-if="items.length > 0" class="table-responsive">
                <table class="table align-middle">
                    <thead class="bg-light">
                    <tr>
                        <th style="width: 35%">Produs</th>
                        <th style="width: 15%">Preț Unitar</th>
                        <th style="width: 15%">Cantitate</th>
                        <th style="width: 15%">Discount %</th>
                        <th style="width: 15%">Total</th>
                        <th style="width: 5%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(item, index) in items" :key="item.product_id">
                        <td>
                        <div class="fw-bold">{{ item.product_name }}</div>
                        <div class="small text-muted">{{ item.sku }}</div>
                        <div v-if="item.requires_approval" class="badge bg-warning text-dark mt-1">Necesită aprobare</div>
                        </td>
                        <td>
                        <!-- Price Override -->
                        <div v-if="canOverride">
                            <input type="number" class="form-control form-control-sm" 
                                v-model.number="item.price_override" 
                                placeholder="Preț"
                                @change="calculateTotals">
                            <small class="text-muted d-block" v-if="item.price_override">
                            (Original: {{ formatPrice(item.original_price) }})
                            </small>
                        </div>
                        <div v-else>
                            {{ formatPrice(item.unit_price) }}
                        </div>
                        </td>
                        <td>
                        <input type="number" class="form-control form-control-sm" 
                                v-model.number="item.quantity" 
                                min="1" 
                                @change="calculateTotals">
                        </td>
                        <td>
                        <!-- Discount Override -->
                        <div v-if="canOverride">
                            <input type="number" class="form-control form-control-sm" 
                                v-model.number="item.discount_override" 
                                placeholder="%"
                                min="0" max="100"
                                @change="calculateTotals">
                            <small class="text-success d-block" v-if="item.applied_discount > 0 && !item.discount_override">
                            Promo: {{ item.applied_discount }}%
                            </small>
                        </div>
                        <div v-else>
                            <span :class="{'text-success fw-bold': item.applied_discount > 0}">
                            {{ item.applied_discount }}%
                            </span>
                        </div>
                        </td>
                        <td class="fw-bold">
                        {{ formatPrice(item.line_total) }}
                        </td>
                        <td>
                        <button class="btn btn-sm btn-outline-danger border-0" @click="removeItem(index)">
                            <i class="bi bi-trash"></i>
                        </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
                </div>
                <div v-else class="text-center py-5 text-muted">
                <i class="bi bi-basket display-4 d-block mb-3"></i>
                Nu sunt produse selectate.
                </div>
            </div>
            </div>
        </div>

        <!-- Tab: Client Data -->
        <div v-if="currentTab === 'client'">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Detalii Client</h6>
                </div>
                <div class="card-body" v-if="selectedCustomer">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="text-muted small text-uppercase fw-bold mb-3">Informații Generale</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4 text-muted small">Nume</dt>
                                <dd class="col-sm-8 fw-bold">{{ selectedCustomer.name }}</dd>

                                <dt class="col-sm-4 text-muted small">CUI / CIF</dt>
                                <dd class="col-sm-8">{{ selectedCustomer.cif || '-' }}</dd>

                                <dt class="col-sm-4 text-muted small">Reg. Com.</dt>
                                <dd class="col-sm-8">{{ selectedCustomer.reg_com || '-' }}</dd>

                                <dt class="col-sm-4 text-muted small">Adresă</dt>
                                <dd class="col-sm-8">{{ selectedCustomer.address || '-' }}</dd>

                                <dt class="col-sm-4 text-muted small">Email</dt>
                                <dd class="col-sm-8">{{ selectedCustomer.email || '-' }}</dd>

                                <dt class="col-sm-4 text-muted small">Telefon</dt>
                                <dd class="col-sm-8">{{ selectedCustomer.phone || '-' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small text-uppercase fw-bold mb-3">Informații Financiare</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4 text-muted small">Sold Curent</dt>
                                <dd class="col-sm-8 fw-bold text-danger">{{ formatPrice(selectedCustomer.current_balance) }}</dd>

                                <dt class="col-sm-4 text-muted small">Limită Credit</dt>
                                <dd class="col-sm-8">{{ formatPrice(selectedCustomer.credit_limit) }}</dd>

                                <dt class="col-sm-4 text-muted small">Termen Plată</dt>
                                <dd class="col-sm-8">{{ selectedCustomer.payment_terms_days ? selectedCustomer.payment_terms_days + ' zile' : '-' }}</dd>

                                <dt class="col-sm-4 text-muted small">Bancă</dt>
                                <dd class="col-sm-8">{{ selectedCustomer.bank_name || '-' }}</dd>

                                <dt class="col-sm-4 text-muted small">IBAN</dt>
                                <dd class="col-sm-8">{{ selectedCustomer.iban || '-' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="card-body text-center py-5 text-muted" v-else>
                    <i class="bi bi-person-x display-4 d-block mb-3"></i>
                    Selectați un client pentru a vedea detaliile.
                </div>
            </div>
        </div>

      </div>

      <!-- Right Column: Customer & Summary -->
      <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Client Selectat</h6>
          </div>
          <div class="card-body">
            <div class="mb-4">
              <div v-if="selectedCustomer" class="d-flex align-items-center justify-content-between p-3 border rounded bg-light mb-2">
                 <div>
                    <div class="fw-bold">{{ selectedCustomer.name }}</div>
                    <div class="small text-muted">CUI: {{ selectedCustomer.cif }}</div>
                    <div class="small fw-bold mt-1 text-primary">Sold: {{ formatPrice(selectedCustomer.current_balance) }}</div>
                 </div>
                 <button v-if="canSelectCustomer" class="btn btn-sm btn-outline-danger" @click="selectedCustomer = null; items = []">
                    Schimbă
                 </button>
              </div>
              <CustomerSelector v-else-if="canSelectCustomer" @select="selectCustomer" :disabled="!canSelectCustomer" />
              <div v-else class="alert alert-info small">
                 Selectarea clientului este restricționată.
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Sumar Comandă</h6>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Subtotal:</span>
              <span class="fw-bold">{{ formatPrice(totals.subtotal) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2 text-success">
              <span class="text-muted">Discount Total:</span>
              <span>-{{ formatPrice(totals.discount_total) }}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-3 h5">
              <span>Total:</span>
              <span class="text-primary fw-bold">{{ formatPrice(totals.total) }}</span>
            </div>

            <div v-if="requiresApproval" class="alert alert-warning small">
              <i class="bi bi-info-circle me-2"></i> Această comandă necesită aprobarea directorului din cauza discounturilor aplicate.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Promotions Modal -->
    <div v-if="showPromotionsModal" class="modal d-block" style="background-color: rgba(0,0,0,0.5)" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Promoții Disponibile</h5>
            <button type="button" class="btn-close" @click="closePromotionsModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="loadingPromotions" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            <div v-else-if="availablePromotions.length === 0" class="text-center py-4">
              <p class="text-muted mb-0">Nu există promoții active disponibile pentru acest produs și client.</p>
            </div>
            <div v-else>
               <div class="alert alert-info small mb-3">
                 Preț de bază (fără promoții): <strong>{{ formatPrice(currentPromoBasePrice) }}</strong>
               </div>
               
               <div class="list-group">
                 <button v-for="promo in availablePromotions" :key="promo.id" 
                    class="list-group-item list-group-item-action"
                    @click="applySelectedPromotion(promo)">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                      <div>
                        <h6 class="mb-1 fw-bold text-primary">
                           <i class="bi bi-tag-fill me-2"></i>{{ promo.name }}
                        </h6>
                        <p class="mb-1 small">{{ promo.description || 'Fără descriere' }}</p>
                        <small class="text-muted">
                           Valabilă: {{ promo.start_at ? new Date(promo.start_at).toLocaleDateString() : 'Nedefinit' }} - 
                           {{ promo.end_at ? new Date(promo.end_at).toLocaleDateString() : 'Nedefinit' }}
                        </small>
                      </div>
                      <div class="text-end">
                        <div class="fw-bold text-success fs-5">
                            <span v-if="promo.bonus_type === 'discount_percent'">-{{ promo.discount_percent }}%</span>
                            <span v-else-if="promo.bonus_type === 'discount_value'">-{{ formatPrice(promo.discount_value) }}</span>
                            <span v-else>{{ formatPrice(promo.promo_price) }}</span>
                        </div>
                        <div class="small text-muted">
                           Preț final: {{ formatPrice(promo.promo_price) }}
                        </div>
                      </div>
                    </div>
                 </button>
               </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closePromotionsModal">Închide</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { adminApi } from '@/services/http';
import { useAuthStore } from '@/store/auth';
import { useVisitStore } from '@/store/visit';
import { useTrackingStore } from '@/store/tracking';
import { useToast } from 'vue-toastification';
import CustomerSelector from '@/components/admin/CustomerSelector.vue';
import ProductSelector from '@/components/admin/ProductSelector.vue';

const router = useRouter();
const authStore = useAuthStore();
const visitStore = useVisitStore();
const trackingStore = useTrackingStore();
const toast = useToast();

const selectedCustomer = ref(null);
const items = ref([]);
const totals = ref({
  subtotal: 0,
  discount_total: 0,
  total: 0
});
const requiresApproval = ref(false);
const submitting = ref(false);
const calculating = ref(false);
const isCustomerLocked = ref(false);
const currentTab = ref('order');

const canOverride = computed(() => {
  return ['admin', 'sales_director', 'sales_agent'].includes(authStore.role);
});

const canSelectCustomer = computed(() => {
  return ['admin', 'sales_director', 'sales_agent'].includes(authStore.role) && !isCustomerLocked.value;
});

const fetchCustomerDetails = async (customerId) => {
    try {
        const { data } = await adminApi.get(`/customers/${customerId}`);
        selectedCustomer.value = data;
        await fetchCustomerPromotions();
    } catch (e) {
        console.error("Failed to fetch full customer details", e);
    }
};

const fetchCustomerPromotions = async () => {
    if (!selectedCustomer.value) {
        customerPromotions.value = [];
        return;
    }
    
    try {
        const { data } = await adminApi.post('/quick-order/customer-promotions', {
            customer_id: selectedCustomer.value.id
        });
        customerPromotions.value = Array.isArray(data) ? data : [];
    } catch (e) {
        console.error("Failed to fetch customer promotions", e);
        customerPromotions.value = [];
    }
};

const addPromoProduct = (product, promo) => {
    const existing = items.value.find(i => i.product_id === product.id);
    
    if (existing) {
        existing.quantity++;
        // If it's a free item, force override
        if (promo.bonus_type === 'free_item') {
             existing.price_override = 0;
             existing.discount_override = 100;
        } else if (product.discount_percent > 0) {
             // If product has specific discount from promo
             existing.discount_override = product.discount_percent;
             existing.price_override = null;
        }
    } else {
        items.value.push({
          product_id: product.id,
          product_name: product.name,
          sku: product.sku,
          quantity: 1,
          unit_price: parseFloat(product.base_price || 0),
          original_price: parseFloat(product.base_price || 0),
          applied_discount: product.discount_percent,
          price_override: promo.bonus_type === 'free_item' ? 0 : null,
          discount_override: product.discount_percent > 0 ? product.discount_percent : null,
          line_total: parseFloat(product.promo_price || 0),
          requires_approval: false
        });
    }
    calculateTotals();
    toast.success('Produs adăugat din promoție');
};

onMounted(async () => {
    // Check for active visit restriction for Agents and Directors
    if (['sales_agent', 'sales_director'].includes(authStore.role)) {
        // Check Shift Status first
        if (!trackingStore.isShiftActive) {
            // Try to fetch status just in case
            await trackingStore.checkStatus();
            if (!trackingStore.isShiftActive) {
                 toast.error('Trebuie să începeți programul de lucru pentru a prelua comenzi!');
                 router.push({ name: 'agent-dashboard' });
                 return;
            }
        }

        if (!visitStore.activeVisit) {
             // Allow impersonation as fallback when no active visit
             if (authStore.impersonatedCustomer) {
                 await fetchCustomerDetails(authStore.impersonatedCustomer.id);
                 isCustomerLocked.value = true;
             } else {
                 toast.error('Trebuie să aveți o vizită activă pentru a crea o comandă rapidă!');
                 router.push({ name: 'agent-dashboard' }); // Redirect to dashboard to start visit
                 return;
             }
        } else {
             await fetchCustomerDetails(visitStore.activeVisit.customer.id);
             isCustomerLocked.value = true;
        }
    }
    
    // Legacy/Admin fallback logic
    if (!selectedCustomer.value && !canSelectCustomer.value && authStore.user && authStore.user.customer_id) {
         await fetchCustomerDetails(authStore.user.customer_id);
    }
});

const formatPrice = (value) => {
  return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(value || 0);
};

const selectCustomer = async (customer) => {
  await fetchCustomerDetails(customer.id);
  calculateTotals(); // Re-calculate prices for new customer
};

const addProduct = (product) => {
  // Check if already exists
  const existing = items.value.find(i => i.product_id === product.id);
  if (existing) {
    existing.quantity++;
  } else {
    items.value.push({
      product_id: product.id,
      product_name: product.name,
      sku: product.internal_code || product.sku,
      quantity: 1,
      unit_price: parseFloat(product.list_price || product.price || 0), // Temporary until calc
      original_price: parseFloat(product.list_price || product.price || 0),
      applied_discount: 0,
      price_override: null,
      discount_override: null,
      line_total: parseFloat(product.list_price || product.price || 0),
      requires_approval: false
    });
  }
  calculateTotals();
};

const removeItem = (index) => {
  items.value.splice(index, 1);
  calculateTotals();
};

// Promotions Modal Logic
const showPromotionsModal = ref(false);
const loadingPromotions = ref(false);
const availablePromotions = ref([]);
const currentPromoItem = ref(null);
const currentPromoBasePrice = ref(0);

const openPromotionsModal = async (item) => {
  if (!selectedCustomer.value) return;
  
  currentPromoItem.value = item;
  showPromotionsModal.value = true;
  loadingPromotions.value = true;
  availablePromotions.value = [];
  
  try {
    const { data } = await adminApi.post('/quick-order/available-promotions', {
      customer_id: selectedCustomer.value.id,
      product_id: item.product_id
    });
    
    availablePromotions.value = Array.isArray(data.promotions) ? data.promotions : [];
    currentPromoBasePrice.value = data.base_price;
  } catch (e) {
    console.error("Failed to load promotions", e);
    availablePromotions.value = [];
    toast.error("Nu s-au putut încărca promoțiile.");
  } finally {
    loadingPromotions.value = false;
  }
};

const closePromotionsModal = () => {
  showPromotionsModal.value = false;
  currentPromoItem.value = null;
  availablePromotions.value = [];
};

const applySelectedPromotion = (promotion) => {
  if (!currentPromoItem.value) return;
  
  // Apply discount logic
  // If it's a percentage discount, we set discount_override
  // If it's a value discount, we might need to calculate the equivalent percentage or set price_override?
  // QuickOrder supports price_override and discount_override.
  
  // Reset overrides first
  currentPromoItem.value.price_override = null;
  currentPromoItem.value.discount_override = null;
  
  if (promotion.bonus_type === 'discount_percent') {
    currentPromoItem.value.discount_override = parseFloat(promotion.discount_percent);
  } else if (promotion.bonus_type === 'discount_value') {
    // If it's a fixed value discount, we calculate the new price
    // But wait, the controller returned `promo_price` which is the final price.
    // So we can just override the price to that value.
    currentPromoItem.value.price_override = parseFloat(promotion.promo_price);
  } else if (promotion.bonus_type === 'fixed_price') {
      currentPromoItem.value.price_override = parseFloat(promotion.promo_price);
  } else {
      // Fallback: use calculated percent if available
      if (promotion.calculated_discount_percent > 0) {
          currentPromoItem.value.discount_override = parseFloat(promotion.calculated_discount_percent);
      }
  }
  
  calculateTotals();
  closePromotionsModal();
  toast.success(`Promoția "${promotion.name}" a fost aplicată.`);
};

const calculateTotals = async () => {
  if (!selectedCustomer.value || items.value.length === 0) {
      totals.value = { subtotal: 0, discount_total: 0, total: 0 };
      return;
  }
  
  calculating.value = true;
  try {
    const payload = {
      customer_id: selectedCustomer.value.id,
      customer_visit_id: visitStore.activeVisit?.id,
      items: items.value.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity,
        price_override: i.price_override,
        discount_override: i.discount_override
      }))
    };

    const { data } = await adminApi.post('/quick-order/calculate', payload);
    
    // Merge results back into items
    data.items.forEach((cItem, idx) => {
      // Find matching item in local state (assuming order might vary, but API preserves it usually)
      // Since API returns flat list based on input, index should match if we send all.
      if (items.value[idx] && items.value[idx].product_id === cItem.product_id) {
         items.value[idx].unit_price = cItem.unit_price; // This is base price (or overridden price)
         items.value[idx].applied_discount = cItem.discount_percent;
         items.value[idx].line_total = cItem.line_total;
         items.value[idx].applied_promotions = cItem.applied_promotions || [];
         items.value[idx].requires_approval = false; // We can check global flag or logic here
         
         // Store original price if we want to show it?
         // The API returns 'unit_price' which is the base used.
      }
    });

    totals.value = {
      subtotal: data.subtotal,
      discount_total: data.discount_total,
      total: data.total
    };
    requiresApproval.value = data.requires_approval;

  } catch (error) {
    console.error('Calculation error:', error);
    // Don't toast on every calc error, might be annoying while typing
  } finally {
    calculating.value = false;
  }
};

const submitOrder = async () => {
  if (!selectedCustomer.value) {
    toast.error('Selectează un client!');
    return;
  }
  if (items.value.length === 0) {
    toast.error('Adaugă cel puțin un produs!');
    return;
  }

  if (!confirm('Ești sigur că vrei să finalizezi comanda?')) return;

  submitting.value = true;
  try {
     const payload = {
      customer_id: selectedCustomer.value.id,
      customer_visit_id: visitStore.activeVisit?.id,
      items: items.value.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity,
        price_override: i.price_override,
        discount_override: i.discount_override
      }))
    };

    const { data } = await adminApi.post('/quick-order/create', payload);
    
    toast.success(data.message);
    
    // Redirect to order details
    if (data.order_id) {
       const routeName = authStore.role === 'admin' ? 'admin-order-details' : 'account-order-details';
       // Check if route exists, otherwise generic list
       router.push(`/admin/orders/${data.order_id}`);
    } else {
       router.push('/admin/orders');
    }

  } catch (error) {
    console.error(error);
    toast.error(error.response?.data?.message || 'Eroare la crearea comenzii.');
  } finally {
    submitting.value = false;
  }
};

// Debounce calculation
let debounceTimer;
watch(() => items.value, (newVal) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        calculateTotals();
    }, 500);
}, { deep: true });

</script>
