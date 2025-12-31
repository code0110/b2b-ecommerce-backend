<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Notificări admin</h1>
      <button
        class="btn btn-sm btn-outline-secondary"
        type="button"
        @click="loadNotifications"
      >
        Reîncarcă
      </button>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div class="card">
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <li
            v-if="loading"
            class="list-group-item small text-muted text-center py-3"
          >
            Se încarcă...
          </li>
          <li
            v-if="!loading && notifications.length === 0"
            class="list-group-item small text-muted text-center py-3"
          >
            Nu există notificări.
          </li>

          <li
            v-for="n in notifications"
            :key="n.id"
            class="list-group-item small d-flex justify-content-between align-items-start"
            :class="n.read_at ? 'bg-white' : 'bg-light'"
          >
            <div>
              <div class="fw-semibold">
                {{ n.data?.title || n.title || n.type || 'Notificare' }}
              </div>
              <div class="text-muted">
                {{ n.data?.message || n.data?.body || n.message || n.body || '—' }}
              </div>
              <div class="text-muted">
                <small>{{ formatDate(n.created_at) }}</small>
              </div>
            </div>
            <div class="ms-3">
              <button
                v-if="!n.read_at"
                class="btn btn-sm btn-outline-secondary"
                type="button"
                @click="markRead(n)"
              >
                Marchează citită
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  fetchAdminNotifications,
  markNotificationRead
} from '@/services/admin/notifications'
import { useNotificationsStore } from '@/store/notifications'

const notificationsStore = useNotificationsStore()
const notifications = ref([])
const loading = ref(false)
const error = ref('')

const formatDate = (val) => {
  if (!val) return ''
  const d = new Date(val)
  return d.toLocaleString('ro-RO')
}

const loadNotifications = async () => {
  loading.value = true
  error.value = ''
  try {
    const resp = await fetchAdminNotifications()
    notifications.value = resp.data || resp || []
    // Also update the count in the store/header
    notificationsStore.fetchAdminUnreadCount()
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca notificările.'
  } finally {
    loading.value = false
  }
}

const markRead = async (n) => {
  try {
    await markNotificationRead(n.id)
    await loadNotifications()
  } catch (e) {
    console.error(e)
    alert('Nu s-a putut marca notificarea ca citită.')
  }
}

onMounted(loadNotifications)
</script>
