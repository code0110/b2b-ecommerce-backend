<template>
  <div class="section-features mb-5">
    <div class="container">
      <div class="row g-4">
        <div v-for="(item, index) in data.items" :key="index" class="col-md-3 col-sm-6">
          <div class="d-flex align-items-center p-3 border rounded shadow-sm h-100 bg-white">
            <div class="flex-shrink-0 me-3 text-orange">
              <i :class="['bi fs-2', item.icon || 'bi-check-circle']"></i>
            </div>
            <div>
              <h6 class="mb-1 fw-bold">{{ item.title }}</h6>
              <small class="text-muted">{{ item.description }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({ items: [] })
  }
});
</script>
