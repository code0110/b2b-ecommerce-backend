<template>
  <div class="container py-4">
    <div class="row mb-3">
      <div class="col-lg-8">
        <h1 class="h5 mb-1">Blog / Noutăți</h1>
        <p class="text-muted small mb-0">
          Articole educaționale, noutăți despre produse și studii de caz pentru clienți B2B și B2C.
          Conținutul de mai jos este demonstrativ, dar structura acoperă un blog comercial tipic.
        </p>
      </div>
      <div class="col-lg-4 mt-3 mt-lg-0 small">
        <div class="alert alert-info mb-0">
          <strong>Notă demo:</strong>
          articolele sunt definite local în componentă. În implementarea reală, acestea ar fi
          încărcate dintr-un API și optimizate SEO (slug, meta tags, open graph etc.).
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Filtre</strong>
          </div>
          <div class="card-body small">
            <div class="mb-2">
              <label class="form-label">Categorie</label>
              <select
                v-model="filters.category"
                class="form-select form-select-sm"
              >
                <option value="">Toate categoriile</option>
                <option
                  v-for="cat in categories"
                  :key="cat"
                  :value="cat"
                >
                  {{ cat }}
                </option>
              </select>
            </div>
            <div class="mb-0">
              <label class="form-label">Căutare</label>
              <input
                v-model="filters.search"
                type="text"
                class="form-control form-control-sm"
                placeholder="titlu, tag, categorie..."
              />
            </div>
          </div>
          <div class="card-footer small text-muted">
            Articole găsite: <strong>{{ filteredPosts.length }}</strong>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="row g-3">
          <div
            class="col-12 col-lg-6"
            v-for="post in filteredPosts"
            :key="post.id"
          >
            <div class="card shadow-sm h-100">
              <div class="card-body small d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <span class="badge bg-light text-dark">
                    {{ post.category }}
                  </span>
                  <span class="text-muted">
                    {{ post.createdAt }} · {{ post.readTime }} min
                  </span>
                </div>
                <h2 class="h6 mb-1">
                  {{ post.title }}
                </h2>
                <p class="text-muted mb-2">
                  {{ post.excerpt }}
                </p>
                <div class="mb-2">
                  <span
                    v-for="tag in post.tags"
                    :key="tag"
                    class="badge bg-secondary bg-opacity-10 text-muted border me-1 mb-1"
                  >
                    #{{ tag }}
                  </span>
                </div>
                <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    @click="openPost(post)"
                  >
                    Citește articolul
                  </button>
                  <span class="text-muted small">
                    Slug: {{ post.slug }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div v-if="filteredPosts.length === 0" class="col-12">
            <div class="alert alert-secondary small mb-0">
              Nu am găsit articole pentru criteriile selectate. Încearcă să schimbi
              categoria sau să golești filtrul de căutare.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const posts = ref([
  {
    id: 1,
    title: 'Cum pregătești un flux B2B online integrat cu ERP-ul',
    slug: 'flux-b2b-online-integrat-erp',
    category: 'B2B',
    createdAt: '2025-02-10',
    readTime: 6,
    excerpt: 'Un ghid practic pentru companiile care vor să-și mute procesul de achiziții și vânzări în online, fără să piardă controlul din ERP.',
    tags: ['b2b', 'erp', 'integrări']
  },
  {
    id: 2,
    title: 'Top 5 funcționalități esențiale pentru un magazin B2C modern',
    slug: 'top-5-functionalitati-magazin-b2c',
    category: 'B2C',
    createdAt: '2025-02-18',
    readTime: 5,
    excerpt: 'De la căutare avansată la recomandări personalizate, trecem în revistă elementele fără de care un magazin online B2C nu mai este competitiv.',
    tags: ['b2c', 'ux', 'conversie']
  },
  {
    id: 3,
    title: 'Studiu de caz: optimizarea costurilor de livrare pentru clienți B2B',
    slug: 'studiu-caz-optimizare-costuri-livrare-b2b',
    category: 'Studiu de caz',
    createdAt: '2025-03-05',
    readTime: 7,
    excerpt: 'Vezi cum o companie de distribuție a redus cu 18% costurile de transport prin configurarea corectă a regulilor de livrare și integrarea cu curierii.',
    tags: ['b2b', 'logistică', 'transport']
  },
  {
    id: 4,
    title: 'Checklist pentru lansarea unui canal online de vânzări',
    slug: 'checklist-lansare-canal-online',
    category: 'Ghid practic',
    createdAt: '2025-03-20',
    readTime: 8,
    excerpt: 'O listă de verificare pentru echipele de vânzări și marketing care pregătesc lansarea unui nou canal digital.',
    tags: ['ghid', 'lansare', 'marketing']
  }
])

const filters = ref({
  category: '',
  search: ''
})

const categories = computed(() => {
  const set = new Set()
  posts.value.forEach((p) => set.add(p.category))
  return Array.from(set).sort((a, b) => a.localeCompare(b, 'ro-RO'))
})

const filteredPosts = computed(() => {
  const category = filters.value.category
  const searchRaw = filters.value.search || ''
  const search = searchRaw.toLowerCase().trim()

  return posts.value.filter((post) => {
    if (category && post.category !== category) {
      return false
    }

    if (search) {
      const haystack = (
        post.title +
        ' ' +
        post.category +
        ' ' +
        post.tags.join(' ')
      )
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
      const needle = search
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')

      if (!haystack.includes(needle)) {
        return false
      }
    }

    return true
  })
})

const openPost = (post) => {
  const message =
    "Demo: aici s-ar deschide pagina articolului cu slug-ul " +
    post.slug +
    ". În implementarea reală, s-ar folosi o rută dedicată (ex: /blog/" +
    post.slug +
    ") și conținut încărcat din backend."
  window.alert(message)
}
</script>
