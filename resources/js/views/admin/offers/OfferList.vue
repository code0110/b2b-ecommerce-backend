<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h5 class="mb-0">Oferte comerciale</h5>
        <small class="text-muted">
          Gestionare oferte &amp; negocieri B2B/B2C – cereri provenite din pagini de produs sau din contact direct.
        </small>
      </div>
    </div>

    <div class="row g-3">
      <!-- Lista oferte -->
      <div class="col-lg-7">
        <div class="card shadow-sm">
          <div class="card-body small">
            <div class="row g-2 mb-2">
              <div class="col-md-3">
                <label class="form-label text-muted">Status</label>
                <select
                  v-model="filters.status"
                  class="form-select form-select-sm"
                >
                  <option value="">Toate</option>
                  <option value="requested">Solicitare</option>
                  <option value="in_review">În analiză</option>
                  <option value="approved">Aprobate</option>
                  <option value="rejected">Respinse</option>
                  <option value="converted">Convertite</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted">Tip client</label>
                <select
                  v-model="filters.customerType"
                  class="form-select form-select-sm"
                >
                  <option value="">Toate</option>
                  <option value="B2B">B2B</option>
                  <option value="B2C">B2C</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted">Agent</label>
                <select
                  v-model="filters.assignedAgent"
                  class="form-select form-select-sm"
                >
                  <option value="">Toți</option>
                  <option value="__unassigned">Nealocate</option>
                  <option
                    v-for="agent in distinctAgents"
                    :key="agent"
                    :value="agent"
                  >
                    {{ agent }}
                  </option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted">Căutare</label>
                <input
                  v-model="filters.search"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Client / cod ofertă / proiect"
                />
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Cod</th>
                    <th>Client</th>
                    <th>Valoare</th>
                    <th>Status</th>
                    <th>Agent</th>
                    <th>Creată</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredOffers.length === 0">
                    <td colspan="6" class="text-center text-muted py-4">
                      Nu există oferte pentru filtrele selectate.
                    </td>
                  </tr>
                  <tr
                    v-for="offer in filteredOffers"
                    :key="offer.id"
                    :class="{ 'table-active': selectedOffer && selectedOffer.id === offer.id }"
                    style="cursor: pointer;"
                    @click="selectOffer(offer)"
                  >
                    <td class="small fw-semibold">
                      {{ offer.code }}
                    </td>
                    <td class="small">
                      <div>{{ offer.customerName || '-' }}</div>
                      <div class="text-muted">
                        <small>{{ offer.customerEmail }}</small>
                      </div>
                    </td>
                    <td class="small">
                      <div>
                        <strong v-if="offer.totalProposed">
                          {{ formatMoney(offer.totalProposed, offer.currency) }}
                        </strong>
                        <strong v-else-if="offer.totalList">
                          {{ formatMoney(offer.totalList, offer.currency) }}
                        </strong>
                        <span v-else>-</span>
                      </div>
                    </td>
                    <td class="small">
                      <span :class="['badge', statusBadgeClass(offer.status)]">
                        {{ statusLabel(offer.status) }}
                      </span>
                    </td>
                    <td class="small">
                      <span v-if="offer.assignedAgent">{{ offer.assignedAgent }}</span>
                      <span v-else class="text-muted">Nealocat</span>
                    </td>
                    <td class="small">
                      {{ formatDateTime(offer.createdAt) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p class="small text-muted mt-2 mb-0">
              Template: în implementarea reală, aici se pot adăuga filtre după perioadă, tip proiect,
              canal de generare (online / offline) și pot fi expuse acțiuni de bulk (export, asignare, etc.).
            </p>
          </div>
        </div>
      </div>

      <!-- Detalii ofertă -->
      <div class="col-lg-5">
        <div v-if="selectedOffer" class="card shadow-sm mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <div>
              <strong>{{ selectedOffer.code }}</strong>
              <span class="text-muted ms-2">
                {{ selectedOffer.customerType }} – {{ selectedOffer.customerName }}
              </span>
            </div>
            <span :class="['badge', statusBadgeClass(selectedOffer.status)]">
              {{ statusLabel(selectedOffer.status) }}
            </span>
          </div>
          <div class="card-body small">
            <div class="mb-2">
              <h6 class="mb-1">Client &amp; identificare</h6>
              <div class="text-muted">
                Email: {{ selectedOffer.customerEmail || '-' }}<br />
                Agent alocat:
                <span v-if="selectedOffer.assignedAgent">
                  {{ selectedOffer.assignedAgent }}
                </span>
                <span v-else class="text-muted">
                  Nealocat
                </span><br />
                Aprobat de: {{ selectedOffer.approvedBy || '-' }}
              </div>
            </div>

            <div class="mb-2">
              <h6 class="mb-1">Valori ofertă</h6>
              <div>
                <span class="text-muted">Valoare listă:</span>
                <strong class="ms-1">
                  {{ selectedOffer.totalList ? formatMoney(selectedOffer.totalList, selectedOffer.currency) : '-' }}
                </strong>
              </div>
              <div>
                <span class="text-muted">Valoare propusă:</span>
                <strong class="ms-1">
                  {{ selectedOffer.totalProposed ? formatMoney(selectedOffer.totalProposed, selectedOffer.currency) : '-' }}
                </strong>
              </div>
              <div v-if="selectedOffer.relatedOrderCode" class="mt-1">
                <span class="text-muted">Comandă generată:</span>
                <span class="ms-1">
                  {{ selectedOffer.relatedOrderCode }}
                </span>
              </div>
            </div>

            <div class="mb-2">
              <h6 class="mb-1">Produse incluse</h6>
              <div class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead>
                    <tr>
                      <th>Produs</th>
                      <th class="text-end">Cant.</th>
                      <th class="text-end">Listă</th>
                      <th class="text-end">Propus</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="line in selectedOffer.lines" :key="line.id">
                      <td>
                        <div class="fw-semibold">{{ line.productName }}</div>
                        <div class="text-muted">
                          <small>{{ line.productCode }}</small>
                        </div>
                      </td>
                      <td class="text-end">
                        {{ line.requestedQty }} {{ line.unit }}
                      </td>
                      <td class="text-end">
                        {{ line.listPrice != null ? formatMoney(line.listPrice, selectedOffer.currency) : '-' }}
                      </td>
                      <td class="text-end">
                        {{ line.proposedPrice != null ? formatMoney(line.proposedPrice, selectedOffer.currency) : '-' }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="mb-2">
              <h6 class="mb-1">Note &amp; mesaje</h6>
              <div class="mb-1">
                <span class="text-muted">Mesaj client:</span>
                <div class="border rounded p-2 mt-1 bg-light">
                  {{ selectedOffer.notesFromCustomer || 'Fără mesaj de la client.' }}
                </div>
              </div>
              <div class="mb-1">
                <span class="text-muted">Note interne:</span>
                <div class="border rounded p-2 mt-1">
                  {{ selectedOffer.internalNotes || 'Completează în CRM / modul dedicat de ofertare.' }}
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer small d-flex justify-content-between align-items-center">
            <div class="btn-group btn-group-sm" role="group">
              <button
                type="button"
                class="btn btn-outline-info"
                @click="setStatus('in_review')"
              >
                În analiză
              </button>
              <button
                type="button"
                class="btn btn-outline-success"
                @click="setStatus('approved')"
              >
                Aprobă
              </button>
              <button
                type="button"
                class="btn btn-outline-danger"
                @click="setStatus('rejected')"
              >
                Respinge
              </button>
            </div>
            <button
              type="button"
              class="btn btn-outline-primary btn-sm"
              @click="markAsConverted"
            >
              Marchează drept comandă
            </button>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body small text-muted">
            <h6>Recomandări de proces</h6>
            <ul class="mb-0">
              <li>Alocare automată a ofertelor pe agenți în funcție de regiune / portofoliu.</li>
              <li>Flux de aprobare pentru oferte cu discount peste un anumit prag.</li>
              <li>Conversie directă ofertă &rarr; comandă, cu urmărire marjă și targete.</li>
            </ul>
          </div>
        </div>

        <p v-if="infoMessage" class="small text-muted mt-2 mb-0">
          {{ infoMessage }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useOffersStore } from '@/store/offers'

const offersStore = useOffersStore()

const filters = reactive({
  status: '',
  customerType: '',
  assignedAgent: '',
  search: ''
})

const selectedOffer = ref(null)
const infoMessage = ref('')

const allOffers = computed(() => offersStore.all)

const distinctAgents = computed(() => {
  const agents = allOffers.value
    .map((o) => o.assignedAgent)
    .filter((a) => !!a)
  return Array.from(new Set(agents)).sort()
})

const filteredOffers = computed(() => {
  return allOffers.value.filter((offer) => {
    if (filters.status && offer.status !== filters.status) return false
    if (filters.customerType && offer.customerType !== filters.customerType) return false
    if (filters.assignedAgent) {
      if (filters.assignedAgent === '__unassigned') {
        if (offer.assignedAgent) return false
      } else if (offer.assignedAgent !== filters.assignedAgent) {
        return false
      }
    }
    if (filters.search) {
      const term = filters.search.toLowerCase()
      const haystack = [
        offer.code,
        offer.customerName,
        offer.customerEmail,
        offer.notesFromCustomer
      ]
        .join(' ')
        .toLowerCase()
      if (!haystack.includes(term)) return false
    }
    return true
  })
})

const selectOffer = (offer) => {
  selectedOffer.value = offer
  infoMessage.value = ''
}

const statusLabel = (status) => {
  switch (status) {
    case 'requested':
      return 'Solicitare'
    case 'in_review':
      return 'În analiză'
    case 'approved':
      return 'Aprobată'
    case 'rejected':
      return 'Respinsă'
    case 'converted':
      return 'Convertită'
    default:
      return status
  }
}

const statusBadgeClass = (status) => {
  switch (status) {
    case 'requested':
      return 'bg-warning text-dark'
    case 'in_review':
      return 'bg-info text-dark'
    case 'approved':
      return 'bg-success'
    case 'rejected':
      return 'bg-danger'
    case 'converted':
      return 'bg-primary'
    default:
      return 'bg-secondary'
  }
}

const formatDateTime = (iso) => {
  if (!iso) return '-'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  return (
    d.toLocaleDateString('ro-RO', { year: 'numeric', month: '2-digit', day: '2-digit' }) +
    ' ' +
    d.toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit' })
  )
}

const formatMoney = (value, currency = 'RON') => {
  const v = Number(value || 0)
  return (
    v.toLocaleString('ro-RO', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) +
    ' ' +
    currency
  )
}

const setStatus = (status) => {
  if (!selectedOffer.value) return
  offersStore.updateStatus(selectedOffer.value.id, status)
  infoMessage.value =
    'Template: statusul ofertei a fost actualizat la „' + statusLabel(status) + '”.'
}

const markAsConverted = () => {
  if (!selectedOffer.value) return
  const orderCode = 'CMD-' + selectedOffer.value.code.replace('OFF-', '')
  offersStore.markConvertedToOrder(selectedOffer.value.id, orderCode)
  infoMessage.value =
    'Template: oferta a fost marcată ca &bdquo;convertită în comandă&rdquo; cu codul ' +
    orderCode +
    '. În implementarea reală, aici se va apela API-ul ERP.'
}
</script>
