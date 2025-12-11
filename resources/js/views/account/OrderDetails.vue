<template>
  <div class="container" v-if="order">
    <PageHeader
      title="Detaliu comandă"
      :subtitle="'Comanda ' + order.number"
    >
      <RouterLink :to="{ name: 'account-orders' }" class="btn btn-outline-secondary btn-sm">
        Înapoi la comenzi
      </RouterLink>
    </PageHeader>

    <!-- Rezumat comandă -->
    <div class="card shadow-sm mb-3">
      <div class="card-body small">
        <div class="row">
          <div class="col-md-3 mb-2">
            <div class="text-muted">Nr. comandă</div>
            <div class="fw-semibold">{{ order.number }}</div>
            <div class="text-muted mt-2">Tip</div>
            <span :class="['badge', order.isB2B ? 'bg-secondary' : 'bg-light text-dark']">
              {{ order.isB2B ? 'B2B' : 'B2C' }}
            </span>
          </div>
          <div class="col-md-3 mb-2">
            <div class="text-muted">Dată comandă</div>
            <div class="fw-semibold">{{ formatDate(order.createdAt) }}</div>
            <div class="text-muted mt-2">Status comandă</div>
            <span :class="['badge', statusBadgeClass(order.status)]">
              {{ statusLabel(order.status) }}
            </span>
          </div>
          <div class="col-md-3 mb-2">
            <div class="text-muted">Valoare totală</div>
            <div class="fw-semibold">
              {{ order.totalGross.toLocaleString('ro-RO') }} {{ order.currency }}
            </div>
            <div class="text-muted mt-2">Status plată</div>
            <span :class="['badge', paymentBadgeClass(order.paymentStatus)]">
              {{ paymentStatusLabel(order.paymentStatus) }}
            </span>
          </div>
          <div class="col-md-3 mb-2">
            <div class="text-muted">Metodă plată</div>
            <div class="fw-semibold text-capitalize">{{ order.paymentMethod }}</div>
            <div class="text-muted mt-2">Metodă livrare</div>
            <div class="fw-semibold">{{ order.shippingMethod }}</div>
          </div>
        </div>

        <div v-if="order.isB2B" class="row mt-3">
          <div class="col-md-6">
            <div class="alert alert-info py-2 mb-0">
              <div class="small mb-0">
                <strong>B2B:</strong>
                Comanda este înregistrată pe client cu termen de plată contractual.
                <span v-if="order.dueDate">
                  Scadență estimată: <strong>{{ order.dueDate }}</strong>.
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-6" v-if="order.creditUsed">
            <div class="alert alert-warning py-2 mb-0">
              <div class="small mb-0">
                O parte din valoarea comenzii utilizează limita de credit a clientului.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <!-- Produse & RMA -->
      <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Produse comandate</strong>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Produs</th>
                    <th>Cod</th>
                    <th class="text-end">Cantitate</th>
                    <th class="text-end">Preț unitar</th>
                    <th class="text-end">Total linie</th>
                    <th>Retur (RMA)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="line in order.lines" :key="line.lineId">
                    <td class="small">
                      <div class="fw-semibold">{{ line.productName }}</div>
                    </td>
                    <td class="small">
                      {{ line.productCode }}
                    </td>
                    <td class="text-end small">
                      {{ line.qty }} {{ line.unit }}
                    </td>
                    <td class="text-end small">
                      {{ line.unitPrice.toLocaleString('ro-RO') }} {{ order.currency }}
                    </td>
                    <td class="text-end small">
                      {{ line.lineTotal.toLocaleString('ro-RO') }} {{ order.currency }}
                    </td>
                    <td class="small">
                      <div v-if="!line.canReturn" class="text-muted small">
                        Neeligibil pentru retur.
                      </div>

                      <div v-else>
                        <div v-if="line.rmaStatus === 'none'">
                          <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="toggleRmaForm(line.lineId)"
                          >
                            Inițiază retur
                          </button>
                        </div>
                        <div v-else>
                          <span :class="['badge', rmaBadgeClass(line.rmaStatus)]">
                            {{ rmaStatusLabel(line.rmaStatus) }}
                          </span>
                        </div>

                        <div
                          v-if="rmaForms[line.lineId]?.open"
                          class="border rounded mt-2 p-2 bg-light"
                        >
                          <div class="mb-1">
                            <label class="form-label text-muted small mb-0">
                              Cantitate de returnat
                            </label>
                            <input
                              v-model.number="rmaForms[line.lineId].qty"
                              type="number"
                              min="1"
                              :max="line.qty"
                              class="form-control form-control-sm"
                            />
                          </div>
                          <div class="mb-1">
                            <label class="form-label text-muted small mb-0">
                              Motiv retur
                            </label>
                            <textarea
                              v-model="rmaForms[line.lineId].reason"
                              class="form-control form-control-sm"
                              rows="2"
                            />
                          </div>
                          <div class="mb-2">
                            <label class="form-label text-muted small mb-0">
                              Tip retur
                            </label>
                            <select
                              v-model="rmaForms[line.lineId].type"
                              class="form-select form-select-sm"
                            >
                              <option value="return">Retur marfă</option>
                              <option value="replacement">Înlocuire produse</option>
                              <option value="other">Alt tip</option>
                            </select>
                          </div>
                          <div class="d-flex justify-content-end gap-2">
                            <button
                              type="button"
                              class="btn btn-outline-secondary btn-sm"
                              @click="toggleRmaForm(line.lineId)"
                            >
                              Anulează
                            </button>
                            <button
                              type="button"
                              class="btn btn-primary btn-sm"
                              @click="submitRma(line)"
                            >
                              Trimite solicitare
                            </button>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="rmaInfo" class="border-top small text-muted p-2">
              {{ rmaInfo }}
            </div>
          </div>
        </div>
      </div>

      <!-- Adrese, plată, acțiuni -->
      <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Adrese & livrare</strong>
          </div>
          <div class="card-body small">
            <div class="mb-2">
              <div class="text-muted">Adresă facturare</div>
              <pre class="mb-0">{{ order.billingAddress }}</pre>
            </div>
            <div>
              <div class="text-muted">Adresă livrare</div>
              <pre class="mb-0">{{ order.shippingAddress }}</pre>
            </div>
          </div>
        </div>

        <div class="card shadow-sm mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Livrare & AWB</strong>
            <span v-if="shipmentsForOrder.length" class="small text-muted">
              {{ shipmentsForOrder[0].courier }}
            </span>
          </div>
          <div class="card-body small">
            <div v-if="shipmentsForOrder.length">
              <div
                v-for="shipment in shipmentsForOrder"
                :key="shipment.id"
                class="mb-2 pb-2"
                :class="{
                  'border-bottom': shipmentsForOrder.length > 1,
                  'mb-0 pb-0 border-0': shipmentsForOrder.length === 1
                }"
              >
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="text-muted">AWB</div>
                    <div class="fw-semibold">
                      {{ shipment.awbNumber || 'N/A' }}
                    </div>
                    <div class="text-muted small mt-1">
                      Status:
                      <span :class="['badge', shipmentBadgeClass(shipment.status)]">
                        {{ shipmentStatusLabel(shipment.status) }}
                      </span>
                    </div>
                  </div>
                  <div class="text-end small">
                    <div v-if="shipment.createdAt">
                      Creat: {{ formatDateTime(shipment.createdAt) }}
                    </div>
                    <div v-if="shipment.lastUpdate">
                      Ultima actualizare: {{ formatDateTime(shipment.lastUpdate) }}
                    </div>
                    <div v-if="shipment.estimatedDeliveryDate">
                      Estimare livrare: {{ formatDate(shipment.estimatedDeliveryDate) }}
                    </div>
                  </div>
                </div>
                <div class="mt-2 d-flex justify-content-between align-items-center">
                  <div v-if="shipment.trackingUrl">
                    <a
                      :href="shipment.trackingUrl"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-decoration-none small"
                    >
                      Deschide tracking curier
                    </a>
                  </div>
                  <div v-else class="text-muted small">
                    Link de tracking indisponibil în demo.
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-muted small">
              AWB-ul va fi generat după procesarea comenzii. În acest demo nu există încă o
              expediere asociată.
            </div>
          </div>
        </div>

        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Plată</strong>
          </div>
          <div class="card-body small">
            <p class="mb-1">
              Metodă plată:
              <strong class="text-capitalize">{{ order.paymentMethod }}</strong>
            </p>
            <p class="mb-1">
              Status plată:
              <span :class="['badge', paymentBadgeClass(order.paymentStatus)]">
                {{ paymentStatusLabel(order.paymentStatus) }}
              </span>
            </p>
            <p v-if="order.isB2B && order.dueDate" class="mb-1">
              Scadență: <strong>{{ order.dueDate }}</strong>
            </p>
            <p class="text-muted mb-0">
              În implementarea reală, aici poți afișa și butonul "Plătește acum" pentru facturi / proforme
              neachitate (card sau OP).
            </p>
          </div>
        </div>

        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Acțiuni comandă</strong>
          </div>
          <div class="card-body small">
            <div class="d-grid gap-2">
              <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                @click="onReorder(order)"
              >
                Comandă din nou
              </button>
              <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                Descarcă proforma
              </button>
              <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                Descarcă factura
              </button>
            </div>
            <p class="text-muted small mb-0 mt-2">
              Butoanele de descărcare vor fi legate de fișierele generate de ERP (PDF).
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="container">
    <PageHeader title="Detaliu comandă" />
    <div class="card shadow-sm">
      <div class="card-body text-center text-muted">
        Comanda nu a fost găsită.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { useOrdersStore } from '@/store/orders'
import { useShipmentsStore } from '@/store/shipments'

const route = useRoute()
const router = useRouter()
const ordersStore = useOrdersStore()
const shipmentsStore = useShipmentsStore()

const order = computed(() => ordersStore.getById(route.params.id))
const rmaForms = reactive({})
const rmaInfo = ref('')

const shipmentsForOrder = computed(() => {
  if (!order.value) {
    return []
  }
  return shipmentsStore.forOrderId(order.value.id)
})

const reorderInfoLocal = ref('')

onMounted(() => {
  if (!order.value) return
  // Inițializează form-urile RMA pentru linii eligibile
  order.value.lines.forEach((line) => {
    if (!rmaForms[line.lineId]) {
      rmaForms[line.lineId] = {
        open: false,
        qty: 1,
        reason: '',
        type: 'return'
      }
    }
  })
})

const formatDate = (isoString) => {
  const d = new Date(isoString)
  return d.toLocaleDateString('ro-RO', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

const statusLabel = (s) => {
  switch (s) {
    case 'new':
      return 'Nouă'
    case 'processing':
      return 'În procesare'
    case 'shipped':
      return 'Expediată'
    case 'delivered':
      return 'Livrată'
    case 'cancelled':
      return 'Anulată'
    default:
      return s
  }
}

const statusBadgeClass = (s) => {
  switch (s) {
    case 'new':
      return 'bg-secondary'
    case 'processing':
      return 'bg-info text-dark'
    case 'shipped':
      return 'bg-primary'
    case 'delivered':
      return 'bg-success'
    case 'cancelled':
      return 'bg-danger'
    default:
      return 'bg-light text-dark'
  }
}

const paymentStatusLabel = (s) => {
  switch (s) {
    case 'pending':
      return 'Plată în așteptare'
    case 'paid':
      return 'Plătită'
    case 'failed':
      return 'Eroare plată'
    case 'refunded':
      return 'Rambursată'
    default:
      return s
  }
}

const paymentBadgeClass = (s) => {
  switch (s) {
    case 'pending':
      return 'bg-warning text-dark'
    case 'paid':
      return 'bg-success'
    case 'failed':
      return 'bg-danger'
    case 'refunded':
      return 'bg-info text-dark'
    default:
      return 'bg-light text-dark'
  }
}

const rmaStatusLabel = (s) => {
  switch (s) {
    case 'requested':
      return 'RMA solicitată'
    case 'approved':
      return 'RMA aprobată'
    case 'rejected':
      return 'RMA respinsă'
    default:
      return 'Fără RMA'
  }
}

const rmaBadgeClass = (s) => {
  switch (s) {
    case 'requested':
      return 'bg-warning text-dark'
    case 'approved':
      return 'bg-success'
    case 'rejected':
      return 'bg-danger'
    default:
      return 'bg-light text-dark'
  }
}

const toggleRmaForm = (lineId) => {
  if (!rmaForms[lineId]) return
  rmaForms[lineId].open = !rmaForms[lineId].open
}

const submitRma = (line) => {
  const form = rmaForms[line.lineId]
  if (!form) return

  if (!form.qty || form.qty < 1 || form.qty > line.qty) {
    rmaInfo.value = 'Cantitatea pentru retur nu este validă.'
    return
  }

  if (!form.reason) {
    rmaInfo.value = 'Te rugăm să completezi motivul returului.'
    return
  }

  // În demo marcăm linia ca "requested". Într-un proiect real se trimite cererea la backend.
  line.rmaStatus = 'requested'
  form.open = false

  rmaInfo.value =
    'Template: a fost inițiată o cerere RMA pentru linia cu produsul ' +
    line.productName +
    ', cantitate ' +
    form.qty +
    ' ' +
    line.unit +
    '.'
}

const onReorder = (orderObj) => {
  const result = ordersStore.reorder(orderObj.id)
  if (!result) {
    reorderInfoLocal.value = 'A apărut o eroare la generarea comenzii noi.'
    return
  }
  reorderInfoLocal.value =
    'Template: s-ar genera un coș nou pe baza comenzii #' +
    orderObj.number +
    '.'

  // Într-un flux real ai redirecționa către coș / checkout:
  // router.push({ name: 'cart', query: { fromOrder: orderObj.id } })
}
</script>
