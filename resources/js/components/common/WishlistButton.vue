<template>
  <button 
    class="btn d-flex align-items-center justify-content-center"
    :class="[
        isActive ? 'btn-danger text-white' : 'bg-white text-secondary',
        round ? 'rounded-circle p-0' : '',
        size ? `btn-${size}` : '',
        customClass
    ]"
    :style="round ? 'width: 32px; height: 32px; z-index: 2;' : ''"
    @click.prevent.stop="toggle"
    :title="isActive ? 'Elimină de la favorite' : 'Adaugă la favorite'"
  >
    <i class="bi" :class="isActive ? 'bi-heart-fill' : 'bi-heart'"></i>
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { useWishlistStore } from '@/store/wishlist';

const props = defineProps({
  product: {
    type: Object,
    required: true
  },
  customClass: {
    type: String,
    default: 'position-absolute top-0 end-0 m-2 shadow-sm'
  },
  round: {
    type: Boolean,
    default: true
  },
  size: {
    type: String,
    default: 'sm'
  }
});

const wishlistStore = useWishlistStore();

const isActive = computed(() => wishlistStore.isFavorite(props.product.id));

const toggle = () => {
  wishlistStore.toggleProduct(props.product);
};
</script>
