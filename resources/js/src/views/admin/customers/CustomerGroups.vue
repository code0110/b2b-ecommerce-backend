<template>
  <div class="container py-3">
    <PageHeader
      title="Grupuri de clienți"
      subtitle="Configurare grupuri B2B/B2C, condiții comerciale implicite și seturi de promoții."
    >
      <template #actions>
        <button
          type="button"
          class="btn btn-sm btn-primary"
          @click="newGroup"
        >
          Creează grup nou (demo)
        </button>
      </template>
    </PageHeader>

    <div class="row g-3">
      <!-- Lista grupuri -->
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong class="small text-uppercase">Grupuri definite (demo)</strong>
            <span class="badge bg-light text-dark small">
              {{ groups.length }} grupuri
            </span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light small text-uppercase text-muted">
                  <tr>
                    <th>Denumire</th>
                    <th class="text-center" style="width: 80px;">Tip</th>
                    <th class="text-end" style="width: 110px;">Discount</th>
                    <th class="text-center" style="width: 110px;">Termen plată</th>
                    <th class="text-end" style="width: 130px;">Limită credit</th>
                    <th class="text-center" style="width: 70px;">Promoții</th>
                  </tr>
                </thead>
                <tbody class="small">
                  <tr
                    v-for="group in groups"
                    :key="group.id"
                    :class="{
                      'table-active': editingId === group.id
                    }"
                    role="button"
                    @click="selectGroup(group)"
                  >
                    <td>
                      <div class="fw-semibold">{{ group.name }}</div>
                      <div class="text-muted">
                        {{ group.segmentation }}
                      </div>
                    </td>
                    <td class="text-center">
                      <span
                        class="badge"
                        :class="groupTypeBadgeClass(group.type)"
                      >
                        {{ groupTypeLabel(group.type) }}
                      </span>
                    </td>
                    <td class="text-end">
                      {{ group.defaultDiscount }}%
                    </td>
                    <td class="text-center">
                      {{ group.paymentTerm }} zile
                    </td>
                    <td class="text-end">
                      <span v-if="group.creditLimit != null">
                        {{ formatMoney(group.creditLimit) }}
                      </span>
                      <span v-else class="text-muted">
                        nedefinită
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark">
                        {{ group.promotions.length }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="groups.length === 0">
                    <td colspan="6">
                      <div class="text-center text-muted py-4">
                        Nu există grupuri de clienți definite în acest template demo.
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer small text-muted">
            <strong>Notă demo:</strong>
            În varianta reală, grupurile ar fi gestionate prin API, cu legături directe
            la condițiile comerciale din ERP și la promoțiile active.
          </div>
        </div>
      </div>

      <!-- Formular detalii grup -->
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2">
            <strong class="small text-uppercase">
              {{ editingId ? 'Detalii grup / editare' : 'Creare grup nou' }}
            </strong>
          </div>
          <div class="card-body small">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label">Denumire grup</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="ex: Distribuitori, Retaileri, Clienți VIP"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Tip</label>
                <select
                  v-model="form.type"
                  class="form-select form-select-sm"
                >
                  <option value="B2B">B2B</option>
                  <option value="B2C">B2C</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Discount default (%)</label>
                <input
                  v-model.number="form.defaultDiscount"
                  type="number"
                  min="0"
                  max="100"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Termen plată (zile)</label>
                <input
                  v-model.number="form.paymentTerm"
                  type="number"
                  min="0"
                  class="form-control form-control-sm"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Limită credit (RON)</label>
                <input
                  v-model="form.creditLimit"
                  type="number"
                  min="0"
                  class="form-control form-control-sm"
                  placeholder="gol = fără limită"
                />
              </div>

              <div class="col-12">
                <label class="form-label">Promoții asociate (slug-uri separate prin virgulă)</label>
                <input
                  v-model="form.promotionsText"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="ex: promotie-primavara, black-friday, contract-distribuitori-2025"
                />
                <div class="form-text">
                  Aceste valori sunt demonstrative. În producție, aici ai avea selector
                  de promoții existente sau reguli de segmentare.
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">Segmentare / descriere</label>
                <textarea
                  v-model="form.segmentation"
                  rows="3"
                  class="form-control form-control-sm"
                  placeholder="Descriere pe scurt a criteriilor de includere în grup (ex: cifră de afaceri, regiune, canal de vânzare)."
                ></textarea>
              </div>
            </div>
          </div>
          <div class="card-footer d-flex justify-content-between align-items-center small">
            <div class="text-muted">
              <span v-if="editingId">
                Editare grup existent (ID: {{ editingId }})
              </span>
              <span v-else>
                Creare grup nou (demo, doar în memorie)
              </span>
            </div>
            <div class="d-flex gap-2">
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                @click="newGroup"
              >
                Resetează
              </button>
              <button
                type="button"
                class="btn btn-primary btn-sm"
                @click="saveGroup"
              >
                Salvează (demo)
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'

const groups = ref([
  {
    id: 1,
    name: 'Distribuitori',
    type: 'B2B',
    defaultDiscount: 15,
    paymentTerm: 30,
    creditLimit: 150000,
    promotions: ['distribuitori-primavara', 'bonus-volume-q1'],
    segmentation: 'Distribuitori regionali cu contract și target anual.'
  },
  {
    id: 2,
    name: 'Retaileri',
    type: 'B2B',
    defaultDiscount: 8,
    paymentTerm: 14,
    creditLimit: 50000,
    promotions: ['retaileri-sezon-estival'],
    segmentation: 'Magazine de retail cu comenzi recurente și expunere la raft.'
  },
  {
    id: 3,
    name: 'Clienți finali premium',
    type: 'B2C',
    defaultDiscount: 5,
    paymentTerm: 0,
    creditLimit: null,
    promotions: ['b2c-vip', 'b2c-newsletter-special'],
    segmentation: 'Clienți finali cu istoric bun de cumpărături și valoare mare în coș.'
  }
])

const editingId = ref(null)

const createEmptyForm = () => ({
  id: null,
  name: '',
  type: 'B2B',
  defaultDiscount: 0,
  paymentTerm: 30,
  creditLimit: null,
  promotionsText: '',
  segmentation: ''
})

const form = ref(createEmptyForm())

const groupTypeLabel = (type) => {
  if (type === 'B2B') return 'B2B'
  if (type === 'B2C') return 'B2C'
  return type
}

const groupTypeBadgeClass = (type) => {
  if (type === 'B2B') return 'bg-primary'
  if (type === 'B2C') return 'bg-info text-dark'
  return 'bg-secondary'
}

const formatMoney = (value) => {
  const number = Number(value || 0)
  return (
    number.toLocaleString('ro-RO', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' RON'
  )
}

const selectGroup = (group) => {
  editingId.value = group.id
  form.value = {
    id: group.id,
    name: group.name,
    type: group.type,
    defaultDiscount: group.defaultDiscount,
    paymentTerm: group.paymentTerm,
    creditLimit: group.creditLimit,
    promotionsText: group.promotions.join(', '),
    segmentation: group.segmentation
  }
}

const newGroup = () => {
  editingId.value = null
  form.value = createEmptyForm()
}

const saveGroup = () => {
  const name = (form.value.name || '').trim()
  if (!name) {
    window.alert('Te rog completează denumirea grupului de clienți (demo).')
    return
  }

  const type = form.value.type || 'B2B'
  const defaultDiscount = Number(form.value.defaultDiscount || 0)
  const paymentTerm = Number(form.value.paymentTerm || 0)
  const creditLimit =
    form.value.creditLimit === null || form.value.creditLimit === ''
      ? null
      : Number(form.value.creditLimit)

  const promotionsArray = form.value.promotionsText
    ? form.value.promotionsText
        .split(',')
        .map((s) => s.trim())
        .filter((s) => s.length > 0)
    : []

  if (editingId.value == null) {
    const newId =
      groups.value.reduce((max, g) => (g.id > max ? g.id : max), 0) + 1
    const newGroupObj = {
      id: newId,
      name,
      type,
      defaultDiscount,
      paymentTerm,
      creditLimit,
      promotions: promotionsArray,
      segmentation: (form.value.segmentation || '').trim()
    }
    groups.value.push(newGroupObj)
    editingId.value = newId
  } else {
    const idx = groups.value.findIndex((g) => g.id === editingId.value)
    if (idx !== -1) {
      groups.value[idx] = {
        id: editingId.value,
        name,
        type,
        defaultDiscount,
        paymentTerm,
        creditLimit,
        promotions: promotionsArray,
        segmentation: (form.value.segmentation || '').trim()
      }
    }
  }

  window.alert(
    'Demo: grupul de clienți a fost salvat în lista locală. În implementarea reală, aici s-ar apela un API de administrare / ERP.'
  )
}

// Selectează implicit primul grup, dacă există
if (groups.value.length > 0) {
  selectGroup(groups.value[0])
}
</script>
