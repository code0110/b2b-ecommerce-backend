<template>
  <div class="container py-4">
    <h2 class="mb-1">Checkout</h2>
    <p class="text-muted mb-3">
      Finalizarea comenzii pentru clientul activ în front (B2B/B2C), inclusiv
      suport pentru scenariile în care un agent sau director plasează comanda
      în numele clientului.
    </p>

    <!-- Context client & impersonare -->
    <div class="alert alert-info small mb-3" v-if="frontClientType">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div>
            Client activ:
            <strong>{{ frontCustomerName }}</strong>
            <span class="badge bg-secondary ms-2">{{ frontClientType }}</span>
          </div>
          <div class="mt-1" v-if="isImpersonating">
            Lucrezi în modul de impersonare –
            <span class="fw-semibold">{{ user?.name }}</span>
            ({{ user?.role || 'utilizator' }})
            plasează comanda în numele acestui client.
          </div>
          <div class="mt-1 text-muted" v-else>
            Client autentificat direct în platformă (fără impersonare).
          </div>
        </div>
        <div class="text-end" v-if="isImpersonating">
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            @click="stopImpersonation"
          >
            Ieși din modul client
          </button>
        </div>
      </div>
    </div>

    <div class="alert alert-warning small" v-else>
      <strong>Atenție:</strong> Nu există niciun client activ în front. Într-o
      implementare reală, nu s-ar putea continua checkout-ul până când clientul
      nu este identificat (login sau impersonare).
    </div>

    <!-- Layout 2 coloane: pași + sumar -->
    <div class="row mt-3">
      <!-- Pași checkout -->
      <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <div class="d-flex justify-content-between align-items-center small">
              <div>
                <span
                  class="badge rounded-pill me-1"
                  :class="currentStep >= 1 ? 'bg-primary' : 'bg-light text-dark'"
                  >1</span
                >
                Adrese
              </div>
              <div>
                <span
                  class="badge rounded-pill me-1"
                  :class="currentStep >= 2 ? 'bg-primary' : 'bg-light text-dark'"
                  >2</span
                >
                Livrare
              </div>
              <div>
                <span
                  class="badge rounded-pill me-1"
                  :class="currentStep >= 3 ? 'bg-primary' : 'bg-light text-dark'"
                  >3</span
                >
                Plată
              </div>
              <div>
                <span
                  class="badge rounded-pill me-1"
                  :class="currentStep >= 4 ? 'bg-primary' : 'bg-light text-dark'"
                  >4</span
                >
                Rezumat
              </div>
            </div>
          </div>
          <div class="card-body small">
            <!-- Pas 1: Adrese -->
            <div v-if="currentStep === 1">
              <h5 class="mb-3">1. Adrese livrare & facturare</h5>
              <p class="text-muted">
                Într-o implementare completă, adresele ar fi preluate din contul
                clientului (inclusiv puncte de lucru B2B). Aici este ilustrată
                doar structura.
              </p>

              <div class="row">
                <div class="col-md-6">
                  <h6>Adresă de facturare</h6>
                  <div class="form-check" v-for="opt in billingOptions" :key="opt.value">
                    <input
                      class="form-check-input"
                      type="radio"
                      name="billingAddress"
                      :id="`billing-${opt.value}`"
                      :value="opt.value"
                      v-model="selectedBillingAddress"
                    />
                    <label class="form-check-label" :for="`billing-${opt.value}`">
                      {{ opt.label }}
                    </label>
                  </div>
                  <div class="mt-2" v-if="frontClientType === 'B2B'">
                    <div class="form-text">
                      Pentru clienții B2B, această adresă va fi utilizată pentru
                      facturarea pe firmă (CUI, Nr. Reg. Com etc.).
                    </div>
                  </div>
                </div>

                <div class="col-md-6 mt-3 mt-md-0">
                  <h6>Adresă de livrare</h6>
                  <div class="form-check" v-for="opt in shippingAddressOptions" :key="opt.value">
                    <input
                      class="form-check-input"
                      type="radio"
                      name="shippingAddress"
                      :id="`shipping-${opt.value}`"
                      :value="opt.value"
                      v-model="selectedShippingAddress"
                    />
                    <label class="form-check-label" :for="`shipping-${opt.value}`">
                      {{ opt.label }}
                    </label>
                  </div>
                  <div class="mt-2">
                    <div class="form-text">
                      Poți adăuga ulterior puncte de livrare multiple (de ex.
                      depozite, magazine, șantiere).
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-end mt-3">
                <button
                  type="button"
                  class="btn btn-primary btn-sm"
                  @click="goToStep(2)"
                >
                  Continuă la livrare
                </button>
              </div>
            </div>

            <!-- Pas 2: Livrare -->
            <div v-else-if="currentStep === 2">
              <h5 class="mb-3">2. Metodă de livrare</h5>
              <p class="text-muted">
                Costurile și opțiunile de transport pot fi configurate în
                secțiunea de Admin → Setări livrare & transport.
              </p>

              <div class="mb-3">
                <div
                  class="form-check"
                  v-for="opt in shippingMethods"
                  :key="opt.value"
                >
                  <input
                    class="form-check-input"
                    type="radio"
                    name="shippingMethod"
                    :id="`ship-${opt.value}`"
                    :value="opt.value"
                    v-model="shippingMethod"
                  />
                  <label class="form-check-label" :for="`ship-${opt.value}`">
                    <strong>{{ opt.label }}</strong>
                    <span class="text-muted ms-2">{{ opt.description }}</span>
                  </label>
                </div>
              </div>

              <div class="d-flex justify-content-between mt-3">
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  @click="goToStep(1)"
                >
                  Înapoi la adrese
                </button>
                <button
                  type="button"
                  class="btn btn-primary btn-sm"
                  @click="goToStep(3)"
                >
                  Continuă la plată
                </button>
              </div>
            </div>

            <!-- Pas 3: Plată -->
            <div v-else-if="currentStep === 3">
              <h5 class="mb-3">3. Metodă de plată</h5>

              <div class="alert alert-light border small" v-if="isB2B">
                Pentru clienții B2B, se poate ține cont de termenii de plată
                contractuali (ex: 30/60/90 zile) și de limita de credit, conform
                datelor din ERP.
              </div>

              <div class="mb-3">
                <div
                  class="form-check"
                  v-for="opt in paymentOptions"
                  :key="opt.value"
                >
                  <input
                    class="form-check-input"
                    type="radio"
                    name="paymentMethod"
                    :id="`pay-${opt.value}`"
                    :value="opt.value"
                    v-model="paymentMethod"
                    :disabled="opt.onlyFor === 'B2B' && !isB2B"
                  />
                  <label class="form-check-label" :for="`pay-${opt.value}`">
                    <strong>{{ opt.label }}</strong>
                    <span class="text-muted ms-2">{{ opt.description }}</span>
                    <span
                      v-if="opt.onlyFor === 'B2B'"
                      class="badge bg-secondary ms-2"
                    >
                      Doar B2B
                    </span>
                  </label>
                </div>
              </div>

              <div class="d-flex justify-content-between mt-3">
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  @click="goToStep(2)"
                >
                  Înapoi la livrare
                </button>
                <button
                  type="button"
                  class="btn btn-primary btn-sm"
                  @click="goToStep(4)"
                >
                  Continuă la rezumat
                </button>
              </div>
            </div>

            <!-- Pas 4: Rezumat -->
            <div v-else-if="currentStep === 4">
              <h5 class="mb-3">4. Rezumat comandă</h5>

              <div class="row">
                <div class="col-md-6">
                  <h6>Client & context</h6>
                  <p class="mb-1">
                    Client:
                    <strong>{{ frontCustomerName || 'Nespecificat' }}</strong>
                  </p>
                  <p class="mb-1">
                    Tip:
                    <span class="badge bg-secondary">
                      {{ frontClientType || 'N/A' }}
                    </span>
                  </p>
                  <p class="mb-1" v-if="isImpersonating">
                    Comanda este plasată de
                    <strong>{{ user?.name }}</strong>
                    ({{ user?.role || 'utilizator' }}) în numele acestui client.
                  </p>
                  <p class="mb-1 text-muted" v-else>
                    Comandă plasată direct de client.
                  </p>
                </div>
                <div class="col-md-6">
                  <h6>Livrare & plată</h6>
                  <p class="mb-1">
                    Livrare:
                    <strong>{{ shippingMethodLabel }}</strong>
                  </p>
                  <p class="mb-1">
                    Plată:
                    <strong>{{ paymentMethodLabel }}</strong>
                  </p>
                </div>
              </div>

              <div class="alert alert-light border small mt-3">
                Într-o integrare reală, aici s-ar afișa detaliile exacte ale
                produselor din coș (linile de comandă), totalurile (inclusiv
                discounturi, TVA, transport) și s-ar pregăti payload-ul pentru
                ERP (proformă B2B/B2C, status de plată etc.).
              </div>

              <div class="d-flex justify-content-between mt-3">
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  @click="goToStep(3)"
                >
                  Înapoi la plată
                </button>
                <button
                  type="button"
                  class="btn btn-success btn-sm"
                  :disabled="!frontClientType"
                  @click="placeOrder"
                >
                  Confirmă comanda (demo)
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Col dreapta: Sumar coș -->
      <div class="col-lg-4">
        <div class="card shadow-sm">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Rezumat coș (demo)</strong>
          </div>
          <div class="card-body small">
            <p class="text-muted">
              În acest template, rezumatul este demonstrativ. Într-o implementare
              reală, ar fi legat de store-ul de coș și de regulile de promoții &
              discounturi.
            </p>
            <ul class="list-unstyled mb-2">
              <li class="d-flex justify-content-between">
                <span>3 produse demo</span>
                <span>750 RON</span>
              </li>
              <li class="d-flex justify-content-between text-success">
                <span>Discount promoțional</span>
                <span>-50 RON</span>
              </li>
              <li class="d-flex justify-content-between">
                <span>Transport estimat</span>
                <span>25 RON</span>
              </li>
            </ul>
            <hr />
            <div class="d-flex justify-content-between fw-semibold mb-2">
              <span>Total estimat</span>
              <span>725 RON</span>
            </div>
            <div class="text-muted small">
              Reguli precum transport gratuit peste o anumită valoare sau
              condiții speciale pentru B2B (ex: fără plată online, doar OP /
              termen la plată) pot fi modelate pe baza segmentării clientului
              activ.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const router = useRouter()
const authStore = useAuthStore()

const user = computed(() => authStore.user || null)
const isImpersonating = computed(() => !!authStore.impersonatedCustomer)

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

const isB2B = computed(() => frontClientType.value === 'B2B')
const isB2C = computed(() => frontClientType.value === 'B2C')

const currentStep = ref(1)

const billingOptions = [
  { value: 'default', label: 'Adresă standard de facturare' },
  { value: 'new', label: 'Altă adresă (demo)' }
]

const shippingAddressOptions = [
  { value: 'default', label: 'Adresă standard de livrare' },
  { value: 'pickup', label: 'Ridicare din depozit (demo)' }
]

const shippingMethods = [
  {
    value: 'courier',
    label: 'Curier rapid',
    description: 'Livrare prin curier partener, 1–2 zile lucrătoare.'
  },
  {
    value: 'own-fleet',
    label: 'Flota proprie',
    description: 'Disponibilă pentru anumite regiuni și comenzi voluminoase.'
  },
  {
    value: 'pickup',
    label: 'Ridicare din depozit',
    description: 'Clientul ridică marfa direct din depozit.'
  }
]

const paymentOptions = [
  {
    value: 'card',
    label: 'Card online',
    description: 'Procesator plăți, 3D Secure.',
    onlyFor: 'ALL'
  },
  {
    value: 'op',
    label: 'Ordin de plată (OP)',
    description: 'Se emite proformă, plata se face prin transfer bancar.',
    onlyFor: 'ALL'
  },
  {
    value: 'b2b-contract',
    label: 'Condiții contractuale B2B',
    description: 'Termen de plată și limite de credit din contract.',
    onlyFor: 'B2B'
  }
]

const selectedBillingAddress = ref('default')
const selectedShippingAddress = ref('default')
const shippingMethod = ref('courier')
const paymentMethod = ref('card')

const shippingMethodLabel = computed(() => {
  const found = shippingMethods.find(m => m.value === shippingMethod.value)
  return found ? found.label : 'Nespecificat'
})

const paymentMethodLabel = computed(() => {
  const found = paymentOptions.find(p => p.value === paymentMethod.value)
  return found ? found.label : 'Nespecificat'
})

const goToStep = (step) => {
  if (step < 1 || step > 4) return
  currentStep.value = step
}

const stopImpersonation = () => {
  if (authStore.stopImpersonation) {
    authStore.stopImpersonation()
  }
  router.push({ name: 'home' })
}

const placeOrder = () => {
  if (!frontClientType.value) {
    window.alert(
      'Nu există client activ în front. Într-o implementare reală, trebuie să existe un client identificat pentru a putea finaliza comanda.'
    )
    return
  }

  // În acest template nu trimit nimic către backend – doar simulez un succes.
  window.alert(
    `Comanda demo a fost "plasată" în numele clientului ${frontCustomerName.value || '-'} (${frontClientType.value}).`
  )

  router.push({ name: 'account-dashboard' })
}
</script>
