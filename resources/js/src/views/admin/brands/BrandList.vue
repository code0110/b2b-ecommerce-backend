<template>
  <div class="container py-4">
    <PageHeader
      title="Mărci (Branduri)"
      subtitle="Management branduri: logo, descriere, status publicare și ordinea de afișare."
    >
      <template #actions>
        <button type="button" class="btn btn-primary btn-sm" @click="onCreateBrand">
          <i class="bi bi-plus-lg me-1"></i>
          Adaugă brand (demo)
        </button>
      </template>
    </PageHeader>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-end">
              <div>
                <h2 class="h6 mb-1">Listă branduri</h2>
                <p class="text-muted small mb-0">
                  Branduri disponibile în catalog, folosite în filtre, pagini dedicate și promoții.
                </p>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light border-end-0">
                    <i class="bi bi-search"></i>
                  </span>
                  <input
                    type="text"
                    class="form-control border-start-0"
                    placeholder="Caută după denumire sau slug..."
                    v-model="searchTerm"
                  />
                </div>
                <select
                  class="form-select form-select-sm"
                  v-model="statusFilter"
                  aria-label="Filtru status publicare"
                >
                  <option value="">Status: toate</option>
                  <option value="published">Doar publicate</option>
                  <option value="hidden">Doar ascunse</option>
                </select>
              </div>
            </div>
          </div>

          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                  <tr>
                    <th style="width: 28%">Brand</th>
                    <th style="width: 18%">Slug</th>
                    <th style="width: 12%">Status</th>
                    <th style="width: 12%">Ordine</th>
                    <th style="width: 18%">Produse active</th>
                    <th style="width: 12%" class="text-end">Acțiuni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="brand in sortedFilteredBrands"
                    :key="brand.id"
                    :class="{
                      'table-active': selectedBrand && selectedBrand.id === brand.id
                    }"
                  >
                    <td>
                      <div class="d-flex align-items-center">
                        <div
                          class="rounded border bg-light d-flex align-items-center justify-content-center me-2"
                          style="width: 32px; height: 32px"
                        >
                          <span class="small fw-semibold text-muted">
                            {{ brand.initials }}
                          </span>
                        </div>
                        <div>
                          <div class="fw-semibold">
                            {{ brand.name }}
                            <span
                              v-if="brand.featured"
                              class="badge bg-info-subtle text-info border ms-1 align-middle"
                            >
                              evidențiat
                            </span>
                          </div>
                          <div class="small text-muted">
                            {{ brand.tagline }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="small text-muted">
                      /brand/{{ brand.slug }}
                    </td>
                    <td>
                      <span class="badge" :class="statusBadgeClass(brand)">
                        {{ brand.published ? 'Publicat' : 'Ascuns' }}
                      </span>
                    </td>
                    <td class="small">
                      <span class="badge bg-light text-secondary border">
                        #{{ brand.sortOrder.toString().padStart(2, '0') }}
                      </span>
                    </td>
                    <td class="small">
                      <strong>{{ brand.productCount }}</strong> produse active
                    </td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <button
                          type="button"
                          class="btn btn-outline-secondary"
                          @click="selectBrand(brand)"
                        >
                          <i class="bi bi-eye"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-outline-primary"
                          @click="onEditBrand(brand)"
                        >
                          <i class="bi bi-pencil"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="sortedFilteredBrands.length === 0">
                    <td colspan="6" class="text-center py-4 text-muted small">
                      Nu există branduri care să corespundă filtrelor selectate.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="card-footer bg-white border-0 small text-muted d-flex justify-content-between">
            <div>
              <i class="bi bi-info-circle me-1"></i>
              Date demo. Într-o implementare reală, brandurile ar fi gestionate în backend și
              sincronizate cu ERP sau PIM.
            </div>
            <div>
              Branduri afișate: <strong>{{ sortedFilteredBrands.length }}</strong> din
              <strong>{{ brands.length }}</strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-white border-0 pb-0">
            <h2 class="h6 mb-1">Previzualizare brand selectat</h2>
            <p class="text-muted small mb-0">
              Rezumat pentru pagina publică de brand și context pentru campaniile de marketing.
            </p>
          </div>
          <div class="card-body">
            <div v-if="selectedBrand">
              <div class="d-flex mb-3">
                <div
                  class="rounded border bg-light d-flex align-items-center justify-content-center me-3"
                  style="width: 48px; height: 48px"
                >
                  <span class="fw-semibold text-muted">
                    {{ selectedBrand.initials }}
                  </span>
                </div>
                <div>
                  <div class="d-flex align-items-center mb-1">
                    <h3 class="h6 mb-0 me-2">
                      {{ selectedBrand.name }}
                    </h3>
                    <span class="badge" :class="statusBadgeClass(selectedBrand)">
                      {{ selectedBrand.published ? 'Publicat' : 'Ascuns' }}
                    </span>
                  </div>
                  <div class="small text-muted">
                    Slug: <code>/brand/{{ selectedBrand.slug }}</code>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <h4 class="h6 mb-2">Descriere pentru pagina de brand</h4>
                <p class="small mb-1">
                  {{ selectedBrand.description }}
                </p>
                <p v-if="selectedBrand.websiteUrl" class="small mb-0">
                  Website oficial:
                  <a :href="selectedBrand.websiteUrl" target="_blank" rel="noopener">
                    {{ selectedBrand.websiteUrl }}
                  </a>
                </p>
              </div>

              <div class="mb-3">
                <h4 class="h6 mb-2">Context catalog & promoții</h4>
                <ul class="small mb-0">
                  <li>
                    Produse active în catalog:
                    <strong>{{ selectedBrand.productCount }}</strong>
                  </li>
                  <li>
                    Afișare în liste de branduri și în filtrele de categorie, conform ordinii
                    de sortare.
                  </li>
                  <li>
                    Poate avea landing-uri dedicate de promoții sau colecții sezoniere.
                  </li>
                </ul>
              </div>

              <div class="alert alert-light border small mb-0">
                <div class="fw-semibold mb-1">Notă de implementare</div>
                <p class="mb-1">
                  În proiectul real, pagina publică <code>/brand/{{ selectedBrand.slug }}</code>
                  ar lista produsele asociate brandului, cu filtre și bannere dedicate.
                </p>
                <p class="mb-0">
                  În plus, brandurile pot fi folosite ca dimensiune de segmentare în promoții și în
                  rapoartele de vânzări.
                </p>
              </div>
            </div>

            <div v-else class="text-muted small">
              Selectează un brand din listă pentru a vedea detaliile și contextul său.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'

const brands = ref([
  {
    id: 1,
    name: 'BuildPro',
    initials: 'BP',
    slug: 'buildpro',
    tagline: 'Soluții profesionale pentru șantiere moderne.',
    description:
      'BuildPro este un brand dedicat profesioniștilor din construcții, cu un portofoliu complet de materiale, adezivi și soluții pentru șantiere complexe.',
    published: true,
    sortOrder: 1,
    productCount: 124,
    featured: true,
    websiteUrl: 'https://www.buildpro-demo.ro'
  },
  {
    id: 2,
    name: 'CasaLine',
    initials: 'CL',
    slug: 'casaline',
    tagline: 'Finisaje și design interior pentru proiecte rezidențiale.',
    description:
      'CasaLine propune o gamă largă de finisaje, vopsele și accesorii pentru amenajări interioare, adresate atât clienților finali cât și proiectanților.',
    published: true,
    sortOrder: 2,
    productCount: 86,
    featured: false,
    websiteUrl: 'https://www.casaline-demo.ro'
  },
  {
    id: 3,
    name: 'EcoTherm',
    initials: 'ET',
    slug: 'ecotherm',
    tagline: 'Izolații și soluții eficiente energetic.',
    description:
      'EcoTherm este un brand orientat către eficiență energetică și sustenabilitate, cu produse de izolație termică și fonică pentru proiecte moderne.',
    published: false,
    sortOrder: 3,
    productCount: 42,
    featured: false,
    websiteUrl: null
  }
])

const searchTerm = ref('')
const statusFilter = ref('')
const selectedBrand = ref(brands.value[0] || null)

const filteredBrands = computed(() => {
  const term = searchTerm.value.trim().toLowerCase()

  return brands.value.filter((brand) => {
    const matchesStatus =
      !statusFilter.value ||
      (statusFilter.value === 'published' && brand.published) ||
      (statusFilter.value === 'hidden' && !brand.published)

    if (!term) {
      return matchesStatus
    }

    const haystack = [brand.name, brand.slug, brand.tagline, brand.description]
      .join(' ')
      .toLowerCase()

    const matchesSearch = haystack.includes(term)

    return matchesStatus && matchesSearch
  })
})

const sortedFilteredBrands = computed(() => {
  return [...filteredBrands.value].sort((a, b) => a.sortOrder - b.sortOrder)
})

const selectBrand = (brand) => {
  selectedBrand.value = brand
}

const statusBadgeClass = (brand) => {
  if (brand.published) {
    return 'bg-success-subtle text-success border'
  }
  return 'bg-secondary-subtle text-secondary border'
}

const onCreateBrand = () => {
  window.alert(
    'Demo: aici s-ar deschide un formular pentru a crea un brand nou (denumire, logo, descriere, status, ordine sortare).'
  )
}

const onEditBrand = (brand) => {
  window.alert(
    `Demo: aici s-ar deschide ecranul de editare pentru brandul "${brand.name}", cu tab-uri pentru detalii, SEO și conținut pentru landing page.`
  )
}
</script>
