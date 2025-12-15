<template>
  <div class="container py-4">
    <div v-if="loading" class="text-center py-5 text-muted">
      Se încarcă articolul...
    </div>

    <div v-else-if="error" class="alert alert-danger small">
      {{ error }}
    </div>

    <div v-else-if="post">
      <nav class="small mb-2">
        <RouterLink
          to="/blog"
          class="text-decoration-none"
        >
          ← Înapoi la blog
        </RouterLink>
      </nav>

      <h1 class="h3 mb-2">{{ post.title }}</h1>
      <div class="small text-muted mb-3">
        <span v-if="post.category">
          {{ post.category.name }} ·
        </span>
        <span v-if="post.published_at">
          {{ formatDate(post.published_at) }}
        </span>
      </div>

      <div
        class="mb-4 cms-content"
        v-html="post.content"
      ></div>

      <section
        v-if="related.length"
        class="mt-5"
      >
        <h2 class="h6 mb-3">Articole similare</h2>
        <div class="row g-3">
          <div
            v-for="item in related"
            :key="item.id"
            class="col-md-4"
          >
            <div class="card h-100 border-0 shadow-sm">
              <div class="card-body">
                <RouterLink
                  :to="`/blog/${item.slug}`"
                  class="fw-semibold text-decoration-none d-block mb-1"
                >
                  {{ item.title }}
                </RouterLink>
                <div class="small text-muted">
                  <span v-if="item.published_at">
                    {{ formatDate(item.published_at) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { fetchBlogPost } from '@/services/content';

const route = useRoute();

const loading = ref(false);
const error = ref('');
const post = ref(null);
const related = ref([]);

const formatDate = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleDateString('ro-RO');
};

const load = async () => {
  loading.value = true;
  error.value = '';
  post.value = null;
  related.value = [];

  try {
    const data = await fetchBlogPost(route.params.slug);
    post.value = data.post ?? data;
    related.value = data.related ?? [];
  } catch (e) {
    console.error(e);
    error.value = 'Nu am putut încărca articolul.';
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
