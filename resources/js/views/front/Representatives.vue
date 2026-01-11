<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Reprezentanți vânzări</h1>
        <p class="text-muted small mb-0">
          Pagină demo pentru localizarea reprezentanților de vânzări pe regiuni / județe.
        </p>
      </div>
      <div class="small text-muted">
        {{ filteredRepresentatives.length }} reprezentanți găsiți
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label small">Regiune</label>
        <select
          class="form-select form-select-sm"
          v-model="selectedRegion"
        >
          <option value="">Toate regiunile</option>
          <option
            v-for="region in regions"
            :key="region"
            :value="region"
          >
            {{ region }}
          </option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small">Județ</label>
        <select
          class="form-select form-select-sm"
          v-model="selectedCounty"
        >
          <option value="">Toate județele</option>
          <option
            v-for="county in counties"
            :key="county"
            :value="county"
          >
            {{ county }}
          </option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small">Căutare după nume</label>
        <input
          type="text"
          class="form-control form-control-sm"
          placeholder="nume reprezentant..."
          v-model="search"
        />
      </div>
    </div>

    <!-- "Hartă" demo -->
    <div class="border rounded bg-light p-3 mb-3 small">
      <div class="fw-semibold mb-1">Hartă interactivă (demo)</div>
      <p class="mb-0 text-muted">
        Într-o implementare reală aici ar fi integrată o hartă interactivă (ex. Leaflet / Google Maps)
        cu zonele de acoperire ale reprezentanților. În acest demo este doar o zonă de prezentare.
      </p>
    </div>

    <div class="row g-3">
      <div
        v-for="rep in filteredRepresentatives"
        :key="rep.id"
        class="col-md-4"
      >
        <div class="card h-100 shadow-sm">
          <div class="card-body small d-flex flex-column">
            <h2 class="h6 mb-1">{{ rep.name }}</h2>
            <p class="text-muted mb-2">
              Regiune: {{ rep.region }}<br />
              Județe: {{ rep.counties.join(', ') }}
            </p>
            <p class="mb-2">
              <strong>Telefon:</strong>
              <a :href="`tel:${rep.phone}`">{{ rep.phone }}</a><br />
              <strong>E-mail:</strong>
              <a :href="`mailto:${rep.email}`">{{ rep.email }}</a>
            </p>
            <div class="mt-auto">
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm w-100"
                @click="contactRepDemo(rep)"
              >
                Contactează
              </button>
            </div>
          </div>
        </div>
      </div>
      <div v-if="filteredRepresentatives.length === 0" class="col-12">
        <div class="alert alert-info small mb-0">
          Nu există reprezentanți care să corespundă filtrării.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const representatives = [
  {
    id: 1,
    name: 'Popescu Mihai',
    region: 'Sud',
    counties: ['București', 'Ilfov', 'Giurgiu'],
    phone: '+40 721 000 001',
    email: 'mihai.popescu@example.com'
  },
  {
    id: 2,
    name: 'Ionescu Adrian',
    region: 'Centru',
    counties: ['Brașov', 'Sibiu', 'Alba'],
    phone: '+40 721 000 002',
    email: 'adrian.ionescu@example.com'
  },
  {
    id: 3,
    name: 'Georgescu Ana',
    region: 'Nord',
    counties: ['Cluj', 'Bihor', 'Satu Mare'],
    phone: '+40 721 000 003',
    email: 'ana.georgescu@example.com'
  }
]

const selectedRegion = ref('')
const selectedCounty = ref('')
const search = ref('')

const regions = computed(() => {
  const set = new Set(representatives.map((r) => r.region))
  return Array.from(set).sort()
})

const counties = computed(() => {
  const set = new Set()
  representatives.forEach((r) => r.counties.forEach((c) => set.add(c)))
  return Array.from(set).sort()
})

const filteredRepresentatives = computed(() => {
  return representatives.filter((r) => {
    const matchesRegion = !selectedRegion.value || r.region === selectedRegion.value
    const matchesCounty =
      !selectedCounty.value || r.counties.includes(selectedCounty.value)
    const matchesSearch =
      !search.value ||
      r.name.toLowerCase().includes(search.value.toLowerCase())

    return matchesRegion && matchesCounty && matchesSearch
  })
})

const contactRepDemo = (rep) => {
  window.alert(
    `Demo: s-ar deschide un formular de contact pentru reprezentantul ${rep.name} ` +
      `(${rep.email}, ${rep.phone}).`
  )
}
</script>
