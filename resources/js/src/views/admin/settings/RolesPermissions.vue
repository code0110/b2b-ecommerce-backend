<template>
  <div class="container-fluid py-4">
    <PageHeader
      title="Roluri & permisiuni"
      subtitle="Matrice de permisiuni pentru administrator, operator, agent, director, marketer și alți utilizatori interni."
    >
      <!-- Slot pentru butoane de acțiune (ex: Export, Sincronizare cu IDM/SSO etc.) -->
    </PageHeader>

    <div class="row g-3 mb-3">
      <div class="col-lg-7">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong class="small text-uppercase">Roluri definite</strong>
            <span class="text-muted small">
              Roluri demo – în implementarea reală, ar fi gestionate din backend / IDM.
            </span>
          </div>
          <div class="card-body small">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 180px;">Rol</th>
                    <th>Descriere</th>
                    <th style="width: 160px;">Tip utilizator</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="role in roles" :key="role.key">
                    <td>
                      <div class="fw-semibold">{{ role.label }}</div>
                      <div class="text-muted">{{ role.key }}</div>
                    </td>
                    <td>
                      {{ role.description }}
                    </td>
                    <td>
                      <span
                        class="badge"
                        :class="role.type === 'internal' ? 'bg-primary' : 'bg-secondary'"
                      >
                        {{ role.type === 'internal' ? 'Intern (admin / back-office)' : 'Client (cont front)' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer small text-muted">
            În practică, rolurile interne pot fi mapate pe grupuri din Active Directory / LDAP sau pe roluri din ERP.
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Legendă permisiuni</strong>
          </div>
          <div class="card-body small">
            <p class="text-muted mb-2">
              Matricea de mai jos este o reprezentare vizuală. În implementarea reală, permisiunile ar fi salvate la nivel de backend și ar acoperi inclusiv API-urile.
            </p>
            <ul class="list-unstyled mb-3">
              <li class="mb-1 d-flex align-items-center">
                <span class="badge bg-success me-2">&nbsp;</span>
                <span><strong>Acces complet</strong> – listare, creare, editare, ștergere, configurări.</span>
              </li>
              <li class="mb-1 d-flex align-items-center">
                <span class="badge bg-warning text-dark me-2">&nbsp;</span>
                <span><strong>Editare limitată</strong> – modificări operaționale (fără setări globale).</span>
              </li>
              <li class="mb-1 d-flex align-items-center">
                <span class="badge bg-info text-dark me-2">&nbsp;</span>
                <span><strong>Doar vizualizare</strong> – acces read-only.</span>
              </li>
              <li class="mb-1 d-flex align-items-center">
                <span class="badge bg-light text-muted border me-2">&nbsp;</span>
                <span><strong>Fără acces</strong> – modulul nu este disponibil în interfață.</span>
              </li>
            </ul>
            <div class="border rounded p-2 bg-light">
              <div class="mb-2"><strong>Notă demo:</strong></div>
              <p class="mb-1">
                Click pe o celulă din matrice pentru a simula schimbarea nivelului de acces. Modificarea este doar locală, nu se salvează nicăieri.
              </p>
              <p class="mb-0">
                În producție, aceste schimbări ar declanșa un apel la un API de administrare / IDM, cu audit separat.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header py-2 d-flex justify-content-between align-items-center small">
        <div>
          <strong class="text-uppercase">Matrice permisiuni pe module</strong>
        </div>
        <div class="d-flex align-items-center gap-2">
          <div class="d-none d-md-flex align-items-center">
            <span class="me-1">Evidențiază rol:</span>
            <select v-model="highlightRole" class="form-select form-select-sm w-auto">
              <option value="">Toate</option>
              <option v-for="role in roles" :key="role.key" :value="role.key">
                {{ role.label }}
              </option>
            </select>
          </div>
          <div class="d-none d-md-flex align-items-center">
            <span class="me-1">Evidențiază modul:</span>
            <select v-model="highlightArea" class="form-select form-select-sm w-auto">
              <option value="">Toate</option>
              <option v-for="area in permissionAreas" :key="area.key" :value="area.key">
                {{ area.label }}
              </option>
            </select>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light small">
              <tr>
                <th style="width: 220px;">Modul / resursă</th>
                <th
                  v-for="role in roles"
                  :key="role.key"
                  class="text-center"
                >
                  <div class="fw-semibold">{{ role.label }}</div>
                  <div class="text-muted">{{ role.shortLabel }}</div>
                </th>
              </tr>
            </thead>
            <tbody class="small">
              <tr
                v-for="area in permissionAreas"
                :key="area.key"
                :class="{
                  'table-active': highlightArea === area.key
                }"
              >
                <td>
                  <div class="fw-semibold">{{ area.label }}</div>
                  <div class="text-muted">{{ area.description }}</div>
                </td>
                <td
                  v-for="role in roles"
                  :key="role.key"
                  class="text-center"
                  :class="cellWrapperClass(area.key, role.key)"
                >
                  <button
                    type="button"
                    class="btn btn-xs btn-sm px-2 py-1"
                    :class="cellButtonClass(area.key, role.key)"
                    @click="togglePermission(area.key, role.key)"
                  >
                    <span class="d-block">
                      {{ permissionLabel(matrix[area.key][role.key]) }}
                    </span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer small text-muted">
        Aceasta este o matrice statică pentru demo. Într-un proiect real, permisiunile ar fi evaluate și în backend, nu doar în UI, iar schimbările ar fi logate în Audit log.
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'

const roles = ref([
  {
    key: 'admin',
    label: 'Administrator',
    shortLabel: 'Admin',
    description: 'Acces complet la toate modulele, inclusiv setări și audit.',
    type: 'internal'
  },
  {
    key: 'director',
    label: 'Director vânzări',
    shortLabel: 'Director',
    description: 'Acces complet pe zona comercială și vizualizare pe setări.',
    type: 'internal'
  },
  {
    key: 'agent',
    label: 'Agent vânzări',
    shortLabel: 'Agent',
    description: 'Acces pe portofoliul de clienți și comenzi aferente.',
    type: 'internal'
  },
  {
    key: 'operator',
    label: 'Operator birou / suport',
    shortLabel: 'Operator',
    description: 'Acces operațional pe comenzi, clienți și ticketing.',
    type: 'internal'
  },
  {
    key: 'marketer',
    label: 'Marketer',
    shortLabel: 'Marketer',
    description: 'Acces pe promoții, campanii, bannere și conținut.',
    type: 'internal'
  }
])

const permissionAreas = ref([
  {
    key: 'products',
    label: 'Produse & prețuri',
    description: 'Catalog produse, prețuri, stocuri și atribute.'
  },
  {
    key: 'promotions',
    label: 'Promoții & discounturi',
    description: 'Campanii, reguli de discount, landing pages.'
  },
  {
    key: 'customers',
    label: 'Clienți & grupuri',
    description: 'Fișe clienți, grupuri, condiții comerciale.'
  },
  {
    key: 'orders',
    label: 'Comenzi & livrări',
    description: 'Comenzi, AWB, statusuri de livrare.'
  },
  {
    key: 'payments',
    label: 'Plăți & încasări',
    description: 'Plăți online, încasări CHS/BO/CEC, reconciliere.'
  },
  {
    key: 'shipping',
    label: 'Reguli transport',
    description: 'Configurare curieri, regiuni, praguri de transport.'
  },
  {
    key: 'content',
    label: 'Conținut & marketing',
    description: 'Bannere, blog, pagini statice, reprezentanți.'
  },
  {
    key: 'settings',
    label: 'Setări & audit',
    description: 'Configurări generale, roluri, audit log.'
  }
])

const matrix = reactive({
  products: {
    admin: 'full',
    director: 'full',
    agent: 'edit',
    operator: 'edit',
    marketer: 'read'
  },
  promotions: {
    admin: 'full',
    director: 'edit',
    agent: 'read',
    operator: 'read',
    marketer: 'full'
  },
  customers: {
    admin: 'full',
    director: 'full',
    agent: 'edit',
    operator: 'edit',
    marketer: 'read'
  },
  orders: {
    admin: 'full',
    director: 'full',
    agent: 'edit',
    operator: 'edit',
    marketer: 'read'
  },
  payments: {
    admin: 'full',
    director: 'edit',
    agent: 'read',
    operator: 'edit',
    marketer: 'none'
  },
  shipping: {
    admin: 'full',
    director: 'read',
    agent: 'none',
    operator: 'none',
    marketer: 'none'
  },
  content: {
    admin: 'full',
    director: 'read',
    agent: 'none',
    operator: 'none',
    marketer: 'full'
  },
  settings: {
    admin: 'full',
    director: 'read',
    agent: 'none',
    operator: 'none',
    marketer: 'none'
  }
})

const levels = ['none', 'read', 'edit', 'full']

const highlightRole = ref('')
const highlightArea = ref('')

const permissionLabel = (level) => {
  if (level === 'full') return 'Complet'
  if (level === 'edit') return 'Editare'
  if (level === 'read') return 'Vizualizare'
  return 'Fără acces'
}

const cellButtonClass = (areaKey, roleKey) => {
  const level = matrix[areaKey][roleKey]
  if (level === 'full') return 'btn-success'
  if (level === 'edit') return 'btn-warning text-dark'
  if (level === 'read') return 'btn-info text-dark'
  return 'btn-light text-muted border'
}

const cellWrapperClass = (areaKey, roleKey) => {
  const classes = {}
  if (highlightRole.value && highlightRole.value === roleKey) {
    classes['table-primary'] = true
  }
  if (highlightArea.value && highlightArea.value === areaKey) {
    classes['table-primary'] = true
  }
  return classes
}

const togglePermission = (areaKey, roleKey) => {
  const current = matrix[areaKey][roleKey] || 'none'
  const index = levels.indexOf(current)
  const next = levels[(index + 1) % levels.length]
  matrix[areaKey][roleKey] = next
}
</script>
