<template>
  <div class="container py-4">
    <div class="row">
      <!-- Sidebar filtre -->
      <div class="col-md-3 mb-4 mb-md-0">
        <h1 class="h5 mb-3">Blog / Noutăți</h1>

        <div class="mb-3">
          <label class="form-label small text-muted">Căutare</label>
          <div class="input-group input-group-sm">
            <input
              v-model="search"
              type="text"
              class="form-control"
              placeholder="Caută în articole..."
              @keyup.enter="applyFilters"
            />
            <button class="btn btn-outline-secondary" @click="applyFilters">
              Caută
            </button>
          </div>
        </div>

        <div v-if="categories.length" class="mb-3">
          <div class="small text-muted mb-1">Categorii</div>
          <ul class="list-unstyled small mb-0">
            <li>
              <button
                type="button"
                class="btn btn-link p-0 mb-1 text-decoration-none"
                :class="{
                  'fw-semibold':
                    !currentCategory,
                }"
                @click="setCategory(null)"
              >
                Toate articolele
              </button>
            </li>
            <li v-for="cat in categories" :key="cat.id">
              <button
                type="button"
                class="btn btn-link p-0 mb-1 text-decoration-none"
                :class="{
                  'fw-semibold':
                    currentCategory === cat.slug,
                }"
                @click="setCategory(cat.slug)"
              >
                {{ cat.name }}
              </button>
            </li>
          </ul>
        </div>
      </div>

      <!-- Listă articole -->
      <div class="col-md-9">
        <div v-if="loading" class="text-center py-5 text-muted">
          Se încarcă articolele...
        </div>

        <div v-else-if="error" class="alert alert-danger small">
          {{ error }}
        </div>

        <div v-else>
          <div
            v-if="posts.data && posts.data.length"
            class="vstack gap-3"
          >
            <article
              v-for="post in posts.data"
              :key="post.id"
              class="card border-0 shadow-sm"
            >
              <div class="card-body">
                <RouterLink
                  :to="`/blog/${post.slug}`"
                  class="h5 text-decoration-none d-block mb-1"
                >
                  {{ post.title }}
                </RouterLink>
                <div class="small text-muted mb-2">
                  <span v-if="post.category">
                    {{ post.category.name }} ·
                  </span>
                  <span v-if="post.published_at">
                    {{ formatDate(post.published_at) }}
                  </span>
                </div>
                <p
                  v-if="post.excerpt"
                  class="mb-2 small text-muted"
                >
                  {{ post.excerpt }}
                </p>
                <RouterLink
                  :to="`/blog/${post.slug}`"
                  class="btn btn-outline-primary btn-sm"
                >
                  Citește mai mult
                </RouterLink>
              </div>
            </article>
          </div>

          <div v-else class="text-muted small">
            Nu există articole pentru criteriile selectate.
          </div>

          <!-- Paginare simplificată -->
          <nav
            v-if="
              posts.meta &&
              (posts.meta.current_page > 1 ||
                posts.meta.current_page <
                  posts.meta.last_page)
            "
            class="mt-3"
          >
            <ul class="pagination pagination-sm mb-0">
              <li
                class="page-item"
                :class="{ disabled: posts.meta.current_page <= 1 }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="goToPage(posts.meta.current_page - 1)"
                >
                  «
                </button>
              </li>
              <li class="page-item disabled">
                <span class="page-link">
                  Pagina
                  {{ posts.meta.current_page }} /
                  {{ posts.meta.last_page }}
                </span>
              </li>
              <li
                class="page-item"
                :class="{
                  disabled:
                    posts.meta.current_page >=
                    posts.meta.last_page,
                }"
              >
                <button
                  class="page-link"
                  type="button"
                  @click="goToPage(posts.meta.current_page + 1)"
                >
                  »
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchBlogList } from '@/services/content';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const error = ref('');

const posts = reactive({
  data: [],
  meta: null,
});

const categories = ref([]);

const search = ref(route.query.q || '');
const currentCategory = ref(route.query.category || null);
const currentPage = ref(Number(route.query.page || 1) || 1);

const formatDate = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleDateString('ro-RO');
};

const syncUrl = () => {
  router.replace({
    name: 'blog-list',
    query: {
      ...(search.value ? { q: search.value } : {}),
      ...(currentCategory.value
        ? { category: currentCategory.value }
        : {}),
      ...(currentPage.value > 1
        ? { page: currentPage.value }
        : {}),
    },
  });
};

const load = async () => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      page: currentPage.value,
    };
    if (search.value) params.q = search.value;
    if (currentCategory.value)
      params.category = currentCategory.value;

    const data = await fetchBlogList(params);

    const source = data.posts ?? data;

    posts.data = source.data ?? [];
    posts.meta = source.meta ?? null;
    categories.value = data.categories ?? [];
  } catch (e) {
    console.error(e);
    error.value = 'Nu am putut încărca articolele.';
  } finally {
    loading.value = false;
  }
};

const applyFilters = () => {
  currentPage.value = 1;
  syncUrl();
  load();
};

const setCategory = (slug) => {
  currentCategory.value = slug;
  applyFilters();
};

const goToPage = (page) => {
  if (!posts.meta) return;
  if (
    page < 1 ||
    page > posts.meta.last_page ||
    page === posts.meta.current_page
  ) {
    return;
  }
  currentPage.value = page;
  syncUrl();
  load();
};

onMounted(load);
</script>
