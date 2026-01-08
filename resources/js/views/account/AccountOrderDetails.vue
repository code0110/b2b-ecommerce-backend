<template>
  <div class="container py-4">
    <div class="mb-3 d-flex justify-content-between align-items-center">
      <button
        type="button"
        class="btn btn-link text-decoration-none ps-0"
        @click="goBack"
      >
        ← Înapoi la listă
      </button>
      <div class="small text-muted" v-if="frontCustomerName">
        Client: <strong>{{ frontCustomerName }}</strong>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
      <p class="mt-2 text-muted">Se încarcă detaliile comenzii...</p>
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-else-if="!order" class="alert alert-warning">
      Comanda nu a fost găsită.
    </div>

    <div v-else class="row g-3">
      <!-- Col stânga: detalii comandă -->
      <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">Comanda {{ order.order_number }}</h4>
              <div class="text-muted small">
                Plasată în data de {{ formatDate(order.created_at) }}
              </div>
            </div>
            <div class="text-end small">
              <div class="mb-1">
                <span
                  class="badge"
                  :class="{
                    'bg-warning text-dark': order.status === 'pending' || order.status === 'processing',
                    'bg-info text-dark': order.status === 'shipping' || order.status === 'in_livrare',
                    'bg-success': order.status === 'completed' || order.status === 'livrata',
                    'bg-secondary': order.status === 'cancelled' || order.status === 'anulata'
                  }"
                >
                  {{ formatStatus(order.status) }}
                </span>
              </div>
              <div>
                Status plată:
                <span
                  class="badge"
                  :class="{
                    'bg-danger': order.payment_status === 'unpaid' || order.payment_status === 'neplatita',
                    'bg-warning text-dark': order.payment_status === 'pending' || order.payment_status === 'in_asteptare',
                    'bg-success': order.payment_status === 'paid' || order.payment_status === 'platita',
                    'bg-info text-dark': order.payment_status === 'refunded'
                  }"
                >
                  {{ formatPaymentStatus(order.payment_status) }}
                </span>
              </div>
            </div>
          </div>
          
          <div class="card-body small">
            <h6 class="text-uppercase text-muted mb-2">Produse comandate</h6>
            <div class="vstack gap-2">
              <div
                v-for="item in order.items"
                :key="item.id"
                class="border rounded p-2"
              >
                <div class="d-flex justify-content-between align-items-start">
                  <div class="me-3">
                    <div class="fw-semibold">{{ item.product_name || item.product?.name }}</div>
                    <div class="text-muted small">
                      Cod: {{ item.sku || item.product?.sku }}
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="small text-muted">Cantitate</div>
                    <div class="fw-semibold">{{ item.quantity }}</div>
                  </div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                  <div class="small text-muted">Preț unit. (RON)</div>
                  <div class="fw-semibold">{{ formatMoney(item.unit_price) }}</div>
                </div>
                <div class="d-flex justify-content-between">
                  <div class="small text-muted">Total linie (RON)</div>
                  <div class="fw-bold">{{ formatMoney(item.total) }}</div>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white">
            <div class="row">
              <div class="col-md-6">
                <!-- Notes or other info -->
              </div>
              <div class="col-md-6">
                <div class="d-flex justify-content-between mb-1">
                  <span>Subtotal:</span>
                  <span>{{ formatMoney(order.subtotal) }} RON</span>
                </div>
                <div class="d-flex justify-content-between mb-1 text-danger" v-if="order.discount_total > 0">
                  <span>Reducere:</span>
                  <span>-{{ formatMoney(order.discount_total) }} RON</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>Transport:</span>
                  <span>{{ formatMoney(order.shipping_total) }} RON</span>
                </div>
                <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2 fs-6">
                  <span>Total General:</span>
                  <span>{{ formatMoney(order.grand_total) }} RON</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Adrese -->
        <div class="row g-3">
          <div class="col-md-6">
            <div class="card shadow-sm h-100">
              <div class="card-header bg-light py-2">
                <small class="fw-bold text-uppercase">Adresă de Livrare</small>
              </div>
              <div class="card-body small">
                <div v-if="order.shipping_address">
                  <strong>{{ order.shipping_address.contact_name }}</strong><br>
                  {{ order.shipping_address.address_line1 }}<br>
                  <span v-if="order.shipping_address.address_line2">{{ order.shipping_address.address_line2 }}<br></span>
                  {{ order.shipping_address.city }}, {{ order.shipping_address.county }}<br>
                  {{ order.shipping_address.postal_code }}<br>
                  <div v-if="order.shipping_address.phone" class="mt-2">
                    <i class="bi bi-telephone me-1"></i> {{ order.shipping_address.phone }}
                  </div>
                </div>
                <div v-else class="text-muted fst-italic">
                  Nu există adresă de livrare specificată.
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card shadow-sm h-100">
              <div class="card-header bg-light py-2">
                <small class="fw-bold text-uppercase">Adresă de Facturare</small>
              </div>
              <div class="card-body small">
                <div v-if="order.billing_address">
                   <strong>{{ order.billing_address.contact_name || order.customer?.name }}</strong><br>
                   {{ order.billing_address.address_line1 }}<br>
                   <span v-if="order.billing_address.address_line2">{{ order.billing_address.address_line2 }}<br></span>
                   {{ order.billing_address.city }}, {{ order.billing_address.county }}<br>
                   {{ order.billing_address.postal_code }}<br>
                   <div v-if="order.billing_address.cif" class="mt-2">
                     CIF: {{ order.billing_address.cif }}
                   </div>
                   <div v-if="order.billing_address.reg_com">
                     Reg. Com: {{ order.billing_address.reg_com }}
                   </div>
                </div>
                <div v-else class="text-muted fst-italic">
                   Aceeași cu adresa de livrare sau nespecificată.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Col dreapta: info extra / actiuni -->
      <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <h6 class="card-title">Sumar client</h6>
            <div v-if="order.customer">
              <div class="mb-2">
                <strong>{{ order.customer.name }}</strong>
              </div>
              <div class="small text-muted mb-2">
                {{ order.customer.email }}<br>
                {{ order.customer.phone }}
              </div>
              <div class="small" v-if="order.customer.cif">
                CIF: {{ order.customer.cif }}
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body">
            <h6 class="card-title">Acțiuni rapide</h6>
            <div class="d-grid gap-2">
              <button class="btn btn-outline-primary btn-sm" @click="downloadInvoice">
                <i class="bi bi-file-earmark-pdf me-2"></i> Descarcă Factura
              </button>
              <button class="btn btn-outline-secondary btn-sm" @click="repeatOrder">
                <i class="bi bi-arrow-repeat me-2"></i> Comandă din nou
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { fetchOrder } from '@/services/account/orders'
import { adminApi } from '@/services/http'
import api from '@/services/http'
import { fetchInvoices } from '@/services/account/documents'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const order = ref(null)
const loading = ref(true)
const error = ref('')
const processing = ref(false)

const frontCustomerName = computed(() => {
  if (order.value?.customer?.name) return order.value.customer.name
  if (authStore.user?.name) return authStore.user.name
  return 'Client'
})

const isDirector = computed(() => {
  return authStore.user?.role === 'sales_director'
})

const loadOrder = async () => {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchOrder(route.params.id)
    order.value = data
  } catch (e) {
    console.error('Failed to load order:', e)
    error.value = 'Nu s-a putut încărca comanda. ' + (e.response?.data?.message || '')
  } finally {
    loading.value = false
  }
}

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('ro-RO', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const formatStatus = (status) => {
  const map = {
    'pending': 'În așteptare',
    'processing': 'În procesare',
    'shipping': 'În livrare',
    'completed': 'Finalizată',
    'cancelled': 'Anulată',
    'on_hold': 'În așteptare',
    'awaiting_payment': 'Așteaptă plata'
  }
  return map[status] || status
}

const formatPaymentStatus = (status) => {
  const map = {
    'paid': 'Plătită',
    'unpaid': 'Neplătită',
    'pending': 'În așteptare',
    'refunded': 'Rambursată',
    'failed': 'Eșuată'
  }
  return map[status] || status
}

const approveOrder = async () => {
  if (!confirm('Sunteți sigur că doriți să aprobați această derogare?')) return
  
  processing.value = true
  try {
    await adminApi.post(`/orders/${order.value.id}/approve`)
    // Reload order to update status
    await loadOrder()
    alert('Comanda a fost aprobată cu succes.')
  } catch (e) {
    alert('Eroare la aprobare: ' + (e.response?.data?.message || e.message))
  } finally {
    processing.value = false
  }
}

const rejectOrder = async () => {
  if (!confirm('Sunteți sigur că doriți să respingeți această derogare? Comanda va fi anulată.')) return
  
  processing.value = true
  try {
    await adminApi.post(`/orders/${order.value.id}/reject`)
    await loadOrder()
    alert('Comanda a fost respinsă.')
  } catch (e) {
    alert('Eroare la respingere: ' + (e.response?.data?.message || e.message))
  } finally {
    processing.value = false
  }
}

const goBack = () => {
  router.push('/account/orders')
}

const repeatOrder = async () => {
  if (!order.value) return
  processing.value = true
  try {
    const items = (order.value.items || []).map(i => ({
      product_id: i.product_id || i.product?.id,
      quantity: i.quantity
    })).filter(i => i.product_id && i.quantity > 0)

    if (!items.length) {
      alert('Nu există articole valide pentru a recrea coșul.')
      return
    }

    await api.post('/quick-order/add-to-cart', { items })
    router.push('/checkout')
  } catch (e) {
    alert('Eroare la recrearea coșului: ' + (e.response?.data?.message || e.message))
  } finally {
    processing.value = false
  }
}

const downloadInvoice = async () => {
  if (!order.value) return
  processing.value = true
  try {
    const resp = await fetchInvoices()
    const list = Array.isArray(resp) ? resp : (resp?.data || [])
    const inv = list.find(x => x.order_id === order.value.id)

    if (!inv) {
      alert('Nu am găsit nicio factură asociată acestei comenzi.')
      return
    }

    if (inv.pdf_url) {
      window.open(inv.pdf_url, '_blank')
    } else {
      router.push('/account/invoices')
    }
  } catch (e) {
    alert('Eroare la descărcarea facturii: ' + (e.response?.data?.message || e.message))
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  loadOrder()
})
</script>
