<template>
  <div class="section-html mb-5">
    <div class="container" v-html="data.content"></div>
  </div>
</template>

<script setup>
defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({ content: '' })
  }
});
</script>
