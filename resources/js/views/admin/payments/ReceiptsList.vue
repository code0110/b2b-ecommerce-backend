<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-1">Încasări clienți (CHS / BO / CEC) – Demo</h4>
        <p class="text-muted small mb-0">
          Zonă demo pentru încasări gestionate de agenți și directori de vânzări,
          cu impact asupra soldului și limitei de credit ale clienților B2B.
        </p>
      </div>
      <div class="text-end small" v-if="currentUser">
        <div>
          Utilizator curent:
          <strong>{{ currentUser.name }}</strong>
          <span class="badge bg-light text-dark ms-1">
            {{ currentUser.role || 'fără rol' }}
          </span>
        </div>
        <div class="text-muted">
          Ierarhie: client → agent → director → admin/operator
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body small">
        <div class="row g-2">
          <div class="col-md-3">
            <label class="form-label">Client</label>
            <input
              type="text"
              class="form-control form-control-sm"
              placeholder="Caută după nume client..."
              v-model="filters.customer"
            />
          </div>
          <div class="col-md-2">
            <label class="form-label">Tip încasare</label>
            <select class="form-select form-select-sm" v-model="filters.type">
              <option value="">Toate</option>
              <option value="CHS">CHS – numerar</option>
              <option value="BO">BO – bilet la ordin</option>
              <option value="CEC">CEC – cec</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Agent</label>
            <input
              type="text"
              class="form-control form-control-sm"
              placeholder="Nume agent..."
              v-model="filters.agent"
            />
          </div>
          <div class="col-md-2">
            <label class="form-label">Status ERP</label>
            <select class="form-select form-select-sm" v-model="filters.erpStatus">
              <option value="">Toate</option>
              <option value="sync">Sincronizată</option>
              <option value="pending">În așteptare</option>
              <option value="error">Eroare</option>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end justify-content-end">
            <button
              type="button"
              class="btn btn-sm btn-primary"
              @click="goToNewReceipt"
            >
              + Înregistrare încasare (demo)
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-uppercase text-muted mb-0">Încasări recente</h6>
        <span class="badge bg-light text-dark border">
          {{ filteredReceipts.length }} înregistrări
        </span>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 mb-4">
      <div v-for="receipt in filteredReceipts" :key="receipt.id" class="col">
        <div class="card h-100 border shadow-sm hover-shadow transition-all">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
             <div>
                <span class="badge rounded-pill me-2" 
                   :class="{
                      'bg-success bg-opacity-10 text-success': receipt.type === 'CHS',
                      'bg-primary bg-opacity-10 text-primary': receipt.type === 'BO',
                      'bg-info bg-opacity-10 text-info': receipt.type === 'CEC'
                   }">
                   {{ receipt.type }}
                </span>
                <span class="small text-muted">{{ receipt.date }}</span>
             </div>
             <div class="fw-bold text-dark">
                {{ receipt.amount.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
             </div>
          </div>
          
          <div class="card-body">
             <div class="mb-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">CLIENT</small>
                <div class="fw-semibold text-dark text-truncate">{{ receipt.customerName }}</div>
                <div class="small text-muted">{{ receipt.customerIdentifier }}</div>
             </div>

             <div class="mb-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">DETALII DOCUMENT</small>
                <div class="d-flex justify-content-between align-items-center mt-1">
                   <span class="text-dark small"><i class="bi bi-file-text me-1 text-muted"></i> {{ receipt.documentNumber }}</span>
                   <span class="badge bg-light text-dark border">{{ receipt.collectionTypeLabel }}</span>
                </div>
                <div class="mt-1 small text-muted" v-if="receipt.invoiceCode">
                   Ref: {{ receipt.invoiceCode }} <span v-if="receipt.orderCode">/ {{ receipt.orderCode }}</span>
                </div>
             </div>

             <div class="mb-3" v-if="receipt.agentName || receipt.directorName">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">ASIGNARE</small>
                <div class="d-flex flex-column gap-1 mt-1">
                   <div v-if="receipt.agentName" class="d-flex align-items-center small text-dark">
                      <i class="bi bi-person me-2 text-muted"></i> Agent: {{ receipt.agentName }}
                   </div>
                   <div v-if="receipt.directorName" class="d-flex align-items-center small text-dark">
                      <i class="bi bi-person-badge me-2 text-muted"></i> Director: {{ receipt.directorName }}
                   </div>
                </div>
             </div>
          </div>

          <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
             <span class="small text-muted">Status ERP:</span>
             <div class="d-flex align-items-center">
                <span
                   class="badge rounded-pill"
                   :class="{
                      'bg-success bg-opacity-10 text-success': receipt.erpStatus === 'sync',
                      'bg-warning bg-opacity-10 text-warning': receipt.erpStatus === 'pending',
                      'bg-danger bg-opacity-10 text-danger': receipt.erpStatus === 'error'
                   }"
                >
                   <i class="bi me-1" :class="{
                      'bi-check-circle-fill': receipt.erpStatus === 'sync',
                      'bi-hourglass-split': receipt.erpStatus === 'pending',
                      'bi-exclamation-circle-fill': receipt.erpStatus === 'error'
                   }"></i>
                   {{
                      receipt.erpStatus === 'sync'
                        ? 'Sincronizat'
                        : receipt.erpStatus === 'pending'
                          ? 'În așteptare'
                          : 'Eroare'
                   }}
                </span>
             </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="alert alert-info small shadow-sm border-0">
        <i class="bi bi-info-circle-fill me-2"></i>
        Aceasta este o listă statică de încasări demo. În implementarea reală, înregistrarea CHS/BO/CEC ar actualiza soldul clientului, limita de credit disponibilă și ar genera înregistrarea aferentă în ERP.
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const router = useRouter()
const authStore = useAuthStore()
const currentUser = computed(() => authStore.user || null)

const filters = reactive({
  customer: '',
  type: '',
  agent: '',
  erpStatus: ''
})

const receipts = [
  {
    id: 1,
    date: '2025-02-20',
    customerName: 'SC Construct Plus SRL',
    customerIdentifier: 'CUI RO12345678',
    type: 'CHS',
    amount: 5000.0,
    documentNumber: 'CHS-2025-001',
    collectionTypeLabel: 'Încasare numerar',
    invoiceCode: 'FAC-2025-001',
    orderCode: 'CMD-1001',
    agentName: 'Popescu Mihai',
    directorName: 'Ionescu Adrian',
    erpStatus: 'sync',
    erpMessage: 'Înregistrată în ERP'
  },
  {
    id: 2,
    date: '2025-02-18',
    customerName: 'SC Retail Market SRL',
    customerIdentifier: 'CUI RO87654321',
    type: 'BO',
    amount: 20000.0,
    documentNumber: 'BO-2025-010',
    collectionTypeLabel: 'Bilet la ordin',
    invoiceCode: 'FAC-2025-010',
    orderCode: null,
    agentName: 'Georgescu Ana',
    directorName: 'Ionescu Adrian',
    erpStatus: 'pending',
    erpMessage: 'În așteptare confirmare bancă'
  },
  {
    id: 3,
    date: '2025-02-15',
    customerName: 'SC Industrial Tech SRL',
    customerIdentifier: 'CUI RO99887766',
    type: 'CEC',
    amount: 15000.5,
    documentNumber: 'CEC-2025-002',
    collectionTypeLabel: 'CEC',
    invoiceCode: 'FAC-2025-005',
    orderCode: 'CMD-0998',
    agentName: 'Popescu Mihai',
    directorName: 'Ionescu Adrian',
    erpStatus: 'error',
    erpMessage: 'Respinsă – date incomplete'
  }
]

const filteredReceipts = computed(() => {
  return receipts.filter((r) => {
    const matchesCustomer =
      !filters.customer ||
      r.customerName.toLowerCase().includes(filters.customer.toLowerCase())
    const matchesType = !filters.type || r.type === filters.type
    const matchesAgent =
      !filters.agent ||
      (r.agentName && r.agentName.toLowerCase().includes(filters.agent.toLowerCase()))
    const matchesStatus = !filters.erpStatus || r.erpStatus === filters.erpStatus
    return matchesCustomer && matchesType && matchesAgent && matchesStatus
  })
})

const goToNewReceipt = () => {
  router.push('/admin/payments/new')
}
</script>
