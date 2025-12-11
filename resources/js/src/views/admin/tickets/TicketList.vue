<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Tichete suport & mesagerie (demo)</h1>
        <p class="text-muted small mb-0">
          Zonă administrativă pentru gestionarea tichetelor dintre clienți și echipa internă
          (suport, agenți, directori). Datele sunt demo, dar structura urmărește modelul:
          subiect · categorie · status · istoric mesaje.
        </p>
      </div>
      <div class="text-end small">
        <div class="mb-1">
          Rol curent (demo): <strong>{{ currentRoleLabel }}</strong>
        </div>
        <span class="badge bg-light text-dark">
          {{ filteredTickets.length }} tichete afișate
        </span>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body small">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Căutare</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="subiect, client, CUI, agent..."
            />
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select
              v-model="filters.status"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="new">Nou</option>
              <option value="in_progress">În lucru</option>
              <option value="waiting_customer">Așteaptă client</option>
              <option value="resolved">Rezolvat</option>
              <option value="closed">Închis</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Categorie</label>
            <select
              v-model="filters.category"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="order">Comenzi</option>
              <option value="billing">Facturare & plăți</option>
              <option value="product">Produse & stocuri</option>
              <option value="technical">Tehnic / cont</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Tip client</label>
            <select
              v-model="filters.customerType"
              class="form-select form-select-sm"
            >
              <option value="">Toți</option>
              <option value="b2b">B2B</option>
              <option value="b2c">B2C</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Agent</label>
            <select
              v-model="filters.agent"
              class="form-select form-select-sm"
            >
              <option value="">Toți</option>
              <option v-for="agent in agents" :key="agent" :value="agent">
                {{ agent }}
              </option>
            </select>
          </div>
          <div class="col-md-1 form-check mt-4 pt-2">
            <input
              v-model="filters.myTicketsOnly"
              class="form-check-input"
              type="checkbox"
              id="myTickets"
            />
            <label class="form-check-label" for="myTickets">
              Doar ale mele
            </label>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light small text-uppercase text-muted">
              <tr>
                <th style="width: 90px;">#</th>
                <th style="width: 120px;">Data</th>
                <th>Subiect</th>
                <th style="width: 180px;">Client</th>
                <th style="width: 130px;">Categorie</th>
                <th style="width: 110px;">Status</th>
                <th style="width: 160px;">Agent</th>
                <th style="width: 130px;">Ultima actualizare</th>
                <th style="width: 170px;" class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody class="small">
              <tr
                v-for="ticket in filteredTickets"
                :key="ticket.id"
                :class="{ 'table-active': selectedTicket && selectedTicket.id === ticket.id }"
              >
                <td>
                  <span class="fw-semibold">TCK-{{ ticket.id }}</span>
                  <div class="text-muted">canal: {{ ticket.channel }}</div>
                </td>
                <td>{{ ticket.createdAt }}</td>
                <td>
                  <div class="fw-semibold">{{ ticket.subject }}</div>
                  <div class="text-muted">
                    {{ ticket.preview }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold">
                    {{ ticket.customerName }}
                    <span
                      class="badge ms-1"
                      :class="ticket.customerType === 'b2b' ? 'bg-primary' : 'bg-secondary'"
                    >
                      {{ ticket.customerType.toUpperCase() }}
                    </span>
                  </div>
                  <div class="text-muted">
                    {{ ticket.customerCode }}
                  </div>
                </td>
                <td>{{ categoryLabel(ticket.category) }}</td>
                <td>
                  <span class="badge" :class="statusBadgeClass(ticket.status)">
                    {{ statusLabel(ticket.status) }}
                  </span>
                </td>
                <td>
                  <div v-if="ticket.assignedAgent">
                    {{ ticket.assignedAgent }}
                    <div class="text-muted">{{ ticket.assignedAgentRole }}</div>
                  </div>
                  <div v-else class="text-muted">Nealocat</div>
                </td>
                <td>{{ ticket.updatedAt }}</td>
                <td class="text-end">
                  <button
                    type="button"
                    class="btn btn-outline-primary btn-sm me-1"
                    @click="selectTicket(ticket)"
                  >
                    Detalii
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-success btn-sm me-1"
                    @click="markInProgress(ticket)"
                  >
                    În lucru
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    @click="closeTicket(ticket)"
                  >
                    Închide
                  </button>
                </td>
              </tr>
              <tr v-if="filteredTickets.length === 0">
                <td colspan="9">
                  <div class="text-center text-muted py-4">
                    Nu există tichete care să corespundă filtrării curente.
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer small text-muted">
        <strong>Notă demo:</strong>
        În varianta completă, această listă ar fi alimentată din API (ERP / sistem ticketing),
        cu posibilitatea de a filtra pe agent, director, client și categorie, inclusiv log de audit
        pentru schimbarea statusului și reasignări.
      </div>
    </div>

    <div v-if="selectedTicket" class="card">
      <div class="card-header py-2 d-flex justify-content-between align-items-center small">
        <div>
          <span class="fw-semibold">TCK-{{ selectedTicket.id }}</span>
          · {{ selectedTicket.subject }}
          <span class="ms-2 badge" :class="statusBadgeClass(selectedTicket.status)">
            {{ statusLabel(selectedTicket.status) }}
          </span>
        </div>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          @click="selectedTicket = null"
        >
          Închide panoul de detaliu
        </button>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-lg-8 border-end">
            <div class="mb-3">
              <h2 class="h6 text-uppercase text-muted mb-2">Istoric mesaje</h2>
              <div class="ticket-thread border rounded p-2 bg-light">
                <div
                  v-for="message in selectedTicket.messages"
                  :key="message.id"
                  class="mb-3"
                  :class="{
                    'text-end': message.fromType !== 'customer',
                    'text-start': message.fromType === 'customer'
                  }"
                >
                  <div
                    class="d-inline-block px-2 py-1 rounded-3"
                    :class="messageClass(message)"
                    style="max-width: 100%;"
                  >
                    <div class="small fw-semibold mb-1">
                      {{ message.senderName }}
                      <span v-if="message.fromType === 'agent'" class="text-muted">
                        · agent
                      </span>
                      <span v-else-if="message.fromType === 'director'" class="text-muted">
                        · director
                      </span>
                      <span v-else-if="message.fromType === 'system'" class="text-muted">
                        · sistem
                      </span>
                    </div>
                    <div class="small mb-1" style="white-space: pre-line;">
                      {{ message.content }}
                    </div>
                    <div class="text-muted small">
                      {{ message.sentAt }}
                    </div>
                  </div>
                </div>
                <div v-if="!selectedTicket.messages || selectedTicket.messages.length === 0" class="text-muted small">
                  Nu există încă mesaje pe acest tichet.
                </div>
              </div>
            </div>

            <div class="mt-3">
              <h2 class="h6 text-uppercase text-muted mb-2">Răspuns rapid (demo)</h2>
              <form @submit.prevent="sendReply">
                <div class="mb-2">
                  <textarea
                    v-model="replyText"
                    class="form-control form-control-sm"
                    rows="3"
                    placeholder="Scrie răspunsul către client / agent..."
                  ></textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center small">
                  <div class="text-muted">
                    Răspunsul va apărea în thread ca mesaj din partea rolului curent.
                  </div>
                  <button
                    type="submit"
                    class="btn btn-primary btn-sm"
                    :disabled="!replyText"
                  >
                    Trimite răspuns (demo)
                  </button>
                </div>
              </form>
            </div>
          </div>
          <div class="col-lg-4 small mt-3 mt-lg-0">
            <h2 class="h6 text-uppercase text-muted mb-2">Detalii client</h2>
            <p class="mb-1">
              <strong>{{ selectedTicket.customerName }}</strong>
              <span
                class="badge ms-1"
                :class="selectedTicket.customerType === 'b2b' ? 'bg-primary' : 'bg-secondary'"
              >
                {{ selectedTicket.customerType.toUpperCase() }}
              </span>
            </p>
            <p class="mb-1 text-muted">
              Cod client: {{ selectedTicket.customerCode }}
            </p>
            <p class="mb-1 text-muted">
              Punct de lucru / regiune: {{ selectedTicket.region }}
            </p>

            <hr />

            <h2 class="h6 text-uppercase text-muted mb-2">Routing tichet</h2>
            <p class="mb-1">
              <strong>Agent asignat:</strong>
              {{ selectedTicket.assignedAgent || 'Nealocat' }}
            </p>
            <p class="mb-1">
              <strong>Escaladare:</strong>
              {{ selectedTicket.escalationLevel || 'Nu este escaladat' }}
            </p>
            <p class="mb-1">
              <strong>Legat de:</strong>
              {{ selectedTicket.linkedEntity || 'Nu este legat de comandă/ofertă anume (demo)' }}
            </p>
            <p class="text-muted mb-0">
              În implementarea reală, aici se pot afișa link-uri către comanda / oferta / clientul
              din ERP, plus istoric de interacțiuni.
            </p>
          </div>
        </div>
      </div>
      <div class="card-footer small text-muted">
        <strong>Notă demo:</strong>
        această zonă ilustrează structura de bază a unui sistem de ticketing integrat cu platforma B2B/B2C,
        unde agenții și directorii pot colabora pentru a rezolva cererile clienților.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useAuthStore } from '@/store/auth'

const authStore = useAuthStore()

const currentRole = computed(() => authStore.user?.role || 'admin')

const currentRoleLabel = computed(() => {
  switch (currentRole.value) {
    case 'agent':
      return 'Agent vânzări'
    case 'director':
      return 'Director vânzări'
    case 'operator':
      return 'Operator birou / suport'
    case 'admin':
    case 'administrator':
      return 'Administrator'
    default:
      return currentRole.value
  }
})

const tickets = ref([
  {
    id: 101,
    subject: 'Probleme la plasarea comenzii recurente',
    preview: 'La pasul de confirmare primesc un mesaj de eroare...',
    category: 'order',
    status: 'new',
    customerName: 'SC Construct Nord SRL',
    customerType: 'b2b',
    customerCode: 'CL-B2B-001',
    region: 'Nord / Cluj',
    channel: 'cont client',
    createdAt: '2025-02-20 10:32',
    updatedAt: '2025-02-20 10:32',
    assignedAgent: 'Agent Nord – exemplu',
    assignedAgentRole: 'Agent vânzări',
    escalationLevel: null,
    linkedEntity: 'Comandă #PO-10234 (demo)',
    messages: [
      {
        id: 1,
        fromType: 'customer',
        senderName: 'Popescu Mihai',
        sentAt: '2025-02-20 10:32',
        content:
          'Bună ziua,\nÎncerc să plasez o comandă recurenta pe șantierul din Cluj, dar la pasul de confirmare apare un mesaj de eroare. Puteți verifica, vă rog?'
      }
    ]
  },
  {
    id: 102,
    subject: 'Clarificare factură și plăți restante',
    preview: 'Am primit notificare de blocare credit, dar consider că...',
    category: 'billing',
    status: 'in_progress',
    customerName: 'Distribuție Sud SRL',
    customerType: 'b2b',
    customerCode: 'CL-B2B-015',
    region: 'Sud / București',
    channel: 'e-mail importat',
    createdAt: '2025-02-18 09:05',
    updatedAt: '2025-02-18 12:41',
    assignedAgent: 'Agent Sud – exemplu',
    assignedAgentRole: 'Agent vânzări',
    escalationLevel: 'Director vânzări',
    linkedEntity: 'Sold client & facturi restante (demo)',
    messages: [
      {
        id: 1,
        fromType: 'customer',
        senderName: 'Ionescu Adrian',
        sentAt: '2025-02-18 09:05',
        content:
          'Bună ziua,\nAm primit notificare că avem blocat creditul pentru facturi restante, însă unele plăți au fost deja făcute prin OP. Puteți verifica soldul real?'
      },
      {
        id: 2,
        fromType: 'agent',
        senderName: 'Agent Sud – exemplu',
        sentAt: '2025-02-18 10:12',
        content:
          'Bună ziua,\nAm verificat în ERP, două dintre plăți nu au fost încă reconciliate. Le marcăm manual și revenim cu confirmarea limitelor de credit.'
      },
      {
        id: 3,
        fromType: 'director',
        senderName: 'Director Vânzări Sud',
        sentAt: '2025-02-18 12:41',
        content:
          'Confirm actualizarea soldului și deblocarea temporară a limitelor de credit, în așteptarea reconcilerii complete din bancă.'
      }
    ]
  },
  {
    id: 103,
    subject: 'Informații tehnice despre produs și fișe tehnice',
    preview: 'Avem nevoie de fișa tehnică și certificat de conformitate...',
    category: 'product',
    status: 'waiting_customer',
    customerName: 'Retail Vest SRL',
    customerType: 'b2b',
    customerCode: 'CL-B2B-031',
    region: 'Vest / Timiș',
    channel: 'cont client',
    createdAt: '2025-02-15 15:20',
    updatedAt: '2025-02-16 09:10',
    assignedAgent: 'Agent Vest – exemplu',
    assignedAgentRole: 'Agent vânzări',
    escalationLevel: null,
    linkedEntity: 'Produs #PRD-00123 (demo)',
    messages: [
      {
        id: 1,
        fromType: 'customer',
        senderName: 'Georgescu Ana',
        sentAt: '2025-02-15 15:20',
        content:
          'Bună ziua,\nAvem nevoie de fișa tehnică și de certificatul de conformitate pentru produsul PRD-00123, pentru un proiect de audit.'
      },
      {
        id: 2,
        fromType: 'agent',
        senderName: 'Agent Vest – exemplu',
        sentAt: '2025-02-15 16:02',
        content:
          'Bună ziua,\nAm atașat fișa tehnică în zona de documente a produsului și vom încărca și certificatul de conformitate până mâine.'
      },
      {
        id: 3,
        fromType: 'agent',
        senderName: 'Agent Vest – exemplu',
        sentAt: '2025-02-16 09:10',
        content:
          'Am încărcat și certificatul de conformitate. Vă rugăm să confirmați dacă sunt suficiente documentele livrate.'
      }
    ]
  }
])

const filters = ref({
  search: '',
  status: '',
  category: '',
  customerType: '',
  agent: '',
  myTicketsOnly: false
})

const agents = computed(() => {
  const set = new Set(
    tickets.value
      .filter((t) => !!t.assignedAgent)
      .map((t) => t.assignedAgent)
  )
  return Array.from(set).sort()
})

const filteredTickets = computed(() => {
  return tickets.value.filter((t) => {
    const search = filters.value.search.trim().toLowerCase()
    const matchesSearch =
      !search ||
      t.subject.toLowerCase().includes(search) ||
      t.customerName.toLowerCase().includes(search) ||
      t.customerCode.toLowerCase().includes(search) ||
      (t.assignedAgent && t.assignedAgent.toLowerCase().includes(search))

    const matchesStatus = !filters.value.status || t.status === filters.value.status
    const matchesCategory = !filters.value.category || t.category === filters.value.category
    const matchesCustomerType =
      !filters.value.customerType || t.customerType === filters.value.customerType
    const matchesAgent = !filters.value.agent || t.assignedAgent === filters.value.agent

    let matchesMyTickets = true
    if (filters.value.myTicketsOnly && currentRole.value === 'agent') {
      const agentName = authStore.user?.name || ''
      matchesMyTickets =
        t.assignedAgent &&
        agentName &&
        t.assignedAgent.toLowerCase().includes(agentName.toLowerCase())
    }

    return (
      matchesSearch &&
      matchesStatus &&
      matchesCategory &&
      matchesCustomerType &&
      matchesAgent &&
      matchesMyTickets
    )
  })
})

const selectedTicket = ref(null)
const replyText = ref('')

const statusLabel = (status) => {
  switch (status) {
    case 'new':
      return 'Nou'
    case 'in_progress':
      return 'În lucru'
    case 'waiting_customer':
      return 'Așteaptă client'
    case 'resolved':
      return 'Rezolvat'
    case 'closed':
      return 'Închis'
    default:
      return status
  }
}

const statusBadgeClass = (status) => {
  switch (status) {
    case 'new':
      return 'bg-primary'
    case 'in_progress':
      return 'bg-warning text-dark'
    case 'waiting_customer':
      return 'bg-info text-dark'
    case 'resolved':
      return 'bg-success'
    case 'closed':
      return 'bg-secondary'
    default:
      return 'bg-light text-dark'
  }
}

const categoryLabel = (category) => {
  switch (category) {
    case 'order':
      return 'Comenzi'
    case 'billing':
      return 'Facturare & plăți'
    case 'product':
      return 'Produse & stocuri'
    case 'technical':
      return 'Tehnic / cont'
    default:
      return category
  }
}

const messageClass = (message) => {
  if (message.fromType === 'customer') {
    return 'bg-white border'
  }
  if (message.fromType === 'system') {
    return 'bg-secondary text-white'
  }
  return 'bg-primary text-white'
}

const selectTicket = (ticket) => {
  selectedTicket.value = ticket
}

const markInProgress = (ticket) => {
  if (ticket.status === 'in_progress') {
    window.alert('Tichetul este deja marcat ca „În lucru” (demo).')
    return
  }
  ticket.status = 'in_progress'
  ticket.updatedAt = '2025-02-21 09:00 (demo)'
  ticket.messages.push({
    id: Date.now(),
    fromType: 'system',
    senderName: 'Sistem',
    sentAt: ticket.updatedAt,
    content: 'Status tichet modificat în „În lucru” (demo).'
  })
}

const closeTicket = (ticket) => {
  if (ticket.status === 'closed') {
    window.alert('Tichetul este deja închis (demo).')
    return
  }
  ticket.status = 'closed'
  ticket.updatedAt = '2025-02-21 09:05 (demo)'
  ticket.messages.push({
    id: Date.now(),
    fromType: 'system',
    senderName: 'Sistem',
    sentAt: ticket.updatedAt,
    content:
      'Tichet închis din interfața de admin. În implementarea reală, clientul ar primi notificare e-mail / în cont.'
  })
}

const sendReply = () => {
  if (!selectedTicket.value || !replyText.value) return

  const senderName = authStore.user?.name || currentRoleLabel.value || 'Agent demo'
  const now = '2025-02-21 09:10 (demo)'

  selectedTicket.value.messages.push({
    id: Date.now(),
    fromType: currentRole.value === 'director' ? 'director' : 'agent',
    senderName,
    sentAt: now,
    content: replyText.value
  })

  selectedTicket.value.status = 'in_progress'
  selectedTicket.value.updatedAt = now

  replyText.value = ''

  window.alert(
    'Demo: răspunsul a fost adăugat în thread.\nÎn varianta reală, clientul ar fi notificat prin e-mail / notificări în cont, iar mesajul ar fi salvat în sistemul de ticketing / ERP.'
  )
}
</script>

<style scoped>
.ticket-thread {
  max-height: 420px;
  overflow-y: auto;
}
</style>
