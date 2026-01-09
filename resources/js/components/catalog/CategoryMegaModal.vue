<template>
  <div class="catalog-overlay" @click.self="close">
    <div class="catalog-panel bg-white shadow-lg">
      <!-- Header portocaliu -->
      <div class="catalog-header d-flex justify-content-between align-items-center px-4 py-3" style="background-color: #ff7900;">
        <div>
          <div class="text-uppercase small text-white-50 mb-1">
            Categorie
          </div>
          <h2 class="h5 mb-0 text-white d-flex align-items-center gap-2 catalog-title">
            <i class="bi bi-collection"></i>
            <span>{{ selectedCategory?.name || 'Catalog produse' }}</span>
            <span
              v-if="selectedCategory"
              class="badge bg-white bg-opacity-25 text-white rounded-pill"
            >
              {{ (selectedCategory?.children || []).length }} subcategorii
            </span>
          </h2>
          <ul v-if="selectedCategory" class="breadcrumb mb-1">
            <li class="breadcrumb-item">
              <span class="text-white-50">Catalog</span>
            </li>
            <li
              v-for="(item, i) in breadcrumbPath"
              :key="item.slug || item.name || i"
              class="breadcrumb-item"
              :class="{ active: i === breadcrumbPath.length - 1 }"
              aria-current="page"
            >
              <RouterLink
                v-if="i !== breadcrumbPath.length - 1 && item.slug"
                :to="{ name: 'category', params: { slug: item.slug } }"
                class="text-white-50 text-decoration-none"
              >
                {{ item.name }}
              </RouterLink>
              <span v-else class="text-white">
                {{ item.name }}
              </span>
            </li>
          </ul>
          <p class="small text-white-50 mb-0">
            Explorați subcategoriile sau vedeți toate produsele din categorie.
          </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap header-actions">
          <div class="input-group input-group-sm header-search me-2">
            <span class="input-group-text bg-white bg-opacity-25 border-0 text-white">
              <i class="bi bi-search"></i>
            </span>
            <input
              v-model="searchTerm"
              type="search"
              class="form-control bg-white bg-opacity-25 border-0 text-white"
              placeholder="Caută categorie..."
            />
          </div>
          <button
            v-if="selectedCategory"
            type="button"
            class="btn btn-light btn-sm"
            @click="goToCategory(selectedCategory)"
          >
            <i class="bi bi-arrow-right-short me-1"></i>
            Vezi tot
          </button>
          <button
            type="button"
            class="btn btn-outline-light btn-sm"
            @click="close"
          >
            <i class="bi bi-x-lg me-1"></i>
            Închide
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="catalog-body d-flex">
        <!-- Sidebar categorii -->
        <aside class="catalog-sidebar border-end p-3">
          <div class="mb-3">
            <input
              v-model="searchTerm"
              type="text"
              class="form-control form-control-sm"
              placeholder="Căutați categorie..."
            />
          </div>
          <div class="list-group list-group-flush small">
            <button
              v-for="cat in filteredCategories"
              :key="cat.id || cat.slug"
              type="button"
              class="list-group-item list-group-item-action border-0 category-item text-start"
              :class="{ active: selectedCategory && selectedCategory.id === cat.id }"
              @click="selectCategory(cat)"
            >
              <div class="d-flex justify-content-between align-items-center">
                <span>{{ cat.name }}</span>
                <span class="badge bg-light text-muted ms-2">
                  {{ (cat.children || []).length }}
                </span>
              </div>
            </button>
          </div>
        </aside>

        <!-- Conținut subcategorii -->
        <section class="catalog-content p-3 flex-grow-1">
          <div v-if="loading" class="text-muted small py-4">
            Se încarcă categoriile...
          </div>

          <div v-else-if="error" class="alert alert-danger small mb-3">
            {{ error }}
          </div>

          <div v-else-if="!selectedCategory" class="text-muted small py-4">
            Selectați o categorie din stânga pentru a vedea subcategoriile.
          </div>

          <div v-else>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <div class="small text-muted mb-1">Categorie</div>
                <h3 class="h5 mb-0">{{ selectedCategory.name }}</h3>
              </div>
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                @click="goToCategory(selectedCategory)"
              >
                Vezi toate produsele
              </button>
            </div>

            <div class="row g-3">
              <div
                v-for="sub in selectedCategory.children || []"
                :key="sub.id || sub.slug"
                class="col-md-4 col-sm-6"
              >
                <div class="subcategory-card h-100 d-flex flex-column">
                  <div class="mb-3">
                    <div class="small text-muted mb-1">
                      Subcategorie
                    </div>
                    <h4 class="h6 mb-0">
                      {{ sub.name }}
                    </h4>
                  </div>
                  <div class="subcategory-placeholder mb-3"></div>
                  <button
                    type="button"
                    class="btn btn-link btn-sm px-0 text-decoration-none mt-auto"
                    @click="goToCategory(sub)"
                  >
                    Vezi produse →
                  </button>
                </div>
              </div>

              <div
                v-if="!(selectedCategory.children || []).length"
                class="col-12"
              >
                <div class="alert alert-light border small mb-0">
                  Categoria nu are subcategorii definite. Folosiți „Vezi toate produsele”
                  pentru a accesa produsele din această categorie.
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <div class="catalog-footer border-top small text-muted px-4 py-2 d-flex justify-content-between">
        <span>Demo B2B: structura de categorii sincronizată din ERP.</span>
        <span>Alegeți o categorie & subcategorie pentru a naviga în catalog.</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { fetchCategoryTree } from '@/services/catalog';

const emit = defineEmits(['close']);
const router = useRouter();

const loading = ref(false);
const error = ref('');
const categories = ref([]);
const selectedCategory = ref(null);
const searchTerm = ref('');

const filteredCategories = computed(() => {
  if (!searchTerm.value) {
    return categories.value;
  }
  const term = searchTerm.value.toLowerCase();
  return categories.value.filter((cat) =>
    (cat.name || '').toLowerCase().includes(term),
  );
});

const selectCategory = (cat) => {
  selectedCategory.value = cat;
};

const goToCategory = (cat) => {
  if (!cat || !cat.slug) return;
  emit('close');
  router.push({
    name: 'category',
    params: { slug: cat.slug },
  });
};

const close = () => {
  emit('close');
};

const handleKey = (e) => {
  if (e.key === 'Escape') {
    close();
  }
};

const breadcrumbPath = computed(() => {
  if (!selectedCategory.value) return [];
  const matchId = selectedCategory.value.id;
  const matchSlug = selectedCategory.value.slug;
  const find = (list, trail = []) => {
    for (const cat of list) {
      const t = [...trail, cat];
      if ((matchId && cat.id === matchId) || (matchSlug && cat.slug === matchSlug)) {
        return t;
      }
      if (Array.isArray(cat.children) && cat.children.length) {
        const r = find(cat.children, t);
        if (r) return r;
      }
    }
    return null;
  };
  const res = find(categories.value) || [selectedCategory.value];
  return res.map((c) => ({ name: c.name, slug: c.slug }));
});

const loadCategories = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchCategoryTree();
    const items = Array.isArray(data)
      ? data
      : data.categories || data.data || [];

    categories.value = items;
    if (items.length && !selectedCategory.value) {
      selectedCategory.value = items[0];
    }
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca categoriile.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  window.addEventListener('keydown', handleKey);
  loadCategories();
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKey);
});
</script>

<style scoped>
.catalog-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1050;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 1.5rem 0;
}

.catalog-panel {
  width: min(1120px, 100% - 2rem);
  border-radius: 1.25rem;
  overflow: hidden;
}

/* Header portocaliu */
.catalog-header {
  background: linear-gradient(135deg, #f97316, #ea580c 60%, #c2410c);
  color: #fff;
  box-shadow: inset 0 -1px 0 rgba(255,255,255,0.15);
}
.catalog-header .breadcrumb {
  --bs-breadcrumb-divider: '›';
}
.catalog-header .breadcrumb .breadcrumb-item {
  font-size: .75rem;
}
.catalog-header .breadcrumb .breadcrumb-item + .breadcrumb-item::before {
  color: rgba(255,255,255,.5);
}
.catalog-header .text-white-50 {
  color: rgba(255,255,255,.75) !important;
}
.catalog-header .breadcrumb .breadcrumb-item a.text-white-50:hover {
  color: rgba(255,255,255,.9) !important;
}

/* Body */
.catalog-body {
  min-height: 400px;
  max-height: calc(100vh - 8rem);
  overflow: hidden;
}

/* Sidebar */
.catalog-sidebar {
  width: 260px;
  max-height: 100%;
  overflow-y: auto;
  background: #fafafa;
}

.category-item {
  border-radius: 0.5rem;
  margin-bottom: 0.25rem;
  background: #ffffff;
  color: #0f172a;
}

.category-item.active,
.category-item:hover {
  background: rgba(234, 88, 12, 0.10);
}

.category-item .badge {
  background-color: #f1f5f9;
  color: #475569;
}

.category-item:hover .badge {
  background-color: #ffffff;
  color: #0f172a;
}

.category-item.active {
  background-color: rgba(234, 88, 12, 0.14);
}

.category-item.active .badge {
  background-color: #ffffff;
  color: #0f172a;
}

.category-item:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.35);
}
/* Content */
.catalog-content {
  max-height: 100%;
  overflow-y: auto;
}

/* Subcategory cards */
.subcategory-card {
  border-radius: 1rem;
  border: 1px solid #f0f0f0;
  background: linear-gradient(145deg, #ffffff, #fafafa);
  padding: 1rem;
  transition: all 0.15s ease-out;
}

.subcategory-card:hover {
  box-shadow: 0 0.75rem 1.5rem rgba(15, 23, 42, 0.08);
  transform: translateY(-1px);
}

.subcategory-placeholder {
  height: 56px;
  border-radius: 0.75rem;
  background: linear-gradient(90deg, #f5f5f5, #f0f0f0);
}

.catalog-title {
  letter-spacing: .2px;
}
.header-actions .btn {
  border-radius: 999px;
}
.header-actions .btn.btn-light {
  color: #0f172a;
}
.header-actions .btn:hover {
  filter: brightness(1.05);
}
.header-search .form-control::placeholder {
  color: rgba(255,255,255,.7);
}
.header-search .form-control:focus {
  box-shadow: none;
}
.header-search {
  width: 240px;
  min-width: 0;
}
@media (max-width: 992px) {
  .header-search {
    width: 200px;
  }
}
@media (max-width: 768px) {
  .catalog-header {
    padding: 1rem;
  }
  .catalog-title {
    flex-wrap: wrap;
  }
  .header-actions {
    justify-content: flex-end;
  }
  .header-search {
    width: 100%;
    order: 3;
    margin-top: .5rem;
  }
  .header-actions .btn {
    flex: 1 1 auto;
  }
}
@media (max-width: 576px) {
  .catalog-header .breadcrumb {
    display: none;
  }
}
</style>
