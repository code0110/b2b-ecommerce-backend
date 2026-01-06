<template>
  <div class="container">
    <PageHeader title="Pagină de conținut" subtitle="Pagini statice: Despre noi, Termeni, GDPR etc.">
      <!-- Slot pentru butoane de acțiune (ex: Adaugă, Export etc.) -->
    </PageHeader>

    <div class="card shadow-sm">
      <div class="card-body">
        <p class="text-muted">
          Aceasta este o pagină de template pentru <strong>Pagină de conținut</strong>.
          Completează cu tabele, formulare și componente specifice proiectului tău.
        </p>
        <ul class="small text-muted">
          <li>Respectă structura și câmpurile din specificația funcțională.</li>
          <li>Leagă această pagină de API-ul backend / ERP / procesator plăți.</li>
          <li>Extinde componentele Bootstrap sau creează componente personalizate.</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import PageHeader from '@/components/common/PageHeader.vue'
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { fetchStaticPage } from '@/services/content'
import { setTitle, setMeta, setMetaProperty, setCanonical, setJsonLd } from '@/utils/seo'

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
    const slug = route.params.slug
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
    error.value = 'Nu am putut încărca pagina.'
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
