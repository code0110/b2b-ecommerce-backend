<template>
  <div class="container py-4" v-if="promotion">
    <nav aria-label="breadcrumb" class="small mb-3">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <RouterLink to="/">Acasă</RouterLink>
        </li>
        <li class="breadcrumb-item">
          <RouterLink to="/promotii">Promoții</RouterLink>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
          {{ promotion.title }}
        </li>
      </ol>
    </nav>

    <div class="card mb-3 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <h1 class="h4 mb-1">{{ promotion.title }}</h1>
            <p class="small text-muted mb-0">
              {{ promotion.teaser }}
            </p>
          </div>
          <div class="text-end small">
            <div class="mb-1">
              <span
                class="badge"
                :class="{
                  'bg-success': promotion.status === 'active',
                  'bg-warning text-dark': promotion.status === 'upcoming',
                  'bg-secondary': promotion.status === 'expired'
                }"
              >
                {{ promotion.statusLabel }}
              </span>
              <span
                class="badge ms-1"
                :class="promotion.segment === 'B2B' ? 'bg-primary' : promotion.segment === 'B2C' ? 'bg-info text-dark' : 'bg-dark'"
              >
                {{ promotion.segmentLabel }}
              </span>
            </div>
            <div>
              <strong>Perioadă:</strong> {{ promotion.period }}
            </div>
          </div>
        </div>
        <hr />
        <div class="row small">
          <div class="col-md-8">
            <h2 class="h6 text-uppercase text-muted mb-2">Descriere campanie (demo)</h2>
            <p>
              Aceasta este o descriere exemplificativă pentru campania promoțională. În implementarea reală,
              conținutul ar putea fi editat din backend de către marketer și optimizat SEO.
            </p>
            <p>
              Tip promoție: <strong>{{ promotion.typeLabel }}</strong>
            </p>
            <ul>
              <li>Condiții de aplicare configurabile pe categorii / branduri / liste de produse;</li>
              <li>Segmentare pe B2B / B2C / clienți individuali sau grupuri;</li>
              <li>Posibilitatea de a defini promoții exclusive sau cumulative.</li>
            </ul>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-3 bg-light h-100">
              <h3 class="h6 text-uppercase text-muted mb-2">Condiții principale (demo)</h3>
              <ul class="small mb-0">
                <li v-if="promotion.minOrderValue">
                  Valoare minimă coș: {{ promotion.minOrderValue.toLocaleString('ro-RO') }} RON
                </li>
                <li v-if="promotion.minQuantityPerLine">
                  Cantitate minimă pe linie: {{ promotion.minQuantityPerLine }} buc
                </li>
                <li>
                  Se aplică pe categoriile: {{ promotion.categoriesLabel }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Produse incluse în promoție -->
    <div class="card">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong class="small text-uppercase">Produse incluse în promoție (demo)</strong>
        <RouterLink to="/reduceri" class="btn btn-link btn-sm text-decoration-none">
          Vezi toate produsele aflate în promoții →
        </RouterLink>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div
            v-for="product in promotion.products"
            :key="product.slug"
            class="col-md-3 col-sm-6"
          >
            <div class="card h-100">
              <div class="card-body d-flex flex-column small">
                <div class="text-muted mb-1">{{ product.category }}</div>
                <h3 class="h6 mb-1">{{ product.name }}</h3>
                <div class="text-muted mb-2">{{ product.code }}</div>
                <div class="mb-2">
                  <span class="text-muted text-decoration-line-through me-1">
                    {{ product.price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                  </span>
                  <span class="fw-semibold">
                    {{ product.promoPrice.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                    RON
                  </span>
                </div>
                <div class="mt-auto">
                  <RouterLink
                    :to="`/produs/${product.slug}`"
                    class="btn btn-outline-primary btn-sm w-100"
                  >
                    Detalii produs
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>
          <div v-if="promotion.products.length === 0" class="col-12">
            <div class="alert alert-info small mb-0">
              Pentru această promoție nu sunt definite produse demo.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="container py-4">
    <div class="alert alert-warning">
      Promoția nu a fost găsită în setul de date demo.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const slug = computed(() => route.params.slug || '')

const promotions = [
  {
    slug: 'campanie-primavara-b2b',
    title: 'Campanie de primăvară B2B',
    teaser: 'Discount suplimentar la materiale de construcții pentru comenzi peste 10.000 RON.',
    period: '01.03 – 30.04',
    segment: 'B2B',
    segmentLabel: 'B2B',
    status: 'active',
    statusLabel: 'Activă',
    typeLabel: 'Discount procentual pe coș',
    minOrderValue: 10000,
    minQuantityPerLine: null,
    categoriesLabel: 'Materiale de construcții',
    products: [
      {
        slug: 'ciment-portland-40kg',
        name: 'Ciment Portland 40kg',
        code: 'PRD-001',
        category: 'Materiale de construcții',
        price: 45.0,
        promoPrice: 40.5
      }
    ]
  },
  {
    slug: 'weekend-special-b2c',
    title: 'Weekend special B2C',
    teaser: 'Reduceri pentru clienți persoane fizice la produse de bricolaj și grădină.',
    period: 'În fiecare weekend',
    segment: 'B2C',
    segmentLabel: 'B2C',
    status: 'upcoming',
    statusLabel: 'În curând',
    typeLabel: 'Discount valoric per produs',
    minOrderValue: null,
    minQuantityPerLine: 3,
    categoriesLabel: 'Bricolaj, grădinărit',
    products: []
  },
  {
    slug: 'pachet-santier-start',
    title: 'Pachet Șantier Start',
    teaser: 'Pachet promoțional de produse pentru deschiderea unui șantier nou.',
    period: '01.02 – 31.05',
    segment: 'ALL',
    segmentLabel: 'B2B & B2C',
    status: 'active',
    statusLabel: 'Activă',
    typeLabel: 'Pachete produse (bundle)',
    minOrderValue: 5000,
    minQuantityPerLine: null,
    categoriesLabel: 'Echipamente șantier, materiale',
    products: []
  }
]

const promotion = computed(() => promotions.find((p) => p.slug === slug.value) || null)
</script>
