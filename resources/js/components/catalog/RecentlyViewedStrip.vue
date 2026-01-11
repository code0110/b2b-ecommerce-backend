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
          <ProductCard :product="p" :compact="true" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useProductNavigationStore } from '@/store/productNavigation'
import ProductCard from '@/components/common/ProductCard.vue'

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
// const compareCount = computed(() => navStore.compareIds.length) // No longer needed from navStore if we use CompareStore globally

</script>
