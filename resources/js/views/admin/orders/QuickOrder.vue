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

    <div class="row g-4">
      <!-- Left Column: Items -->
      <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Produse</h6>
          </div>
          <div class="card-body">
            <div class="mb-4">
              <label class="form-label small fw-bold text-muted">Adaugă Produs</label>
              <ProductSelector @select="addProduct" />
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

      <!-- Right Column: Customer & Summary -->
      <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Client</h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <CustomerSelector v-if="canSelectCustomer" @select="selectCustomer" ref="customerSelectorRef" />
              <div v-if="selectedCustomer" class="mt-3 p-3 bg-light rounded border">
                <div class="fw-bold mb-1">{{ selectedCustomer.name }}</div>
                <div class="small text-muted mb-1"><i class="bi bi-upc-scan me-2"></i>{{ selectedCustomer.cif }}</div>
                <div class="small text-muted"><i class="bi bi-geo-alt me-2"></i>{{ selectedCustomer.address }}</div>
                <div class="small text-muted mt-2" v-if="selectedCustomer.group">
                  <span class="badge bg-secondary">{{ selectedCustomer.group.name }}</span>
                </div>
              </div>
              <div v-else class="alert alert-warning mt-3 small">
                <i class="bi bi-exclamation-triangle me-2"></i> Selectează un client pentru a vedea prețurile corecte.
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Totaluri</h6>
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
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/http';
import { useAuthStore } from '@/store/auth';
import { useToast } from 'vue-toastification';
import CustomerSelector from '@/components/admin/CustomerSelector.vue';
import ProductSelector from '@/components/admin/ProductSelector.vue';

const router = useRouter();
const authStore = useAuthStore();
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

const canOverride = computed(() => {
  return ['admin', 'sales_director', 'sales_agent'].includes(authStore.role);
});

const canSelectCustomer = computed(() => {
  return ['admin', 'sales_director', 'sales_agent'].includes(authStore.role);
});

onMounted(async () => {
    if (!canSelectCustomer.value && authStore.user && authStore.user.customer_id) {
        if (authStore.user.customer) {
            selectedCustomer.value = authStore.user.customer;
        } else {
             try {
                 const { data } = await api.get(`/customers/${authStore.user.customer_id}`);
                 selectedCustomer.value = data;
             } catch(e) {
                 console.error("Failed to load customer", e);
             }
        }
    }
});

const formatPrice = (value) => {
  return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(value);
};

const selectCustomer = (customer) => {
  selectedCustomer.value = customer;
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

const calculateTotals = async () => {
  if (!selectedCustomer.value || items.value.length === 0) {
      totals.value = { subtotal: 0, discount_total: 0, total: 0 };
      return;
  }
  
  calculating.value = true;
  try {
    const payload = {
      customer_id: selectedCustomer.value.id,
      items: items.value.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity,
        price_override: i.price_override,
        discount_override: i.discount_override
      }))
    };

    const { data } = await api.post('/quick-order/calculate', payload);
    
    // Merge results back into items
    data.items.forEach((cItem, idx) => {
      // Find matching item in local state (assuming order might vary, but API preserves it usually)
      // Since API returns flat list based on input, index should match if we send all.
      if (items.value[idx] && items.value[idx].product_id === cItem.product_id) {
         items.value[idx].unit_price = cItem.unit_price; // This is base price (or overridden price)
         items.value[idx].applied_discount = cItem.discount_percent;
         items.value[idx].line_total = cItem.line_total;
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
      items: items.value.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity,
        price_override: i.price_override,
        discount_override: i.discount_override
      }))
    };

    const { data } = await api.post('/quick-order/create', payload);
    
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
