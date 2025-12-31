<template>
  <div class="container" v-if="promotion">
    <div class="card shadow-sm mb-3 border-0">
      <div
        v-if="promotion.images.header"
        class="ratio ratio-21x9 rounded-top"
        :style="{ backgroundImage: 'url(' + promotion.images.header + ')', backgroundSize: 'cover', backgroundPosition: 'center' }"
      ></div>
      <div class="card-body">
        <h1 class="h3 mb-1">
          {{ promotion.landingTitle || promotion.name }}
        </h1>
        <p v-if="promotion.landingSubtitle" class="text-muted mb-2">
          {{ promotion.landingSubtitle }}
        </p>
        <p class="small text-muted mb-2">
          Perioadă promoție: <strong>{{ promotion.startDate }} – {{ promotion.endDate }}</strong>
        </p>
        <div class="mb-2">
          <span :class="['badge me-1', statusBadgeClass(promotion.status)]">
            {{ statusLabel(promotion.status) }}
          </span>
          <span class="badge bg-light text-dark me-1">
            {{ typeLabel(promotion.type) }}
          </span>
          <span
            v-for="ct in promotion.clientTypes"
            :key="ct"
            class="badge bg-secondary me-1"
          >
            {{ ct }}
          </span>
        </div>

        <div
          v-if="promotion.longDescription"
          class="mt-3 small"
          v-html="promotion.longDescription"
        ></div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-4">
        <!-- Condiții promoție -->
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Condiții promoție</strong>
          </div>
          <div class="card-body small">
            <p class="mb-1">
              <strong>Cantitate minimă per produs:</strong>
              <span v-if="promotion.trigger.minQtyPerProduct">
                {{ promotion.trigger.minQtyPerProduct }}
              </span>
              <span v-else>nu este impusă</span>
            </p>
            <p class="mb-1">
              <strong>Valoare minimă coș:</strong>
              <span v-if="promotion.trigger.minCartValue">
                {{ promotion.trigger.minCartValue }} RON
              </span>
              <span v-else>nu este impusă</span>
            </p>
            <p v-if="promotion.trigger.notes" class="mb-1">
              <strong>Note:</strong> {{ promotion.trigger.notes }}
            </p>
            <p class="mb-1">
              <strong>Segmentare:</strong>
            </p>
            <ul class="mb-0">
              <li>
                Tip client:
                <span v-for="ct in promotion.clientTypes" :key="ct" class="badge bg-light text-dark me-1">
                  {{ ct }}
                </span>
              </li>
              <li v-if="promotion.customerGroups && promotion.customerGroups.length">
                Grupuri clienți:
                <span
                  v-for="g in promotion.customerGroups"
                  :key="g"
                  class="badge bg-secondary me-1"
                >
                  {{ g }}
                </span>
              </li>
              <li v-if="promotion.categories && promotion.categories.length">
                Categorii produse:
                <span
                  v-for="c in promotion.categories"
                  :key="c"
                  class="badge bg-info text-dark me-1"
                >
                  {{ c }}
                </span>
              </li>
              <li v-if="promotion.brands && promotion.brands.length">
                Branduri:
                <span
                  v-for="b in promotion.brands"
                  :key="b"
                  class="badge bg-warning text-dark me-1"
                >
                  {{ b }}
                </span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Beneficiu -->
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Beneficii</strong>
          </div>
          <div class="card-body small">
            <p v-if="promotion.benefit.discountPercent">
              Discount: <strong>{{ promotion.benefit.discountPercent }}%</strong>
            </p>
            <p v-if="promotion.benefit.discountValue">
              Discount fix: <strong>{{ promotion.benefit.discountValue }} RON</strong>
            </p>
            <p v-if="promotion.benefit.freeProductCode">
              Produs gratuit: <strong>{{ promotion.benefit.freeProductCode }}</strong>
            </p>
            <p v-if="promotion.benefit.specialPriceCode">
              Produs cu preț special: <strong>{{ promotion.benefit.specialPriceCode }}</strong>
            </p>
            <p v-if="!promotion.benefit.discountPercent && !promotion.benefit.discountValue && !promotion.benefit.freeProductCode && !promotion.benefit.specialPriceCode" class="mb-0">
              Beneficiile vor fi afișate aici, în funcție de regula promoției.
            </p>
          </div>
        </div>
      </div>

      <!-- Produse incluse -->
      <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Produse incluse în promoție</strong>
            <span v-if="promotion.landingShowFilters" class="small text-muted">
              (aici pot fi adăugate filtre: brand, preț, atribute etc.)
            </span>
          </div>
          <div class="card-body">
            <p class="small text-muted" v-if="!promotion.productList || promotion.productList.length === 0">
              În implementarea finală, aici vei lista produsele incluse în promoție (printr-un API sau filtrare de catalog).
            </p>
            <ul v-else class="small mb-0">
              <li v-for="code in promotion.productList" :key="code">
                Produs cu cod promoțional: <strong>{{ code }}</strong>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="container">
    <div class="card shadow-sm">
      <div class="card-body text-center text-muted">
        Promoția solicitată nu a fost găsită.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { fetchPromotionDetails } from '@/services/promotions'

const route = useRoute()
const loading = ref(true)
const promotion = ref(null)

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
    case 'discount_value':
      return 'Discount fix'
    case 'free_item':
      return 'Produs gratuit'
    case 'bundle':
      return 'Pachet'
    default:
      return t
  }
}

onMounted(async () => {
  try {
    const data = await fetchPromotionDetails(route.params.slug)
    const p = data.promotion
    const products = data.products || []

    promotion.value = {
      landingTitle: p.name,
      landingSubtitle: p.short_description,
      startDate: p.start_at ? p.start_at.substring(0, 10) : '',
      endDate: p.end_at ? p.end_at.substring(0, 10) : '',
      status: p.status,
      type: p.bonus_type,
      clientTypes: p.customer_type === 'both' ? ['B2B', 'B2C'] : [p.customer_type?.toUpperCase() || 'ALL'],
      longDescription: p.description,
      images: {
        header: p.banner_image || p.hero_image
      },
      trigger: {
        minQtyPerProduct: p.min_qty_per_product > 0 ? p.min_qty_per_product : null,
        minCartValue: parseFloat(p.min_cart_total) > 0 ? parseFloat(p.min_cart_total) : null,
        notes: null
      },
      benefit: {
        discountPercent: p.discount_percent ? parseFloat(p.discount_percent) : null,
        discountValue: p.discount_value ? parseFloat(p.discount_value) : null,
        freeProductCode: null,
        specialPriceCode: null
      },
      productList: products.map(prod => prod.internal_code),
      customerGroups: (p.customer_groups || []).map(g => g.name),
      categories: (p.categories || []).map(c => c.name),
      brands: (p.brands || []).map(b => b.name)
    }
  } catch (error) {
    console.error('Failed to load promotion details:', error)
    promotion.value = null
  } finally {
    loading.value = false
  }
})
</script>
