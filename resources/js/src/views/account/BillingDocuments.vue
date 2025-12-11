<template>
  <div class="container">
    <PageHeader
      title="Documente financiare"
      subtitle="Facturi și proforme asociate comenzilor tale, cu status de plată și link-uri PDF."
    />

    <!-- Filtre -->
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <form class="row g-3 align-items-end" @submit.prevent>
          <div class="col-md-4">
            <label class="form-label small text-muted">Tip document</label>
            <select v-model="filters.type" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="invoice">Facturi</option>
              <option value="proforma">Proforme</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted">Status plată</label>
            <select v-model="filters.status" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="paid">Plătit</option>
              <option value="unpaid">Neplătit</option>
              <option value="overdue">Restanță</option>
              <option value="cancelled">Anulat</option>
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

    <!-- Tabel documente -->
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Tip</th>
                <th>Număr</th>
                <th>Comandă</th>
                <th>Dată emitere</th>
                <th>Scadență</th>
                <th>Valoare</th>
                <th>Status plată</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredDocs.length === 0">
                <td colspan="8" class="text-center text-muted py-4">
                  Nu există documente pentru filtrele selectate.
                </td>
              </tr>
              <tr v-for="doc in filteredDocs" :key="doc.id">
                <td class="small">
                  <span :class="['badge', typeBadgeClass(doc.type)]">
                    {{ typeLabel(doc.type) }}
                  </span>
                </td>
                <td class="small">
                  {{ doc.docNumber }}
                </td>
                <td class="small">
                  <RouterLink
                    v-if="doc.orderId"
                    :to="{ name: 'account-order-details', params: { id: doc.orderId } }"
                    class="text-decoration-none"
                  >
                    #{{ doc.orderId }}
                  </RouterLink>
                  <span v-else class="text-muted">-</span>
                </td>
                <td class="small">
                  {{ formatDate(doc.issueDate) }}
                </td>
                <td class="small">
                  {{ doc.dueDate ? formatDate(doc.dueDate) : '-' }}
                </td>
                <td class="small">
                  <strong>{{ doc.amountGross.toLocaleString('ro-RO') }} {{ doc.currency }}</strong>
                </td>
                <td class="small">
                  <span :class="['badge', statusBadgeClass(doc.status)]">
                    {{ statusLabel(doc.status) }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      :disabled="!doc.pdfUrl"
                    >
                      Descarcă PDF
                    </button>
                    <button
                      v-if="doc.allowOnlinePayment && (doc.status === 'unpaid' || doc.status === 'overdue')"
                      type="button"
                      class="btn btn-primary"
                      @click="onPay(doc)"
                    >
                      Plătește acum
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="payInfo" class="border-top small text-muted p-2">
          {{ payInfo }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { useBillingDocumentsStore } from '@/store/billingDocuments'
import { useAuthStore } from '@/store/auth'

const billingStore = useBillingDocumentsStore()
const authStore = useAuthStore()

const filters = reactive({
  type: '',
  status: '',
  period: 'all'
})

const payInfo = ref('')

const now = new Date()

const userDocs = computed(() => {
  if (authStore.user) {
    return billingStore.forUser(authStore.user.id)
  }
  return billingStore.all
})

const filteredDocs = computed(() => {
  const maxAgeDays = filters.period === 'all' ? null : Number(filters.period)

  return userDocs.value.filter((doc) => {
    if (filters.type && doc.type !== filters.type) return false
    if (filters.status && doc.status !== filters.status) return false

    if (maxAgeDays) {
      const issue = new Date(doc.issueDate)
      const diffMs = now - issue
      const diffDays = diffMs / (1000 * 60 * 60 * 24)
      if (diffDays > maxAgeDays) return false
    }

    return true
  })
})

const formatDate = (isoOrDate) => {
  const d = new Date(isoOrDate)
  return d.toLocaleDateString('ro-RO', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

const typeLabel = (t) => {
  switch (t) {
    case 'invoice':
      return 'Factură'
    case 'proforma':
      return 'Proformă'
    default:
      return t
  }
}

const typeBadgeClass = (t) => {
  switch (t) {
    case 'invoice':
      return 'bg-secondary'
    case 'proforma':
      return 'bg-light text-dark'
    default:
      return 'bg-light text-dark'
  }
}

const statusLabel = (s) => {
  switch (s) {
    case 'paid':
      return 'Plătit'
    case 'unpaid':
      return 'Neplătit'
    case 'overdue':
      return 'Restanță'
    case 'cancelled':
      return 'Anulat'
    default:
      return s
  }
}

const statusBadgeClass = (s) => {
  switch (s) {
    case 'paid':
      return 'bg-success'
    case 'unpaid':
      return 'bg-warning text-dark'
    case 'overdue':
      return 'bg-danger'
    case 'cancelled':
      return 'bg-secondary'
    default:
      return 'bg-light text-dark'
  }
}

const onPay = (doc) => {
  // Template: aici se va iniția fluxul de plată (card sau OP).
  payInfo.value =
    'Template: ai inițiat plata online pentru ' +
    typeLabel(doc.type).toLowerCase() +
    ' ' +
    doc.docNumber +
    ' în valoare de ' +
    doc.amountGross.toLocaleString('ro-RO') +
    ' ' +
    doc.currency +
    '.'
}
</script>
