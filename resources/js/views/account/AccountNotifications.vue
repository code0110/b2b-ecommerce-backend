<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Notificări</h1>
        <p class="small text-muted mb-0">
          Actualizări legate de comenzi, plăți, oferte și promoții.
        </p>
      </div>
      <div class="d-flex gap-2">
        <RouterLink 
            :to="{ name: 'account-notification-settings' }" 
            class="btn btn-outline-primary btn-sm"
        >
            <i class="bi bi-gear-fill me-1"></i> Setări
        </RouterLink>
        <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            :disabled="loading || notifications.length === 0"
            @click="handleMarkAll"
        >
            Marchează toate ca citite
        </button>
      </div>
    </div>

    <div v-if="loading && notifications.length === 0" class="text-center py-4">
      <div class="spinner-border spinner-border-sm" role="status" />
      <div class="small text-muted mt-2">Se încarcă notificările...</div>
    </div>

    <div v-else>
      <div v-if="notifications.length === 0" class="alert alert-info small">
        Nu ai notificări în acest moment.
      </div>

      <div class="list-group">
        <button
          v-for="notif in notifications"
          :key="notif.id"
          type="button"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
          :class="{ 'bg-light': !notif.read_at }"
          @click="handleClick(notif)"
        >
          <div class="me-3">
            <div class="fw-semibold small">
              {{ notif.data?.title || notif.title || 'Notificare' }}
            </div>
            <div class="small text-muted">
              {{ notif.data?.message || notif.message || '' }}
            </div>
            <div class="small text-muted mt-1">
              {{ formatDateTime(notif.created_at) }}
            </div>
          </div>
          <div class="text-end small">
            <span
              v-if="!notif.read_at"
              class="badge bg-primary"
            >
              Nou
            </span>
            <div class="mt-2" v-if="notif.data?.level">
                <i class="bi" :class="getIconForLevel(notif.data.level)" :style="{ color: getColorForLevel(notif.data.level) }"></i>
            </div>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificationsStore } from '@/store/notifications';
import { storeToRefs } from 'pinia';

const router = useRouter();
const store = useNotificationsStore();
const { notifications, loading } = storeToRefs(store);

const formatDateTime = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleString('ro-RO');
};

const getIconForLevel = (level) => {
    switch(level) {
        case 'success': return 'bi-check-circle-fill';
        case 'warning': return 'bi-exclamation-triangle-fill';
        case 'error': return 'bi-x-circle-fill';
        default: return 'bi-info-circle-fill';
    }
};

const getColorForLevel = (level) => {
    switch(level) {
        case 'success': return '#198754';
        case 'warning': return '#ffc107';
        case 'error': return '#dc3545';
        default: return '#0d6efd';
    }
};

const handleClick = async (notif) => {
  if (!notif.read_at) {
    await store.markRead(notif.id);
  }

  const url = notif.data?.action_url || notif.action_url;
  if (url) {
      if (url.startsWith('http')) {
          window.location.href = url;
      } else {
          router.push(url);
      }
  }
};

const handleMarkAll = async () => {
  await store.markAllRead();
};

onMounted(() => {
  store.fetchNotifications();
  store.fetchUnreadCount();
});
</script>
