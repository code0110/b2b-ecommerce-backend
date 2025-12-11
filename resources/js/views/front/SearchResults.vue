<template>
  <div class="container">
    <PageHeader
      title="Căutare produse"
      :subtitle="subtitle"
    >
      <template #breadcrumbs>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <RouterLink :to="{ name: 'home' }">Acasă</RouterLink>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              Căutare
            </li>
          </ol>
        </nav>
      </template>
    </PageHeader>

    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <form class="row g-2 align-items-end" @submit.prevent="doSearch">
          <div class="col-md-8">
            <label class="form-label small text-muted">Caută în catalog</label>
            <input
              v-model="term"
              type="search"
              class="form-control form-control-sm"
              placeholder="Denumire produs, cod intern, ERP ID, cod de bare..."
            />
            <div class="form-text small text-muted">
              Căutare full-text în denumire, cod produs intern, cod client (unde există), ERP ID și cod de bare.
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Brand (opțional)</label>
            <input
              v-model="brandFilter"
              type="text"
              class="form-control form-control-sm"
              placeholder="DemoBrand..."
            />
          </div>
          <div class="col-md-2 d-flex">
            <button type="submit" class="btn btn-primary btn-sm w-100">
              Caută
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="!normalizedTerm" class="alert alert-info small">
      Introdu un termen de căutare pentru a vedea rezultatele din catalogul de produse.
    </div>

    <div v-else>
      <p class="small text-muted mb-2">
        {{ results.length }} produs{{ results.length === 1 ? '' : 'e' }} găsit{{ results.length === 1 ? '' : 'e' }}
        pentru termenul „{{ term }}”.
      </p>

      <div v-if="results.length === 0" class="alert alert-warning small">
        Nu au fost găsite produse care să corespundă criteriilor de căutare.
      </div>

      <div v-else class="table-responsive">
        <table class="table table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th style="width: 40%">Produs</th>
              <th style="width: 15%">Cod intern</th>
              <th style="width: 15%">Brand</th>
              <th style="width: 15%" class="text-end">Preț listă</th>
              <th style="width: 15%" class="text-end">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="p in results"
              :key="p.id"
            >
              <td>
                <div class="fw-semibold">
                  <RouterLink :to="{ name: 'product-details', params: { slug: 'produs-demo-' + p.id } }">
                    {{ p.name }}
                  </RouterLink>
                </div>
                <div class="small text-muted">
                  ERP: {{ p.erpId }} · Cod bare: {{ p.barcode }}
                </div>
              </td>
              <td class="small">
                {{ p.internalCode }}
              </td>
              <td class="small">
                {{ p.brand || '-' }}
              </td>
              <td class="text-end small">
                {{ formatMoney(p.overridePrice || p.listPrice) }}
              </td>
              <td class="text-end">
                <RouterLink
                  :to="{ name: 'product-details', params: { slug: 'produs-demo-' + p.id } }"
                  class="btn btn-outline-primary btn-sm"
                >
                  Vezi detalii
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { useProductsStore } from '@/store/products'

const route = useRoute()
const router = useRouter()
const productsStore = useProductsStore()

const term = ref(route.query.q ? String(route.query.q) : '')
const brandFilter = ref(route.query.brand ? String(route.query.brand) : '')

const normalizedTerm = computed(() => term.value.trim().toLowerCase())
const normalizedBrand = computed(() => brandFilter.value.trim().toLowerCase())

const subtitle = computed(() => {
  if (!normalizedTerm.value) {
    return 'Căutare full-text în catalogul B2B/B2C.'
  }
  return `Rezultate pentru: „${term.value}”`
})

const results = computed(() => {
  const q = normalizedTerm.value
  const brand = normalizedBrand.value

  if (!q) return []

  return productsStore.all.filter((p) => {
    const name = (p.name || '').toLowerCase()
    const internalCode = (p.internalCode || '').toLowerCase()
    const clientCode = (p.clientCode || '').toLowerCase()
    const erpId = (p.erpId || '').toLowerCase()
    const barcode = (p.barcode || '').toLowerCase()

    if (
      !(
        name.includes(q) ||
        internalCode.includes(q) ||
        clientCode.includes(q) ||
        erpId.includes(q) ||
        barcode.includes(q)
      )
    ) {
      return false
    }

    if (brand && !(p.brand || '').toLowerCase().includes(brand)) {
      return false
    }

    return true
  })
})

const formatMoney = (value) => {
  const v = Number(value || 0)
  return (
    v.toLocaleString('ro-RO', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' RON'
  )
}

const doSearch = () => {
  const q = term.value.trim()
  const b = brandFilter.value.trim()

  router.push({
    name: 'search-results',
    query: {
      q: q || undefined,
      brand: b || undefined
    }
  })
}

watch(
  () => route.query,
  (query) => {
    term.value = query.q ? String(query.q) : ''
    brandFilter.value = query.brand ? String(query.brand) : ''
  }
)
</script>
