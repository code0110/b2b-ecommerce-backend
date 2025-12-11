<template>
  <div class="container-fluid">
    <PageHeader
      title="Notificări admin"
      subtitle="Notificări privind comenzi, blocaje de credit, promoții și alte evenimente critice."
    />

    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Total notificări: <strong>{{ notifications.length }}</strong>
          <span v-if="unreadCount > 0">
            &middot; Necitite: <strong>{{ unreadCount }}</strong>
          </span>
        </div>
        <button
          type="button"
          class="btn btn-link btn-sm text-decoration-none"
          :disabled="unreadCount === 0"
          @click="markAll"
        >
          Marchează toate ca citite
        </button>
      </div>

      <div class="table-responsive mb-0">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 40px;"></th>
              <th>Titlu</th>
              <th>Tip</th>
              <th>Mesaj</th>
              <th>Creat la</th>
              <th>Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="notifications.length === 0">
              <td colspan="6" class="text-center text-muted py-4">
                Nu există notificări pentru moment.
              </td>
            </tr>
            <tr
              v-for="n in notificationsSorted"
              :key="n.id"
              :class="{ 'table-info': !n.read }"
            >
              <td>
                <span
                  v-if="!n.read"
                  class="badge bg-primary"
                  style="font-size: 0.6rem;"
                >
                  Nou
                </span>
              </td>
              <td class="fw-semibold">
                {{ n.title }}
              </td>
              <td class="small text-muted">
                {{ formatType(n.type) }}
              </td>
              <td class="small">
                {{ n.message }}
              </td>
              <td class="small text-muted">
                {{ formatDateTime(n.createdAt) }}
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button
                    type="button"
                    class="btn btn-outline-primary btn-sm"
                    @click="goTo(n)"
                  >
                    Deschide
                  </button>
                  <button
                    v-if="!n.read"
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    @click="markOne(n)"
                  >
                    Marchează citit
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="card-footer small text-muted">
        Acesta este un template de listă notificări pentru echipa internă
        (administrator, operator, agent, director). În proiectul real se vor filtra
        după utilizator și rol, iar datele vor veni din backend / ERP.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { useNotificationsStore } from '@/store/notifications'

const router = useRouter()
const notificationsStore = useNotificationsStore()

const notifications = computed(() => notificationsStore.adminNotifications)
const unreadCount = computed(() => notificationsStore.adminUnreadCount)

const notificationsSorted = computed(() => {
  return [...notifications.value].sort((a, b) => {
    const da = new Date(a.createdAt).getTime()
    const db = new Date(b.createdAt).getTime()
    return db - da
  })
})

const formatDateTime = (value) => {
  if (!value) return ''
  const d = new Date(value)
  return d.toLocaleString('ro-RO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatType = (type) => {
  switch (type) {
    case 'order_status':
      return 'Status comandă'
    case 'credit_block':
      return 'Blocaj credit'
    case 'promotion_expiring':
      return 'Promoție / campanie'
    case 'ticket':
      return 'Tichet suport'
    default:
      return 'General'
  }
}

const goTo = (notification) => {
  notificationsStore.markAsRead(notification.id)
  if (notification.route) {
    router.push(notification.route)
  }
}

const markOne = (notification) => {
  notificationsStore.markAsRead(notification.id)
}

const markAll = () => {
  notificationsStore.markAllAsReadFor('admin')
}
</script>
