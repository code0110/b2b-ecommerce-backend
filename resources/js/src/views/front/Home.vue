<template>
  <div v-if="pageContent" class="bg-light border-bottom py-4 mb-4">
    <div class="container" v-html="pageContent.content"></div>
  </div>

  <div v-else class="bg-light border-bottom py-4 mb-4">
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
import { ref, onMounted } from 'vue'
import { fetchHomeData } from '@/services/catalog'
import { fetchStaticPage } from '@/services/content'

const homePromotions = ref([])
const newProducts = ref([])
const recommendedProducts = ref([])
const loading = ref(true)
const pageContent = ref(null)

onMounted(async () => {
  try {
    // Încercăm să încărcăm conținutul paginii "home"
    try {
      pageContent.value = await fetchStaticPage('home')
    } catch (e) {
      // Ignorăm eroarea dacă pagina nu există, folosim fallback-ul
    }

    const data = await fetchHomeData()
    
    // Map API data to view structure
    homePromotions.value = (data.promotions || []).map(p => ({
      slug: p.slug,
      title: p.name,
      teaser: p.short_description || p.description,
      badge: p.customer_type === 'b2b' ? 'B2B' : (p.customer_type === 'b2c' ? 'B2C' : 'ALL'),
      period: 'Activă',
      segmentLabel: p.customer_type === 'b2b' ? 'B2B' : (p.customer_type === 'b2c' ? 'B2C' : 'B2B & B2C')
    }))

    newProducts.value = (data.new_products || []).map(p => ({
      slug: p.slug,
      name: p.name,
      code: p.internal_code,
      category: '', // API doesn't return category relationship in home()
      price: Number(p.list_price)
    }))

    recommendedProducts.value = (data.recommended || []).map(p => ({
      slug: p.slug,
      name: p.name,
      code: p.internal_code,
      category: '',
      price: Number(p.list_price),
      hasDiscount: false, // Simple mapping
      discountPercent: 0,
      promoPrice: Number(p.list_price)
    }))

  } catch (error) {
    console.error('Failed to load home data:', error)
  } finally {
    loading.value = false
  }
})
</script>
