<template>
  <div>
    <div v-if="page" class="dd-page-header py-3 mb-3">
      <div class="container">
        <nav aria-label="breadcrumb" class="small mb-2">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><router-link to="/">Acasă</router-link></li>
            <li class="breadcrumb-item active" aria-current="page">{{ page.title }}</li>
          </ol>
        </nav>
        <h1 class="h4 mb-0">{{ page.title }}</h1>
      </div>
    </div>

    <div class="container pb-4">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Se încarcă...</span>
        </div>
      </div>

      <div v-else-if="error" class="alert alert-danger" role="alert">
        {{ error }}
      </div>

      <div v-else-if="page" class="static-page card">
        <div class="card-body">
          <div class="content" v-html="page.content"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { fetchStaticPage } from '@/services/content'
import { setTitle, setMeta, setMetaProperty, setCanonical, setJsonLd } from '@/utils/seo'

const props = defineProps({
  slug: {
    type: String,
    default: null
  }
})

const route = useRoute()
const router = useRouter()
const page = ref(null)
const loading = ref(false)
const error = ref('')

const load = async () => {
  loading.value = true
  error.value = ''
  page.value = null
  try {
    const slug = props.slug || route.params.slug
    const data = await fetchStaticPage(slug)
    page.value = data.page ?? data
    const title = (page.value?.meta_title || page.value?.title || 'Pagină') + ' | ' + (document?.querySelector('meta[name=\"application-name\"]')?.getAttribute('content') || '')
    const desc = page.value?.meta_description || ''
    const url = window.location.origin + (router.resolve({ name: 'static-page', params: { slug } }).href || location.pathname)
    setTitle(title)
    setMeta('description', desc)
    setMetaProperty('og:type', 'website')
    setMetaProperty('og:title', title)
    setMetaProperty('og:description', desc)
    setMetaProperty('og:url', url)
    setCanonical(url)
    const breadcrumb = {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      'itemListElement': [
        { '@type': 'ListItem', position: 1, name: 'Acasă', item: window.location.origin + '/' },
        { '@type': 'ListItem', position: 2, name: page.value?.title || 'Pagină', item: url }
      ]
    }
    const webPage = {
      '@context': 'https://schema.org',
      '@type': 'WebPage',
      'name': page.value?.title || 'Pagină',
      'url': url,
      'description': desc || undefined
    }
    setJsonLd({ '@graph': [breadcrumb, webPage] })
  } catch (e) {
    if (e.response) {
      if (e.response.status === 404) {
        error.value = 'Pagina nu a fost găsită.'
      } else {
        error.value = `Nu am putut încărca pagina (Eroare ${e.response.status}).`
      }
    } else {
      error.value = 'Nu am putut încărca pagina (Eroare de conexiune).'
    }
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(
  () => route.params.slug,
  () => load()
)
</script>
