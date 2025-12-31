<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Utilizatori back-office</h1>
        <p class="text-muted small mb-0">
          Administrare utilizatori (admin, operator, agent, marketer etc.) și rolurile aferente.
        </p>
      </div>
    </div>

    <div class="row g-3">
      <!-- LISTĂ UTILIZATORI -->
      <div class="col-lg-7">
        <div class="card">
          <div class="card-body">
            <form class="row g-2 align-items-end mb-3" @submit.prevent="loadUsers">
              <div class="col-sm-4">
                <label class="form-label form-label-sm">Căutare</label>
                <input
                  v-model="filters.search"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Nume sau email"
                >
              </div>
              <div class="col-sm-3">
                <label class="form-label form-label-sm">Rol</label>
                <select v-model="filters.role" class="form-select form-select-sm">
                  <option value="">Toate</option>
                  <option
                    v-for="role in roles"
                    :key="role.id"
                    :value="role.slug"
                  >
                    {{ role.name }}
                  </option>
                </select>
              </div>
              <div class="col-sm-3">
                <label class="form-label form-label-sm">Status</label>
                <select v-model="filters.is_active" class="form-select form-select-sm">
                  <option value="">Toți</option>
                  <option value="1">Activi</option>
                  <option value="0">Inactivi</option>
                </select>
              </div>
              <div class="col-sm-2 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-link btn-sm" @click="resetFilters">
                  Reset
                </button>
                <button type="submit" class="btn btn-primary btn-sm">
                  Caută
                </button>
              </div>
            </form>

            <div v-if="loading" class="text-center text-muted py-4">
              Se încarcă utilizatorii…
            </div>

            <div v-else-if="error" class="alert alert-danger">
              {{ error }}
            </div>

            <div v-else>
              <div v-if="users.length === 0" class="text-center text-muted py-4 small">
                Nu există utilizatori pentru filtrele selectate.
              </div>
              <div v-else>
                <div class="table-responsive">
                  <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Nume</th>
                        <th>Email</th>
                        <th>Roluri</th>
                        <th>Status</th>
                        <th style="width: 120px;"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="u in users" :key="u.id">
                        <td>
                          <div class="fw-semibold">{{ u.name }}</div>
                        </td>
                        <td class="small">{{ u.email }}</td>
                        <td class="small">
                          <span
                            v-for="r in u.roles"
                            :key="r.id"
                            class="badge bg-secondary me-1 mb-1"
                          >
                            {{ r.name }}
                          </span>
                        </td>
                        <td>
                          <span
                            class="badge"
                            :class="u.is_active ? 'bg-success' : 'bg-secondary'"
                          >
                            {{ u.is_active ? 'Activ' : 'Inactiv' }}
                          </span>
                        </td>
                        <td class="text-end">
                          <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm me-1"
                            @click="editUser(u)"
                            title="Editează"
                          >
                            <i class="bi bi-pencil"></i>
                          </button>
                          
                          <button
                            v-if="u.is_active && u.id !== currentUserId"
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            @click="deactivateUser(u)"
                            title="Dezactivează"
                          >
                            <i class="bi bi-person-x"></i>
                          </button>

                          <button
                            v-if="!u.is_active && u.id !== currentUserId"
                            type="button"
                            class="btn btn-outline-success btn-sm"
                            @click="activateUser(u)"
                            title="Activează"
                          >
                            <i class="bi bi-person-check"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 small" v-if="pagination.last_page > 1">
                  <div class="text-muted">
                    Afișare {{ pagination.from }} - {{ pagination.to }} din {{ pagination.total }} utilizatori
                  </div>
                  <nav>
                    <ul class="pagination pagination-sm mb-0">
                      <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                        <button class="page-link" @click="changePage(pagination.current_page - 1)">
                          &laquo;
                        </button>
                      </li>
                      <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                        <button class="page-link" @click="changePage(pagination.current_page + 1)">
                          &raquo;
                        </button>
                      </li>
                    </ul>
                  </nav>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- FORM UTILIZATOR -->
      <div class="col-lg-5">
        <div class="card">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <span class="small text-uppercase text-muted fw-semibold">
              {{ form.id ? 'Editează utilizator' : 'Utilizator nou' }}
            </span>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitForm" class="small">
              <div class="row g-2">
                <div class="col-sm-6">
                  <label class="form-label form-label-sm">Prenume</label>
                  <input
                    v-model="form.first_name"
                    type="text"
                    class="form-control form-control-sm"
                    required
                  >
                </div>
                <div class="col-sm-6">
                  <label class="form-label form-label-sm">Nume</label>
                  <input
                    v-model="form.last_name"
                    type="text"
                    class="form-control form-control-sm"
                  >
                </div>
              </div>

              <div class="mt-2">
                <label class="form-label form-label-sm">Email</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="form-control form-control-sm"
                  required
                >
              </div>

              <div class="mt-2">
                <label class="form-label form-label-sm">Telefon</label>
                <input
                  v-model="form.phone"
                  type="text"
                  class="form-control form-control-sm"
                >
              </div>

              <div class="mt-2">
                <label class="form-label form-label-sm">
                  Parolă
                  <span v-if="form.id" class="text-muted">(lasă gol pentru a nu o schimba)</span>
                </label>
                <input
                  v-model="form.password"
                  type="password"
                  class="form-control form-control-sm"
                  :required="!form.id"
                >
              </div>

              <div class="mt-2">
                <label class="form-label form-label-sm">Roluri</label>
                <div class="border rounded p-2" style="max-height: 180px; overflow-y: auto;">
                  <div
                    v-for="role in roles"
                    :key="role.id"
                    class="form-check form-check-sm"
                  >
                    <input
                      class="form-check-input"
                      type="checkbox"
                      :id="`role-${role.id}`"
                      :value="role.id"
                      v-model="form.role_ids"
                    >
                    <label class="form-check-label" :for="`role-${role.id}`">
                      {{ role.name }}
                      <span class="text-muted small">({{ role.slug }})</span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="mt-2 form-check form-switch">
                <input
                  v-model="form.is_active"
                  class="form-check-input"
                  type="checkbox"
                  id="is-active"
                >
                <label class="form-check-label" for="is-active">
                  Utilizator activ
                </label>
              </div>

              <div v-if="formError" class="alert alert-danger mt-2 py-1 mb-2">
                {{ formError }}
              </div>

              <div class="mt-3 d-flex justify-content-between">
                <button
                  type="button"
                  class="btn btn-link btn-sm"
                  @click="resetForm"
                >
                  Reset formular
                </button>
                <button
                  type="submit"
                  class="btn btn-primary btn-sm"
                  :disabled="formLoading"
                >
                  <span
                    v-if="formLoading"
                    class="spinner-border spinner-border-sm me-1"
                  ></span>
                  {{ form.id ? 'Salvează modificările' : 'Creează utilizator' }}
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
import { onMounted, reactive, ref } from 'vue';
import { fetchUsers, createUser, updateUser, deleteUser } from '@/services/admin/users';
import { fetchRoles } from '@/services/admin/roles';
import { useAuthStore } from '@/store/auth';

const authStore = useAuthStore();
const currentUserId = authStore.user?.id ?? null;

const users = ref([]);
const roles = ref([]);

const loading = ref(false);
const error = ref('');

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
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
});

const loadRoles = async () => {
  try {
    roles.value = await fetchRoles();
  } catch (e) {
    console.error('loadRoles error', e);
  }
};

const loadUsers = async (page = 1) => {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      ...filters,
      page,
    };
    const data = await fetchUsers(params);
    users.value = data.data || data.items || [];
    
    pagination.current_page = data.current_page || 1;
    pagination.last_page = data.last_page || 1;
    pagination.total = data.total || 0;
    pagination.from = data.from || 0;
    pagination.to = data.to || 0;
  } catch (e) {
    console.error('loadUsers error', e);
    error.value = 'Nu s-au putut încărca utilizatorii.';
  } finally {
    loading.value = false;
  }
};

const changePage = (page) => {
  if (page < 1 || page > pagination.last_page) return;
  loadUsers(page);
};

const resetFilters = () => {
  filters.search = '';
  filters.role = '';
  filters.is_active = '';
  loadUsers(1);
};

const resetForm = () => {
  form.id = null;
  form.first_name = '';
  form.last_name = '';
  form.email = '';
  form.phone = '';
  form.password = '';
  form.is_active = true;
  form.role_ids = [];
  formError.value = '';
};

const editUser = (u) => {
  form.id = u.id;
  form.first_name = u.first_name || (u.name || '').split(' ')[0];
  form.last_name = u.last_name || (u.name || '').split(' ').slice(1).join(' ');
  form.email = u.email;
  form.phone = u.phone || '';
  form.password = '';
  form.is_active = !!u.is_active;
  form.role_ids = u.roles.map(r => r.id);
  formError.value = '';
};

const deactivateUser = async (u) => {
  if (!confirm(`Sigur vrei să dezactivezi utilizatorul ${u.name}?`)) return;

  try {
    await deleteUser(u.id);
    await loadUsers(pagination.current_page);
  } catch (e) {
    console.error('deactivateUser error', e);
    alert('Nu s-a putut dezactiva utilizatorul.');
  }
};

const activateUser = async (u) => {
  if (!confirm(`Sigur vrei să activezi utilizatorul ${u.name}?`)) return;

  try {
    await updateUser(u.id, { is_active: true });
    await loadUsers(pagination.current_page);
  } catch (e) {
    console.error('activateUser error', e);
    alert('Nu s-a putut activa utilizatorul.');
  }
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
    } else {
      await createUser(payload);
    }

    await loadUsers(pagination.current_page);
    resetForm();
  } catch (e) {
    console.error('submitForm error', e);
    formError.value = e.response?.data?.message || 'Eroare la salvarea utilizatorului.';
  } finally {
    formLoading.value = false;
  }
};

onMounted(async () => {
  await loadRoles();
  await loadUsers();
});
</script>
