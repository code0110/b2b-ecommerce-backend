<template>
  <div v-if="show" class="modal-backdrop-custom" @click="close">
    <div class="modal-panel-custom" @click.stop>
      <div class="card border-0 shadow-lg h-100">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <div>
            <h5 class="modal-title fw-bold mb-0">
              Comanda #{{ orderDetails?.order?.order_number || orderDetails?.order?.id || '...' }}
            </h5>
            <div class="small text-muted" v-if="orderDetails?.order?.created_at">
              Plasată pe {{ formatDate(orderDetails.order.created_at) }}
            </div>
          </div>
          <button type="button" class="btn-close" @click="close"></button>
        </div>
        <div class="card-body overflow-auto custom-scrollbar bg-light">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Se încarcă...</span>
            </div>
          </div>
          <div v-else-if="error" class="alert alert-danger">
            {{ error }}
          </div>
          <div v-else-if="orderDetails">
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body">
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                  <div class="d-flex align-items-center gap-3">
                    <div>
                      <label class="small text-muted d-block text-uppercase fw-bold mb-1">Status Comandă</label>
                      <span class="badge rounded-pill border px-3 py-2" :class="getStatusBadgeClass(orderDetails.order.status)">
                        {{ getStatusLabel(orderDetails.order.status) }}
                      </span>
                    </div>
                    <div class="vr mx-2"></div>
                    <div>
                      <label class="small text-muted d-block text-uppercase fw-bold mb-1">Status Plată</label>
                      <span class="badge rounded-pill border px-3 py-2" :class="getPaymentStatusBadgeClass(orderDetails.order.payment_status)">
                        {{ getPaymentStatusLabel(orderDetails.order.payment_status) }}
                      </span>
                    </div>
                  </div>
                  <div class="d-flex gap-2">
                    <div class="dropdown">
                      <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-pencil-square me-1"></i> Modifică Status
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="dropdown-header">Schimbă status în:</li>
                        <li v-for="status in availableStatuses" :key="status.value">
                          <button 
                            class="dropdown-item d-flex align-items-center justify-content-between" 
                            @click="updateStatus(status.value)"
                            :class="{ 'active': orderDetails.order.status === status.value }"
                          >
                            {{ status.label }}
                            <i v-if="orderDetails.order.status === status.value" class="bi bi-check-lg"></i>
                          </button>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row g-4">
              <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                  <div class="card-header bg-white py-2">
                    <h6 class="mb-0 text-uppercase small fw-bold text-muted">Client</h6>
                  </div>
                  <div class="card-body">
                    <div v-if="orderDetails.order.customer" class="d-flex align-items-center mb-3">
                      <div class="avatar-circle me-3 bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px;">
                        {{ getInitials(orderDetails.order.customer.name) }}
                      </div>
                      <div>
                        <div class="fw-bold text-dark">{{ orderDetails.order.customer.name }}</div>
                        <div class="small text-muted">{{ orderDetails.order.customer.email }}</div>
                        <div class="small text-muted">{{ orderDetails.order.customer.phone }}</div>
                      </div>
                    </div>
                    <div v-else class="text-muted small">Client necunoscut</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                  <div class="card-header bg-white py-2">
                    <h6 class="mb-0 text-uppercase small fw-bold text-muted">Livrare & Facturare</h6>
                  </div>
                  <div class="card-body small">
                    <div class="row">
                      <div class="col-6">
                        <strong class="d-block mb-1">Adresă Livrare</strong>
                        <div v-if="orderDetails.shipping_address" class="text-muted">
                          {{ orderDetails.shipping_address.address }}<br>
                          {{ orderDetails.shipping_address.city }}, {{ orderDetails.shipping_address.county }}<br>
                          {{ orderDetails.shipping_address.postal_code }}
                        </div>
                        <span v-else class="text-muted fst-italic">Nespecificat</span>
                      </div>
                      <div class="col-6">
                        <strong class="d-block mb-1">Adresă Facturare</strong>
                        <div v-if="orderDetails.billing_address" class="text-muted">
                          {{ orderDetails.billing_address.address }}<br>
                          {{ orderDetails.billing_address.city }}, {{ orderDetails.billing_address.county }}<br>
                          {{ orderDetails.billing_address.postal_code }}
                        </div>
                        <span v-else class="text-muted fst-italic">Aceeași cu livrarea</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="card border-0 shadow-sm">
                  <div class="card-header bg-white py-2">
                    <h6 class="mb-0 text-uppercase small fw-bold text-muted">Produse Comandate</h6>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                      <thead class="bg-light">
                        <tr>
                          <th class="ps-3 py-2 text-muted small border-0">Produs</th>
                          <th class="py-2 text-muted small border-0 text-center">Cant.</th>
                          <th class="py-2 text-muted small border-0 text-end">Preț Unit.</th>
                          <th class="pe-3 py-2 text-muted small border-0 text-end">Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="item in orderDetails.items" :key="item.id">
                          <td class="ps-3">
                            <div class="fw-medium text-dark">{{ item.product_name }}</div>
                            <div class="small text-muted">SKU: {{ item.sku }}</div>
                          </td>
                          <td class="text-center">{{ item.quantity }}</td>
                          <td class="text-end">{{ formatPrice(item.unit_price) }}</td>
                          <td class="pe-3 text-end fw-bold">{{ formatPrice(item.total) }}</td>
                        </tr>
                      </tbody>
                      <tfoot class="bg-light">
                        <tr>
                          <td colspan="3" class="text-end py-3 fw-bold text-muted">Total General:</td>
                          <td class="pe-3 text-end py-3 fw-bold fs-6 text-primary">
                            {{ formatPrice(orderDetails.order.grand_total || orderDetails.order.total_amount) }}
                          </td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-light" @click="close">Închide</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { adminApi } from '@/services/http'
import { useToast } from 'vue-toastification'

const props = defineProps({
  show: Boolean,
  orderId: [Number, String]
})

const emit = defineEmits(['close', 'update'])
const toast = useToast()

const loading = ref(false)
const error = ref(null)
const orderDetails = ref(null)

const availableStatuses = [
  { value: 'pending', label: 'În așteptare' },
  { value: 'processing', label: 'În procesare' },
  { value: 'awaiting_payment', label: 'Așteaptă plată' },
  { value: 'on_hold', label: 'On hold' },
  { value: 'completed', label: 'Finalizată' },
  { value: 'cancelled', label: 'Anulată' }
]

const loadDetails = async () => {
  if (!props.orderId) return
  
  loading.value = true
  error.value = null
  orderDetails.value = null

  try {
    const response = await adminApi.get(`/orders/${props.orderId}`)
    orderDetails.value = response.data
  } catch (e) {
    error.value = 'Nu s-au putut încărca detaliile comenzii.'
  } finally {
    loading.value = false
  }
}

const updateStatus = async (newStatus) => {
  if (!props.orderId) return
  
  try {
    await adminApi.post(`/orders/${props.orderId}/status`, { status: newStatus })
    if (orderDetails.value && orderDetails.value.order) {
      orderDetails.value.order.status = newStatus
    }
    toast.success('Status actualizat cu succes!')
    emit('update')
  } catch (e) {
    toast.error('Eroare la actualizarea statusului.')
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    loadDetails()
  }
})

const close = () => {
  emit('close')
}

const formatPrice = (value) => {
  return new Intl.NumberFormat('ro-RO', {
    style: 'currency',
    currency: 'RON'
  }).format(value || 0)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ro-RO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getInitials = (name) => {
  if (!name) return '?'
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getStatusBadgeClass = (status) => {
  switch(status) {
    case 'pending': return 'bg-warning bg-opacity-10 text-warning border-warning border-opacity-25'
    case 'processing': return 'bg-info bg-opacity-10 text-info border-info border-opacity-25'
    case 'awaiting_payment': return 'bg-primary bg-opacity-10 text-primary border-primary border-opacity-25'
    case 'on_hold': return 'bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25'
    case 'completed': return 'bg-success bg-opacity-10 text-success border-success border-opacity-25'
    case 'cancelled': return 'bg-danger bg-opacity-10 text-danger border-danger border-opacity-25'
    default: return 'bg-light text-dark border-secondary border-opacity-25'
  }
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'În așteptare',
    processing: 'În procesare',
    awaiting_payment: 'Așteaptă plată',
    on_hold: 'On hold',
    completed: 'Finalizată',
    cancelled: 'Anulată'
  }
  return labels[status] || status
}

const getPaymentStatusBadgeClass = (status) => {
  switch(status) {
    case 'paid': return 'bg-success bg-opacity-10 text-success border-success border-opacity-25'
    case 'pending': return 'bg-warning bg-opacity-10 text-warning border-warning border-opacity-25'
    case 'failed': return 'bg-danger bg-opacity-10 text-danger border-danger border-opacity-25'
    case 'partially_paid': return 'bg-info bg-opacity-10 text-info border-info border-opacity-25'
    default: return 'bg-light text-dark border-secondary border-opacity-25'
  }
}

const getPaymentStatusLabel = (status) => {
  const labels = {
    paid: 'Plătită',
    pending: 'În așteptare',
    failed: 'Eșuată',
    refunded: 'Rambursată',
    partially_paid: 'Parțial plătită'
  }
  return labels[status] || status
}
</script>

<style scoped>
.modal-backdrop-custom {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1050;
  backdrop-filter: blur(4px);
}

.modal-panel-custom {
  background: white;
  width: 900px;
  max-width: 95vw;
  max-height: 90vh;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  animation: slideIn 0.3s ease-out;
}

.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #dee2e6 #f8f9fa;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f8f9fa;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #dee2e6;
  border-radius: 3px;
}

@keyframes slideIn {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.avatar-circle {
  font-size: 1rem;
}
</style>
