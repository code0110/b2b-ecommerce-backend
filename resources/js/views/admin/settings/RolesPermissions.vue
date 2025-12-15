<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h4 mb-1">Roluri & permisiuni</h1>
        <p class="text-muted small mb-0">
          Configurează rolurile (admin, operator, agent, marketer, clienți) și permisiunile asociate.
        </p>
      </div>
    </div>

    <ul class="nav nav-pills mb-3 small">
      <li class="nav-item">
        <button
          class="nav-link"
          :class="{ active: activeTab === 'roles' }"
          @click="activeTab = 'roles'"
        >
          Roluri
        </button>
      </li>
      <li class="nav-item">
        <button
          class="nav-link"
          :class="{ active: activeTab === 'permissions' }"
          @click="activeTab = 'permissions'"
        >
          Permisiuni
        </button>
      </li>
    </ul>

    <!-- TAB ROLURI -->
    <div v-if="activeTab === 'roles'" class="row g-3">
      <div class="col-lg-5">
        <div class="card h-100">
          <div class="card-header py-2">
            <span class="small text-uppercase text-muted fw-semibold">
              Roluri existente
            </span>
          </div>
          <div class="card-body p-0">
            <div v-if="rolesLoading" class="text-center text-muted py-3 small">
              Se încarcă rolurile…
            </div>
            <div v-else class="table-responsive">
              <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Nume</th>
                    <th>Slug</th>
                    <th>Cod</th>
                    <th style="width: 40px;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="role in roles"
                    :key="role.id"
                    :class="{ 'table-active': role.id === roleForm.id }"
                    @click="selectRole(role)"
                    style="cursor: pointer;"
                  >
                    <td>
                      <div class="fw-semibold">{{ role.name }}</div>
                      <div class="small text-muted">{{ role.description }}</div>
                    </td>
                    <td class="small">{{ role.slug }}</td>
                    <td class="small">{{ role.code }}</td>
                    <td class="text-end">
                      <span
                        v-if="role.is_system"
                        class="badge bg-secondary"
                      >
                        sistem
                      </span>
                    </td>
                  </tr>
                  <tr v-if="roles.length === 0">
                    <td colspan="4" class="text-center text-muted small py-3">
                      Nu există roluri definite.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-if="rolesError" class="alert alert-danger m-2 py-1 small">
              {{ rolesError }}
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <span class="small text-uppercase text-muted fw-semibold">
              {{ roleForm.id ? 'Editează rol' : 'Rol nou' }}
            </span>
            <button
              type="button"
              class="btn btn-link btn-sm"
              @click="resetRoleForm"
            >
              Reset
            </button>
          </div>
          <div class="card-body small">
            <form @submit.prevent="submitRole">
              <div class="row g-2">
                <div class="col-sm-4">
                  <label class="form-label form-label-sm">Slug</label>
                  <input
                    v-model="roleForm.slug"
                    type="text"
                    class="form-control form-control-sm"
                    :disabled="roleForm.is_system"
                    required
                  >
                </div>
                <div class="col-sm-4">
                  <label class="form-label form-label-sm">Cod</label>
                  <input
                    v-model="roleForm.code"
                    type="text"
                    class="form-control form-control-sm"
                    :disabled="roleForm.is_system"
                    required
                  >
                </div>
                <div class="col-sm-4">
                  <label class="form-label form-label-sm">Nume rol</label>
                  <input
                    v-model="roleForm.name"
                    type="text"
                    class="form-control form-control-sm"
                    required
                  >
                </div>
              </div>

              <div class="mt-2">
                <label class="form-label form-label-sm">Descriere</label>
                <input
                  v-model="roleForm.description"
                  type="text"
                  class="form-control form-control-sm"
                >
              </div>

              <div class="mt-3">
                <label class="form-label form-label-sm d-flex justify-content-between">
                  <span>Permisiuni pentru acest rol</span>
                  <span
                    v-if="roleForm.permission_ids.length"
                    class="text-muted"
                  >
                    {{ roleForm.permission_ids.length }} selectate
                  </span>
                </label>
                <div class="border rounded p-2" style="max-height: 260px; overflow-y: auto;">
                  <div
                    v-for="(group, module) in permissionsByModule"
                    :key="module || 'no-module'"
                    class="mb-2"
                  >
                    <div class="fw-semibold small mb-1">
                      {{ module || 'Fără modul' }}
                    </div>
                    <div class="row g-1">
                      <div
                        class="col-6"
                        v-for="perm in group"
                        :key="perm.id"
                      >
                        <div class="form-check form-check-sm">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            :id="`perm-${perm.id}`"
                            :value="perm.id"
                            v-model="roleForm.permission_ids"
                          >
                          <label
                            class="form-check-label"
                            :for="`perm-${perm.id}`"
                          >
                            {{ perm.name }}
                            <span class="text-muted small">({{ perm.code }})</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-if="permissions.length === 0" class="text-muted small">
                    Nu există permisiuni definite încă.
                  </div>
                </div>
              </div>

              <div v-if="roleError" class="alert alert-danger mt-2 py-1">
                {{ roleError }}
              </div>

              <div class="mt-3 d-flex justify-content-between">
                <button
                  v-if="roleForm.id && !roleForm.is_system"
                  type="button"
                  class="btn btn-outline-danger btn-sm"
                  @click="deleteCurrentRole"
                >
                  Șterge rol
                </button>
                <span></span>
                <button
                  type="submit"
                  class="btn btn-primary btn-sm"
                  :disabled="roleLoading"
                >
                  <span
                    v-if="roleLoading"
                    class="spinner-border spinner-border-sm me-1"
                  ></span>
                  {{ roleForm.id ? 'Salvează rolul' : 'Creează rol' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB PERMISIUNI -->
    <div v-else class="card">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span class="small text-uppercase text-muted fw-semibold">
          Permisiuni
        </span>
        <button
          type="button"
          class="btn btn-primary btn-sm"
          @click="startNewPermission"
        >
          Adaugă permisiune
        </button>
      </div>
      <div class="card-body small">
        <div class="row g-3">
          <div class="col-lg-7">
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Nume</th>
                    <th>Cod</th>
                    <th>Modul</th>
                    <th style="width: 60px;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="perm in permissions"
                    :key="perm.id"
                    :class="{ 'table-active': perm.id === permissionForm.id }"
                  >
                    <td>
                      <div class="fw-semibold">{{ perm.name }}</div>
                      <div class="text-muted">{{ perm.description }}</div>
                    </td>
                    <td>{{ perm.code }}</td>
                    <td>{{ perm.module || '—' }}</td>
                    <td class="text-end">
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        @click="editPermission(perm)"
                      >
                        Editează
                      </button>
                    </td>
                  </tr>
                  <tr v-if="permissions.length === 0">
                    <td colspan="4" class="text-center text-muted py-3">
                      Nu există permisiuni definite.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="border rounded p-3">
              <h6 class="mb-2">
                {{ permissionForm.id ? 'Editează permisiune' : 'Permisiune nouă' }}
              </h6>
              <form @submit.prevent="submitPermission">
                <div class="mb-2">
                  <label class="form-label form-label-sm">Nume</label>
                  <input
                    v-model="permissionForm.name"
                    type="text"
                    class="form-control form-control-sm"
                    required
                  >
                </div>
                <div class="mb-2">
                  <label class="form-label form-label-sm">Cod</label>
                  <input
                    v-model="permissionForm.code"
                    type="text"
                    class="form-control form-control-sm"
                    required
                  >
                </div>
                <div class="mb-2">
                  <label class="form-label form-label-sm">Modul</label>
                  <input
                    v-model="permissionForm.module"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="ex: products / orders / customers"
                  >
                </div>
                <div class="mb-2">
                  <label class="form-label form-label-sm">Descriere</label>
                  <input
                    v-model="permissionForm.description"
                    type="text"
                    class="form-control form-control-sm"
                  >
                </div>

                <div v-if="permissionError" class="alert alert-danger py-1 mb-2">
                  {{ permissionError }}
                </div>

                <div class="d-flex justify-content-between mt-2">
                  <button
                    v-if="permissionForm.id"
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    @click="deleteCurrentPermission"
                  >
                    Șterge
                  </button>
                  <span></span>
                  <button
                    type="submit"
                    class="btn btn-primary btn-sm"
                    :disabled="permissionLoading"
                  >
                    <span
                      v-if="permissionLoading"
                      class="spinner-border spinner-border-sm me-1"
                    ></span>
                    {{ permissionForm.id ? 'Salvează' : 'Creează' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { fetchRoles, createRole, updateRole, deleteRole } from '@/services/admin/roles';
import {
  fetchPermissions,
  createPermission,
  updatePermission,
  deletePermission,
} from '@/services/admin/permissions';

const activeTab = ref('roles');

// ROLES
const roles = ref([]);
const rolesLoading = ref(false);
const rolesError = ref('');

const roleForm = reactive({
  id: null,
  slug: '',
  name: '',
  code: '',
  description: '',
  is_system: false,
  permission_ids: [],
});
const roleLoading = ref(false);
const roleError = ref('');

// PERMISSIONS
const permissions = ref([]);
const permissionLoading = ref(false);
const permissionError = ref('');

const permissionForm = reactive({
  id: null,
  name: '',
  code: '',
  module: '',
  description: '',
});

const loadRoles = async () => {
  rolesLoading.value = true;
  rolesError.value = '';
  try {
    roles.value = await fetchRoles();
  } catch (e) {
    console.error('loadRoles error', e);
    rolesError.value = 'Nu s-au putut încărca rolurile.';
  } finally {
    rolesLoading.value = false;
  }
};

const loadPermissions = async () => {
  try {
    permissions.value = await fetchPermissions();
  } catch (e) {
    console.error('loadPermissions error', e);
  }
};

const permissionsByModule = computed(() => {
  const groups = {};
  permissions.value.forEach((p) => {
    const key = p.module || '';
    if (!groups[key]) groups[key] = [];
    groups[key].push(p);
  });
  return groups;
});

const resetRoleForm = () => {
  roleForm.id = null;
  roleForm.slug = '';
  roleForm.name = '';
  roleForm.code = '';
  roleForm.description = '';
  roleForm.is_system = false;
  roleForm.permission_ids = [];
  roleError.value = '';
};

const selectRole = (role) => {
  roleForm.id = role.id;
  roleForm.slug = role.slug;
  roleForm.name = role.name;
  roleForm.code = role.code;
  roleForm.description = role.description;
  roleForm.is_system = !!role.is_system;
  roleForm.permission_ids = (role.permissions || []).map(p => p.id);
  roleError.value = '';
};

const submitRole = async () => {
  roleLoading.value = true;
  roleError.value = '';

  const payload = {
    slug: roleForm.slug,
    name: roleForm.name,
    code: roleForm.code,
    description: roleForm.description || null,
    permission_ids: roleForm.permission_ids,
  };

  try {
    if (roleForm.id) {
      await updateRole(roleForm.id, payload);
    } else {
      await createRole(payload);
    }
    await loadRoles();
    resetRoleForm();
  } catch (e) {
    console.error('submitRole error', e);
    roleError.value = e.response?.data?.message || 'Eroare la salvarea rolului.';
  } finally {
    roleLoading.value = false;
  }
};

const deleteCurrentRole = async () => {
  if (!roleForm.id) return;
  if (!confirm('Sigur vrei să ștergi acest rol?')) return;

  try {
    await deleteRole(roleForm.id);
    await loadRoles();
    resetRoleForm();
  } catch (e) {
    console.error('deleteRole error', e);
    roleError.value =
      e.response?.data?.message || 'Rolul nu a putut fi șters (probabil are utilizatori sau este de sistem).';
  }
};

// PERMISSIONS TAB LOGIC
const startNewPermission = () => {
  permissionForm.id = null;
  permissionForm.name = '';
  permissionForm.code = '';
  permissionForm.module = '';
  permissionForm.description = '';
  permissionError.value = '';
};

const editPermission = (perm) => {
  permissionForm.id = perm.id;
  permissionForm.name = perm.name;
  permissionForm.code = perm.code;
  permissionForm.module = perm.module || '';
  permissionForm.description = perm.description || '';
  permissionError.value = '';
};

const submitPermission = async () => {
  permissionLoading.value = true;
  permissionError.value = '';

  const payload = {
    name: permissionForm.name,
    code: permissionForm.code,
    module: permissionForm.module || null,
    description: permissionForm.description || null,
  };

  try {
    if (permissionForm.id) {
      await updatePermission(permissionForm.id, payload);
    } else {
      await createPermission(payload);
    }
    await loadPermissions();
    startNewPermission();
  } catch (e) {
    console.error('submitPermission error', e);
    permissionError.value =
      e.response?.data?.message || 'Eroare la salvarea permisiunii.';
  } finally {
    permissionLoading.value = false;
  }
};

const deleteCurrentPermission = async () => {
  if (!permissionForm.id) return;
  if (!confirm('Sigur vrei să ștergi această permisiune?')) return;

  try {
    await deletePermission(permissionForm.id);
    await loadPermissions();
    startNewPermission();
  } catch (e) {
    console.error('deletePermission error', e);
    permissionError.value =
      e.response?.data?.message || 'Permisiunea nu a putut fi ștearsă (probabil este asociată unor roluri).';
  }
};

onMounted(async () => {
  await Promise.all([loadPermissions(), loadRoles()]);
});
</script>
