<template>
  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1 text-gray-800">Utilizatori Back-office</h1>
        <p class="text-muted small mb-0">Gestionează administratorii, operatorii și agenții.</p>
      </div>
      <button class="btn btn-primary shadow-sm d-flex align-items-center gap-2" @click="openCreateModal">
        <i class="bi bi-person-plus-fill"></i>
        <span>Utilizator Nou</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-3 bg-white rounded">
        <form @submit.prevent="loadUsers(1)" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label text-muted small fw-bold text-uppercase">Căutare</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-search text-muted"></i>
              </span>
              <input
                v-model="filters.search"
                type="text"
                class="form-control bg-light border-start-0 ps-0"
                placeholder="Nume, email..."
              />
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label text-muted small fw-bold text-uppercase">Rol</label>
            <select v-model="filters.role" class="form-select bg-light" @change="loadUsers(1)">
              <option value="">Toate rolurile</option>
              <option v-for="role in roles" :key="role.id" :value="role.slug">
                {{ role.name }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label text-muted small fw-bold text-uppercase">Status</label>
            <select v-model="filters.is_active" class="form-select bg-light" @change="loadUsers(1)">
              <option value="">Toate statusurile</option>
              <option value="1">Activi</option>
              <option value="0">Inactivi</option>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
              <i class="bi bi-funnel-fill me-1"></i> Filtrează
            </button>
            <button type="button" class="btn btn-light border" @click="resetFilters" title="Resetează">
              <i class="bi bi-arrow-counterclockwise"></i>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm overflow-hidden">
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Se încarcă...</span>
          </div>
        </div>

        <div v-else-if="users.length === 0" class="text-center py-5">
          <div class="mb-3">
            <i class="bi bi-people text-muted opacity-25" style="font-size: 3rem;"></i>
          </div>
          <h5 class="text-muted">Nu au fost găsiți utilizatori</h5>
          <p class="text-muted small">Încearcă să modifici filtrele de căutare.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 py-3 text-muted small text-uppercase fw-bold border-0">Utilizator</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Roluri</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Telefon</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Status</th>
                <th class="pe-4 py-3 text-muted small text-uppercase fw-bold border-0 text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in users" :key="u.id" class="cursor-pointer-row">
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="avatar-circle me-3 bg-gradient-primary text-white fw-bold d-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 40px; height: 40px;">
                      {{ getInitials(u.name) }}
                    </div>
                    <div>
                      <div class="fw-semibold text-dark">{{ u.name }}</div>
                      <div class="small text-muted">{{ u.email }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1">
                    <span v-for="r in u.roles" :key="r.id" class="badge bg-light text-dark border">
                      {{ r.name }}
                    </span>
                  </div>
                </td>
                <td class="text-muted small">
                  <div v-if="u.phone"><i class="bi bi-telephone me-1"></i>{{ u.phone }}</div>
                  <div v-else>-</div>
                </td>
                <td>
                  <span class="badge rounded-pill" :class="u.is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary'">
                    {{ u.is_active ? 'Activ' : 'Inactiv' }}
                  </span>
                </td>
                <td class="pe-4 text-end">
                  <div class="btn-group">
                    <button class="btn btn-sm btn-light border" @click="editUser(u)" title="Editează">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button 
                      v-if="u.is_active && u.id !== currentUserId" 
                      class="btn btn-sm btn-light border text-danger" 
                      @click="deactivateUser(u)" 
                      title="Dezactivează"
                    >
                      <i class="bi bi-person-x"></i>
                    </button>
                    <button 
                      v-if="!u.is_active && u.id !== currentUserId" 
                      class="btn btn-sm btn-light border text-success" 
                      @click="activateUser(u)" 
                      title="Activează"
                    >
                      <i class="bi bi-person-check"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="card-footer bg-white border-top py-3">
        <div class="d-flex justify-content-between align-items-center">
          <div class="small text-muted">
            Afișare {{ pagination.from }} - {{ pagination.to }} din {{ pagination.total }} utilizatori
          </div>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button class="page-link border-0" @click="changePage(pagination.current_page - 1)">
                  <i class="bi bi-chevron-left"></i>
                </button>
              </li>
              <li class="page-item active">
                <span class="page-link border-0 bg-primary">{{ pagination.current_page }}</span>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <button class="page-link border-0" @click="changePage(pagination.current_page + 1)">
                  <i class="bi bi-chevron-right"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="modal-backdrop-custom">
      <div class="modal-panel-custom">
        <div class="card border-0 shadow-lg" style="width: 600px; max-width: 95vw;">
          <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="modal-title fw-bold mb-0">{{ form.id ? 'Editează Utilizator' : 'Utilizator Nou' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="card-body pt-4">
            <form @submit.prevent="submitForm">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted text-uppercase">Prenume</label>
                  <input v-model="form.first_name" type="text" class="form-control" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted text-uppercase">Nume</label>
                  <input v-model="form.last_name" type="text" class="form-control" />
                </div>
                <div class="col-md-12">
                  <label class="form-label small fw-bold text-muted text-uppercase">Email</label>
                  <input v-model="form.email" type="email" class="form-control" required />
                </div>
                <div class="col-md-12">
                  <label class="form-label small fw-bold text-muted text-uppercase">Telefon</label>
                  <input v-model="form.phone" type="text" class="form-control" />
                </div>
                <div class="col-md-12">
                  <label class="form-label small fw-bold text-muted text-uppercase">Parolă</label>
                  <input 
                    v-model="form.password" 
                    type="password" 
                    class="form-control" 
                    :placeholder="form.id ? 'Lasă gol pentru a nu schimba' : 'Obligatoriu pentru utilizatori noi'"
                    :required="!form.id"
                  />
                </div>
                
                <div class="col-md-12">
                  <label class="form-label small fw-bold text-muted text-uppercase">Roluri</label>
                  <div class="card bg-light border-0">
                    <div class="card-body p-2" style="max-height: 150px; overflow-y: auto;">
                      <div v-for="role in roles" :key="role.id" class="form-check">
                        <input 
                          class="form-check-input" 
                          type="checkbox" 
                          :value="role.id" 
                          :id="'role-' + role.id"
                          v-model="form.role_ids"
                        >
                        <label class="form-check-label small" :for="'role-' + role.id">
                          {{ role.name }} <span class="text-muted">({{ role.slug }})</span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-12">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="activeSwitch" v-model="form.is_active">
                    <label class="form-check-label small fw-bold" for="activeSwitch">Cont Activ</label>
                  </div>
                </div>
              </div>

              <div v-if="formError" class="alert alert-danger mt-3 mb-0 small">
                {{ formError }}
              </div>

              <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light" @click="closeModal">Anulează</button>
                <button type="submit" class="btn btn-primary" :disabled="formLoading">
                  <span v-if="formLoading" class="spinner-border spinner-border-sm me-2"></span>
                  {{ form.id ? 'Salvează' : 'Creează' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import { fetchUsers, createUser, updateUser, deleteUser } from '@/services/admin/users';
import { fetchRoles } from '@/services/admin/roles';
import { useAuthStore } from '@/store/auth';
import { useToast } from 'vue-toastification';

const authStore = useAuthStore();
const toast = useToast();
const currentUserId = authStore.user?.id ?? null;

const users = ref([]);
const roles = ref([]);
const directors = ref([]);
const loading = ref(false);
const showModal = ref(false);
const formLoading = ref(false);
const formError = ref('');

const filters = reactive({
  search: '',
  role: '',
  is_active: '',
});

const form = reactive({
  id: null,
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
  is_active: true,
  role_ids: [],
  director_id: null,
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
});

const getInitials = (name) => {
  if (!name) return '??';
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

const loadDirectors = async () => {
  try {
    const data = await fetchUsers({ role: 'sales_director', per_page: 100 });
    directors.value = data.data || data.items || [];
  } catch (e) {
    console.error('loadDirectors error', e);
  }
};

const loadRoles = async () => {
  try {
    roles.value = await fetchRoles();
  } catch (e) {
    console.error('loadRoles error', e);
  }
};

const loadUsers = async (page = 1) => {
  loading.value = true;
  try {
    const params = { ...filters, page };
    const data = await fetchUsers(params);
    users.value = data.data || data.items || [];
    
    pagination.current_page = data.current_page || 1;
    pagination.last_page = data.last_page || 1;
    pagination.total = data.total || 0;
    pagination.from = data.from || 0;
    pagination.to = data.to || 0;
  } catch (e) {
    console.error('loadUsers error', e);
    toast.error('Nu s-au putut încărca utilizatorii.');
  } finally {
    loading.value = false;
  }
};

const changePage = (page) => {
  if (page >= 1 && page <= pagination.last_page) {
    loadUsers(page);
  }
};

const resetFilters = () => {
  filters.search = '';
  filters.role = '';
  filters.is_active = '';
  loadUsers(1);
};

const isSalesAgent = computed(() => {
  const agentRole = roles.value.find(r => r.slug === 'sales_agent');
  if (!agentRole) return false;
  return form.role_ids.includes(agentRole.id);
});

const openCreateModal = () => {
  form.id = null;
  form.first_name = '';
  form.last_name = '';
  form.email = '';
  form.phone = '';
  form.password = '';
  form.is_active = true;
  form.role_ids = [];
  form.director_id = null;
  formError.value = '';
  showModal.value = true;
};

const editUser = (u) => {
  form.id = u.id;
  const nameParts = (u.name || '').split(' ');
  form.first_name = u.first_name || nameParts[0];
  form.last_name = u.last_name || nameParts.slice(1).join(' ');
  form.email = u.email;
  form.phone = u.phone || '';
  form.password = '';
  form.is_active = !!u.is_active;
  form.role_ids = u.roles ? u.roles.map(r => r.id) : [];
  form.director_id = u.director_id || null;
  formError.value = '';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const submitForm = async () => {
  formLoading.value = true;
  formError.value = '';

  const payload = {
    first_name: form.first_name,
    last_name: form.last_name || null,
    email: form.email,
    phone: form.phone || null,
    is_active: form.is_active,
    role_ids: form.role_ids,
  };

  if (form.password) {
    payload.password = form.password;
  }

  try {
    if (form.id) {
      await updateUser(form.id, payload);
      toast.success('Utilizator actualizat cu succes!');
    } else {
      await createUser(payload);
      toast.success('Utilizator creat cu succes!');
    }
    await loadUsers(pagination.current_page);
    closeModal();
  } catch (e) {
    console.error('submitForm error', e);
    if (e.response && e.response.data && e.response.data.errors) {
      formError.value = Object.values(e.response.data.errors).flat().join(', ');
    } else {
      formError.value = 'A apărut o eroare la salvarea utilizatorului.';
    }
    toast.error('Eroare la salvarea utilizatorului.');
  } finally {
    formLoading.value = false;
  }
};

const deactivateUser = async (u) => {
  if (!confirm(`Sigur vrei să dezactivezi utilizatorul ${u.name}?`)) return;
  try {
    await deleteUser(u.id); // Assuming deleteUser deactivates (soft delete)
    await loadUsers(pagination.current_page);
    toast.success('Utilizator dezactivat cu succes.');
  } catch (e) {
    toast.error('Nu s-a putut dezactiva utilizatorul.');
  }
};

const activateUser = async (u) => {
  if (!confirm(`Sigur vrei să activezi utilizatorul ${u.name}?`)) return;
  try {
    await updateUser(u.id, { is_active: true });
    await loadUsers(pagination.current_page);
    toast.success('Utilizator activat cu succes.');
  } catch (e) {
    toast.error('Nu s-a putut activa utilizatorul.');
  }
};

onMounted(() => {
  loadRoles();
  loadDirectors();
  loadUsers();
});
</script>

<style scoped>
.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(2px);
  z-index: 1040;
}
.modal-panel-custom {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 1050;
  animation: slideIn 0.3s ease-out;
}
@keyframes slideIn {
  from { opacity: 0; transform: translate(-50%, -48%); }
  to { opacity: 1; transform: translate(-50%, -50%); }
}
.cursor-pointer-row {
  transition: background-color 0.15s ease;
}
.avatar-circle {
  font-size: 1rem;
  background: linear-gradient(135deg, #4f46e5, #3b82f6);
}
.bg-gradient-primary {
  background: linear-gradient(135deg, #4f46e5, #3b82f6);
}
</style>
