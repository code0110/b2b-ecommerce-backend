<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Promoții active & în curând</h1>
        <p class="text-muted small mb-0">
          Campaniile promoționale active în acest moment.
        </p>
      </div>
      <div class="small text-muted" v-if="!loading">
        {{ filteredPromotions.length }} promoții
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body small">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Caută promoție</label>
            <input
              type="text"
              class="form-control form-control-sm"
              placeholder="denumire promoție..."
              v-model="filters.search"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label">Segment</label>
            <select
              class="form-select form-select-sm"
              v-model="filters.segment"
            >
              <option value="">Toate</option>
              <option value="B2B">B2B</option>
              <option value="B2C">B2C</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Status (Scope)</label>
            <select
              class="form-select form-select-sm"
              v-model="filters.scope"
              @change="loadPromotions"
            >
              <option value="current">Active acum</option>
              <option value="upcoming">În curând</option>
              <option value="all">Toate</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-else class="row g-3">
      <div
        v-for="promo in filteredPromotions"
        :key="promo.slug"
        class="col-md-4"
      >
        <div class="card h-100 shadow-sm">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <span
                class="badge"
                :class="{
                  'bg-success': promo.status === 'active',
                  'bg-warning text-dark': promo.status === 'upcoming',
                  'bg-secondary': promo.status === 'inactive'
                }"
              >
                {{ promo.status === 'active' ? 'Activă' : promo.status }}
              </span>
              <span
                class="badge"
                :class="promo.segmentLabel.includes('B2B') ? 'bg-primary' : (promo.segmentLabel.includes('B2C') ? 'bg-info text-dark' : 'bg-dark')"
              >
                {{ promo.segmentLabel }}
              </span>
            </div>
            <h2 class="h6 mb-1">{{ promo.title }}</h2>
            <p class="small text-muted mb-2">
              {{ promo.teaser }}
            </p>
            <p class="small mb-1">
              <strong>Perioadă:</strong>
              {{ promo.period }}
            </p>
            <p class="small mb-3 text-muted">
              {{ promo.badge }}
            </p>
            <div class="mt-auto d-flex justify-content-between align-items-center">
              <RouterLink
                :to="`/promotii/${promo.slug}`"
                class="btn btn-outline-primary btn-sm"
              >
                Detalii
              </RouterLink>
              <RouterLink
                to="/reduceri"
                class="btn btn-link btn-sm text-decoration-none"
              >
                Vezi produse →
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
      <div v-if="filteredPromotions.length === 0" class="col-12">
        <div class="alert alert-info small mb-0">
          Nu sunt promoții care să corespundă filtrării curente.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, ref, onMounted } from 'vue'
import { fetchPromotions } from '@/services/promotions'

const promotions = ref([])
const loading = ref(false)
const error = ref(null)

const filters = reactive({
  search: '',
  segment: '',
  scope: 'current'
})

const loadPromotions = async () => {
  loading.value = true
  error.value = null
  try {
    // Pass scope to API
    const response = await fetchPromotions({ 
        scope: filters.scope 
    })
    
    // API returns { data: [...], meta: {...} }
    const data = response.data || []
    
    promotions.value = data.map(p => ({
        slug: p.slug,
        title: p.name,
        teaser: p.short_description,
        status: p.status, // 'active', etc.
        period: p.period,
        segmentLabel: p.segmentLabel,
        badge: p.badge
    }))
    
  } catch (err) {
    console.error(err)
    error.value = "Eroare la încărcarea promoțiilor."
  } finally {
    loading.value = false
  }
}

const filteredPromotions = computed(() => {
  return promotions.value.filter((p) => {
    const matchesSearch =
      !filters.search ||
      (p.title && p.title.toLowerCase().includes(filters.search.toLowerCase())) ||
      (p.teaser && p.teaser.toLowerCase().includes(filters.search.toLowerCase()))

    const matchesSegment = !filters.segment || (p.segmentLabel && p.segmentLabel.includes(filters.segment))

    return matchesSearch && matchesSegment
  })
})

onMounted(() => {
  loadPromotions()
})
</script>
