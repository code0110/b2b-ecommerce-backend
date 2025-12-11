<template>
  <div class="container py-4">
    <!-- Header cont -->
    <div class="mb-4">
      <h2 class="mb-1">Dashboard cont client</h2>
      <p class="text-muted mb-2">
        Vizualizezi o versiune demo a secțiunii de self-service pentru clienți B2B/B2C.
      </p>

      <div class="alert alert-info mb-0" v-if="user">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="fw-semibold">
              Autentificat ca:
              <span class="badge bg-light text-dark ms-1">
                {{ user.name }} ({{ user.role || 'fără rol' }})
              </span>
            </div>
            <div class="small text-muted mt-1" v-if="!isImpersonating">
              Lucrezi direct pe contul acestui utilizator.
            </div>
            <div class="small text-muted mt-1" v-else>
              Lucrezi în numele clientului
              <strong>{{ frontCustomerName }}</strong>
              <span v-if="frontClientType" class="ms-1">
                ({{ frontClientType }})
              </span>
              – orice comandă plasată în front va fi considerată a acestui client.
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

      <div class="alert alert-warning mt-3 mb-0" v-if="!frontClientType">
        <strong>Atenție:</strong> În acest moment nu există un client activ în front.
        Pentru a demonstra fluxurile B2B/B2C, autentifică-te ca un client sau
        pornește o impersonare din zona de Admin.
      </div>
    </div>

    <!-- Grid principal de carduri -->
    <div class="row g-3">
      <!-- Comenzi în derulare -->
      <div class="col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong class="small text-uppercase">Comenzi în derulare</strong>
            <span class="badge bg-primary">Demo</span>
          </div>
          <div class="card-body small">
            <p class="text-muted mb-2">
              Această secțiune ar afișa comenzile recente ale clientului activ
              ({{ frontCustomerName || 'fără client activ' }}).
            </p>
            <ul class="list-unstyled mb-0">
              <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                <span>#CMD-1001</span>
                <span class="badge bg-warning text-dark">În curs de procesare</span>
              </li>
              <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                <span>#CMD-0998</span>
                <span class="badge bg-info text-dark">În livrare</span>
              </li>
              <li class="d-flex justify-content-between align-items-center py-1">
                <span>#CMD-0995</span>
                <span class="badge bg-success">Livrată</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Status credit & sold (doar pentru B2B) -->
      <div class="col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong class="small text-uppercase">Status credit & sold</strong>
            <span class="badge" :class="isB2B ? 'bg-success' : 'bg-secondary'">
              {{ isB2B ? 'B2B' : 'Doar pentru B2B' }}
            </span>
          </div>
          <div class="card-body small">
            <div v-if="isB2B">
              <p class="text-muted mb-2">
                Informații afișate pentru clientul B2B activ în front.
              </p>
              <dl class="row mb-0">
                <dt class="col-6">Limită credit</dt>
                <dd class="col-6 text-end">
                  100.000 RON
                </dd>
                <dt class="col-6">Sold curent</dt>
                <dd class="col-6 text-end text-danger">
                  24.350 RON
                </dd>
                <dt class="col-6">Sold restant</dt>
                <dd class="col-6 text-end text-danger">
                  4.350 RON
                </dd>
                <dt class="col-6">Termen plată</dt>
                <dd class="col-6 text-end">
                  30 zile
                </dd>
              </dl>
            </div>
            <div v-else>
              <p class="text-muted mb-0">
                Pentru clienții B2B, aici se afișează soldul, limita de credit și
                termenii de plată, conform datelor din ERP. În prezent, clientul
                activ nu este B2B sau nu este setat.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Promoții personalizate / recomandate -->
      <div class="col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong class="small text-uppercase">Promoții & recomandări</strong>
            <span class="badge bg-light text-dark">
              {{ isB2B ? 'Segment B2B' : isB2C ? 'Segment B2C' : 'Nesegmentat' }}
            </span>
          </div>
          <div class="card-body small">
            <p class="text-muted mb-2">
              Promoțiile afișate sunt filtrate în funcție de tipul clientului activ
              și de eventuale segmentări (grupuri de clienți, listă dedicată etc.).
            </p>
            <ul class="list-unstyled mb-0">
              <li class="mb-2">
                <div class="fw-semibold">Discount volum pentru produse industriale</div>
                <div class="text-muted">
                  Activă pentru clienți B2B din segmentul „Distribuitori”.
                </div>
              </li>
              <li class="mb-2">
                <div class="fw-semibold">Transport gratuit peste 500 RON</div>
                <div class="text-muted">
                  Promoție generică aplicabilă tuturor clienților.
                </div>
              </li>
              <li>
                <div class="fw-semibold">Pachet promoțonal produse noi</div>
                <div class="text-muted">
                  Vizibil în special în zona „Produse noi” din front.
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Extra: rezumat client activ -->
    <div class="card shadow-sm mt-4">
      <div class="card-header py-2">
        <strong class="small text-uppercase">Rezumat client activ în front</strong>
      </div>
      <div class="card-body small">
        <div v-if="!frontClientType">
          <p class="mb-0 text-muted">
            Nu există niciun client activ în front. Autentifică-te ca B2B/B2C sau
            pornește o impersonare din Admin → Clienți → Fișă client.
          </p>
        </div>
        <div v-else>
          <dl class="row mb-0">
            <dt class="col-3">Client activ</dt>
            <dd class="col-9">
              <strong>{{ frontCustomerName }}</strong>
              <span class="badge bg-secondary ms-2">{{ frontClientType }}</span>
            </dd>

            <dt class="col-3">Context</dt>
            <dd class="col-9">
              <span v-if="isImpersonating">
                Lucrezi în modul de impersonare – agent/director/admin plasează
                comenzi în numele acestui client.
              </span>
              <span v-else>
                Client autentificat direct în platformă (fără impersonare).
              </span>
            </dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
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

const stopImpersonation = () => {
  if (authStore.stopImpersonation) {
    authStore.stopImpersonation()
  }
  router.push({ name: 'home' })
}
</script>
