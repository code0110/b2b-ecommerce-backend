<template>
  <div class="container-fluid">
    <PageHeader
      title="AWB & expedieri"
      subtitle="Monitorizare AWB-uri, curieri și statusul livrării pentru comenzi."
    />

    <div class="card shadow-sm mb-3">
      <div class="card-body small">
        <p class="mb-1">
          Acest ecran servește ca <strong>hub operațional</strong> pentru echipa de logistică / back-office:
        </p>
        <ul class="small mb-2">
          <li>vizualizare AWB-uri generate direct din platformă sau din ERP;</li>
          <li>status curent de livrare sincronizat de la curieri (într-un proiect real, prin API);</li>
          <li>link rapid către pagina de tracking a curierului;</li>
          <li>filtrare după curier, status, număr de comandă sau AWB.</li>
        </ul>
        <p class="mb-0 text-muted">
          Într-o implementare completă, de aici s-ar putea porni și
          <em>generarea AWB-ului</em> pe baza datelor de livrare din comandă sau reemiterea / anularea
          unui AWB existent.
        </p>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body p-0">
        <!-- Filtre -->
        <div class="border-bottom bg-light px-3 py-2">
          <form class="row g-2 align-items-end small" @submit.prevent>
            <div class="col-md-3">
              <label class="form-label text-muted mb-1">Caută</label>
              <input
                v-model="filters.query"
                type="text"
                class="form-control form-control-sm"
                placeholder="Nr. comandă sau AWB"
              />
            </div>
            <div class="col-md-3">
              <label class="form-label text-muted mb-1">Curier</label>
              <select v-model="filters.courier" class="form-select form-select-sm">
                <option value="">Toți curierii</option>
                <option
                  v-for="c in couriers"
                  :key="c"
                  :value="c"
                >
                  {{ c }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label text-muted mb-1">Status livrare</label>
              <select v-model="filters.status" class="form-select form-select-sm">
                <option value="">Toate statusurile</option>
                <option value="label_generated">Etichetă generată</option>
                <option value="picked_up">Preluat de curier</option>
                <option value="in_transit">În tranzit</option>
                <option value="out_for_delivery">În livrare</option>
                <option value="delivered">Livrat</option>
                <option value="cancelled">Anulat</option>
              </select>
            </div>
            <div class="col-md-3 text-end">
              <button type="button" class="btn btn-outline-secondary btn-sm" @click="resetFilters">
                Resetează filtre
              </button>
            </div>
          </form>
        </div>

        <!-- Tabel expedieri -->
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>AWB</th>
                <th>Comandă / client</th>
                <th>Curier</th>
                <th>Status livrare</th>
                <th>Estimare livrare</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredShipments.length === 0">
                <td colspan="6" class="text-center text-muted py-4">
                  Nu există expedieri pentru filtrele selectate.
                </td>
              </tr>
              <tr
                v-for="shipment in filteredShipments"
                :key="shipment.id"
              >
                <td class="small">
                  <div class="fw-semibold">
                    {{ shipment.awbNumber || '—' }}
                  </div>
                  <div class="text-muted small">
                    Creat: {{ formatDateTime(shipment.createdAt) }}
                  </div>
                  <div v-if="shipment.lastUpdate" class="text-muted small">
                    Ultima actualizare: {{ formatDateTime(shipment.lastUpdate) }}
                  </div>
                </td>
                <td class="small">
                  <div class="fw-semibold">
                    {{ shipment.orderNumber }}
                  </div>
                  <div class="text-muted small">
                    {{ shipment.customerName }}
                  </div>
                </td>
                <td class="small">
                  {{ shipment.courier }}
                </td>
                <td class="small">
                  <span :class="['badge', shipmentBadgeClass(shipment.status)]">
                    {{ shipmentStatusLabel(shipment.status) }}
                  </span>
                  <div class="text-muted small mt-1" v-if="shipment.notes">
                    {{ shipment.notes }}
                  </div>
                </td>
                <td class="small">
                  <div v-if="shipment.estimatedDeliveryDate">
                    {{ formatDate(shipment.estimatedDeliveryDate) }}
                  </div>
                  <div v-else class="text-muted small">
                    Nedisponibil.
                  </div>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a
                      v-if="shipment.trackingUrl"
                      :href="shipment.trackingUrl"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="btn btn-outline-primary"
                    >
                      Tracking curier
                    </a>
                    <button
                      v-if="canSimulateNextStatus(shipment.status)"
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="simulateNextStatus(shipment)"
                    >
                      Simulează următorul status
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="infoMessage" class="border-top small text-muted px-3 py-2">
          {{ infoMessage }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import { useShipmentsStore } from '@/store/shipments'

const store = useShipmentsStore()

const filters = reactive({
  query: '',
  courier: '',
  status: ''
})

const infoMessage = ref('')

const shipments = computed(() => store.all)

const couriers = computed(() => {
  const set = new Set()
  shipments.value.forEach((s) => {
    if (s.courier) {
      set.add(s.courier)
    }
  })
  return Array.from(set).sort()
})

const filteredShipments = computed(() => {
  const q = filters.query.trim().toLowerCase()
  return shipments.value.filter((s) => {
    if (q) {
      const haystack = `${s.orderNumber} ${s.awbNumber || ''} ${s.customerName}`.toLowerCase()
      if (!haystack.includes(q)) {
        return false
      }
    }

    if (filters.courier && s.courier !== filters.courier) {
      return false
    }

    if (filters.status && s.status !== filters.status) {
      return false
    }

    return true
  })
})

const resetFilters = () => {
  filters.query = ''
  filters.courier = ''
  filters.status = ''
}

const shipmentStatusLabel = (status) => {
  switch (status) {
    case 'label_generated':
      return 'Etichetă generată'
    case 'picked_up':
      return 'Preluat de curier'
    case 'in_transit':
      return 'În tranzit'
    case 'out_for_delivery':
      return 'În livrare'
    case 'delivered':
      return 'Livrat'
    case 'cancelled':
      return 'Anulat'
    default:
      return status
  }
}

const shipmentBadgeClass = (status) => {
  switch (status) {
    case 'label_generated':
      return 'bg-secondary'
    case 'picked_up':
    case 'in_transit':
    case 'out_for_delivery':
      return 'bg-info text-dark'
    case 'delivered':
      return 'bg-success'
    case 'cancelled':
      return 'bg-danger'
    default:
      return 'bg-light text-dark'
  }
}

const canSimulateNextStatus = (status) => {
  return ['label_generated', 'picked_up', 'in_transit', 'out_for_delivery'].includes(status)
}

const simulateNextStatus = (shipment) => {
  const order = ['label_generated', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered']
  const currentIndex = order.indexOf(shipment.status)
  let nextStatus = 'delivered'

  if (currentIndex !== -1 && currentIndex < order.length - 1) {
    nextStatus = order[currentIndex + 1]
  }

  store.updateStatus(shipment.id, nextStatus)

  infoMessage.value =
    'Template: statusul AWB-ului ' +
    shipment.awbNumber +
    ' a fost actualizat la "' +
    shipmentStatusLabel(nextStatus) +
    '". În implementarea reală, modificarea ar fi trimisă înapoi în ERP / curier.'
}

const formatDateTime = (iso) => {
  if (!iso) return ''
  const d = new Date(iso)
  return (
    d.toLocaleDateString('ro-RO', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
    ' ' +
    d.toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit' })
  )
}

const formatDate = (isoDate) => {
  if (!isoDate) return ''
  const d = new Date(isoDate)
  return d.toLocaleDateString('ro-RO', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>
