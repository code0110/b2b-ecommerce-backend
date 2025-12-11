<template>
  <div class="container py-4">
    <h2 class="mb-1">Promoții & oferte personalizate</h2>
    <p class="text-muted mb-3">
      În această secțiune se afișează promoțiile active pentru clientul curent,
      ținând cont de tip (B2B/B2C), grupuri de clienți și eventuale oferte dedicate.
    </p>

    <div class="alert alert-info small" v-if="frontClientType">
      <div>
        Client activ:
        <strong>{{ frontCustomerName }}</strong>
        <span class="badge bg-secondary ms-2">{{ frontClientType }}</span>
      </div>
      <div class="mt-1" v-if="isImpersonating">
        Lucrezi în modul de impersonare – promoțiile afișate sunt cele ale
        clientului în numele căruia lucrezi.
      </div>
      <div class="mt-1 text-muted" v-else>
        Client autentificat direct în platformă.
      </div>
    </div>

    <div class="alert alert-warning small" v-else>
      Nu există un client activ în front. Autentifică-te ca B2B/B2C sau
      pornește o impersonare din zona de administrare.
    </div>

    <div class="row g-3 mt-3">
      <div class="col-md-6 col-lg-4" v-for="offer in visibleOffers" :key="offer.id">
        <div class="card h-100 shadow-sm">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title mb-1">{{ offer.title }}</h5>
            <h6 class="card-subtitle mb-2 text-muted small">
              {{ offer.segmentLabel }}
            </h6>
            <p class="card-text small flex-grow-1">
              {{ offer.description }}
            </p>
            <div class="mt-2 small text-muted">
              Perioadă:
              <strong>{{ offer.period }}</strong>
            </div>
            <div class="mt-2">
              <span
                v-for="tag in offer.tags"
                :key="tag"
                class="badge bg-light text-dark me-1 mb-1"
              >
                {{ tag }}
              </span>
            </div>
          </div>
        </div>
      </div>
      <div v-if="!visibleOffers.length" class="col-12">
        <div class="alert alert-light border small mb-0">
          Nu există oferte demo de afișat pentru segmentul curent.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/store/auth'

const authStore = useAuthStore()

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

// Demo static offers
const allOffers = [
  {
    id: 1,
    title: 'Discount volum pentru materiale de construcții',
    segment: 'B2B',
    segmentLabel: 'B2B – Distribuitori & Retaileri',
    description:
      'Pentru comenzi peste 10.000 RON pe lună, se acordă un discount suplimentar de 3% pe întregul portofoliu de produse eligibile.',
    period: '01.01 – 31.03',
    tags: ['discount procentual', 'B2B', 'volum']
  },
  {
    id: 2,
    title: 'Transport gratuit peste 500 RON',
    segment: 'ALL',
    segmentLabel: 'B2B & B2C',
    description:
      'Pentru orice comandă cu valoare totală peste 500 RON, transportul este gratuit prin curier partener.',
    period: 'campanie continuă',
    tags: ['transport gratuit', 'B2B', 'B2C']
  },
  {
    id: 3,
    title: 'Pachet promoțional unelte DIY',
    segment: 'B2C',
    segmentLabel: 'B2C – Clienți finali',
    description:
      'La achiziția a oricăror 3 produse din gama DIY, primești 15% discount pe produsul cu valoarea cea mai mică.',
    period: '15.02 – 15.04',
    tags: ['pachet promoțional', 'B2C']
  }
]

const visibleOffers = computed(() => {
  if (!frontClientType.value) {
    return []
  }
  if (isB2B.value) {
    return allOffers.filter(o => o.segment === 'B2B' || o.segment === 'ALL')
  }
  if (isB2C.value) {
    return allOffers.filter(o => o.segment === 'B2C' || o.segment === 'ALL')
  }
  return allOffers.filter(o => o.segment === 'ALL')
})
</script>
