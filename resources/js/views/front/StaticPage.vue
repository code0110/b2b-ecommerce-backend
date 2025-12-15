<template>
  <div class="container py-4">
    <div v-if="loading" class="text-center py-5 text-muted">
      Se încarcă pagina...
    </div>

    <div v-else-if="error" class="alert alert-danger small">
      {{ error }}
    </div>

    <div v-else-if="page">
      <h1 class="h3 mb-3">{{ page.title }}</h1>
      <div class="cms-content" v-html="page.content"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { fetchStaticPage } from '@/services/content';

const route = useRoute();

const loading = ref(false);
const error = ref('');
const page = ref(null);

const load = async () => {
  loading.value = true;
  error.value = '';
  page.value = null;

  try {
    const data = await fetchStaticPage(route.params.slug);
    page.value = data;
  } catch (e) {
    console.error(e);
    error.value = 'Nu am putut încărca pagina.';
  } finally {
    loading.value = false;
  }
};

onMounted(load);
watch(
  () => route.params.slug,
  () => load()
);
</script>

<style scoped>
.cms-content :global(h2) {
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
}
.cms-content :global(p) {
  margin-bottom: 0.75rem;
}
</style>
