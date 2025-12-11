<template>
  <div class="container py-4">
    <h2 class="mb-1">Tichete suport & mesaje</h2>
    <p class="text-muted mb-3">
      Sistem demo de ticketing între client și echipa de suport / agent /
      director de vânzări.
    </p>

    <div class="alert alert-info small" v-if="frontClientType">
      <div>
        Client activ:
        <strong>{{ frontCustomerName }}</strong>
        <span class="badge bg-secondary ms-2">{{ frontClientType }}</span>
      </div>
      <div class="mt-1" v-if="isImpersonating">
        Orice tichet nou va fi deschis în numele acestui client. Util pentru
        agenți și directori care gestionează comunicarea pentru portofoliul lor.
      </div>
      <div class="mt-1 text-muted" v-else>
        Client autentificat direct în platformă.
      </div>
    </div>

    <div class="alert alert-warning small" v-else>
      Nu există client activ. Într-o implementare reală, crearea de tichete ar
      fi blocată până la identificarea clientului.
    </div>

    <div class="row g-3 mt-3">
      <div class="col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong class="small text-uppercase">Deschide tichet nou</strong>
            <span class="badge bg-primary">Demo</span>
          </div>
          <div class="card-body small">
            <div class="mb-2">
              <label class="form-label">Subiect</label>
              <input
                type="text"
                class="form-control form-control-sm"
                placeholder="Ex: Nelămurire privind o factură"
                v-model="newTicket.subject"
              />
            </div>
            <div class="mb-2">
              <label class="form-label">Categorie</label>
              <select
                class="form-select form-select-sm"
                v-model="newTicket.category"
              >
                <option value="general">General</option>
                <option value="comenzi">Comenzi & livrare</option>
                <option value="facturi">Facturi & plăți</option>
                <option value="tehnic">Suport tehnic</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Mesaj</label>
              <textarea
                class="form-control form-control-sm"
                rows="4"
                placeholder="Descrie pe scurt problema..."
                v-model="newTicket.message"
              ></textarea>
            </div>
            <button
              type="button"
              class="btn btn-sm btn-primary w-100"
              :disabled="!frontClientType"
              @click="createTicket"
            >
              Trimite tichet
            </button>
            <p class="small text-muted mt-2 mb-0">
              În acest demo, tichetele nu se salvează pe server – logica este doar
              de prezentare a fluxului.
            </p>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong class="small text-uppercase">Tichetele mele</strong>
            <span class="badge bg-light text-dark">
              {{ demoTickets.length }} tichete demo
            </span>
          </div>
          <div class="card-body small">
            <div
              v-for="ticket in demoTickets"
              :key="ticket.id"
              class="border-bottom pb-2 mb-2"
            >
              <div class="d-flex justify-content-between">
                <div>
                  <div class="fw-semibold">
                    {{ ticket.subject }}
                    <span class="badge bg-light text-dark ms-1">
                      {{ ticket.categoryLabel }}
                    </span>
                  </div>
                  <div class="text-muted">
                    ID: {{ ticket.code }} • Creat: {{ ticket.createdAt }}
                  </div>
                </div>
                <div class="text-end">
                  <span
                    class="badge"
                    :class="{
                      'bg-secondary': ticket.status === 'nou',
                      'bg-info text-dark': ticket.status === 'in_lucru',
                      'bg-success': ticket.status === 'rezolvat'
                    }"
                  >
                    {{
                      ticket.status === 'nou'
                        ? 'Nou'
                        : ticket.status === 'in_lucru'
                          ? 'În lucru'
                          : 'Rezolvat'
                    }}
                  </span>
                </div>
              </div>
              <div class="mt-2">
                <div class="fw-semibold mb-1">Istoric mesaje</div>
                <ul class="list-unstyled mb-0">
                  <li
                    v-for="(msg, idx) in ticket.messages"
                    :key="idx"
                    class="mb-1"
                  >
                    <span class="fw-semibold">{{ msg.from }}:</span>
                    <span class="ms-1">{{ msg.text }}</span>
                    <span class="text-muted ms-2">({{ msg.when }})</span>
                  </li>
                </ul>
              </div>
            </div>
            <div v-if="!demoTickets.length" class="alert alert-light border mb-0">
              Nu există tichete demo. Creează unul nou din formularul alăturat.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/store/auth'

const authStore = useAuthStore()

const isImpersonating = computed(() => !!authStore.impersonatedCustomer)

const frontClientType = computed(() => {
  if (authStore.impersonatedCustomer?.clientType) {
    return authStore.impersonatedCustomer.clientType
  }
  if (authStore.user?.role === 'b2b') return 'B2B'
  if (authStore.user?.role === 'b2c') return 'B2C'
  return null
})

const frontCustomerName = computed(() => {
  if (authStore.impersonatedCustomer?.name) {
    return authStore.impersonatedCustomer.name
  }
  if (authStore.user && (authStore.user.role === 'b2b' || authStore.user.role === 'b2c')) {
    return authStore.user.name
  }
  return null
})

// Demo tickets
const demoTickets = ref([
  {
    id: 1,
    code: 'TCK-1001',
    subject: 'Întrebare despre termenul de livrare',
    category: 'comenzi',
    categoryLabel: 'Comenzi & livrare',
    status: 'in_lucru',
    createdAt: '2025-02-10',
    messages: [
      {
        from: 'Client',
        text: 'Bună ziua, care este termenul estimat de livrare pentru comanda #CMD-0998?',
        when: '10.02, 09:15'
      },
      {
        from: 'Suport',
        text: 'Comanda este în curs de livrare, termen estimat: 12.02.',
        when: '10.02, 10:02'
      }
    ]
  },
  {
    id: 2,
    code: 'TCK-0997',
    subject: 'Neconcordanță factură',
    category: 'facturi',
    categoryLabel: 'Facturi & plăți',
    status: 'rezolvat',
    createdAt: '2025-01-28',
    messages: [
      {
        from: 'Client',
        text: 'Pe factura #FAC-2025-001 apar două linii dublate.',
        when: '28.01, 14:20'
      },
      {
        from: 'Suport',
        text: 'A fost emisă o factură storno, problema este rezolvată.',
        when: '29.01, 09:05'
      }
    ]
  }
])

const newTicket = ref({
  subject: '',
  category: 'general',
  message: ''
})

const createTicket = () => {
  if (!frontClientType.value) {
    return
  }
  if (!newTicket.value.subject || !newTicket.value.message) {
    return
  }

  const nextId = demoTickets.value.length
    ? Math.max(...demoTickets.value.map(t => t.id)) + 1
    : 1

  demoTickets.value.unshift({
    id: nextId,
    code: `TCK-${1000 + nextId}`,
    subject: newTicket.value.subject,
    category: newTicket.value.category,
    categoryLabel:
      newTicket.value.category === 'comenzi'
        ? 'Comenzi & livrare'
        : newTicket.value.category === 'facturi'
          ? 'Facturi & plăți'
          : newTicket.value.category === 'tehnic'
            ? 'Suport tehnic'
            : 'General',
    status: 'nou',
    createdAt: new Date().toISOString().slice(0, 10),
    messages: [
      {
        from: frontCustomerName.value || 'Client',
        text: newTicket.value.message,
        when: 'acum'
      }
    ]
  })

  newTicket.value = {
    subject: '',
    category: 'general',
    message: ''
  }
}
</script>
