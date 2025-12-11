<template>
  <div class="container-fluid py-4">
    <div class="mb-3">
      <button
        type="button"
        class="btn btn-link text-decoration-none ps-0"
        @click="goBack"
      >
        ← Înapoi la lista de comenzi
      </button>
    </div>

    <div v-if="!order" class="alert alert-warning">
      Comanda nu a fost găsită în setul de date demo.
    </div>

    <div v-else class="row g-3">
      <!-- Col stânga: detalii comandă -->
      <div class="col-xl-8">
        <div class="card shadow-sm mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">Comanda {{ order.code }}</h4>
              <div class="text-muted small">
                Data: {{ order.date }} • Total:
                <strong>
                  {{ order.total.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                  RON
                </strong>
              </div>
            </div>
            <div class="text-end">
              <span
                class="badge"
                :class="{
                  'bg-warning text-dark': order.status === 'in_procesare',
                  'bg-info text-dark': order.status === 'in_livrare',
                  'bg-success': order.status === 'livrata',
                  'bg-danger': order.status === 'blocata'
                }"
              >
                {{
                  order.status === 'in_procesare'
                    ? 'În procesare'
                    : order.status === 'in_livrare'
                      ? 'În livrare'
                      : order.status === 'livrata'
                        ? 'Livrată'
                        : 'Blocată'
                }}
              </span>
              <div class="small text-muted mt-1" v-if="order.creditBlocked">
                Blocată pe credit – necesită aprobare director / admin.
              </div>
            </div>
          </div>
          <div class="card-body small">
            <h6 class="text-uppercase text-muted mb-2">Linii comandă (demo)</h6>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Produs</th>
                    <th style="width: 120px;">Cod intern</th>
                    <th style="width: 90px;" class="text-end">Cant.</th>
                    <th style="width: 120px;" class="text-end">Preț unit. (RON)</th>
                    <th style="width: 120px;" class="text-end">Total linie (RON)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(line, idx) in order.lines" :key="idx">
                    <td>
                      <div class="fw-semibold">{{ line.productName }}</div>
                      <div class="text-muted">
                        {{ line.productBrand }} • {{ line.category }}
                      </div>
                    </td>
                    <td>{{ line.internalCode }}</td>
                    <td class="text-end">{{ line.quantity }}</td>
                    <td class="text-end">
                      {{ line.unitPrice.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                    </td>
                    <td class="text-end">
                      {{ (line.unitPrice * line.quantity).toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Mesaje / istoric status (demo) -->
        <div class="card shadow-sm">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Istoric status & mesaje</strong>
          </div>
          <div class="card-body small">
            <ul class="list-unstyled mb-0">
              <li
                v-for="(event, idx) in order.history"
                :key="idx"
                class="mb-2"
              >
                <div class="fw-semibold">
                  {{ event.when }} – {{ event.title }}
                </div>
                <div class="text-muted">
                  {{ event.description }}
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Col dreapta: client & ierarhie -->
      <div class="col-xl-4">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Client & tip</strong>
          </div>
          <div class="card-body small">
            <dl class="row mb-0">
              <dt class="col-4">Client</dt>
              <dd class="col-8">
                <div class="fw-semibold">{{ order.clientName }}</div>
                <div class="text-muted">
                  {{ order.clientIdentifier }}
                </div>
              </dd>

              <dt class="col-4">Tip client</dt>
              <dd class="col-8">
                <span
                  class="badge"
                  :class="order.clientType === 'B2B' ? 'bg-primary' : 'bg-secondary'"
                >
                  {{ order.clientType }}
                </span>
              </dd>

              <dt class="col-4">Adresă livrare</dt>
              <dd class="col-8">
                <div>{{ order.shippingAddress.line1 }}</div>
                <div>{{ order.shippingAddress.city }}, {{ order.shippingAddress.county }}</div>
              </dd>

              <dt class="col-4">Adresă facturare</dt>
              <dd class="col-8">
                <div>{{ order.billingAddress.line1 }}</div>
                <div>{{ order.billingAddress.city }}, {{ order.billingAddress.county }}</div>
              </dd>
            </dl>
          </div>
        </div>

        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Inițiator & ierarhie</strong>
          </div>
          <div class="card-body small">
            <dl class="row mb-0">
              <dt class="col-5">Inițiator</dt>
              <dd class="col-7">
                <div class="fw-semibold">{{ order.initiatorName }}</div>
                <div class="text-muted">
                  {{ order.initiatorTypeLabel }}
                </div>
              </dd>

              <dt class="col-5">Mod plasare</dt>
              <dd class="col-7">
                <span v-if="order.isImpersonated">
                  Comandă plasată în numele clientului (impersonare).
                </span>
                <span v-else>
                  Comandă plasată direct de client din front-office.
                </span>
              </dd>

              <dt class="col-5">Agent</dt>
              <dd class="col-7">
                {{ order.agentName || 'Nealocat' }}
              </dd>

              <dt class="col-5">Director</dt>
              <dd class="col-7">
                {{ order.directorName || 'Nealocat' }}
              </dd>

              <dt class="col-5">Creat în sistem</dt>
              <dd class="col-7">
                {{ order.createdInErp ? 'ERP (import)' : 'Platformă online' }}
              </dd>
            </dl>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-header py-2">
            <strong class="small text-uppercase">Credit & condiții comerciale</strong>
          </div>
          <div class="card-body small">
            <dl class="row mb-0">
              <dt class="col-6">Limită credit</dt>
              <dd class="col-6 text-end">
                100.000 RON
              </dd>

              <dt class="col-6">Sold curent</dt>
              <dd class="col-6 text-end text-danger">
                24.350 RON
              </dd>

              <dt class="col-6">Încadrare</dt>
              <dd class="col-6 text-end">
                <span v-if="order.creditBlocked" class="text-danger">
                  Depășită (blocată)
                </span>
                <span v-else class="text-success">
                  În limită
                </span>
              </dd>

              <dt class="col-6">Termen plată</dt>
              <dd class="col-6 text-end">
                30 zile
              </dd>
            </dl>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const demoOrders = [
  {
    id: 1,
    code: 'CMD-1001',
    date: '2025-02-18',
    clientName: 'SC Construct Plus SRL',
    clientIdentifier: 'CUI RO12345678',
    clientType: 'B2B',
    status: 'in_procesare',
    creditBlocked: true,
    total: 24500.5,
    initiatorName: 'Popescu Mihai',
    initiatorType: 'agent',
    initiatorTypeLabel: 'Agent vânzări',
    isImpersonated: true,
    agentName: 'Popescu Mihai',
    directorName: 'Ionescu Adrian',
    createdInErp: false,
    shippingAddress: {
      line1: 'Str. Fabricii nr. 10',
      city: 'București',
      county: 'Ilfov'
    },
    billingAddress: {
      line1: 'Str. Depozitelor nr. 5',
      city: 'București',
      county: 'Ilfov'
    },
    lines: [
      {
        productName: 'Ciment Portland 40kg',
        productBrand: 'BrandX',
        category: 'Materiale de construcții',
        internalCode: 'PRD-001',
        quantity: 200,
        unitPrice: 45.0
      },
      {
        productName: 'Aditiv beton',
        productBrand: 'BrandY',
        category: 'Materiale de construcții',
        internalCode: 'PRD-002',
        quantity: 50,
        unitPrice: 30.5
      }
    ],
    history: [
      {
        when: '18.02, 09:15',
        title: 'Comandă creată',
        description:
          'Comanda a fost plasată de agentul de vânzări în numele clientului (impersonare).'
      },
      {
        when: '18.02, 10:02',
        title: 'Verificare credit',
        description:
          'Comanda a fost blocată pe credit – depășește limita stabilită în contract.'
      }
    ]
  },
  {
    id: 2,
    code: 'CMD-1000',
    date: '2025-02-16',
    clientName: 'SC Retail Market SRL',
    clientIdentifier: 'CUI RO87654321',
    clientType: 'B2B',
    status: 'in_livrare',
    creditBlocked: false,
    total: 12780.0,
    initiatorName: 'SC Retail Market SRL',
    initiatorType: 'client',
    initiatorTypeLabel: 'Client B2B',
    isImpersonated: false,
    agentName: 'Georgescu Ana',
    directorName: 'Ionescu Adrian',
    createdInErp: false,
    shippingAddress: {
      line1: 'Bd. Comerțului nr. 20',
      city: 'Cluj-Napoca',
      county: 'Cluj'
    },
    billingAddress: {
      line1: 'Str. Facturilor nr. 3',
      city: 'Cluj-Napoca',
      county: 'Cluj'
    },
    lines: [
      {
        productName: 'Raft metalic magazin',
        productBrand: 'BrandZ',
        category: 'Echipamente retail',
        internalCode: 'PRD-010',
        quantity: 10,
        unitPrice: 800.0
      }
    ],
    history: [
      {
        when: '16.02, 11:20',
        title: 'Comandă creată',
        description: 'Comanda a fost plasată de client din contul B2B.'
      },
      {
        when: '17.02, 08:40',
        title: 'Pregătire marfă',
        description: 'Comanda a fost pregătită pentru livrare din depozit.'
      },
      {
        when: '17.02, 15:10',
        title: 'Predată curierului',
        description: 'Coletul a fost predat curierului partener.'
      }
    ]
  }
]

const orderId = computed(() => Number(route.params.id))
const order = computed(() => demoOrders.find(o => o.id === orderId.value) || null)

const goBack = () => {
  router.push('/admin/orders')
}
</script>
