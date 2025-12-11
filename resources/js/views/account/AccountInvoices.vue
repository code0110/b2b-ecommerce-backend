<template>
  <div class="container py-4">
    <div class="mb-4">
      <h2 class="mb-1">Documente financiare</h2>
      <p class="text-muted mb-2">
        Secțiune demo pentru facturi și proforme disponibile în contul clientului.
      </p>

      <div class="alert alert-info mb-0" v-if="frontCustomerName">
        Client activ:
        <strong>{{ frontCustomerName }}</strong>
        <span v-if="frontClientType" class="badge bg-primary ms-1">
          {{ frontClientType }}
        </span>
        <span class="text-muted ms-2 small">
          (documentele afișate mai jos sunt doar de exemplu)
        </span>
      </div>

      <div class="alert alert-warning mt-3 mb-0" v-else>
        <strong>Atenție:</strong> Într-o implementare reală, lista de documente
        financiare este disponibilă doar pentru un client autentificat sau pentru
        un client selectat în modul de impersonare.
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong class="small text-uppercase">Facturi & proforme (demo)</strong>
        <span class="badge bg-light text-dark">
          {{ documents.length }} documente demo
        </span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 130px;">Număr</th>
                <th style="width: 100px;">Tip</th>
                <th style="width: 120px;">Data</th>
                <th style="width: 130px;">Comandă</th>
                <th style="width: 120px;" class="text-end">Valoare (RON)</th>
                <th style="width: 130px;">Status plată</th>
                <th style="width: 130px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doc in documents" :key="doc.id">
                <td class="fw-semibold">{{ doc.number }}</td>
                <td>
                  <span class="badge bg-secondary" v-if="doc.type === 'invoice'">
                    Factură
                  </span>
                  <span class="badge bg-info text-dark" v-else>
                    Proformă
                  </span>
                </td>
                <td>{{ doc.date }}</td>
                <td>{{ doc.orderCode || '—' }}</td>
                <td class="text-end fw-semibold">
                  {{ doc.amount.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                </td>
                <td>
                  <span
                    class="badge"
                    :class="{
                      'bg-danger': doc.paymentStatus === 'neplatita',
                      'bg-warning text-dark': doc.paymentStatus === 'in_asteptare',
                      'bg-success': doc.paymentStatus === 'platita'
                    }"
                  >
                    {{
                      doc.paymentStatus === 'neplatita'
                        ? 'Neplătită'
                        : doc.paymentStatus === 'in_asteptare'
                          ? 'Plată în așteptare'
                          : 'Plătită'
                    }}
                  </span>
                </td>
                <td class="text-end">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary me-2"
                    @click="downloadDocument(doc)"
                  >
                    Descarcă PDF
                  </button>
                  <button
                    v-if="doc.paymentStatus !== 'platita'"
                    type="button"
                    class="btn btn-sm btn-outline-success"
                    @click="payDocument(doc)"
                  >
                    Plătește acum
                  </button>
                </td>
              </tr>
              <tr v-if="documents.length === 0">
                <td colspan="7" class="text-center text-muted py-4">
                  Nu există documente demo de afișat.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer small text-muted">
        În implementarea reală, acțiunea „Plătește acum” ar deschide un flux de
        plată cu cardul sau ar afișa detaliile pentru ordin de plată (OP), iar
        starea facturilor s-ar sincroniza cu ERP-ul.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/store/auth'

const authStore = useAuthStore()

const frontClientType = computed(() => {
  if (authStore.impersonatedCustomer?.clientType) {
    return authStore.impersonatedCustomer.clientType
  }
  if (authStore.user?.role === 'b2b') return 'B2B'
  if (authStore.user?.role === 'b2c') return 'B2C'
  return null
})

const frontCustomerName = computed(() => {
  if (authStore.impersonatedCustomer?.name) {
    return authStore.impersonatedCustomer.name
  }
  if (authStore.user && (authStore.user.role === 'b2b' || authStore.user.role === 'b2c')) {
    return authStore.user.name
  }
  return null
})

const documents = [
  {
    id: 1,
    number: 'FAC-2025-001',
    type: 'invoice',
    date: '2025-02-19',
    orderCode: 'CMD-1001',
    amount: 24500.5,
    paymentStatus: 'in_asteptare'
  },
  {
    id: 2,
    number: 'PRF-2025-010',
    type: 'proforma',
    date: '2025-02-18',
    orderCode: 'CMD-0999',
    amount: 520.99,
    paymentStatus: 'neplatita'
  },
  {
    id: 3,
    number: 'FAC-2025-002',
    type: 'invoice',
    date: '2025-02-10',
    orderCode: 'CMD-0999',
    amount: 520.99,
    paymentStatus: 'platita'
  }
]

const downloadDocument = (doc) => {
  window.alert(`Demo: aici s-ar descărca PDF-ul pentru ${doc.number}.`)
}

const payDocument = (doc) => {
  window.alert(
    `Demo: aici s-ar iniția fluxul de plată pentru documentul ${doc.number} (sumă ${doc.amount.toLocaleString(
      'ro-RO',
      { minimumFractionDigits: 2 }
    )} RON).`
  )
}
</script>
