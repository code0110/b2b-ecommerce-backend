<template>
  <div class="container-fluid">
    <PageHeader
      title="Încasări"
      subtitle="Gestionare încasări CHS / BO / CEC / card / OP la nivel de client."
    />

    <div class="row g-3">
      <!-- Filtre + tabel -->
      <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <form class="row g-2 align-items-end" @submit.prevent>
              <div class="col-md-4">
                <label class="form-label small text-muted">Căutare client</label>
                <input
                  v-model="filters.customer"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Nume client"
                />
              </div>
              <div class="col-md-3">
                <label class="form-label small text-muted">Tip încasare</label>
                <select v-model="filters.type" class="form-select form-select-sm">
                  <option value="">Toate</option>
                  <option value="chs">CHS - numerar</option>
                  <option value="bo">BO - bilet la ordin</option>
                  <option value="cec">CEC</option>
                  <option value="card">Card</option>
                  <option value="op">OP - ordin de plată</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label small text-muted">Luna</label>
                <select v-model="filters.month" class="form-select form-select-sm">
                  <option value="">Toate</option>
                  <option value="2025-01">Ianuarie 2025</option>
                  <option value="2025-02">Februarie 2025</option>
                </select>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Dată</th>
                    <th>Client</th>
                    <th>Tip</th>
                    <th>Document</th>
                    <th>Valoare</th>
                    <th>Responsabil</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredCollections.length === 0">
                    <td colspan="6" class="text-center text-muted py-4">
                      Nu există încasări pentru filtrele selectate.
                    </td>
                  </tr>
                  <tr
                    v-for="item in filteredCollections"
                    :key="item.id"
                  >
                    <td class="small">
                      {{ formatDate(item.collectedAt) }}
                    </td>
                    <td class="small">
                      <div class="fw-semibold">{{ item.customerName }}</div>
                    </td>
                    <td class="small">
                      <span :class="['badge', typeBadgeClass(item.type)]">
                        {{ typeLabel(item.type) }}
                      </span>
                    </td>
                    <td class="small">
                      {{ documentLabel(item) }}
                    </td>
                    <td class="small">
                      <strong>
                        {{ item.amount.toLocaleString('ro-RO') }} {{ item.currency }}
                      </strong>
                    </td>
                    <td class="small">
                      {{ item.collectedBy }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Formular adăugare încasare (template) -->
      <div class="col-lg-4">
        <div class="card shadow-sm">
          <div class="card-header py-2">
            <strong>Adaugă încasare</strong>
          </div>
          <div class="card-body small">
            <form @submit.prevent="onAddCollection">
              <div class="mb-2">
                <label class="form-label text-muted">Client</label>
                <input
                  v-model="form.customerName"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Nume client"
                  required
                />
              </div>
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label text-muted">Tip încasare</label>
                  <select
                    v-model="form.type"
                    class="form-select form-select-sm"
                    required
                  >
                    <option value="">Alege...</option>
                    <option value="chs">CHS - numerar</option>
                    <option value="bo">BO - bilet la ordin</option>
                    <option value="cec">CEC</option>
                    <option value="card">Card</option>
                    <option value="op">OP - ordin de plată</option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label text-muted">Dată</label>
                  <input
                    v-model="form.collectedAt"
                    type="date"
                    class="form-control form-control-sm"
                    required
                  />
                </div>
              </div>
              <div class="row g-2 mt-1">
                <div class="col-6">
                  <label class="form-label text-muted">Sumă</label>
                  <input
                    v-model.number="form.amount"
                    type="number"
                    min="0"
                    step="0.01"
                    class="form-control form-control-sm"
                    required
                  />
                </div>
                <div class="col-6">
                  <label class="form-label text-muted">Monedă</label>
                  <input
                    v-model="form.currency"
                    type="text"
                    class="form-control form-control-sm"
                  />
                </div>
              </div>
              <div class="row g-2 mt-1">
                <div class="col-6">
                  <label class="form-label text-muted">Tip document</label>
                  <select
                    v-model="form.documentType"
                    class="form-select form-select-sm"
                  >
                    <option value="">-</option>
                    <option value="invoice">Factură</option>
                    <option value="proforma">Proformă</option>
                    <option value="contract">Contract</option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label text-muted">Nr. document</label>
                  <input
                    v-model="form.documentNumber"
                    type="text"
                    class="form-control form-control-sm"
                  />
                </div>
              </div>
              <div class="mt-1">
                <label class="form-label text-muted">Responsabil</label>
                <input
                  v-model="form.collectedBy"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="mt-1">
                <label class="form-label text-muted">Referință / notă</label>
                <textarea
                  v-model="form.reference"
                  class="form-control form-control-sm"
                  rows="2"
                />
              </div>
              <div class="d-flex justify-content-end mt-2">
                <button type="submit" class="btn btn-primary btn-sm">
                  Salvează încasarea
                </button>
              </div>
              <p v-if="saveInfo" class="small text-muted mt-2 mb-0">
                {{ saveInfo }}
              </p>
            </form>
          </div>
        </div>

        <div class="card shadow-sm mt-3">
          <div class="card-body small text-muted">
            <strong>Notă:</strong> În implementarea reală, salvarea încasărilor va actualiza:
            soldul clientului, limita de credit disponibilă și va înregistra documentele în ERP.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import { useCollectionsStore } from '@/store/collections'

const store = useCollectionsStore()

const filters = reactive({
  customer: '',
  type: '',
  month: ''
})

const form = reactive({
  customerId: null,
  customerName: '',
  type: '',
  amount: null,
  currency: 'RON',
  collectedAt: new Date().toISOString().slice(0, 10),
  collectedBy: '',
  documentType: '',
  documentNumber: '',
  reference: '',
  notes: ''
})

const saveInfo = ref('')

const collections = computed(() => store.all)

const filteredCollections = computed(() => {
  return collections.value.filter((c) => {
    if (filters.customer) {
      const term = filters.customer.toLowerCase()
      if (!c.customerName.toLowerCase().includes(term)) return false
    }
    if (filters.type && c.type !== filters.type) return false

    if (filters.month) {
      if (!c.collectedAt.startsWith(filters.month)) return false
    }

    return true
  })
})

const formatDate = (isoOrDate) => {
  const d = new Date(isoOrDate)
  if (Number.isNaN(d.getTime())) return isoOrDate
  return d.toLocaleDateString('ro-RO', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

const typeLabel = (t) => {
  switch (t) {
    case 'chs':
      return 'CHS - numerar'
    case 'bo':
      return 'BO - bilet la ordin'
    case 'cec':
      return 'CEC'
    case 'card':
      return 'Card'
    case 'op':
      return 'OP - ordin de plată'
    default:
      return t
  }
}

const typeBadgeClass = (t) => {
  switch (t) {
    case 'chs':
      return 'bg-secondary'
    case 'bo':
      return 'bg-info text-dark'
    case 'cec':
      return 'bg-light text-dark'
    case 'card':
      return 'bg-success'
    case 'op':
      return 'bg-primary'
    default:
      return 'bg-light text-dark'
  }
}

const documentLabel = (item) => {
  if (!item.documentType && !item.documentNumber) return '-'
  let label = ''
  switch (item.documentType) {
    case 'invoice':
      label = 'Factură'
      break
    case 'proforma':
      label = 'Proformă'
      break
    case 'contract':
      label = 'Contract'
      break
    default:
      label = item.documentType
  }
  if (item.documentNumber) {
    label += ' ' + item.documentNumber
  }
  return label
}

const onAddCollection = () => {
  if (!form.customerName || !form.type || !form.amount || !form.collectedAt) {
    saveInfo.value = 'Te rugăm să completezi câmpurile obligatorii.'
    return
  }

  store.addCollection({ ...form })
  saveInfo.value =
    'Template: încasarea a fost salvată local. În implementarea reală se va trimite către backend / ERP și se vor recalcula soldul și limita de credit.'
  // Resetăm parțial formularul
  form.amount = null
  form.reference = ''
}
</script>
