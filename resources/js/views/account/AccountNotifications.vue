<template>
  <div class="container py-4">
    <div class="row mb-3">
      <div class="col-md-8">
        <h1 class="h5 mb-1">Notificările mele (demo)</h1>
        <p class="text-muted small mb-0">
          Aici vezi notificările generate de platformă pentru contul tău: comenzi noi,
          actualizări de status, plăți, oferte și mesaje pe tichete. Implementarea este
          demo, dar urmărește structura unui centru de notificări B2B/B2C.
        </p>
      </div>
      <div class="col-md-4 text-md-end small mt-2 mt-md-0">
        <div class="mb-1">
          <span class="badge bg-light text-dark">
            {{ filteredNotifications.length }} notificări afișate
          </span>
        </div>
        <button
          type="button"
          class="btn btn-outline-secondary btn-sm"
          @click="markAllRead"
        >
          Marchează toate ca citite (demo)
        </button>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body small">
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Căutare</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="comandă, mesaj, ofertă..."
            />
          </div>
          <div class="col-md-3">
            <label class="form-label">Tip notificare</label>
            <select
              v-model="filters.type"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="order">Comenzi</option>
              <option value="payment">Plăți</option>
              <option value="offer">Oferte</option>
              <option value="ticket">Tichete</option>
            </select>
          </div>
          <div class="col-md-3">
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
          <div class="col-md-2 form-check mt-4 pt-2">
            <input
              v-model="filters.onlyImportant"
              class="form-check-input"
              type="checkbox"
              id="onlyImportant"
            />
            <label class="form-check-label" for="onlyImportant">
              Doar importante
            </label>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <li
            v-for="notification in filteredNotifications"
            :key="notification.id"
            class="list-group-item small"
            :class="{ 'bg-light': notification.status === 'unread' }"
          >
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="mb-1">
                  <span class="badge me-1" :class="typeBadgeClass(notification.type)">
                    {{ typeLabel(notification.type) }}
                  </span>
                  <span
                    v-if="notification.important"
                    class="badge bg-danger me-1"
                  >
                    Important
                  </span>
                  <strong>{{ notification.title }}</strong>
                </div>
                <div class="text-muted mb-1">
                  {{ notification.message }}
                </div>
                <div v-if="notification.relatedEntity" class="text-muted mb-1">
                  Legat de: {{ notification.relatedEntity }}
                </div>
                <div class="text-muted">
                  {{ notification.createdAt }}
                </div>
              </div>
              <div class="text-end ms-3">
                <button
                  type="button"
                  class="btn btn-link btn-sm p-0 mb-1"
                  @click="toggleRead(notification)"
                >
                  {{ notification.status === 'unread' ? 'Marchează citită' : 'Marchează necitită' }}
                </button>
              </div>
            </div>
          </li>
          <li v-if="filteredNotifications.length === 0" class="list-group-item text-center text-muted py-4">
            Nu există notificări care să corespundă filtrării curente.
          </li>
        </ul>
      </div>
      <div class="card-footer small text-muted">
        <strong>Notă demo:</strong>
        În varianta reală, notificările ar fi paginate și sincronizate cu notificările trimise
        pe e-mail / SMS, cu indicatori clari de „nou” și eventual un badge pe icon-ul de clopot
        din antetul site-ului.
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
    type: 'order',
    title: 'Comanda ta #PO-10234 a fost înregistrată',
    message:
      'Comanda a fost preluată în sistem ca proformă. Vei primi actualizări la schimbarea statusului.',
    relatedEntity: 'Comandă #PO-10234 (demo)',
    status: 'unread',
    important: true
  },
  {
    id: 2,
    createdAt: '2025-02-18 09:15',
    type: 'payment',
    title: 'Plata ta a fost înregistrată',
    message:
      'Am înregistrat o plată de 12.500 RON prin card pentru factura #INV-55421.',
    relatedEntity: 'Factură #INV-55421 (demo)',
    status: 'read',
    important: false
  },
  {
    id: 3,
    createdAt: '2025-02-16 09:20',
    type: 'offer',
    title: 'Ofertă nouă disponibilă în cont',
    message:
      'Agentul tău a încărcat oferta #OFR-7893. O poți vizualiza în secțiunea „Oferte”.',
    relatedEntity: 'Ofertă #OFR-7893 (demo)',
    status: 'read',
    important: false
  },
  {
    id: 4,
    createdAt: '2025-02-15 16:10',
    type: 'ticket',
    title: 'Răspuns nou pe tichetul TCK-103',
    message:
      'Ai primit un răspuns de la agent pe tichetul privind informațiile tehnice ale produsului.',
    relatedEntity: 'Tichet TCK-103 (demo)',
    status: 'unread',
    important: false
  }
])

const filters = ref({
  search: '',
  type: '',
  status: '',
  onlyImportant: false
})

const filteredNotifications = computed(() => {
  return notifications.value.filter((n) => {
    const search = filters.value.search.trim().toLowerCase()
    const matchesSearch =
      !search ||
      n.title.toLowerCase().includes(search) ||
      n.message.toLowerCase().includes(search) ||
      (n.relatedEntity && n.relatedEntity.toLowerCase().includes(search))

    const matchesType = !filters.value.type || n.type === filters.value.type
    const matchesStatus = !filters.value.status || n.status === filters.value.status
    const matchesImportant = !filters.value.onlyImportant || n.important

    return matchesSearch && matchesType && matchesStatus && matchesImportant
  })
})

const typeLabel = (type) => {
  switch (type) {
    case 'order':
      return 'Comenzi'
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

const typeBadgeClass = (type) => {
  switch (type) {
    case 'order':
      return 'bg-primary'
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

const toggleRead = (notification) => {
  notification.status = notification.status === 'unread' ? 'read' : 'unread'
}

const markAllRead = () => {
  notifications.value.forEach((n) => {
    n.status = 'read'
  })
  window.alert(
    'Demo: toate notificările au fost marcate ca citite.\nÎn implementarea reală, această acțiune ar fi memorată și sincronizată cu backend-ul.'
  )
}
</script>
