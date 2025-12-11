<template>
  <div class="container-fluid py-4">
    <!-- Header simplu fără componentă externă -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <button
          type="button"
          class="btn btn-link text-decoration-none ps-0"
          @click="goBack"
        >
          ← Înapoi la lista de clienți
        </button>
        <h4 class="mb-0 mt-2">
          Fișă client
          <span v-if="customer" class="text-muted small">
            • {{ customer.name }}
          </span>
        </h4>
        <p class="text-muted small mb-0">
          Detalii client, structură comercială și ierarhie agent → director → admin.
        </p>
      </div>
      <div class="text-end">
        <button
          v-if="canImpersonateCustomer"
          type="button"
          class="btn btn-outline-primary btn-sm"
          @click="impersonateAsCustomer"
        >
          Plasează comandă ca acest client
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <div v-else-if="!customer" class="alert alert-warning">
      Clientul nu a fost găsit în datele demo.
    </div>

    <div v-else class="row">
      <!-- Col stânga: date generale + multi-user -->
      <div class="col-xl-8 mb-3">
        <div class="card shadow-sm mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0">
                {{ customer.name }}
                <span class="badge bg-secondary ms-2">
                  {{ customer.clientType || 'Nespecificat' }}
                </span>
              </h5>
              <small class="text-muted">
                {{ customer.code || 'Fără cod intern' }}
              </small>
            </div>
            <div class="text-end small">
              <div>
                <span class="text-muted">Status:</span>
                <span
                  :class="[
                    'badge',
                    customer.status === 'blocked' ? 'bg-danger' : 'bg-success'
                  ]"
                >
                  {{ customer.status === 'blocked' ? 'Blocat' : 'Activ' }}
                </span>
              </div>
              <div class="mt-1">
                <span class="text-muted">Grup:</span>
                <strong>{{ customer.groupName || 'Nespecificat' }}</strong>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="small text-muted">Tip client</div>
                <div class="fw-semibold">
                  {{
                    customer.clientType === 'B2B'
                      ? 'Client B2B (companie)'
                      : customer.clientType === 'B2C'
                        ? 'Client B2C (persoană fizică)'
                        : 'Nespecificat'
                  }}
                </div>
              </div>
              <div class="col-md-6">
                <div class="small text-muted">Email</div>
                <div class="fw-semibold">
                  <a
                    v-if="customer.email"
                    :href="`mailto:${customer.email}`"
                    class="text-decoration-none"
                  >
                    {{ customer.email }}
                  </a>
                  <span v-else class="text-muted">-</span>
                </div>
              </div>
              <div class="col-md-6">
                <div class="small text-muted">Telefon</div>
                <div class="fw-semibold">
                  <a
                    v-if="customer.phone"
                    :href="`tel:${customer.phone}`"
                    class="text-decoration-none"
                  >
                    {{ customer.phone }}
                  </a>
                  <span v-else class="text-muted">-</span>
                </div>
              </div>
              <div class="col-md-6" v-if="customer.companyName">
                <div class="small text-muted">Denumire firmă</div>
                <div class="fw-semibold">
                  {{ customer.companyName }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Puncte de lucru / adrese (demo) -->
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Puncte de lucru & adrese</strong>
            <span class="small text-muted">
              Gestionare multi-adresă pentru facturare / livrare (structură demo)
            </span>
          </div>
          <div class="card-body small">
            <div class="row">
              <div class="col-md-6 border-end">
                <div class="fw-semibold mb-2">Adrese facturare</div>
                <div v-if="customer.billingAddresses && customer.billingAddresses.length">
                  <div
                    v-for="addr in customer.billingAddresses"
                    :key="addr.id || addr.label"
                    class="mb-2"
                  >
                    <div class="d-flex justify-content-between">
                      <div class="fw-semibold">
                        {{ addr.label || 'Adresă facturare' }}
                      </div>
                      <span v-if="addr.isDefault" class="badge bg-primary">
                        Implicită
                      </span>
                    </div>
                    <pre class="mb-1">{{ addr.address }}</pre>
                    <div class="text-muted">
                      CUI: {{ addr.vatNumber || customer.vatNumber || '-' }}
                    </div>
                  </div>
                </div>
                <div v-else class="text-muted">
                  Nu sunt definite adrese de facturare în demo.
                </div>
              </div>
              <div class="col-md-6">
                <div class="fw-semibold mb-2">Adrese livrare</div>
                <div v-if="customer.shippingAddresses && customer.shippingAddresses.length">
                  <div
                    v-for="addr in customer.shippingAddresses"
                    :key="addr.id || addr.label"
                    class="mb-2"
                  >
                    <div class="d-flex justify-content-between">
                      <div class="fw-semibold">
                        {{ addr.label || 'Adresă livrare' }}
                      </div>
                      <span v-if="addr.isDefault" class="badge bg-primary">
                        Implicită
                      </span>
                    </div>
                    <pre class="mb-1">{{ addr.address }}</pre>
                    <div class="text-muted">
                      Contact: {{ addr.contactPerson || customer.contactPerson || '-' }}
                    </div>
                  </div>
                </div>
                <div v-else class="text-muted">
                  Nu sunt definite adrese de livrare în demo.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Multi-user B2B -->
        <div v-if="customer.clientType === 'B2B'" class="card shadow-sm mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Utilizatori cont companie</strong>
            <span class="badge bg-light text-dark">
              {{ (customer.users && customer.users.length) || 0 }} utilizatori
            </span>
          </div>
          <div class="card-body small">
            <div v-if="customer.users && customer.users.length">
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Nume</th>
                      <th>Email</th>
                      <th>Rol intern</th>
                      <th class="text-end">Poate plasa comenzi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="user in customer.users" :key="user.id || user.email">
                      <td>{{ user.name }}</td>
                      <td>
                        <a
                          v-if="user.email"
                          :href="`mailto:${user.email}`"
                          class="text-decoration-none"
                        >
                          {{ user.email }}
                        </a>
                        <span v-else class="text-muted">-</span>
                      </td>
                      <td>{{ user.internalRole || 'Utilizator' }}</td>
                      <td class="text-end">
                        <span
                          :class="[
                            'badge',
                            user.canPlaceOrders ? 'bg-success' : 'bg-secondary'
                          ]"
                        >
                          {{ user.canPlaceOrders ? 'Da' : 'Nu' }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-muted">
              În acest demo nu sunt definiți utilizatori suplimentari pentru companie.
            </div>
          </div>
        </div>
      </div>

      <!-- Col dreapta: structură comercială & ierarhie -->
      <div class="col-xl-4 mb-3">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Structură comercială</strong>
          </div>
          <div class="card-body small">
            <dl class="row mb-0">
              <dt class="col-5">Tip client</dt>
              <dd class="col-7">
                {{ customer.clientType || 'Nespecificat' }}
              </dd>

              <dt class="col-5">Grup client</dt>
              <dd class="col-7">
                {{ customer.groupName || 'Nespecificat' }}
              </dd>

              <dt class="col-5">Agent</dt>
              <dd class="col-7">
                {{ customer.assignedAgent || 'Nealocat' }}
              </dd>

              <dt class="col-5">Director</dt>
              <dd class="col-7">
                {{ customer.assignedDirector || 'Nealocat' }}
              </dd>

              <dt class="col-5">Termen plată</dt>
              <dd class="col-7">
                {{
                  customer.paymentTermDays
                    ? customer.paymentTermDays + ' zile'
                    : 'La livrare'
                }}
              </dd>

              <dt class="col-5">Limită credit</dt>
              <dd class="col-7">
                <span v-if="customer.creditLimit != null">
                  {{
                    customer.creditLimit.toLocaleString('ro-RO', {
                      style: 'currency',
                      currency: 'RON'
                    })
                  }}
                </span>
                <span v-else class="text-muted">Fără limită setată</span>
              </dd>

              <dt class="col-5">Sold curent</dt>
              <dd class="col-7">
                <span
                  :class="customer.currentBalance > 0 ? 'text-danger' : 'text-success'"
                >
                  {{
                    customer.currentBalance.toLocaleString('ro-RO', {
                      style: 'currency',
                      currency: 'RON'
                    })
                  }}
                </span>
              </dd>
            </dl>
          </div>
        </div>

        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Condiții comerciale & discounturi</strong>
          </div>
          <div class="card-body small">
            <dl class="row mb-0">
              <dt class="col-6">Discount standard</dt>
              <dd class="col-6 text-end">
                <span v-if="customer.defaultDiscount != null">
                  {{ customer.defaultDiscount.toFixed(1) }}%
                </span>
                <span v-else class="text-muted">-</span>
              </dd>

              <dt class="col-6">Prețuri contractuale</dt>
              <dd class="col-6 text-end">
                <span
                  :class="[
                    'badge',
                    customer.hasContractPrices ? 'bg-success' : 'bg-secondary'
                  ]"
                >
                  {{ customer.hasContractPrices ? 'Există' : 'Nu există' }}
                </span>
              </dd>

              <dt class="col-6">Promoții dedicate</dt>
              <dd class="col-6 text-end">
                <span
                  :class="[
                    'badge',
                    customer.hasDedicatedPromotions ? 'bg-success' : 'bg-secondary'
                  ]"
                >
                  {{ customer.hasDedicatedPromotions ? 'Active' : 'Nu există' }}
                </span>
              </dd>
            </dl>
          </div>
        </div>

<div class="card shadow-sm mb-3">
  <div class="card-header py-2 d-flex justify-content-between align-items-center">
    <strong>Credit & încasări (demo)</strong>
    <span
      v-if="customer && customer.clientType === 'B2B'"
      class="badge bg-light text-dark"
    >
      B2B
    </span>
  </div>
  <div class="card-body small">
    <div v-if="customer && customer.clientType === 'B2B'">
      <dl class="row mb-3" v-if="demoCredit">
        <dt class="col-6">Termen de plată</dt>
        <dd class="col-6">
          {{ demoCredit.paymentTermDays }} zile
        </dd>

        <dt class="col-6">Limită credit</dt>
        <dd class="col-6">
          {{ demoCredit.creditLimit.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
          {{ demoCredit.currency }}
        </dd>

        <dt class="col-6">Sold curent</dt>
        <dd class="col-6">
          {{ demoCredit.currentBalance.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
          {{ demoCredit.currency }}
        </dd>

        <dt class="col-6">Sold restant</dt>
        <dd class="col-6">
          {{ demoCredit.overdueBalance.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
          {{ demoCredit.currency }}
        </dd>

        <dt class="col-6">Credit disponibil</dt>
        <dd class="col-6">
          {{ demoCredit.availableCredit.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
          {{ demoCredit.currency }}
        </dd>
      </dl>

      <h6 class="fw-semibold small mb-2">
        Încasări recente (CHS / BO / CEC)
      </h6>

      <div v-if="demoReceipts.length">
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-2">
            <thead class="table-light">
              <tr>
                <th>Data</th>
                <th>Tip</th>
                <th class="text-end">Sumă</th>
                <th>Doc.</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in demoReceipts" :key="r.id">
                <td>{{ r.date }}</td>
                <td>
                  <span
                    class="badge"
                    :class="{
                      'bg-success': r.type === 'CHS',
                      'bg-primary': r.type === 'BO',
                      'bg-info text-dark': r.type === 'CEC'
                    }"
                  >
                    {{ r.type }}
                  </span>
                </td>
                <td class="text-end">
                  {{ r.amount.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                </td>
                <td>{{ r.documentNumber }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-muted mb-0">
          Într-o implementare reală, aceste încasări ar proveni din modulul de
          încasări (CHS/BO/CEC) și din ERP, având impact direct asupra soldului
          clientului și a limitei de credit disponibile.
        </p>
      </div>
      <div v-else class="text-muted">
        Nu există încasări demo pentru acest client.
      </div>
    </div>
    <div v-else class="text-muted">
      În demo-ul curent, zona de credit și încasări este relevantă în principal
      pentru clienți B2B.
    </div>
  </div>
</div>

        <div class="card shadow-sm">
          <div class="card-header py-2">
            <strong>Informații ierarhie & impersonare</strong>
          </div>
          <div class="card-body small">
            <p class="mb-2">
              Structură ierarhică utilizată în demo:
            </p>
            <ul class="mb-2 ps-3">
              <li>Client B2C</li>
              <li>Client B2B → Agent → Director → Admin / Operator</li>
            </ul>
            <p class="mb-0 text-muted">
              Agentul și directorul pot plasa comenzi în numele acestui client prin
              butonul „Plasează comandă ca acest client”, respectând asignările
              configurate (assignedAgent / assignedDirector).
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCustomersStore } from '@/store/customers'
import { useAuthStore } from '@/store/auth'

const route = useRoute()
const router = useRouter()
const customersStore = useCustomersStore()
const authStore = useAuthStore()

const loading = ref(true)
const customer = ref(null)
const demoCredit = computed(() => {
  if (!customer.value || customer.value.clientType !== 'B2B') return null

  const creditLimit = 50000
  const currentBalance = 24500
  const overdueBalance = 2500

  return {
    paymentTermDays: 30,
    creditLimit,
    currentBalance,
    overdueBalance,
    availableCredit: Math.max(0, creditLimit - currentBalance),
    currency: 'RON'
  }
})

const demoReceipts = computed(() => {
  if (!customer.value || customer.value.clientType !== 'B2B') return []

  return [
    {
      id: 1,
      date: '2025-02-20',
      type: 'CHS',
      amount: 5000.0,
      documentNumber: 'CHS-2025-001'
    },
    {
      id: 2,
      date: '2025-02-18',
      type: 'BO',
      amount: 20000.0,
      documentNumber: 'BO-2025-010'
    },
    {
      id: 3,
      date: '2025-02-15',
      type: 'CEC',
      amount: 15000.5,
      documentNumber: 'CEC-2025-002'
    }
  ]
})


const goBack = () => {
  router.push({ name: 'admin-customers' })
}

onMounted(() => {
  const id = route.params.id
  const fromStore = customersStore.getById
    ? customersStore.getById(id)
    : null

  customer.value = fromStore || null
  loading.value = false
})

const canImpersonateCustomer = computed(() => {
  if (!authStore.user || !customer.value) return false

  const role = authStore.user.role
  const c = customer.value

  // Doar clienți B2B/B2C pot fi impersonați în front
  if (c.clientType !== 'B2B' && c.clientType !== 'B2C') {
    return false
  }

  if (role === 'agent') {
    return !!c.assignedAgent && c.assignedAgent === authStore.user.name
  }

  if (role === 'director') {
    // În acest demo folosim assignedDirector pentru a marca portofoliul directorului.
    return !!c.assignedDirector && c.assignedDirector === authStore.user.name
  }

  if (role === 'admin' || role === 'operator') {
    return true
  }

  return false
})

const impersonateAsCustomer = () => {
  if (!canImpersonateCustomer.value || !customer.value) return

  authStore.startImpersonation &&
    authStore.startImpersonation({
      id: customer.value.id,
      code: customer.value.code,
      name: customer.value.name,
      clientType: customer.value.clientType,
      groupName: customer.value.groupName
    })

  router.push({ name: 'account-dashboard' })
}
</script>
