<template>
  <button 
    class="btn d-flex align-items-center justify-content-center" 
    :class="[
      active ? 'btn-primary text-white' : 'bg-white text-secondary',
      round ? 'rounded-circle p-0' : '',
      size ? `btn-${size}` : '',
      customClass
    ]"
    :style="round ? 'width: 32px; height: 32px; z-index: 2;' : ''"
    @click.stop="toggle"
    :title="active ? 'Elimină din comparare' : 'Adaugă la comparare'"
  >
    <i class="bi bi-arrow-left-right"></i>
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { useCompareStore } from '@/store/compare';

const props = defineProps({
  product: {
    type: Object,
    required: true
  },
  customClass: {
    type: String,
    default: ''
  },
  round: {
    type: Boolean,
    default: false
  },
  size: {
    type: String,
    default: 'sm'
  }
});

const compareStore = useCompareStore();

const active = computed(() => compareStore.isInCompare(props.product.id));

const toggle = () => {
  if (active.value) {
    compareStore.removeFromCompare(props.product.id);
  } else {
    compareStore.addToCompare(props.product);
  }
};
</script>
