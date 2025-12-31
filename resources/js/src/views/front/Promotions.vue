<template>
  <div class="container">
    <PageHeader
      title="Promoții"
      subtitle="Listare promoții active și în curând, pentru clienți B2B și B2C."
    />

    <!-- Filtre -->
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <form class="row g-3 align-items-end" @submit.prevent>
          <div class="col-md-4">
            <label class="form-label small text-muted">Status</label>
            <select v-model="filters.status" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="active">Promoții active</option>
              <option value="upcoming">Promoții în curând</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted">Tip promoție</label>
            <select v-model="filters.type" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="discount_percent">Discount procentual</option>
              <option value="discount_fixed">Discount valoric</option>
              <option value="x_get_y">Cumperi X → primești Y</option>
              <option value="bundle">Pachet (bundle)</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted">Tip client</label>
            <select v-model="filters.clientType" class="form-select form-select-sm">
              <option value="">B2B & B2C</option>
              <option value="B2B">B2B</option>
              <option value="B2C">B2C</option>
            </select>
          </div>
        </form>
      </div>
    </div>

    <!-- Promoții -->
    <div class="row g-3">
      <div v-if="filteredPromotions.length === 0" class="col-12">
        <div class="card shadow-sm">
          <div class="card-body text-center text-muted">
            Nu există promoții pentru filtrele selectate.
          </div>
        </div>
      </div>

      <div
        v-for="promo in filteredPromotions"
        :key="promo.id"
        class="col-md-6 col-lg-4"
      >
        <div class="card shadow-sm h-100">
          <div
            v-if="promo.images.list"
            class="ratio ratio-16x9 bg-light"
            :style="{ backgroundImage: 'url(' + promo.images.list + ')', backgroundSize: 'cover' }"
          ></div>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">{{ promo.name }}</h5>
            <p class="card-text small text-muted mb-1">
              {{ promo.shortDescription }}
            </p>
            <p class="card-text small text-muted">
              Perioadă: {{ promo.startDate }} – {{ promo.endDate }}
            </p>
            <div class="mb-2">
              <span :class="['badge me-1', statusBadgeClass(promo.status)]">
                {{ statusLabel(promo.status) }}
              </span>
              <span class="badge bg-light text-dark me-1">
                {{ typeLabel(promo.type) }}
              </span>
              <span
                v-for="ct in promo.clientTypes"
                :key="ct"
                class="badge bg-secondary me-1"
              >
                {{ ct }}
              </span>
            </div>
            <div class="mt-auto">
              <RouterLink
                :to="{ name: 'promotion-landing', params: { slug: promo.slug } }"
                class="btn btn-primary btn-sm"
              >
                Vezi detalii
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { fetchPromotions } from '@/services/promotions'

const promotions = ref([])
const loading = ref(true)

const filters = reactive({
  status: '',
  type: '',
  clientType: ''
})

const loadPromotions = async () => {
  loading.value = true
  try {
    const response = await fetchPromotions({ scope: 'all' })
    promotions.value = (response.data || []).map(p => ({
      id: p.id,
      slug: p.slug,
      name: p.name,
      shortDescription: p.short_description,
      startDate: p.start_at,
      endDate: p.end_at,
      status: p.status,
      type: p.discount_type || 'discount_percent',
      clientTypes: p.customer_type === 'both' ? ['B2B', 'B2C'] : [p.customer_type?.toUpperCase() || 'ALL'],
      images: {
        list: p.hero_image
      }
    }))
  } catch (error) {
    console.error('Failed to load promotions:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPromotions()
})

const filteredPromotions = computed(() => {
  return promotions.value.filter((p) => {
    if (filters.status && p.status !== filters.status) return false
    if (filters.type && p.type !== filters.type) return false
    if (filters.clientType && !p.clientTypes.includes(filters.clientType)) return false
    return true
  })
})

const statusLabel = (s) => {
  switch (s) {
    case 'active':
      return 'Activă'
    case 'upcoming':
      return 'În curând'
    case 'inactive':
      return 'Inactivă'
    default:
      return s
  }
}

const statusBadgeClass = (s) => {
  switch (s) {
    case 'active':
      return 'bg-success'
    case 'upcoming':
      return 'bg-info text-dark'
    case 'inactive':
      return 'bg-secondary'
    default:
      return 'bg-secondary'
  }
}

const typeLabel = (t) => {
  switch (t) {
    case 'discount_percent':
      return 'Discount %'
    case 'discount_fixed':
      return 'Discount fix'
    case 'x_get_y':
      return 'X → Y'
    case 'bundle':
      return 'Pachet'
    default:
      return t
  }
}
</script>
