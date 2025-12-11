<template>
  <div v-if="products.length" class="card shadow-sm mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
      <strong>{{ title }}</strong>
      <RouterLink
        v-if="showCompareShortcut && compareCount > 0"
        :to="{ name: 'compare-products' }"
        class="btn btn-link btn-sm p-0"
      >
        Compară ({{ compareCount }})
      </RouterLink>
    </div>
    <div class="card-body">
      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3">
        <div
          v-for="p in products"
          :key="p.id"
          class="col"
        >
          <div class="border rounded-3 h-100 p-2 small">
            <div class="fw-semibold mb-1">
              <RouterLink
                :to="{ name: 'product-details', params: { slug: 'produs-demo-' + p.id } }"
                class="text-decoration-none"
              >
                {{ p.name }}
              </RouterLink>
            </div>
            <div class="text-muted mb-1">
              {{ p.internalCode }}
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-semibold">
                {{ formatMoney(p.overridePrice || p.listPrice) }}
              </span>
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                @click="$emit('toggle-compare', p.id)"
              >
                Compară
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useProductNavigationStore } from '@/store/productNavigation'

const props = defineProps({
  title: {
    type: String,
    default: 'Recent vizualizate'
  },
  limit: {
    type: Number,
    default: 4
  },
  showCompareShortcut: {
    type: Boolean,
    default: true
  }
})

const navStore = useProductNavigationStore()

const products = computed(() => navStore.recentlyViewedProducts.slice(0, props.limit))
const compareCount = computed(() => navStore.compareIds.length)

const formatMoney = (value) => {
  const v = Number(value || 0)
  return (
    v.toLocaleString('ro-RO', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' RON'
  )
}
</script>
