<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Utilizatori companie</h1>
        <p class="small text-muted mb-0">
          Invite și gestionează utilizatori în contul companiei (doar pentru B2B).
        </p>
      </div>
      <button
        type="button"
        class="btn btn-primary btn-sm"
        @click="startInvite"
      >
        Invită utilizator nou
      </button>
    </div>

    <div v-if="error" class="alert alert-danger small mb-3">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border spinner-border-sm" role="status" />
      <div class="small text-muted mt-2">
        Se încarcă utilizatorii companiei...
      </div>
    </div>

    <div v-else>
      <div v-if="users.length === 0" class="alert alert-info small">
        Nu există alți utilizatori în contul companiei.
      </div>

      <div v-else class="card shadow-sm mb-3">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr class="small text-muted">
                <th>Nume</th>
                <th>Email</th>
                <th>Rol intern</th>
                <th>Status</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in users" :key="u.id">
                <td>{{ u.name }}</td>
                <td>{{ u.email }}</td>
                <td>{{ roleLabel(u.internal_role) }}</td>
                <td>
                  <span class="badge bg-light text-dark border">
                    {{ u.status_label || u.status }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-sm"
                      @click="startEdit(u)"
                    >
                      Editează
                    </button>
                    <button
                      v-if="!u.is_owner"
                      type="button"
                      class="btn btn-outline-danger btn-sm"
                      @click="confirmDelete(u)"
                    >
                      Șterge
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Formular add/edit -->
      <div v-if="editingUser" class="card shadow-sm">
        <div class="card-body small">
          <div class="d-flex justify-content-between mb-2">
            <h2 class="h6 mb-0">
              {{ editingUser.id ? 'Editează utilizator' : 'Invită utilizator' }}
            </h2>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="editingUser = null"
            >
              Renunță
            </button>
          </div>

          <form class="row g-2" @submit.prevent="submitUser">
            <div class="col-md-4">
              <label class="form-label form-label-sm">Nume</label>
              <input
                v-model="editingUser.name"
                type="text"
                class="form-control form-control-sm"
                required
              />
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-sm">Email</label>
              <input
                v-model="editingUser.email"
                type="email"
                class="form-control form-control-sm"
                required
              />
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-sm">Rol intern</label>
              <select
                v-model="editingUser.internal_role"
                class="form-select form-select-sm"
                required
              >
                <option value="buyer">Poate plasa comenzi</option>
                <option value="requester">Solicită aprobare</option>
                <option value="approver">Aprobă comenzi</option>
                <option value="viewer">Doar vizualizare</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label form-label-sm">Telefon</label>
              <input
                v-model="editingUser.phone"
                type="text"
                class="form-control form-control-sm"
              />
            </div>
            <div class="col-md-8">
              <label class="form-label form-label-sm">Observații</label>
              <input
                v-model="editingUser.notes"
                type="text"
                class="form-control form-control-sm"
              />
            </div>

            <div class="col-12 d-flex justify-content-end">
              <button
                type="submit"
                class="btn btn-primary btn-sm"
                :disabled="saving"
              >
                <span
                  v-if="saving"
                  class="spinner-border spinner-border-sm me-1"
                ></span>
                Salvează
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import {
  fetchCompanyUsers,
  inviteCompanyUser,
  updateCompanyUser,
  deleteCompanyUser,
} from '@/services/account/companyUsers';

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const users = ref([]);
const editingUser = ref(null);

const roleLabel = (role) => {
  switch (role) {
    case 'buyer':
      return 'Poate plasa comenzi';
    case 'requester':
      return 'Solicită aprobare';
    case 'approver':
      return 'Aprobă comenzi';
    case 'viewer':
      return 'Doar vizualizare';
    default:
      return role || '-';
  }
};

const loadUsers = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchCompanyUsers();
    users.value = data.data ?? data;
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut încărca utilizatorii companiei.';
  } finally {
    loading.value = false;
  }
};

const startInvite = () => {
  editingUser.value = {
    id: null,
    name: '',
    email: '',
    internal_role: 'buyer',
    phone: '',
    notes: '',
  };
};

const startEdit = (u) => {
  editingUser.value = { ...u };
};

const submitUser = async () => {
  if (!editingUser.value) return;

  saving.value = true;
  error.value = '';

  try {
    const payload = { ...editingUser.value };
    if (payload.id) {
      await updateCompanyUser(payload.id, payload);
    } else {
      await inviteCompanyUser(payload);
    }
    editingUser.value = null;
    await loadUsers();
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut salva utilizatorul.';
  } finally {
    saving.value = false;
  }
};

const confirmDelete = async (u) => {
  if (!window.confirm('Sigur dorești să ștergi acest utilizator?')) return;
  try {
    await deleteCompanyUser(u.id);
    await loadUsers();
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut șterge utilizatorul.';
  }
};

onMounted(loadUsers);
</script>
