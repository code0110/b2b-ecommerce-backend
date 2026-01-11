<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Promoții active & în curând</h1>
        <p class="text-muted small mb-0">
          Pagină demo de listare a campaniilor promoționale, segmentate pe B2B / B2C.
        </p>
      </div>
      <div class="small text-muted">
        {{ filteredPromotions.length }} promoții demo
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
              <option value="ALL">B2B & B2C</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select
              class="form-select form-select-sm"
              v-model="filters.status"
            >
              <option value="">Toate</option>
              <option value="active">Active</option>
              <option value="upcoming">În curând</option>
              <option value="expired">Expirate</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3">
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
                  'bg-secondary': promo.status === 'expired'
                }"
              >
                {{ promo.statusLabel }}
              </span>
              <span
                class="badge"
                :class="promo.segment === 'B2B' ? 'bg-dd-blue' : promo.segment === 'B2C' ? 'bg-orange text-white' : 'bg-dark'"
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
              Tip promoție: {{ promo.typeLabel }}
            </p>
            <div class="mt-auto d-flex justify-content-between align-items-center">
              <RouterLink
                :to="`/promotii/${promo.slug}`"
                class="btn btn-outline-secondary btn-sm"
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
import { reactive, computed } from 'vue'

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
    typeLabel: 'Discount procentual pe coș'
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
    typeLabel: 'Discount valoric per produs'
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
    typeLabel: 'Pachete produse (bundle)'
  }
]

const filters = reactive({
  search: '',
  segment: '',
  status: ''
})

const filteredPromotions = computed(() => {
  return promotions.filter((p) => {
    const matchesSearch =
      !filters.search ||
      p.title.toLowerCase().includes(filters.search.toLowerCase()) ||
      p.teaser.toLowerCase().includes(filters.search.toLowerCase())

    const matchesSegment = !filters.segment || p.segment === filters.segment
    const matchesStatus = !filters.status || p.status === filters.status

    return matchesSearch && matchesSegment && matchesStatus
  })
})
</script>
