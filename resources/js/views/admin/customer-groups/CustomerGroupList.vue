<template>
  <div class="container py-4">
    <PageHeader
      title="Grupuri de clienți"
      subtitle="Definire grupuri cu condiții comerciale implicite, promoții și segmentări."
    >
      <template #actions>
        <button type="button" class="btn btn-primary btn-sm" @click="onCreateGroup">
          <i class="bi bi-plus-lg me-1"></i>
          Adaugă grup (demo)
        </button>
      </template>
    </PageHeader>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-end">
              <div>
                <h2 class="h6 mb-1">Listă grupuri</h2>
                <p class="text-muted small mb-0">
                  Grupuri de clienți B2B / B2C cu condiții comerciale implicite și promoții asociate.
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
                    placeholder="Caută după nume, promoții, segment..."
                    v-model="searchTerm"
                  />
                </div>
                <select
                  class="form-select form-select-sm"
                  v-model="typeFilter"
                  aria-label="Filtru tip client"
                >
                  <option value="">Tip client: toți</option>
                  <option value="B2B">B2B</option>
                  <option value="B2C">B2C</option>
                </select>
              </div>
            </div>
          </div>

          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                  <tr>
                    <th style="width: 26%">Grup</th>
                    <th style="width: 8%">Tip</th>
                    <th style="width: 34%">Condiții comerciale implicite</th>
                    <th style="width: 16%">Promoții</th>
                    <th style="width: 10%">Segmentare</th>
                    <th style="width: 6%" class="text-end">Detalii</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="group in filteredGroups"
                    :key="group.id"
                    :class="{
                      'table-active': selectedGroup && selectedGroup.id === group.id
                    }"
                  >
                    <td>
                      <div class="fw-semibold">
                        {{ group.name }}
                        <span
                          v-if="group.isDefault"
                          class="badge bg-light text-primary border ms-1 align-middle"
                        >
                          implicit
                        </span>
                      </div>
                      <div class="small text-muted">
                        {{ group.notes }}
                      </div>
                    </td>
                    <td>
                      <span
                        class="badge"
                        :class="group.type === 'B2B' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success'"
                      >
                        {{ group.type }}
                      </span>
                    </td>
                    <td class="small">
                      {{ formatCommercialTerms(group) }}
                    </td>
                    <td>
                      <div class="d-flex flex-wrap gap-1 small">
                        <span
                          v-for="promo in group.promotions"
                          :key="promo"
                          class="badge bg-warning-subtle text-warning border"
                        >
                          {{ promo }}
                        </span>
                        <span v-if="group.promotions.length === 0" class="text-muted small">
                          Fără promoții implicite
                        </span>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex flex-wrap gap-1 small">
                        <span
                          v-for="tag in group.segmentTags"
                          :key="tag"
                          class="badge bg-light text-secondary border"
                        >
                          {{ tag }}
                        </span>
                      </div>
                    </td>
                    <td class="text-end">
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        @click="selectGroup(group)"
                      >
                        <i class="bi bi-eye me-1"></i>
                        Vezi
                      </button>
                    </td>
                  </tr>
                  <tr v-if="filteredGroups.length === 0">
                    <td colspan="6" class="text-center py-4 text-muted small">
                      Nu există grupuri care să corespundă filtrelor selectate.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="card-footer bg-white border-0 small text-muted d-flex justify-content-between">
            <div>
              <i class="bi bi-info-circle me-1"></i>
              Date demo. Într-o implementare reală, grupurile ar proveni din baza de date / ERP,
              cu paginare, sortare și export.
            </div>
            <div>
              Grupuri afișate: <strong>{{ filteredGroups.length }}</strong> din
              <strong>{{ groups.length }}</strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-white border-0 pb-0">
            <h2 class="h6 mb-1">Detalii grup selectat</h2>
            <p class="text-muted small mb-0">
              Condiții comerciale, promoții implicite și segmentări de marketing.
            </p>
          </div>
          <div class="card-body">
            <div v-if="selectedGroup">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <div class="fw-semibold">
                    {{ selectedGroup.name }}
                  </div>
                  <div class="small text-muted">
                    ID intern: CG-{{ selectedGroup.id.toString().padStart(3, '0') }}
                  </div>
                </div>
                <div class="text-end small">
                  <span
                    class="badge"
                    :class="
                      selectedGroup.type === 'B2B'
                        ? 'bg-primary-subtle text-primary'
                        : 'bg-success-subtle text-success'
                    "
                  >
                    {{ selectedGroup.type }}
                  </span>
                  <div v-if="selectedGroup.isDefault" class="text-muted small mt-1">
                    Grup implicit pentru clienții nou creați.
                  </div>
                </div>
              </div>

              <hr />

              <div class="mb-3">
                <h3 class="h6 mb-2">Condiții comerciale implicite</h3>
                <ul class="small mb-2">
                  <li>
                    Discount standard: <strong>{{ selectedGroup.defaultDiscount }}%</strong>
                  </li>
                  <li>
                    Termen de plată: <strong>{{ selectedGroup.paymentTermDays }} zile</strong>
                  </li>
                  <li>
                    Limită de credit:
                    <strong v-if="selectedGroup.creditLimit">
                      {{ selectedGroup.creditLimit.toLocaleString('ro-RO') }}
                      {{ selectedGroup.currency }}
                    </strong>
                    <span v-else class="text-muted">nesetat</span>
                  </li>
                </ul>
                <p class="text-muted small mb-0">
                  În producție, aceste setări ar fi sincronizate cu contractele din ERP și ar putea
                  fi suprascrise punctual la nivel de client sau de companie.
                </p>
              </div>

              <div class="mb-3">
                <h3 class="h6 mb-2">Promoții aplicabile</h3>
                <div class="d-flex flex-wrap gap-1 mb-1 small">
                  <span
                    v-for="promo in selectedGroup.promotions"
                    :key="promo"
                    class="badge bg-warning-subtle text-warning border"
                  >
                    {{ promo }}
                  </span>
                  <span v-if="selectedGroup.promotions.length === 0" class="text-muted">
                    Fără promoții implicite pentru acest grup.
                  </span>
                </div>
                <p class="text-muted small mb-0">
                  Într-o implementare reală, aici s-ar lista regulile de promoții asociate grupului,
                  cu perioadă de valabilitate și status (activă, viitoare, expirată).
                </p>
              </div>

              <div class="mb-3">
                <h3 class="h6 mb-2">Segmentare & landing-uri</h3>
                <div class="d-flex flex-wrap gap-1 mb-2 small">
                  <span
                    v-for="tag in selectedGroup.segmentTags"
                    :key="tag"
                    class="badge bg-light text-secondary border"
                  >
                    {{ tag }}
                  </span>
                </div>
                <p class="text-muted small mb-0">
                  Segmentările sunt utilizate pentru a afișa bannere dedicate, landing-uri
                  personalizate sau campanii de e-mailing direcționate către acest grup.
                </p>
              </div>
            </div>

            <div v-else class="text-muted small">
              Selectează un grup din listă pentru a vedea detaliile sale.
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

const groups = ref([
  {
    id: 1,
    name: 'Distribuitori naționali',
    type: 'B2B',
    defaultDiscount: 12,
    paymentTermDays: 30,
    creditLimit: 150000,
    currency: 'RON',
    isDefault: true,
    promotions: ['PROMO-CONSTRUCTII-PRIMAVARA', 'BUNDLE-PROIECTE-MARI'],
    segmentTags: ['Distribuitori', 'B2B', 'Landing-uri dedicate'],
    notes: 'Distribuitori naționali de materiale de construcții, cu contracte cadru.'
  },
  {
    id: 2,
    name: 'Retaileri specializați',
    type: 'B2B',
    defaultDiscount: 8,
    paymentTermDays: 14,
    creditLimit: 60000,
    currency: 'RON',
    isDefault: false,
    promotions: ['PROMO-OUTLET-FINISAJE'],
    segmentTags: ['Retail', 'Showroom', 'Cross-sell finisaje'],
    notes: 'Magazine de bricolaj și showroom-uri regionale.'
  },
  {
    id: 3,
    name: 'Clienți finali VIP',
    type: 'B2C',
    defaultDiscount: 5,
    paymentTermDays: 0,
    creditLimit: null,
    currency: 'RON',
    isDefault: false,
    promotions: ['CUPON-LOYALTY-VIP'],
    segmentTags: ['B2C', 'Frecvent', 'Newsletter premium'],
    notes: 'Clienți finali cu volum recurent mare și comportament de loialitate ridicat.'
  }
])

const searchTerm = ref('')
const typeFilter = ref('')
const selectedGroup = ref(groups.value[0] || null)

const filteredGroups = computed(() => {
  const term = searchTerm.value.trim().toLowerCase()

  return groups.value.filter((group) => {
    const matchesType = !typeFilter.value || group.type === typeFilter.value

    if (!term) {
      return matchesType
    }

    const haystack = [
      group.name,
      group.notes,
      group.promotions.join(' '),
      group.segmentTags.join(' ')
    ]
      .join(' ')
      .toLowerCase()

    const matchesSearch = haystack.includes(term)

    return matchesType && matchesSearch
  })
})

const selectGroup = (group) => {
  selectedGroup.value = group
}

const formatCommercialTerms = (group) => {
  const parts = []

  parts.push(`Discount standard ${group.defaultDiscount}%`)
  parts.push(
    group.paymentTermDays > 0
      ? `termen plată ${group.paymentTermDays} zile`
      : 'fără termen de plată (plată imediată)'
  )

  if (group.creditLimit) {
    parts.push(
      `limită credit ${group.creditLimit.toLocaleString('ro-RO')} ${group.currency}`
    )
  } else {
    parts.push('fără limită de credit setată')
  }

  return parts.join(' · ')
}

const onCreateGroup = () => {
  window.alert(
    'Demo: aici s-ar deschide un formular pentru a crea un grup nou de clienți, cu condiții comerciale implicite și promoții asociate.'
  )
}
</script>
