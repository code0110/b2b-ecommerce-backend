<template>
  <div class="container-fluid py-3">
    <div class="row mb-3">
      <div class="col-lg-8">
        <h1 class="h5 mb-1">Setări livrare &amp; transport (demo)</h1>
        <p class="text-muted small mb-0">
          Această pagină simulează configurarea regulilor de transport: metode de livrare,
          praguri pe greutate şi valoare comandă, regiuni şi calcul de cost. Datele sunt
          demonstrative, dar structura acoperă scenariile uzuale B2B/B2C.
        </p>
      </div>
      <div class="col-lg-4 text-lg-end small mt-2 mt-lg-0">
        <div class="text-muted">
          Număr metode active: <strong>{{ activeMethodsCount }}</strong>
        </div>
        <div class="text-muted">
          Reguli definite: <strong>{{ shippingRules.length }}</strong>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <!-- Metode livrare -->
      <div class="col-xl-4">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Metode de livrare (demo)</strong>
          </div>
          <div class="card-body small">
            <div
              v-for="method in shippingMethods"
              :key="method.id"
              class="border rounded p-2 mb-2"
              :class="method.active ? 'border-success' : 'border-secondary'"
            >
              <div class="d-flex justify-content-between align-items-center mb-1">
                <div>
                  <strong>{{ method.name }}</strong>
                  <span class="badge ms-1" :class="methodBadgeClass(method.type)">
                    {{ methodTypeLabel(method.type) }}
                  </span>
                </div>
                <span
                  class="badge"
                  :class="method.active ? 'bg-success' : 'bg-secondary'"
                >
                  {{ method.active ? 'Activă' : 'Inactivă' }}
                </span>
              </div>
              <div class="text-muted">
                <div v-if="method.description">
                  {{ method.description }}
                </div>
                <div v-if="method.baseRegion">
                  Zonă principală: {{ method.baseRegion }}
                </div>
                <div v-if="method.carrierName">
                  Curier: {{ method.carrierName }}
                </div>
              </div>
            </div>
            <p class="text-muted mb-0">
              <strong>Notă demo:</strong> în producţie, aceste metode ar fi editabile şi
              sincronizate cu contractele de curierat şi regulile de livrare din ERP.
            </p>
          </div>
        </div>
      </div>

      <!-- Reguli transport -->
      <div class="col-xl-5">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong class="small text-uppercase">Reguli transport pe greutate &amp; valoare</strong>
            <span class="badge bg-light text-dark small">
              {{ shippingRules.length }} reguli
            </span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light small text-uppercase text-muted">
                  <tr>
                    <th>Metodă</th>
                    <th class="text-center" style="width: 130px;">Greutate (kg)</th>
                    <th class="text-center" style="width: 120px;">Regiune</th>
                    <th class="text-end" style="width: 110px;">Cost bază</th>
                    <th class="text-end" style="width: 140px;">Transport gratuit</th>
                  </tr>
                </thead>
                <tbody class="small">
                  <tr v-for="rule in shippingRules" :key="rule.id">
                    <td>
                      <div class="fw-semibold">{{ methodName(rule.methodId) }}</div>
                      <div class="text-muted">
                        {{ rule.description }}
                      </div>
                    </td>
                    <td class="text-center">
                      {{ rule.minWeight }} –
                      <span v-if="rule.maxWeight">{{ rule.maxWeight }}</span>
                      <span v-else>&gt;=</span>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark">
                        {{ regionLabel(rule.regionType, rule.regionCode) }}
                      </span>
                    </td>
                    <td class="text-end">
                      {{ formatMoney(rule.baseCost) }}
                    </td>
                    <td class="text-end">
                      <span v-if="rule.freeOverValue">
                        &gt; {{ formatMoney(rule.freeOverValue) }}
                      </span>
                      <span v-else class="text-muted">fără prag</span>
                    </td>
                  </tr>
                  <tr v-if="shippingRules.length === 0">
                    <td colspan="5">
                      <div class="text-center text-muted py-4">
                        Nu există reguli definite în acest template demo.
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer small text-muted">
            <strong>Prioritate aplicare (demo):</strong>
            mai întâi se caută o regulă specifică zonei (ex. regională), apoi o
            regulă naţională. În producţie, această logică ar fi explicit configurabilă.
          </div>
        </div>
      </div>

      <!-- Calculator cost -->
      <div class="col-xl-3">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Calculator cost transport (demo)</strong>
          </div>
          <div class="card-body small">
            <div class="mb-2">
              <label class="form-label">Metodă livrare</label>
              <select
                v-model.number="sim.methodId"
                class="form-select form-select-sm"
              >
                <option :value="0">Alege metodă</option>
                <option
                  v-for="method in shippingMethods"
                  :key="method.id"
                  :value="method.id"
                >
                  {{ method.name }}
                </option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Greutate comandă (kg)</label>
              <input
                v-model.number="sim.weight"
                type="number"
                min="0"
                step="0.5"
                class="form-control form-control-sm"
              />
            </div>
            <div class="mb-2">
              <label class="form-label">Valoare comandă (RON)</label>
              <input
                v-model.number="sim.orderValue"
                type="number"
                min="0"
                step="10"
                class="form-control form-control-sm"
              />
            </div>
            <div class="mb-2">
              <label class="form-label">Tip regiune</label>
              <select
                v-model="sim.regionType"
                class="form-select form-select-sm"
              >
                <option value="national">Naţional</option>
                <option value="regional">Regional</option>
              </select>
            </div>
            <div class="mb-2" v-if="sim.regionType === 'regional'">
              <label class="form-label">Regiune</label>
              <select
                v-model="sim.regionCode"
                class="form-select form-select-sm"
              >
                <option value="RO-B">Bucureşti &amp; Ilfov</option>
                <option value="RO-NV">Nord-Vest</option>
                <option value="RO-CT">Constanţa &amp; litoral</option>
              </select>
            </div>
            <div class="d-grid mt-2 mb-3">
              <button
                type="button"
                class="btn btn-primary btn-sm"
                @click="simulateShipping"
              >
                Calculează cost estimativ
              </button>
            </div>
            <div v-if="simResult" class="alert alert-info mb-2">
              <div class="fw-semibold mb-1">Rezultat estimativ:</div>
              <div>{{ simResult }}</div>
            </div>
            <p class="text-muted mb-0">
              <strong>Notă:</strong> logica este strict demonstrativă. În producţie,
              calculul costului de transport ar fi centralizat (backend / ERP) pentru
              consistenţă între coş, checkout şi documente.
            </p>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body small">
            <h2 class="h6 text-uppercase text-muted mb-2">Reguli avansate (concept)</h2>
            <ul class="mb-0 ps-3">
              <li>combinaţie reguli pe greutate şi volum;</li>
              <li>reguli specifice pentru anumite categorii produse;</li>
              <li>transport gratuit pentru anumite branduri / promoţii;</li>
              <li>cost suplimentar zone greu accesibile.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const shippingMethods = ref([
  {
    id: 1,
    name: 'Curier standard',
    type: 'courier',
    active: true,
    baseRegion: 'Naţional',
    carrierName: 'Curier Demo',
    description: 'Livrare în 24–48h pentru majoritatea localităţilor.'
  },
  {
    id: 2,
    name: 'Flotă proprie',
    type: 'own_fleet',
    active: true,
    baseRegion: 'Zone urbane şi periurbane',
    carrierName: '',
    description: 'Livrare cu flota proprie în zonele acoperite, pentru comenzi B2B.'
  },
  {
    id: 3,
    name: 'Ridicare din depozit',
    type: 'pickup',
    active: true,
    baseRegion: 'Depozit central',
    carrierName: '',
    description: 'Ridicare marfă de către client, pe bază de programare.'
  }
])

const shippingRules = ref([
  {
    id: 1,
    methodId: 1,
    minWeight: 0,
    maxWeight: 10,
    regionType: 'national',
    regionCode: '',
    baseCost: 25,
    freeOverValue: 500,
    description: 'Curier standard, colet mic/mediu, naţional.'
  },
  {
    id: 2,
    methodId: 1,
    minWeight: 10,
    maxWeight: 40,
    regionType: 'national',
    regionCode: '',
    baseCost: 45,
    freeOverValue: 750,
    description: 'Curier standard, colet voluminos, naţional.'
  },
  {
    id: 3,
    methodId: 1,
    minWeight: 0,
    maxWeight: 30,
    regionType: 'regional',
    regionCode: 'RO-B',
    baseCost: 20,
    freeOverValue: 400,
    description: 'Curier standard, Bucureşti & Ilfov.'
  },
  {
    id: 4,
    methodId: 2,
    minWeight: 0,
    maxWeight: 200,
    regionType: 'regional',
    regionCode: 'RO-NV',
    baseCost: 80,
    freeOverValue: 1500,
    description: 'Flotă proprie, nord-vest (camionetă).'
  },
  {
    id: 5,
    methodId: 2,
    minWeight: 200,
    maxWeight: null,
    regionType: 'regional',
    regionCode: 'RO-NV',
    baseCost: 150,
    freeOverValue: 3000,
    description: 'Flotă proprie, nord-vest – încărcături mari.'
  },
  {
    id: 6,
    methodId: 3,
    minWeight: 0,
    maxWeight: null,
    regionType: 'national',
    regionCode: '',
    baseCost: 0,
    freeOverValue: null,
    description: 'Ridicare din depozit – fără cost transport.'
  }
])

const sim = ref({
  methodId: 0,
  weight: 0,
  orderValue: 0,
  regionType: 'national',
  regionCode: 'RO-B'
})

const simResult = ref('')

const activeMethodsCount = computed(() =>
  shippingMethods.value.filter((m) => m.active).length
)

const methodName = (methodId) => {
  const m = shippingMethods.value.find((x) => x.id === methodId)
  return m ? m.name : 'Metodă necunoscută'
}

const methodTypeLabel = (type) => {
  switch (type) {
    case 'courier':
      return 'Curier'
    case 'own_fleet':
      return 'Flotă proprie'
    case 'pickup':
      return 'Ridicare din depozit'
    default:
      return type
  }
}

const methodBadgeClass = (type) => {
  switch (type) {
    case 'courier':
      return 'bg-primary'
    case 'own_fleet':
      return 'bg-info text-dark'
    case 'pickup':
      return 'bg-secondary'
    default:
      return 'bg-light text-dark'
  }
}

const regionLabel = (regionType, regionCode) => {
  if (regionType === 'national') {
    return 'Naţional'
  }
  if (regionType === 'regional') {
    switch (regionCode) {
      case 'RO-B':
        return 'Bucureşti & Ilfov'
      case 'RO-NV':
        return 'Nord-Vest'
      case 'RO-CT':
        return 'Constanţa & litoral'
      default:
        return 'Regional'
    }
  }
  return regionType
}

const formatMoney = (value) => {
  const number = Number(value || 0)
  return (
    number.toLocaleString('ro-RO', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' RON'
  )
}

const findMatchingRule = (methodId, weight, regionType, regionCode) => {
  const candidates = shippingRules.value.filter((rule) => {
    if (rule.methodId !== methodId) return false
    if (regionType === 'regional' && rule.regionType === 'regional') {
      if (rule.regionCode !== regionCode) return false
    }
    if (regionType === 'national' && rule.regionType === 'regional') {
      return false
    }
    const minOk = weight >= rule.minWeight
    const maxOk = rule.maxWeight == null ? true : weight <= rule.maxWeight
    return minOk && maxOk
  })

  if (candidates.length > 0) {
    candidates.sort((a, b) => {
      if (a.regionType === 'regional' && b.regionType !== 'regional') return -1
      if (b.regionType === 'regional' && a.regionType !== 'regional') return 1
      const aSpan = (a.maxWeight ?? (a.minWeight + 100000)) - a.minWeight
      const bSpan = (b.maxWeight ?? (b.minWeight + 100000)) - b.minWeight
      return aSpan - bSpan
    })
    return candidates[0]
  }

  const fallback = shippingRules.value.find(
    (rule) => rule.methodId === methodId && rule.regionType === 'national'
  )
  return fallback || null
}

const simulateShipping = () => {
  if (!sim.value.methodId) {
    window.alert('Selectează o metodă de livrare pentru calcul (demo).')
    return
  }
  if (!sim.value.weight || sim.value.weight <= 0) {
    window.alert('Introdu o greutate pozitivă pentru comandă (demo).')
    return
  }

  const method = shippingMethods.value.find((m) => m.id === sim.value.methodId)
  if (!method) {
    simResult.value = 'Metoda selectată nu a fost găsită.'
    return
  }

  const rule = findMatchingRule(
    sim.value.methodId,
    sim.value.weight,
    sim.value.regionType,
    sim.value.regionCode
  )

  if (!rule) {
    simResult.value =
      'Nu a fost găsită nicio regulă de transport pentru combinaţia introdusă. În producţie, aici s-ar aplica o regulă de fallback.'
    return
  }

  let cost = rule.baseCost
  let note = ''

  if (rule.freeOverValue && sim.value.orderValue >= rule.freeOverValue) {
    cost = 0
    note =
      'Pragul de transport gratuit a fost atins conform regulilor definite pentru această metodă.'
  } else if (rule.freeOverValue) {
    const diff = rule.freeOverValue - sim.value.orderValue
    note =
      'Comanda nu a atins pragul de transport gratuit. Mai sunt necesari ' +
      formatMoney(diff) +
      ' pentru livrare gratuită.'
  } else {
    note =
      'Pentru această regulă nu este definit un prag de transport gratuit.'
  }

  simResult.value =
    'Metodă: ' +
    method.name +
    ' · Regiune: ' +
    regionLabel(sim.value.regionType, sim.value.regionCode) +
    '. Cost estimat transport: ' +
    formatMoney(cost) +
    '. ' +
    note
}
</script>
