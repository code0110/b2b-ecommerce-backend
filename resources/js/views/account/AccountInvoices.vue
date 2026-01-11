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
        <span v-if="frontClientType" class="badge bg-dd-blue ms-1">
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
      <div class="card-body">
        <div class="row row-cols-1 g-3">
          <div class="col" v-for="doc in documents" :key="doc.id">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="fw-semibold">{{ doc.number }}</div>
                    <div class="small text-muted">{{ doc.date }}</div>
                    <div class="mt-1">
                      <span class="badge bg-dd-blue" v-if="doc.type === 'invoice'">Factură</span>
                      <span class="badge bg-secondary" v-else>Proformă</span>
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold mb-1">
                      {{ doc.amount.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }} RON
                    </div>
                    <div class="mb-1" v-if="doc.orderCode">
                      <span class="badge bg-light text-dark">Comanda {{ doc.orderCode }}</span>
                    </div>
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
                  </div>
                </div>
                <div class="mt-3 d-flex justify-content-end gap-2">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    @click="downloadDocument(doc)"
                  >
                    Descarcă PDF
                  </button>
                  <button
                    v-if="doc.paymentStatus !== 'platita'"
                    type="button"
                    class="btn btn-sm btn-orange"
                    @click="payDocument(doc)"
                  >
                    Plătește acum
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div v-if="documents.length === 0" class="col">
            <div class="text-center py-4 text-muted">
              Nu există documente demo de afișat.
            </div>
          </div>
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
