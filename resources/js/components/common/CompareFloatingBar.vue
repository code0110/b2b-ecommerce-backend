<template>
  <div 
    v-if="compareStore.count > 0 && show"
    class="fixed-bottom bg-white border-top shadow-lg p-3"
    style="z-index: 1040;"
  >
    <div class="container">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold">{{ compareStore.count }} produse selectate</h6>
                <small class="text-muted">pentru comparare</small>
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-3">
             <!-- Preview Thumbs (Hidden on mobile) -->
             <div class="d-none d-md-flex gap-2">
                <div 
                    v-for="item in compareStore.items.slice(0, 4)" 
                    :key="item.id"
                    class="border rounded bg-light"
                    style="width: 40px; height: 40px; overflow: hidden;"
                >
                    <img :src="item.main_image_url" class="w-100 h-100 object-fit-cover" :alt="item.name">
                </div>
             </div>

             <div class="vr mx-2"></div>

             <button class="btn btn-link text-muted text-decoration-none" @click="compareStore.clearCompare">
                Golește
             </button>
             
             <RouterLink to="/comparare" class="btn btn-primary px-4">
                Compară acum
             </RouterLink>
             
             <button class="btn-close ms-2" @click="show = false"></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useCompareStore } from '@/store/compare';
import { useRoute } from 'vue-router';

const compareStore = useCompareStore();
const route = useRoute();
const show = ref(true);

// Hide bar when on compare page
watch(() => route.path, (path) => {
    if (path === '/comparare') {
        show.value = false;
    } else {
        show.value = true;
    }
}, { immediate: true });

// Show again if items added
watch(() => compareStore.count, (newCount, oldCount) => {
    if (newCount > oldCount && route.path !== '/comparare') {
        show.value = true;
    }
});
</script>
