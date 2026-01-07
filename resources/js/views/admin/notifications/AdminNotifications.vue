<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Centru Notificări</h1>
      <button
        v-if="activeTab === 'inbox'"
        class="btn btn-sm btn-outline-secondary"
        type="button"
        @click="loadNotifications"
      >
        Reîncarcă
      </button>
      <button
        v-if="activeTab === 'history'"
        class="btn btn-sm btn-outline-secondary"
        type="button"
        @click="loadHistory"
      >
        Reîncarcă Istoric
      </button>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4">
      <li class="nav-item">
        <a 
          class="nav-link" 
          :class="{ active: activeTab === 'inbox' }" 
          href="#" 
          @click.prevent="activeTab = 'inbox'"
        >
          <i class="bi bi-inbox me-1"></i> Inbox Admin
        </a>
      </li>
      <li class="nav-item">
        <a 
          class="nav-link" 
          :class="{ active: activeTab === 'send' }" 
          href="#" 
          @click.prevent="activeTab = 'send'"
        >
          <i class="bi bi-send me-1"></i> Trimite Notificare
        </a>
      </li>
      <li class="nav-item">
        <a 
          class="nav-link" 
          :class="{ active: activeTab === 'history' }" 
          href="#" 
          @click.prevent="switchTab('history')"
        >
          <i class="bi bi-clock-history me-1"></i> Istoric Trimiteri
        </a>
      </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
      
      <!-- INBOX TAB -->
      <div v-if="activeTab === 'inbox'" class="tab-pane fade show active">
        <div v-if="error" class="alert alert-danger py-2">
          {{ error }}
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <li
                v-if="loading"
                class="list-group-item small text-muted text-center py-3"
              >
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
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
                <div class="d-flex gap-3">
                   <div class="mt-1">
                      <i 
                        class="bi fs-5"
                        :class="getIconForType(n.data?.level || 'info')"
                        :style="{ color: getColorForType(n.data?.level || 'info') }"
                      ></i>
                   </div>
                   <div>
                      <div class="fw-semibold">
                        {{ n.data?.title || n.title || 'Notificare' }}
                      </div>
                      <div class="text-muted">
                        {{ n.data?.message || n.data?.body || n.message || '—' }}
                      </div>
                      <div class="text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-clock"></i> {{ formatDate(n.created_at) }}
                      </div>
                   </div>
                </div>
                <div class="ms-3">
                  <button
                    v-if="!n.read_at"
                    class="btn btn-sm btn-outline-secondary"
                    type="button"
                    @click="markRead(n)"
                    title="Marchează ca citită"
                  >
                    <i class="bi bi-check2"></i>
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- SEND TAB -->
      <div v-if="activeTab === 'send'" class="tab-pane fade show active">
        <div class="row">
          <!-- Formular -->
          <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Configurare Notificare</h6>
              </div>
              <div class="card-body">
                <form @submit.prevent="sendNotification">
                  <div class="mb-4">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Destinatari</label>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" id="targetAll" value="all" v-model="form.target_type">
                        <label class="form-check-label" for="targetAll">Toți utilizatorii</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" id="targetRole" value="role" v-model="form.target_type">
                        <label class="form-check-label" for="targetRole">Grup / Rol</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" id="targetUsers" value="users" v-model="form.target_type">
                        <label class="form-check-label" for="targetUsers">Utilizatori specifici</label>
                      </div>
                    </div>

                    <div v-if="form.target_type === 'role'" class="mb-3">
                       <label class="form-label small">Alege Rolul</label>
                       <select class="form-select" v-model="form.target_id">
                          <option value="">-- Selectează --</option>
                          <option value="sales_agent">Agenți Vânzări</option>
                          <option value="sales_director">Directori Vânzări</option>
                          <option value="customer">Clienți B2B</option>
                          <option value="admin">Administratori</option>
                       </select>
                    </div>

                    <div v-if="form.target_type === 'users'" class="bg-light p-3 rounded mb-3 border">
                      <label class="form-label small">Caută Utilizatori</label>
                      <div class="input-group mb-2">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input 
                          type="text" 
                          class="form-control" 
                          placeholder="Nume sau email..." 
                          v-model="userSearchQuery"
                          @input="handleUserSearch"
                        >
                      </div>

                      <!-- Search Results -->
                      <div v-if="searchResults.length > 0" class="list-group mb-2 shadow-sm" style="max-height: 200px; overflow-y: auto;">
                        <button 
                          v-for="user in searchResults" 
                          :key="user.id"
                          type="button"
                          class="list-group-item list-group-item-action small"
                          @click="addUserToTarget(user)"
                        >
                          <div class="fw-bold">{{ user.name }}</div>
                          <div class="text-muted" style="font-size: 0.75rem">{{ user.email }}</div>
                        </button>
                      </div>

                      <!-- Selected Users -->
                      <div v-if="selectedUsers.length > 0">
                        <div class="d-flex flex-wrap gap-2">
                           <span v-for="(u, idx) in selectedUsers" :key="u.id" class="badge bg-primary d-flex align-items-center">
                              {{ u.name }}
                              <button type="button" class="btn-close btn-close-white ms-2" style="font-size: 0.5rem" @click="removeUserFromTarget(idx)"></button>
                           </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Conținut Mesaj</label>
                    <div class="mb-3">
                      <label class="form-label">Titlu</label>
                      <input type="text" class="form-control" v-model="form.title" placeholder="Ex: Actualizare Termeni" required>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">Tip Notificare</label>
                      <select class="form-select" v-model="form.type">
                        <option value="info">Informație (Albastru)</option>
                        <option value="success">Succes (Verde)</option>
                        <option value="warning">Avertisment (Galben)</option>
                        <option value="error">Eroare (Roșu)</option>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Mesaj</label>
                      <textarea class="form-control" v-model="form.message" rows="4" placeholder="Scrie mesajul aici..." required></textarea>
                    </div>

                    <div class="mb-3">
                       <label class="form-label">Link Acțiune (Opțional)</label>
                       <input type="text" class="form-control" v-model="form.action_url" placeholder="Ex: /promotii">
                    </div>
                  </div>

                  <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4" :disabled="sending">
                      <span v-if="sending" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                      <i v-else class="bi bi-send-fill me-2"></i>
                      Trimite Notificarea
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- Preview -->
          <div class="col-lg-5">
            <div class="sticky-top" style="top: 20px; z-index: 1;">
              <h6 class="text-muted text-uppercase small fw-bold mb-3">Previzualizare</h6>
              <div class="card border-0 shadow-sm mb-4 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 bottom-0" style="width: 4px;" :style="{ backgroundColor: getColorForType(form.type) }"></div>
                <div class="card-body ps-4">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold mb-0" :style="{ color: getColorForType(form.type) }">
                      <i class="bi me-2" :class="getIconForType(form.type)"></i>
                      {{ form.title || 'Titlu Notificare' }}
                    </h6>
                    <small class="text-muted">Acum</small>
                  </div>
                  <p class="text-muted small mb-2" style="white-space: pre-line;">
                    {{ form.message || 'Conținutul notificării va apărea aici...' }}
                  </p>
                  <a v-if="form.action_url" href="#" class="btn btn-sm btn-outline-primary mt-2 disabled">Vezi detalii</a>
                </div>
              </div>
              <div class="alert alert-info small">
                 <i class="bi bi-info-circle me-1"></i>
                 Aceasta este o previzualizare aproximativă a modului în care utilizatorii vor vedea notificarea în panoul lor.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- HISTORY TAB -->
      <div v-if="activeTab === 'history'" class="tab-pane fade show active">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3">
             <h6 class="mb-0 fw-bold">Ultimele 50 notificări trimise</h6>
          </div>
          <div class="card-body p-0">
             <div v-if="loadingHistory" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                Se încarcă istoricul...
             </div>
             <ul v-else class="list-group list-group-flush">
                <li v-for="h in history" :key="h.id" class="list-group-item">
                   <div class="d-flex justify-content-between">
                      <div>
                         <span class="badge me-2" :class="'bg-' + (h.data?.level === 'error' ? 'danger' : h.data?.level === 'warning' ? 'warning' : h.data?.level === 'success' ? 'success' : 'info')">
                            {{ h.data?.level || 'info' }}
                         </span>
                         <strong>{{ h.data?.title }}</strong>
                      </div>
                      <small class="text-muted">{{ formatDate(h.created_at) }}</small>
                   </div>
                   <div class="mt-1 small text-muted">
                      {{ h.data?.message }}
                   </div>
                   <div class="mt-2 small border-top pt-2 d-flex justify-content-between text-secondary">
                      <span>Destinatar: <strong>{{ h.recipient_name }}</strong></span>
                      <span v-if="h.read_at" class="text-success"><i class="bi bi-check-all"></i> Citit: {{ formatDate(h.read_at) }}</span>
                      <span v-else class="text-muted"><i class="bi bi-check"></i> Trimis (necitit)</span>
                   </div>
                </li>
             </ul>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { fetchAdminNotifications, markNotificationRead, fetchNotificationHistory } from '@/services/admin/notifications'
import { useNotificationsStore } from '@/store/notifications'
import api from '@/services/http'
import { useToast } from 'vue-toastification'

const notificationsStore = useNotificationsStore()
const toast = useToast()

// Tabs
const activeTab = ref('inbox')

// Inbox State
const notifications = ref([])
const loading = ref(false)
const error = ref(null)

// History State
const history = ref([])
const loadingHistory = ref(false)

// Send Form State
const sending = ref(false)
const form = reactive({
    target_type: 'all',
    target_id: '',
    title: '',
    message: '',
    type: 'info', // info, success, warning, error
    action_url: ''
})

// User Search State
const userSearchQuery = ref('')
const searchResults = ref([])
const selectedUsers = ref([])

const switchTab = (tab) => {
    activeTab.value = tab;
    if (tab === 'history') {
        loadHistory();
    } else if (tab === 'inbox') {
        loadNotifications();
    }
}

const loadNotifications = async () => {
    loading.value = true
    error.value = null
    try {
        const data = await fetchAdminNotifications({ limit: 50 })
        notifications.value = data.data || data
        // Update store count if needed
        notificationsStore.fetchAdminUnreadCount()
    } catch (e) {
        // console.error(e)
        error.value = 'Nu s-au putut încărca notificările.'
    } finally {
        loading.value = false
    }
}

const loadHistory = async () => {
    loadingHistory.value = true;
    try {
        const data = await fetchNotificationHistory();
        history.value = data;
    } catch (e) {
        // console.error(e);
        toast.error('Nu s-a putut încărca istoricul.');
    } finally {
        loadingHistory.value = false;
    }
}

const markRead = async (n) => {
    try {
        await markNotificationRead(n.id)
        n.read_at = new Date().toISOString()
        notificationsStore.fetchAdminUnreadCount()
    } catch (e) {
        // console.error(e)
    }
}

// Debounced User Search
let searchTimeout = null;
const handleUserSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    
    if (userSearchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }

    searchTimeout = setTimeout(async () => {
        try {
            const { data } = await api.get('/admin/notifications/users-search', {
                params: { query: userSearchQuery.value }
            });
            searchResults.value = data;
        } catch (e) {
            // console.error(e);
        }
    }, 300);
}

const addUserToTarget = (user) => {
    if (!selectedUsers.value.find(u => u.id === user.id)) {
        selectedUsers.value.push(user);
    }
    userSearchQuery.value = '';
    searchResults.value = [];
}

const removeUserFromTarget = (index) => {
    selectedUsers.value.splice(index, 1);
}

const sendNotification = async () => {
    if (form.target_type === 'users' && selectedUsers.value.length === 0) {
        toast.error('Te rog selectează cel puțin un utilizator.');
        return;
    }

    if (!confirm('Ești sigur că vrei să trimiți această notificare?')) return;

    sending.value = true;
    try {
        const payload = { ...form };
        
        if (form.target_type === 'users') {
            payload.target_id = selectedUsers.value.map(u => u.id);
        } else if (form.target_type === 'role') {
             // target_id is already set via v-model
        }

        await api.post('/admin/notifications/send', payload);
        
        toast.success('Notificarea a fost trimisă cu succes!');
        
        // Reset form
        form.title = '';
        form.message = '';
        form.action_url = '';
        form.target_type = 'all';
        form.target_id = '';
        selectedUsers.value = [];
        
        // Refresh history if we switch there or just stay
        if (activeTab.value === 'history') loadHistory();
        
    } catch (e) {
        // console.error(e);
        toast.error('Eroare la trimiterea notificării.');
    } finally {
        sending.value = false;
    }
}

const formatDate = (dateStr) => {
    if (!dateStr) return ''
    return new Date(dateStr).toLocaleString('ro-RO')
}

const getIconForType = (type) => {
    switch(type) {
        case 'success': return 'bi-check-circle-fill';
        case 'warning': return 'bi-exclamation-triangle-fill';
        case 'error': return 'bi-x-circle-fill';
        default: return 'bi-info-circle-fill';
    }
}

const getColorForType = (type) => {
    switch(type) {
        case 'success': return '#198754';
        case 'warning': return '#ffc107';
        case 'error': return '#dc3545';
        default: return '#0d6efd';
    }
}

onMounted(() => {
    loadNotifications();
})
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>
