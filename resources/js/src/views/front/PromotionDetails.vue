<template>
  <div class="container py-4">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <div v-else-if="error" class="alert alert-warning">
      {{ error }}
      <div class="mt-2">
        <RouterLink to="/promotii" class="btn btn-outline-primary btn-sm">
          Înapoi la promoții
        </RouterLink>
      </div>
    </div>

    <div v-else-if="promotion">
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
              <h2 class="h6 text-uppercase text-muted mb-2">Descriere campanie</h2>
              <p v-if="promotion.description">{{ promotion.description }}</p>
              <p v-else class="text-muted">Fără descriere detaliată.</p>
              
              <p>
                Tip promoție: <strong>{{ promotion.typeLabel }}</strong>
              </p>
            </div>
            <div class="col-md-4">
              <!-- Removed hardcoded conditions for now as they are not in standard API response yet -->
            </div>
          </div>
        </div>
      </div>

      <!-- Produse incluse în promoție -->
      <div class="card">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <strong class="small text-uppercase">Produse incluse în promoție</strong>
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
                    <span class="fw-semibold">
                      {{ product.price.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
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
            <div v-if="!promotion.products || promotion.products.length === 0" class="col-12">
              <div class="alert alert-info small mb-0">
                Pentru această promoție nu sunt afișate produse specifice (se poate aplica pe categorii sau coș).
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { fetchPromotionDetails } from '@/services/promotions'

const route = useRoute()
const slug = computed(() => route.params.slug || '')

const promotion = ref(null)
const loading = ref(false)
const error = ref(null)

const loadData = async () => {
  if (!slug.value) return
  
  loading.value = true
  error.value = null
  promotion.value = null
  
  try {
    const data = await fetchPromotionDetails(slug.value)
    // data structure: { promotion: {...}, products: [...] }
    
    const p = data.promotion
    const products = data.products || []
    
    promotion.value = {
      title: p.name,
      teaser: p.short_description,
      description: p.description,
      status: p.status, // active, upcoming, inactive
      statusLabel: p.status === 'active' ? 'Activă' : (p.status === 'upcoming' ? 'În curând' : 'Inactivă'),
      segment: 'ALL', // API doesn't return raw segment code easily, using Label mainly
      segmentLabel: p.segmentLabel,
      period: p.period,
      typeLabel: p.bonus_type || 'Promoție',
      products: products.map(prod => ({
        slug: prod.slug,
        name: prod.name,
        code: prod.code,
        category: prod.category,
        price: prod.price
      }))
    }
  } catch (err) {
    console.error(err)
    error.value = 'Nu s-a putut încărca promoția. Verificați conexiunea sau adresa URL.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

watch(slug, () => {
  loadData()
})
</script>
