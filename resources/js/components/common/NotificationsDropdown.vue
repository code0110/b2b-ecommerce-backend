<template>
  <div class="dropdown">
    <button
      class="btn btn-link text-decoration-none position-relative p-0 me-2"
      type="button"
      id="notificationsDropdown"
      data-bs-toggle="dropdown"
      aria-expanded="false"
      @click="handleDropdownClick"
    >
      <i class="bi bi-bell fs-5" :class="hasUnread ? 'text-primary' : 'text-secondary'"></i>
      <span
        v-if="unreadCount > 0"
        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
        style="font-size: 0.6rem;"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>
    
    <div class="dropdown-menu dropdown-menu-end shadow-sm p-0" aria-labelledby="notificationsDropdown" style="width: 320px; max-height: 480px; overflow-y: auto;">
      <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
        <h6 class="mb-0 fw-bold small text-uppercase text-muted">Notificări</h6>
        <button v-if="hasUnread" class="btn btn-link btn-sm text-decoration-none p-0 small" @click.stop="markAllRead">
          Marchează tot citit
        </button>
      </div>

      <div v-if="loading" class="text-center py-4 text-muted">
        <div class="spinner-border spinner-border-sm mb-1" role="status"></div>
        <div class="small">Se încarcă...</div>
      </div>

      <div v-else-if="notifications.length === 0" class="text-center py-4 text-muted small">
        <i class="bi bi-bell-slash fs-4 d-block mb-2 text-secondary opacity-50"></i>
        Nu ai notificări recente.
      </div>

      <div v-else class="list-group list-group-flush">
        <div
          v-for="n in notifications"
          :key="n.id"
          class="list-group-item list-group-item-action px-3 py-3 border-bottom"
          :class="{ 'bg-light-blue': !n.read_at }"
        >
          <div class="d-flex gap-3">
            <div class="mt-1 flex-shrink-0">
               <i 
                 class="bi fs-5"
                 :class="getIconForType(n.data?.level || 'info')"
                 :style="{ color: getColorForType(n.data?.level || 'info') }"
               ></i>
            </div>
            <div class="flex-grow-1" style="min-width: 0;">
              <div class="d-flex justify-content-between align-items-start mb-1">
                 <strong class="text-dark small mb-0 text-truncate d-block" style="max-width: 180px;">
                   {{ n.data?.title || n.title || 'Notificare' }}
                 </strong>
                 <small class="text-muted ms-2" style="font-size: 0.7rem; white-space: nowrap;">
                   {{ formatTime(n.created_at) }}
                 </small>
              </div>
              
              <p class="text-muted small mb-1 lh-sm text-break">
                {{ truncateText(n.data?.message || n.data?.body || n.message || '', 80) }}
              </p>

              <div class="d-flex align-items-center justify-content-between mt-2">
                 <a 
                   v-if="n.data?.action_url" 
                   :href="n.data.action_url" 
                   class="btn btn-sm btn-outline-primary py-0 px-2"
                   style="font-size: 0.75rem;"
                   @click="markRead(n)"
                 >
                   Vezi detalii
                 </a>
                 <button 
                   v-if="!n.read_at"
                   class="btn btn-link btn-sm p-0 text-secondary"
                   style="font-size: 0.75rem;"
                   @click.stop="markRead(n)"
                 >
                   Marchează citit
                 </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Footer link (optional) -->
      <!-- <div class="p-2 text-center border-top bg-light">
         <RouterLink :to="{ name: 'notifications' }" class="small text-decoration-none">Vezi toate notificările</RouterLink>
      </div> -->
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useNotificationsStore } from '@/store/notifications';
import { storeToRefs } from 'pinia';
import api from '@/services/http';

const store = useNotificationsStore();
const { notifications, unreadCount, loading } = storeToRefs(store);

const hasUnread = computed(() => unreadCount.value > 0);
let pollInterval = null;

const handleDropdownClick = () => {
  // Refresh when opening
  store.fetchNotifications();
};

const markRead = async (n) => {
  await store.markRead(n.id);
};

const markAllRead = async () => {
  await store.markAllRead();
};

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMins / 60);
  const diffDays = Math.floor(diffHours / 24);

  if (diffMins < 1) return 'Acum';
  if (diffMins < 60) return `${diffMins}m`;
  if (diffHours < 24) return `${diffHours}h`;
  if (diffDays < 7) return `${diffDays}z`;
  return date.toLocaleDateString('ro-RO');
};

const truncateText = (text, length) => {
  if (!text) return '';
  if (text.length <= length) return text;
  return text.substring(0, length) + '...';
};

const getIconForType = (type) => {
    switch(type) {
        case 'success': return 'bi-check-circle-fill';
        case 'warning': return 'bi-exclamation-triangle-fill';
        case 'error': return 'bi-x-circle-fill';
        default: return 'bi-info-circle-fill';
    }
};

const getColorForType = (type) => {
    switch(type) {
        case 'success': return '#198754';
        case 'warning': return '#ffc107';
        case 'error': return '#dc3545';
        default: return '#0d6efd';
    }
};

onMounted(() => {
  store.fetchUnreadCount();
  // Poll every 60 seconds for new notifications
  pollInterval = setInterval(() => {
    store.fetchUnreadCount();
  }, 60000);
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<style scoped>
.bg-light-blue {
  background-color: #f0f7ff;
}
.list-group-item:hover {
  background-color: #f8f9fa;
}
/* Custom Scrollbar */
.dropdown-menu::-webkit-scrollbar {
  width: 6px;
}
.dropdown-menu::-webkit-scrollbar-track {
  background: #f1f1f1; 
}
.dropdown-menu::-webkit-scrollbar-thumb {
  background: #c1c1c1; 
  border-radius: 3px;
}
.dropdown-menu::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8; 
}
</style>
