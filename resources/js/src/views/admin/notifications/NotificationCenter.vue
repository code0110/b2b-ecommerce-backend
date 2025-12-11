<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Centru notificări platformă (demo)</h1>
        <p class="text-muted small mb-0">
          Vizualizare centralizată a notificărilor generate de platformă: comenzi noi,
          blocaje de credit, plăți înregistrate, aprobări de oferte, etc.
          În implementarea completă, aceste notificări se sincronizează cu e-mail, SMS sau push.
        </p>
      </div>
      <div class="text-end small">
        <span class="badge bg-light text-dark mb-1">
          {{ filteredNotifications.length }} notificări afișate
        </span>
        <div>
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            @click="markAllRead"
          >
            Marchează toate ca citite (demo)
          </button>
        </div>
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
              placeholder="client, cod, mesaj..."
            />
          </div>
          <div class="col-md-2">
            <label class="form-label">Tip eveniment</label>
            <select
              v-model="filters.eventType"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="order">Comenzi</option>
              <option value="credit">Credit & sold</option>
              <option value="payment">Plăți</option>
              <option value="offer">Oferte</option>
              <option value="ticket">Tichete</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Canal</label>
            <select
              v-model="filters.channel"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="in_app">În cont</option>
              <option value="email">E-mail</option>
              <option value="sms">SMS</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select
              v-model="filters.status"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="unread">Necitite</option>
              <option value="read">Citite</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Destinatar</label>
            <input
              v-model="filters.recipient"
              type="text"
              class="form-control form-control-sm"
              placeholder="client, agent, director..."
            />
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light small text-uppercase text-muted">
              <tr>
                <th style="width: 150px;">Data</th>
                <th style="width: 150px;">Tip eveniment</th>
                <th>Mesaj</th>
                <th style="width: 160px;">Destinatar</th>
                <th style="width: 130px;">Canal</th>
                <th style="width: 110px;">Status</th>
                <th style="width: 140px;" class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody class="small">
              <tr
                v-for="notification in filteredNotifications"
                :key="notification.id"
                :class="{ 'table-active': notification.status === 'unread' }"
              >
                <td>{{ notification.createdAt }}</td>
                <td>
                  <span class="badge" :class="eventTypeBadgeClass(notification.eventType)">
                    {{ eventTypeLabel(notification.eventType) }}
                  </span>
                </td>
                <td>
                  <div class="fw-semibold">
                    {{ notification.title }}
                  </div>
                  <div class="text-muted">
                    {{ notification.message }}
                  </div>
                  <div v-if="notification.relatedEntity" class="text-muted">
                    Legat de: {{ notification.relatedEntity }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold">
                    {{ notification.recipientName }}
                  </div>
                  <div class="text-muted">
                    {{ notification.recipientTypeLabel }}
                  </div>
                </td>
                <td>
                  <span
                    v-for="ch in notification.channels"
                    :key="ch"
                    class="badge bg-light text-dark me-1"
                  >
                    {{ channelLabel(ch) }}
                  </span>
                </td>
                <td>
                  <span
                    class="badge"
                    :class="notification.status === 'unread' ? 'bg-primary' : 'bg-secondary'"
                  >
                    {{ notification.status === 'unread' ? 'Necitită' : 'Citită' }}
                  </span>
                </td>
                <td class="text-end">
                  <button
                    type="button"
                    class="btn btn-outline-primary btn-sm me-1"
                    @click="previewNotification(notification)"
                  >
                    Previziualizare
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    @click="toggleRead(notification)"
                  >
                    {{ notification.status === 'unread' ? 'Marchează citită' : 'Marchează necitită' }}
                  </button>
                </td>
              </tr>
              <tr v-if="filteredNotifications.length === 0">
                <td colspan="7">
                  <div class="text-center text-muted py-4">
                    Nu există notificări care să corespundă filtrării curente.
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer small text-muted">
        <strong>Notă demo:</strong>
        În sistemul real, aici s-ar putea audita ce notificări au fost trimise, pe ce canal și
        dacă au fost livrate / deschise, inclusiv corelare cu log-ul de audit și cu evenimentele
        din ERP (ex: înregistrare plată, generare factură, blocaj de credit).
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const notifications = ref([
  {
    id: 1,
    createdAt: '2025-02-20 10:35',
    eventType: 'order',
    title: 'Comandă nouă plasată în platformă',
    message: 'Clientul SC Construct Nord SRL a plasat comanda #PO-10234 (B2B – proformă).',
    relatedEntity: 'Comandă #PO-10234 (demo)',
    recipientName: 'Agent Nord – exemplu',
    recipientTypeLabel: 'Agent vânzări',
    channels: ['in_app', 'email'],
    status: 'unread'
  },
  {
    id: 2,
    createdAt: '2025-02-18 09:10',
    eventType: 'credit',
    title: 'Blocare credit client B2B',
    message:
      'Clientul Distribuție Sud SRL a depășit limita de credit. Comenzile noi sunt blocate până la regularizare.',
    relatedEntity: 'Sold client & condiții comerciale (demo)',
    recipientName: 'Director Vânzări Sud',
    recipientTypeLabel: 'Director vânzări',
    channels: ['in_app', 'email'],
    status: 'unread'
  },
  {
    id: 3,
    createdAt: '2025-02-18 12:45',
    eventType: 'payment',
    title: 'Plată înregistrată în ERP',
    message:
      'A fost înregistrată o plată de 25.000 RON pentru clientul Distribuție Sud SRL (OP).',
    relatedEntity: 'Încasare #RC-55321 (demo)',
    recipientName: 'Agent Sud – exemplu',
    recipientTypeLabel: 'Agent vânzări',
    channels: ['in_app'],
    status: 'read'
  },
  {
    id: 4,
    createdAt: '2025-02-16 09:15',
    eventType: 'offer',
    title: 'Ofertă aprobată de director',
    message:
      'Oferta #OFR-7893 pentru clientul Retail Vest SRL a fost aprobată de Director Vânzări Vest.',
    relatedEntity: 'Ofertă #OFR-7893 (demo)',
    recipientName: 'Agent Vest – exemplu',
    recipientTypeLabel: 'Agent vânzări',
    channels: ['in_app', 'email'],
    status: 'read'
  },
  {
    id: 5,
    createdAt: '2025-02-15 16:05',
    eventType: 'ticket',
    title: 'Răspuns nou pe tichet suport',
    message:
      'Clientul Retail Vest SRL a primit un răspuns nou pe tichetul TCK-103 (informații tehnice produs).',
    relatedEntity: 'Tichet TCK-103 (demo)',
    recipientName: 'Retail Vest SRL (cont companie)',
    recipientTypeLabel: 'Client B2B',
    channels: ['in_app', 'email'],
    status: 'read'
  }
])

const filters = ref({
  search: '',
  eventType: '',
  channel: '',
  status: '',
  recipient: ''
})

const filteredNotifications = computed(() => {
  return notifications.value.filter((n) => {
    const search = filters.value.search.trim().toLowerCase()
    const matchesSearch =
      !search ||
      n.title.toLowerCase().includes(search) ||
      n.message.toLowerCase().includes(search) ||
      (n.relatedEntity && n.relatedEntity.toLowerCase().includes(search))

    const matchesEventType =
      !filters.value.eventType || n.eventType === filters.value.eventType

    const matchesChannel =
      !filters.value.channel || n.channels.includes(filters.value.channel)

    const matchesStatus = !filters.value.status || n.status === filters.value.status

    const recipientSearch = filters.value.recipient.trim().toLowerCase()
    const matchesRecipient =
      !recipientSearch ||
      n.recipientName.toLowerCase().includes(recipientSearch) ||
      n.recipientTypeLabel.toLowerCase().includes(recipientSearch)

    return (
      matchesSearch &&
      matchesEventType &&
      matchesChannel &&
      matchesStatus &&
      matchesRecipient
    )
  })
})

const eventTypeLabel = (type) => {
  switch (type) {
    case 'order':
      return 'Comenzi'
    case 'credit':
      return 'Credit & sold'
    case 'payment':
      return 'Plăți'
    case 'offer':
      return 'Oferte'
    case 'ticket':
      return 'Tichete'
    default:
      return type
  }
}

const eventTypeBadgeClass = (type) => {
  switch (type) {
    case 'order':
      return 'bg-primary'
    case 'credit':
      return 'bg-danger'
    case 'payment':
      return 'bg-success'
    case 'offer':
      return 'bg-info text-dark'
    case 'ticket':
      return 'bg-warning text-dark'
    default:
      return 'bg-secondary'
  }
}

const channelLabel = (ch) => {
  switch (ch) {
    case 'in_app':
      return 'În cont'
    case 'email':
      return 'E-mail'
    case 'sms':
      return 'SMS'
    default:
      return ch
  }
}

const toggleRead = (notification) => {
  notification.status = notification.status === 'unread' ? 'read' : 'unread'
}

const markAllRead = () => {
  notifications.value.forEach((n) => {
    n.status = 'read'
  })
  window.alert(
    'Demo: toate notificările au fost marcate ca citite. În sistemul real, această acțiune ar fi logată în audit.'
  )
}

const previewNotification = (notification) => {
  window.alert(
    'Previzualizare notificare (demo):\n\n' +
      notification.title +
      '\n\n' +
      notification.message +
      (notification.relatedEntity ? '\n\nLegat de: ' + notification.relatedEntity : '')
  )
}
</script>
