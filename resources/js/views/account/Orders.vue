<template>
  <div class="container">
    <PageHeader
      title="Comenzi"
      subtitle="Istoric comenzi, status plată și opțiune de comandă din nou."
    />

    <!-- Filtre -->
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <form class="row g-3 align-items-end" @submit.prevent>
          <div class="col-md-4">
            <label class="form-label small text-muted">Status comandă</label>
            <select v-model="filters.status" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="new">Nouă</option>
              <option value="processing">În procesare</option>
              <option value="shipped">Expediată</option>
              <option value="delivered">Livrată</option>
              <option value="cancelled">Anulată</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted">Status plată</label>
            <select v-model="filters.paymentStatus" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="pending">Plată în așteptare</option>
              <option value="paid">Plătită</option>
              <option value="failed">Eroare plată</option>
              <option value="refunded">Rambursată</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted">Perioadă</label>
            <select v-model="filters.period" class="form-select form-select-sm">
              <option value="all">Toată perioada</option>
              <option value="30">Ultimele 30 zile</option>
              <option value="180">Ultimele 6 luni</option>
            </select>
          </div>
        </form>
      </div>
    </div>

    <!-- Listă comenzi -->
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Nr. comandă</th>
                <th>Data</th>
                <th>Tip</th>
                <th>Valoare</th>
                <th>Status comandă</th>
                <th>Status plată</th>
                <th>Livrare</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredOrders.length === 0">
                <td colspan="8" class="text-center text-muted py-4">
                  Nu există comenzi pentru filtrele selectate.
                </td>
              </tr>
              <tr v-for="order in filteredOrders" :key="order.id">
                <td>
                  <RouterLink
                    :to="{ name: 'account-order-details', params: { id: order.id } }"
                    class="fw-semibold text-decoration-none"
                  >
                    {{ order.number }}
                  </RouterLink>
                  <div class="small text-muted">
                    Canal: {{ order.channel === 'agent' ? 'Agent vânzări' : 'Online' }}
                  </div>
                </td>
                <td class="small">
                  {{ formatDate(order.createdAt) }}
                </td>
                <td class="small">
                  <span :class="['badge', order.isB2B ? 'bg-secondary' : 'bg-light text-dark']">
                    {{ order.isB2B ? 'B2B' : 'B2C' }}
                  </span>
                </td>
                <td class="small">
                  <strong>{{ order.totalGross.toLocaleString('ro-RO') }} {{ order.currency }}</strong>
                </td>
                <td class="small">
                  <span :class="['badge', statusBadgeClass(order.status)]">
                    {{ statusLabel(order.status) }}
                  </span>
                </td>
                <td class="small">
                  <span :class="['badge', paymentBadgeClass(order.paymentStatus)]">
                    {{ paymentStatusLabel(order.paymentStatus) }}
                  </span>
                </td>
                <td class="small">
                  <div v-if="latestShipmentForOrder(order.id)">
                    <div>
                      <span
                        :class="['badge', shipmentBadgeClass(latestShipmentForOrder(order.id).status)]"
                      >
                        {{ shipmentStatusLabel(latestShipmentForOrder(order.id).status) }}
                      </span>
                    </div>
                    <div class="small text-muted mt-1">
                      AWB:
                      <span class="fw-semibold">
                        {{ latestShipmentForOrder(order.id).awbNumber || 'N/A' }}
                      </span>
                    </div>
                  </div>
                  <div v-else class="text-muted small">
                    Fără AWB (încă).
                  </div>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <RouterLink
                      :to="{ name: 'account-order-details', params: { id: order.id } }"
                      class="btn btn-outline-primary"
                    >
                      Detalii
                    </RouterLink>
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="onReorder(order)"
                    >
                      Comandă din nou
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="reorderInfo" class="border-top small text-muted p-2">
          {{ reorderInfo }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { useOrdersStore } from '@/store/orders'
import { useShipmentsStore } from '@/store/shipments'
import { useAuthStore } from '@/store/auth'

const ordersStore = useOrdersStore()
const authStore = useAuthStore()
const shipmentsStore = useShipmentsStore()

const filters = reactive({
  status: '',
  paymentStatus: '',
  period: 'all'
})

const reorderInfo = ref('')

const now = new Date()

const userOrders = computed(() => {
  if (authStore.user) {
    return ordersStore.forUser(authStore.user.id)
  }
  return ordersStore.all
})

const filteredOrders = computed(() => {
  const maxAgeDays = filters.period === 'all' ? null : Number(filters.period)

  return userOrders.value.filter((order) => {
    if (filters.status && order.status !== filters.status) return false
    if (filters.paymentStatus && order.paymentStatus !== filters.paymentStatus) return false

    if (maxAgeDays) {
      const created = new Date(order.createdAt)
      const diffMs = now - created
      const diffDays = diffMs / (1000 * 60 * 60 * 24)
      if (diffDays > maxAgeDays) return false
    }

    return true
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

const onReorder = (order) => {
  const result = ordersStore.reorder(order.id)
  if (!result) {
    reorderInfo.value = 'A apărut o eroare la generarea comenzii noi.'
    return
  }
  reorderInfo.value =
    'Template: s-ar genera un coș nou pe baza comenzii #' +
    order.number +
    ' (' +
    result.lineCount +
    ' linii, ' +
    result.totalGross.toLocaleString('ro-RO') +
    ' ' +
    order.currency +
    ').'
}
</script>
