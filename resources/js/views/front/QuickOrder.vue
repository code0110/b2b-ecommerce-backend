<template>
  <div class="container py-4">
    <div class="row mb-3">
      <div class="col-md-8">
        <h1 class="h5 mb-1">Comandă rapidă (Quick order – demo)</h1>
        <p class="text-muted small mb-0">
          Pagină orientată B2B pentru introducerea rapidă a produselor în coș,
          pe bază de cod produs, denumire sau eventual cod client. Datele sunt
          demonstrative – în implementarea reală, ar exista integrare cu ERP
          (liste de prețuri, coduri client, șabloane de comandă).
        </p>
      </div>
      <div class="col-md-4 text-md-end small mt-2 mt-md-0" v-if="frontClientLabel">
        <div class="text-muted">
          Client activ: <strong>{{ frontClientLabel }}</strong>
        </div>
        <div class="text-muted" v-if="isImpersonating">
          (mod impersonare – {{ user?.name || 'agent/director' }})
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body small">
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Căutare rapidă</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="denumire, cod produs, cod client (demo)"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label">Categorie (demo)</label>
            <select
              v-model="filters.category"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="materiale">Materiale</option>
              <option value="echipamente">Echipamente</option>
              <option value="consumabile">Consumabile</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Disponibilitate</label>
            <select
              v-model="filters.availability"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="in_stock">În stoc</option>
              <option value="supplier">La comandă</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Afișare</label>
            <select
              v-model.number="filters.limit"
              class="form-select form-select-sm"
            >
              <option :value="10">Top 10</option>
              <option :value="25">Top 25</option>
              <option :value="50">Top 50</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong class="small text-uppercase">Listă produse (demo)</strong>
        <span class="badge bg-light text-dark small">
          {{ filteredProducts.length }} produse afișate
        </span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light small text-uppercase text-muted">
              <tr>
                <th style="width: 40px;" class="text-center">#</th>
                <th>Produs</th>
                <th style="width: 120px;">Cod intern</th>
                <th style="width: 110px;" class="text-end">Preț</th>
                <th style="width: 90px;" class="text-center">Stoc</th>
                <th style="width: 120px;" class="text-center">Cantitate</th>
              </tr>
            </thead>
            <tbody class="small">
              <tr v-for="(product, index) in filteredProducts" :key="product.id">
                <td class="text-center">{{ index + 1 }}</td>
                <td>
                  <div class="fw-semibold">{{ product.name }}</div>
                  <div class="text-muted">
                    {{ product.categoryLabel }} · {{ product.attributeSummary }}
                  </div>
                </td>
                <td>
                  <span class="text-monospace">{{ product.code }}</span>
                </td>
                <td class="text-end">
                  {{ formatMoney(product.price) }}
                </td>
                <td class="text-center">
                  <span
                    class="badge"
                    :class="{
                      'bg-success': product.stockStatus === 'in_stock',
                      'bg-secondary': product.stockStatus === 'supplier'
                    }"
                  >
                    {{ stockStatusLabel(product.stockStatus) }}
                  </span>
                </td>
                <td class="text-center" style="max-width: 120px;">
                  <input
                    type="number"
                    min="0"
                    class="form-control form-control-sm text-center"
                    v-model.number="product.quantity"
                  />
                </td>
              </tr>
              <tr v-if="filteredProducts.length === 0">
                <td colspan="6">
                  <div class="text-center text-muted py-4">
                    Nu există produse care să corespundă filtrării curente.
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center small">
        <div class="text-muted">
          Cantitate totală introdusă: <strong>{{ totalQuantity }}</strong> buc.
          Valoare estimată: <strong>{{ formatMoney(totalValue) }}</strong>
        </div>
        <div class="d-flex gap-2">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            @click="resetQuantities"
          >
            Resetează cantitățile
          </button>
          <button
            type="button"
            class="btn btn-primary btn-sm"
            :disabled="totalQuantity === 0"
            @click="addAllToCart"
          >
            Adaugă tot în coș (demo)
          </button>
        </div>
      </div>
    </div>

    <div class="alert alert-info mt-3 small">
      <strong>Notă demo:</strong>
      Într-un scenariu B2B avansat, această pagină ar putea suporta și:
      import de comenzi din Excel, folosirea codurilor proprii de client,
      șabloane salvate de comenzi recurente și verificare în timp real a
      condițiilor comerciale și a limitelor de credit.
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useAuthStore } from '@/store/auth'

const authStore = useAuthStore()

const user = computed(() => authStore.user || null)
const isImpersonating = computed(() => !!authStore.impersonatedCustomer)
const frontClientLabel = computed(() => {
  if (authStore.impersonatedCustomer) {
    return authStore.impersonatedCustomer.name || ''
  }
  return authStore.user?.name || ''
})

const products = ref([
  {
    id: 1,
    name: 'Ciment Portland 40kg',
    code: 'PRD-CIM-40',
    category: 'materiale',
    categoryLabel: 'Materiale',
    attributeSummary: 'Tip: ciment · Ambalaj: sac 40kg',
    stockStatus: 'in_stock',
    price: 25,
    quantity: 0
  },
  {
    id: 2,
    name: 'Adeziv gresie/faianță flexibil',
    code: 'PRD-ADZ-FLX',
    category: 'materiale',
    categoryLabel: 'Materiale',
    attributeSummary: 'Tip: adeziv · Utilizare: interior/exterior',
    stockStatus: 'in_stock',
    price: 35,
    quantity: 0
  },
  {
    id: 3,
    name: 'Echipament protecție – cască',
    code: 'PRD-ECH-CAS',
    category: 'echipamente',
    categoryLabel: 'Echipamente',
    attributeSummary: 'Categoria: protecție personală',
    stockStatus: 'supplier',
    price: 80,
    quantity: 0
  },
  {
    id: 4,
    name: 'Mănuși muncă nitril',
    code: 'PRD-CNS-MAN',
    category: 'consumabile',
    categoryLabel: 'Consumabile',
    attributeSummary: 'Marime: L · Ambalaj: 100 buc',
    stockStatus: 'in_stock',
    price: 18,
    quantity: 0
  }
])

const filters = ref({
  search: '',
  category: '',
  availability: '',
  limit: 25
})

const filteredProducts = computed(() => {
  const search = filters.value.search.trim().toLowerCase()

  let list = products.value.filter((p) => {
    const matchesSearch =
      !search ||
      p.name.toLowerCase().includes(search) ||
      p.code.toLowerCase().includes(search)

    const matchesCategory =
      !filters.value.category || p.category === filters.value.category

    const matchesAvailability =
      !filters.value.availability || p.stockStatus === filters.value.availability

    return matchesSearch && matchesCategory && matchesAvailability
  })

  if (filters.value.limit && filters.value.limit > 0) {
    list = list.slice(0, filters.value.limit)
  }

  return list
})

const totalQuantity = computed(() =>
  products.value.reduce((sum, p) => sum + (p.quantity || 0), 0)
)

const totalValue = computed(() =>
  products.value.reduce((sum, p) => sum + p.price * (p.quantity || 0), 0)
)

const stockStatusLabel = (status) => {
  switch (status) {
    case 'in_stock':
      return 'În stoc'
    case 'supplier':
      return 'La comandă / în stoc furnizor'
    default:
      return status
  }
}

const formatMoney = (value) => {
  const number = Number(value || 0)
  return number.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }) + ' RON'
}

const resetQuantities = () => {
  products.value.forEach((p) => {
    p.quantity = 0
  })
}

const addAllToCart = () => {
  if (totalQuantity.value === 0) {
    window.alert('Introdu cel puțin o cantitate pentru a adăuga în coș (demo).')
    return
  }

  window.alert(
    'Demo: cantitățile introduse ar fi trimise către store-ul de coș și backend.\nÎn implementarea reală, aici s-ar crea pozițiile în coș și s-ar putea deschide pagina de coș sau de checkout.'
  )

  resetQuantities()
}
</script>
