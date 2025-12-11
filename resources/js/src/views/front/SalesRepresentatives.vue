<template>
  <div class="container py-4">
    <div class="row mb-3">
      <div class="col-lg-8">
        <h1 class="h5 mb-1">Reprezentanți vânzări</h1>
        <p class="text-muted small mb-0">
          Găsește rapid reprezentantul de vânzări responsabil pentru zona ta. Filtrează după
          regiune și județ, apoi contactează direct persoana potrivită.
        </p>
      </div>
      <div class="col-lg-4 mt-3 mt-lg-0 small">
        <div class="alert alert-info mb-0">
          <strong>Notă demo:</strong>
          datele de mai jos sunt doar exemple. În implementarea reală, lista ar fi încărcată
          dintr-un API și sincronizată cu alocările din CRM/ERP.
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-5">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Filtru zonă</strong>
          </div>
          <div class="card-body small">
            <div class="mb-2">
              <label class="form-label">Regiune</label>
              <select
                v-model="filters.regionCode"
                class="form-select form-select-sm"
              >
                <option value="">Toate regiunile</option>
                <option
                  v-for="region in regions"
                  :key="region.code"
                  :value="region.code"
                >
                  {{ region.label }}
                </option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Județ</label>
              <select
                v-model="filters.county"
                class="form-select form-select-sm"
              >
                <option value="">Toate județele</option>
                <option
                  v-for="county in availableCounties"
                  :key="county"
                  :value="county"
                >
                  {{ county }}
                </option>
              </select>
            </div>
            <div class="mb-0">
              <label class="form-label">Căutare reprezentant</label>
              <input
                v-model="filters.search"
                type="text"
                class="form-control form-control-sm"
                placeholder="nume, județ, regiune..."
              />
            </div>
          </div>
          <div class="card-footer small text-muted">
            Reprezentanți găsiți: <strong>{{ filteredRepresentatives.length }}</strong>
          </div>
        </div>
      </div>

      <div class="col-md-7">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Hartă interactivă (demo)</strong>
          </div>
          <div class="card-body">
            <div
              class="border rounded bg-light d-flex align-items-center justify-content-center text-muted small"
              style="height: 260px;"
            >
              Hartă interactivă (placeholder). Aici se poate integra o hartă reală
              (ex: Leaflet, Google Maps) cu zone de acoperire și puncte pentru reprezentanți.
            </div>
          </div>
          <div class="card-footer small text-muted">
            În varianta completă, click pe hartă ar filtra automat reprezentanții pe județ / zonă.
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-6 col-xl-4" v-for="rep in filteredRepresentatives" :key="rep.id">
        <div class="card shadow-sm h-100">
          <div class="card-body small d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h2 class="h6 mb-1">{{ rep.name }}</h2>
                <div class="text-muted">
                  {{ rep.role }}
                </div>
              </div>
              <span class="badge bg-light text-dark">
                {{ rep.regionLabel }}
              </span>
            </div>
            <div class="mb-2">
              <div class="text-muted">
                Județe acoperite:
              </div>
              <div>
                <span
                  v-for="county in rep.counties"
                  :key="county"
                  class="badge bg-secondary bg-opacity-10 text-muted border me-1 mb-1"
                >
                  {{ county }}
                </span>
              </div>
            </div>
            <div class="mb-2">
              <div>
                <i class="bi bi-telephone me-1"></i>
                <a :href="'tel:' + rep.phone" class="text-decoration-none">
                  {{ rep.phone }}
                </a>
              </div>
              <div>
                <i class="bi bi-envelope me-1"></i>
                <a :href="'mailto:' + rep.email" class="text-decoration-none">
                  {{ rep.email }}
                </a>
              </div>
            </div>
            <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
              <button
                type="button"
                class="btn btn-sm btn-outline-primary"
                @click="contactRepresentative(rep)"
              >
                Contactează
              </button>
              <div class="text-muted small">
                Program: {{ rep.schedule }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="filteredRepresentatives.length === 0" class="col-12">
        <div class="alert alert-secondary small mb-0">
          Nu am găsit niciun reprezentant pentru filtrarea curentă. Încearcă să
          schimbi regiunea sau județul, sau șterge filtrul de căutare.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const representatives = ref([
  {
    id: 1,
    name: 'Andrei Pop',
    role: 'Reprezentant vânzări B2B',
    phone: '+40 723 000 111',
    email: 'andrei.pop@example.com',
    regionCode: 'RO-B',
    regionLabel: 'București & Ilfov',
    counties: ['București', 'Ilfov'],
    schedule: 'Luni–Vineri 09:00–17:30'
  },
  {
    id: 2,
    name: 'Maria Ionescu',
    role: 'Reprezentant vânzări B2C / retail',
    phone: '+40 723 000 222',
    email: 'maria.ionescu@example.com',
    regionCode: 'RO-NV',
    regionLabel: 'Nord-Vest',
    counties: ['Cluj', 'Bihor', 'Sălaj'],
    schedule: 'Luni–Vineri 08:30–17:00'
  },
  {
    id: 3,
    name: 'Dan Radu',
    role: 'Key Account Manager B2B',
    phone: '+40 723 000 333',
    email: 'dan.radu@example.com',
    regionCode: 'RO-NV',
    regionLabel: 'Nord-Vest',
    counties: ['Maramureș', 'Satu Mare'],
    schedule: 'Luni–Vineri 09:00–18:00'
  },
  {
    id: 4,
    name: 'Ioana Petrescu',
    role: 'Reprezentant vânzări proiecte',
    phone: '+40 723 000 444',
    email: 'ioana.petrescu@example.com',
    regionCode: 'RO-CT',
    regionLabel: 'Constanța & litoral',
    counties: ['Constanța', 'Tulcea'],
    schedule: 'Luni–Vineri 08:00–16:30'
  }
])

const filters = ref({
  regionCode: '',
  county: '',
  search: ''
})

const regions = computed(() => {
  const map = new Map()
  representatives.value.forEach((rep) => {
    if (!map.has(rep.regionCode)) {
      map.set(rep.regionCode, rep.regionLabel)
    }
  })
  return Array.from(map.entries()).map(([code, label]) => ({
    code,
    label
  }))
})

const availableCounties = computed(() => {
  const set = new Set()
  representatives.value.forEach((rep) => {
    if (!filters.value.regionCode || rep.regionCode === filters.value.regionCode) {
      rep.counties.forEach((c) => set.add(c))
    }
  })
  return Array.from(set).sort((a, b) => a.localeCompare(b, 'ro-RO'))
})

const filteredRepresentatives = computed(() => {
  const search = (filters.value.search || '').toLowerCase().trim()

  return representatives.value.filter((rep) => {
    if (filters.value.regionCode && rep.regionCode !== filters.value.regionCode) {
      return false
    }
    if (filters.value.county) {
      const countyMatch = rep.counties.includes(filters.value.county)
      if (!countyMatch) {
        return false
      }
    }

    if (search) {
      const haystack = (
        rep.name +
        ' ' +
        rep.regionLabel +
        ' ' +
        rep.counties.join(' ')
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

const contactRepresentative = (rep) => {
  const message =
    'Demo: ai inițiat contactul cu reprezentantul ' +
    rep.name +
    ' (' +
    rep.regionLabel +
    '). În implementarea reală, aici s-ar putea deschide un formular de contact, un pop-up sau o integrare cu sistemul de ticketing.'
  window.alert(message)
}
</script>
