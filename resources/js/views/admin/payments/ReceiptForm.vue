<template>
  <div class="container-fluid py-4">
    <div class="mb-3 d-flex justify-content-between align-items-center">
      <button
        type="button"
        class="btn btn-link text-decoration-none ps-0"
        @click="goBack"
      >
        ← Înapoi la lista de încasări
      </button>
      <div class="small text-muted" v-if="currentUser">
        Înregistrare încasare ca:
        <strong>{{ currentUser.name }}</strong>
        <span class="badge bg-light text-dark ms-1">
          {{ currentUser.role || 'fără rol' }}
        </span>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong class="small text-uppercase">Înregistrare încasare client – Demo</strong>
        <span class="badge bg-primary">CHS / BO / CEC</span>
      </div>
      <div class="card-body">
        <div class="row g-3 small">
          <div class="col-md-6">
            <label class="form-label">Client</label>
            <select
              class="form-select form-select-sm"
              v-model="form.customerId"
            >
              <option disabled value="">Selectează clientul...</option>
              <option value="1">SC Construct Plus SRL (B2B)</option>
              <option value="2">SC Retail Market SRL (B2B)</option>
              <option value="3">Ionescu Andrei (B2C)</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tip încasare</label>
            <select
              class="form-select form-select-sm"
              v-model="form.type"
            >
              <option disabled value="">Selectează...</option>
              <option value="CHS">CHS – numerar</option>
              <option value="BO">BO – bilet la ordin</option>
              <option value="CEC">CEC – cec</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Data încasării</label>
            <input
              type="date"
              class="form-control form-control-sm"
              v-model="form.date"
            />
          </div>

          <div class="col-md-3">
            <label class="form-label">Sumă (RON)</label>
            <input
              type="number"
              min="0"
              step="0.01"
              class="form-control form-control-sm"
              v-model.number="form.amount"
            />
            <div class="form-text" v-if="form.type === 'CHS'">
              Exemplu politică: maxim 10.000 RON numerar per încasare.
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Număr document (CHS/BO/CEC)</label>
            <input
              type="text"
              class="form-control form-control-sm"
              v-model="form.documentNumber"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label">Ref. factură</label>
            <input
              type="text"
              class="form-control form-control-sm"
              placeholder="Ex: FAC-2025-001"
              v-model="form.invoiceCode"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label">Ref. comandă</label>
            <input
              type="text"
              class="form-control form-control-sm"
              placeholder="Ex: CMD-1001"
              v-model="form.orderCode"
            />
          </div>

          <div class="col-md-4">
            <label class="form-label">Agent încasator</label>
            <input
              type="text"
              class="form-control form-control-sm"
              v-model="form.agentName"
              :placeholder="currentUser ? currentUser.name : 'Nume agent...'"
            />
          </div>
          <div class="col-md-4">
            <label class="form-label">Director responsabil</label>
            <input
              type="text"
              class="form-control form-control-sm"
              v-model="form.directorName"
              placeholder="Nume director..."
            />
          </div>
          <div class="col-md-4">
            <label class="form-label">Observații interne</label>
            <input
              type="text"
              class="form-control form-control-sm"
              placeholder="Ex: încasare parțială, reeșalonare..."
              v-model="form.internalNote"
            />
          </div>
        </div>

        <hr />

        <div class="small text-muted mb-3">
          Într-o implementare reală, la salvarea încasării:
          <ul class="mb-0">
            <li>se trimite înregistrarea către ERP (tip document + referințe);</li>
            <li>se actualizează soldul clientului și limita de credit disponibilă;</li>
            <li>se păstrează audit (cine, când, ce s-a înregistrat).</li>
          </ul>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            @click="goBack"
          >
            Anulează
          </button>
          <button
            type="button"
            class="btn btn-sm btn-primary"
            @click="saveDemo"
          >
            Salvează încasarea (demo)
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const router = useRouter()
const authStore = useAuthStore()
const currentUser = computed(() => authStore.user || null)

const today = new Date().toISOString().slice(0, 10)

const form = reactive({
  customerId: '',
  type: '',
  date: today,
  amount: null,
  documentNumber: '',
  invoiceCode: '',
  orderCode: '',
  agentName: '',
  directorName: '',
  internalNote: ''
})

const goBack = () => {
  router.push('/admin/payments')
}

const saveDemo = () => {
  if (!form.customerId || !form.type || !form.date || !form.amount || !form.documentNumber) {
    window.alert('Te rog completează câmpurile obligatorii: client, tip, dată, sumă, număr document.')
    return
  }

  if (form.type === 'CHS' && form.amount > 10000) {
    const ok = window.confirm(
      'Sumă numerar > 10.000 RON. Într-o implementare reală, acest lucru ar încălca politica internă. Continui oricum?'
    )
    if (!ok) {
      return
    }
  }

  window.alert(
    'Încasare demo salvată.

' +
      'În producție aici s-ar apela API-ul backend / ERP și s-ar recalcula soldul și limita de credit.'
  )

  router.push('/admin/payments')
}
</script>
