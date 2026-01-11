<template>
  <div class="container py-4">
    <div class="row mb-3">
      <div class="col-12">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item">
              <span>Acasă</span>
            </li>
            <li class="breadcrumb-item">
              <span>Blog / Noutăți</span>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              {{ article.title }}
            </li>
          </ol>
        </nav>
        <h1 class="h4 mb-1">{{ article.title }}</h1>
        <div class="text-muted small">
          {{ article.createdAt }} · {{ article.readTime }} min · {{ article.category }}
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <article class="small">
          <p class="lead">
            {{ article.lead }}
          </p>
          <p v-for="(paragraph, idx) in article.body" :key="idx" class="mb-2">
            {{ paragraph }}
          </p>
          <div class="mt-3">
            <span class="text-muted me-1">Tag-uri:</span>
            <span
              v-for="tag in article.tags"
              :key="tag"
              class="badge bg-secondary bg-opacity-10 text-muted border me-1 mb-1"
            >
              #{{ tag }}
            </span>
          </div>
        </article>
      </div>
      <div class="col-lg-4">
        <div class="card shadow-sm mb-3 small">
          <div class="card-header py-2">
            <strong class="small text-uppercase">SEO / meta (demo)</strong>
          </div>
          <div class="card-body">
            <div><strong>Slug:</strong> {{ article.slug }}</div>
            <div><strong>Meta title:</strong> {{ article.metaTitle }}</div>
            <div class="text-muted">
              <strong>Meta description:</strong>
              <div>{{ article.metaDescription }}</div>
            </div>
          </div>
          <div class="card-footer text-muted">
            În implementarea reală, aceste valori ar fi configurabile din admin și
            folosite în head/meta tags.
          </div>
        </div>
        <div class="card shadow-sm small">
          <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-2">Call to action</h2>
            <p class="mb-2">
              După articol, poți încuraja utilizatorii să devină parteneri sau să
              contacteze un reprezentant de vânzări pentru o ofertă personalizată.
            </p>
            <div class="d-grid gap-2">
              <button type="button" class="btn btn-sm btn-orange">
                Devino partener (demo)
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary">
                Contactează un reprezentant (demo)
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const baseArticle = {
  title: 'Articol demo pentru template-ul de blog',
  slug: 'articol-demo-blog',
  category: 'Ghid practic',
  createdAt: '2025-03-01',
  readTime: 7,
  lead: 'Acesta este un articol demonstrativ care ilustrează structura unei pagini de conținut optimizabile SEO într-o platformă e-commerce B2B/B2C.',
  body: [
    'În implementarea reală, conținutul ar fi încărcat dintr-un CMS sau direct din backend, în funcție de fluxul tău editorial. Acest template îți arată doar cum poate fi aranjat conținutul la nivel de front-end.',
    'Poți structura articolele astfel încât să răspundă la întrebările frecvente ale clienților, să detalieze studii de caz sau să explice noile funcționalități ale platformei.',
    'În plus, articolele bine scrise ajută la indexarea SEO și aduc trafic organic, atât pentru canalele B2B, cât și pentru cele B2C.'
  ],
  tags: ['blog', 'conținut', 'seo'],
  metaTitle: 'Articol demo blog – platformă e-commerce B2B/B2C',
  metaDescription: 'Exemplu de pagină de articol pentru blog, cu structură optimizabilă SEO, pentru un magazin online B2B/B2C.'
}

const article = computed(() => {
  const slugParam = route.params.slug
  if (!slugParam) {
    return baseArticle
  }
  const slug = String(slugParam)
  return {
    ...baseArticle,
    slug,
    title: 'Articol demo: ' + slug.replace(/-/g, ' '),
    metaTitle: 'Articol demo – ' + slug,
    metaDescription:
      'Pagină demo pentru articolul cu slug-ul ' +
      slug +
      '. În implementarea reală, conținutul ar fi încărcat din backend.'
  }
})
</script>
