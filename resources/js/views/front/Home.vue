<template>
  <div class="bg-light border-bottom py-4 mb-4">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-7">
          <h1 class="h3 mb-2">Platformă e-commerce B2B / B2C (demo)</h1>
          <p class="text-muted mb-3">
            Exemplu de homepage cu secțiuni de promoții, produse noi și recomandări,
            gândit pentru clienți finali și parteneri B2B.
          </p>
          <div class="d-flex flex-wrap gap-2">
            <RouterLink to="/promotii" class="btn btn-primary btn-sm">
              Vezi promoțiile active
            </RouterLink>
            <RouterLink to="/devino-partener" class="btn btn-outline-primary btn-sm">
              Devino partener B2B
            </RouterLink>
            <RouterLink to="/reprezentanti-vanzari" class="btn btn-outline-secondary btn-sm">
              Găsește un reprezentant
            </RouterLink>
          </div>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0 small">
          <div class="border rounded p-3 bg-white shadow-sm">
            <div class="fw-semibold mb-1">Segmentare demo</div>
            <ul class="mb-0 text-muted ps-3">
              <li>Clienți B2C – retail, persoane fizice;</li>
              <li>Clienți B2B – companii cu termene de plată și limită de credit;</li>
              <li>Agenți / directori – pot lucra în numele clienților.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container mb-4">
    <!-- Secțiune promoții -->
    <section class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 mb-0">Promoții</h2>
        <RouterLink to="/promotii" class="btn btn-link btn-sm text-decoration-none">
          Vezi toate promoțiile →
        </RouterLink>
      </div>
      <div class="row g-3">
        <div
          v-for="promo in homePromotions"
          :key="promo.slug"
          class="col-md-4"
        >
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <span class="badge bg-danger mb-2">{{ promo.badge }}</span>
              <h3 class="h6">{{ promo.title }}</h3>
              <p class="small text-muted mb-2">{{ promo.teaser }}</p>
              <p class="small mb-1">
                <strong>Perioadă:</strong>
                {{ promo.period }}
              </p>
              <p class="small text-muted mb-3">
                Segment: {{ promo.segmentLabel }}
              </p>
              <RouterLink
                :to="`/promotii/${promo.slug}`"
                class="btn btn-outline-primary btn-sm"
              >
                Detalii promoție
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Secțiune produse noi -->
    <section class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 mb-0">Produse noi</h2>
        <RouterLink to="/noutati" class="btn btn-link btn-sm text-decoration-none">
          Vezi toate noutățile →
        </RouterLink>
      </div>
      <div class="row g-3">
        <div
          v-for="product in newProducts"
          :key="product.slug"
          class="col-md-3 col-sm-6"
        >
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <div class="small text-muted mb-1">{{ product.category }}</div>
              <h3 class="h6 mb-1">{{ product.name }}</h3>
              <div class="small text-muted mb-2">{{ product.code }}</div>
              <div class="mt-auto">
                <div class="fw-semibold mb-1">
                  {{ product.price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                  RON
                </div>
                <RouterLink
                  :to="`/produs/${product.slug}`"
                  class="btn btn-outline-secondary btn-sm"
                >
                  Detalii produs
                </RouterLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Secțiune recomandate -->
    <section class="mb-5">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 mb-0">Produse recomandate</h2>
        <RouterLink to="/reduceri" class="btn btn-link btn-sm text-decoration-none">
          Vezi toate produsele în promoție →
        </RouterLink>
      </div>
      <div class="row g-3">
        <div
          v-for="product in recommendedProducts"
          :key="product.slug"
          class="col-md-3 col-sm-6"
        >
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="small text-muted">{{ product.category }}</div>
                <span
                  v-if="product.hasDiscount"
                  class="badge bg-success"
                >
                  -{{ product.discountPercent }}%
                </span>
              </div>
              <h3 class="h6 mb-1">{{ product.name }}</h3>
              <div class="small text-muted mb-2">{{ product.code }}</div>
              <div class="mt-auto">
                <div class="small text-muted" v-if="product.hasDiscount">
                  <span class="text-decoration-line-through me-1">
                    {{ product.price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                  </span>
                  <span class="fw-semibold">
                    {{ product.promoPrice.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                    RON
                  </span>
                </div>
                <div class="fw-semibold mb-1" v-else>
                  {{ product.price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                  RON
                </div>
                <RouterLink
                  :to="`/produs/${product.slug}`"
                  class="btn btn-outline-primary btn-sm"
                >
                  Detalii produs
                </RouterLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
const homePromotions = [
  {
    slug: 'campanie-primavara-b2b',
    title: 'Campanie de primăvară pentru parteneri B2B',
    teaser: 'Discount suplimentar la materiale de construcții pentru comenzi peste 10.000 RON.',
    badge: 'B2B',
    period: '01.03 – 30.04',
    segmentLabel: 'Clienți B2B (parteneri)'
  },
  {
    slug: 'weekend-special-b2c',
    title: 'Weekend special B2C',
    teaser: 'Reduceri pentru clienți persoane fizice la produse de bricolaj și grădină.',
    badge: 'B2C',
    period: 'În fiecare weekend',
    segmentLabel: 'Clienți B2C (retail)'
  },
  {
    slug: 'transport-gratuit-combinat',
    title: 'Transport gratuit combinat',
    teaser: 'Transport gratuit pentru comenzi mixte B2B/B2C peste 500 RON.',
    badge: 'ALL',
    period: '01.02 – 31.05',
    segmentLabel: 'Toți clienții (B2B & B2C)'
  }
]

const newProducts = [
  {
    slug: 'ciment-premium-42-5',
    name: 'Ciment Premium 42.5',
    code: 'PRD-NEW-001',
    category: 'Materiale de construcții',
    price: 48.5
  },
  {
    slug: 'vopsea-lavabila-interior',
    name: 'Vopsea lavabilă interior 15L',
    code: 'PRD-NEW-002',
    category: 'Finisaje',
    price: 210.0
  },
  {
    slug: 'bormasina-compacta',
    name: 'Bormașină compactă 18V',
    code: 'PRD-NEW-003',
    category: 'Unelte electrice',
    price: 399.9
  },
  {
    slug: 'sistem-scaffolding-aluminiu',
    name: 'Sistem schelă aluminiu',
    code: 'PRD-NEW-004',
    category: 'Echipamente șantier',
    price: 2850.0
  }
]

const recommendedProducts = [
  {
    slug: 'ciment-portland-40kg',
    name: 'Ciment Portland 40kg',
    code: 'PRD-001',
    category: 'Materiale de construcții',
    price: 45.0,
    hasDiscount: true,
    discountPercent: 10,
    promoPrice: 40.5
  },
  {
    slug: 'adeziv-gresie-faianta',
    name: 'Adeziv gresie / faianță',
    code: 'PRD-005',
    category: 'Adezivi',
    price: 35.0,
    hasDiscount: false
  },
  {
    slug: 'pavaj-beton',
    name: 'Pavaj beton 20x10',
    code: 'PRD-020',
    category: 'Pavaje',
    price: 3.2,
    hasDiscount: true,
    discountPercent: 5,
    promoPrice: 3.04
  },
  {
    slug: 'echipament-protectie-kit',
    name: 'Kit echipament protecție',
    code: 'PRD-030',
    category: 'Echipamente protecție',
    price: 150.0,
    hasDiscount: false
  }
]
</script>
