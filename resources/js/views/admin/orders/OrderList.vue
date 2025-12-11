<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-1">Comenzi (demo)</h4>
        <p class="text-muted small mb-0">
          Listă de comenzi cu evidențierea ierarhiei client → agent → director → admin/operator
          și a comenzilor plasate în numele clienților.
        </p>
      </div>
      <div class="text-end small" v-if="currentUser">
        <div>
          Utilizator curent:
          <strong>{{ currentUser.name }}</strong>
          <span class="badge bg-light text-dark ms-1">
            {{ currentUser.role || 'fără rol' }}
          </span>
        </div>
        <div class="text-muted">
          Vizualizare demo – datele nu provin dintr-un API real.
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong class="small text-uppercase">Comenzi recente</strong>
        <span class="badge bg-light text-dark">
          {{ orders.length }} comenzi demo
        </span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 120px;"># Comandă</th>
                <th style="width: 120px;">Data</th>
                <th>Client</th>
                <th style="width: 90px;">Tip</th>
                <th style="width: 140px;">Status</th>
                <th style="width: 190px;">Inițiator</th>
                <th style="width: 190px;">Agent / Director</th>
                <th style="width: 120px;" class="text-end">Total (RON)</th>
                <th style="width: 100px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in orders" :key="order.id">
                <td class="fw-semibold">{{ order.code }}</td>
                <td>{{ order.date }}</td>
                <td>
                  <div class="fw-semibold">{{ order.clientName }}</div>
                  <div class="small text-muted">
                    {{ order.clientIdentifier }}
                  </div>
                </td>
                <td>
                  <span
                    class="badge"
                    :class="order.clientType === 'B2B' ? 'bg-primary' : 'bg-secondary'"
                  >
                    {{ order.clientType }}
                  </span>
                </td>
                <td>
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
                  <div class="small text-muted" v-if="order.creditBlocked">
                    Blocată pe credit
                  </div>
                </td>
                <td>
                  <div class="fw-semibold">
                    {{ order.initiatorName }}
                    <span
                      v-if="order.initiatorTypeLabel"
                      class="badge bg-light text-dark ms-1"
                    >
                      {{ order.initiatorTypeLabel }}
                    </span>
                  </div>
                  <div class="small text-muted">
                    <span v-if="order.isImpersonated">
                      Plasată în numele clientului
                    </span>
                    <span v-else>
                      Plasată direct de client
                    </span>
                  </div>
                </td>
                <td>
                  <div class="small">
                    <div>
                      Agent:
                      <strong>{{ order.agentName || '—' }}</strong>
                    </div>
                    <div>
                      Director:
                      <strong>{{ order.directorName || '—' }}</strong>
                    </div>
                  </div>
                </td>
                <td class="text-end fw-semibold">
                  {{ order.total.toLocaleString('ro-RO', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="text-end">
                  <RouterLink
                    class="btn btn-sm btn-outline-primary"
                    :to="`/admin/orders/${order.id}`"
                  >
                    Detalii
                  </RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer small text-muted">
        Aceasta este o listă statică de comenzi demo. Într-o implementare reală,
        filtrele ar ține cont de rol (agent vede doar portofoliul său, director
        vede portofoliul agenților etc.), iar comenzile marcate ca „Blocată pe
        credit” ar necesita intervenția unui director / administrator.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/store/auth'

const authStore = useAuthStore()
const currentUser = computed(() => authStore.user || null)

const orders = [
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
    directorName: 'Ionescu Adrian'
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
    directorName: 'Ionescu Adrian'
  },
  {
    id: 3,
    code: 'CMD-0999',
    date: '2025-02-10',
    clientName: 'Ionescu Andrei',
    clientIdentifier: 'CNP ****',
    clientType: 'B2C',
    status: 'livrata',
    creditBlocked: false,
    total: 520.99,
    initiatorName: 'Ionescu Andrei',
    initiatorType: 'client',
    initiatorTypeLabel: 'Client B2C',
    isImpersonated: false,
    agentName: null,
    directorName: null
  },
  {
    id: 4,
    code: 'CMD-0998',
    date: '2025-02-08',
    clientName: 'SC Industrial Tech SRL',
    clientIdentifier: 'CUI RO99887766',
    clientType: 'B2B',
    status: 'blocata',
    creditBlocked: true,
    total: 70340.75,
    initiatorName: 'Ionescu Adrian',
    initiatorType: 'director',
    initiatorTypeLabel: 'Director vânzări',
    isImpersonated: true,
    agentName: 'Popescu Mihai',
    directorName: 'Ionescu Adrian'
  }
]
</script>
