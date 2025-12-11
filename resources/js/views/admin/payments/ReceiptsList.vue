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

    <div class="card shadow-sm">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong class="small text-uppercase">Încasări recente</strong>
        <span class="badge bg-light text-dark">
          {{ filteredReceipts.length }} înregistrări demo
        </span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 120px;">Data</th>
                <th>Client</th>
                <th style="width: 80px;">Tip</th>
                <th style="width: 130px;">Sumă (RON)</th>
                <th style="width: 140px;">Doc. încasare</th>
                <th style="width: 140px;">Ref. factură</th>
                <th style="width: 180px;">Agent / Director</th>
                <th style="width: 130px;">Status ERP</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="receipt in filteredReceipts" :key="receipt.id">
                <td>{{ receipt.date }}</td>
                <td>
                  <div class="fw-semibold">{{ receipt.customerName }}</div>
                  <div class="small text-muted">
                    {{ receipt.customerIdentifier }}
                  </div>
                </td>
                <td>
                  <span
                    class="badge"
                    :class="{
                      'bg-success': receipt.type === 'CHS',
                      'bg-primary': receipt.type === 'BO',
                      'bg-info text-dark': receipt.type === 'CEC'
                    }"
                  >
                    {{ receipt.type }}
                  </span>
                </td>
                <td class="text-end fw-semibold">
                  {{ receipt.amount.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                </td>
                <td>
                  <div class="fw-semibold">{{ receipt.documentNumber }}</div>
                  <div class="small text-muted">
                    {{ receipt.collectionTypeLabel }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold">{{ receipt.invoiceCode || '—' }}</div>
                  <div class="small text-muted" v-if="receipt.orderCode">
                    comandă: {{ receipt.orderCode }}
                  </div>
                </td>
                <td>
                  <div class="small">
                    <div>
                      Agent:
                      <strong>{{ receipt.agentName || '—' }}</strong>
                    </div>
                    <div>
                      Director:
                      <strong>{{ receipt.directorName || '—' }}</strong>
                    </div>
                  </div>
                </td>
                <td>
                  <span
                    class="badge"
                    :class="{
                      'bg-success': receipt.erpStatus === 'sync',
                      'bg-warning text-dark': receipt.erpStatus === 'pending',
                      'bg-danger': receipt.erpStatus === 'error'
                    }"
                  >
                    {{
                      receipt.erpStatus === 'sync'
                        ? 'Sincronizată'
                        : receipt.erpStatus === 'pending'
                          ? 'În așteptare'
                          : 'Eroare'
                    }}
                  </span>
                  <div class="small text-muted" v-if="receipt.erpMessage">
                    {{ receipt.erpMessage }}
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer small text-muted">
        Aceasta este o listă statică de încasări demo. În implementarea reală,
        înregistrarea CHS/BO/CEC ar actualiza soldul clientului, limita de credit
        disponibilă și ar genera înregistrarea aferentă în ERP.
      </div>
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
