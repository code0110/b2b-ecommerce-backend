<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Notificări</h1>
        <p class="small text-muted mb-0">
          Actualizări legate de comenzi, plăți, oferte și promoții.
        </p>
      </div>
      <button
        type="button"
        class="btn btn-outline-secondary btn-sm"
        :disabled="loading || notifications.length === 0"
        @click="handleMarkAll"
      >
        Marchează toate ca citite
      </button>
    </div>

    <div v-if="error" class="alert alert-danger small mb-3">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-4">
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
              {{ notif.title || 'Notificare' }}
            </div>
            <div class="small">
              {{ notif.message }}
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
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import {
  fetchNotifications,
  markNotificationRead,
  markAllNotificationsRead,
} from '@/services/account/notifications';
import { useRouter } from 'vue-router';

const router = useRouter();

const loading = ref(false);
const error = ref('');
const notifications = ref([]);

const formatDateTime = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleString('ro-RO');
};

const loadNotifications = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchNotifications();
    notifications.value = data.data ?? data;
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut încărca notificările.';
  } finally {
    loading.value = false;
  }
};

const handleClick = async (notif) => {
  if (!notif.read_at) {
    try {
      await markNotificationRead(notif.id);
      notif.read_at = new Date().toISOString();
    } catch (e) {
      console.error(e);
    }
  }

  if (notif.link_url) {
    router.push(notif.link_url);
  }
};

const handleMarkAll = async () => {
  try {
    await markAllNotificationsRead();
    notifications.value = notifications.value.map((n) => ({
      ...n,
      read_at: n.read_at || new Date().toISOString(),
    }));
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut marca toate notificările ca citite.';
  }
};

onMounted(loadNotifications);
</script>
