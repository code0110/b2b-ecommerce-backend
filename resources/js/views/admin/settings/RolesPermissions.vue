<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Roluri & permisiuni</h1>
      <button
        class="btn btn-sm btn-primary"
        type="button"
        @click="startCreateRole"
      >
        Adaugă rol
      </button>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div class="row g-3">
      <!-- Lista roluri -->
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-header py-2">
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-semibold small">Roluri definite</span>
              <span class="badge bg-light text-dark small">
                {{ roles.length }}
              </span>
            </div>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <li
                v-for="role in roles"
                :key="role.id"
                class="list-group-item d-flex justify-content-between align-items-center small"
                :class="{ 'bg-light': currentRole && currentRole.id === role.id }"
              >
                <div class="me-2">
                  <div class="fw-semibold">{{ role.name }}</div>
                  <div class="text-muted">slug: {{ role.slug }}</div>
                </div>
                <div class="btn-group btn-group-sm">
                  <button
                    class="btn btn-outline-secondary"
                    type="button"
                    @click="selectRole(role)"
                  >
                    Editează
                  </button>
                  <button
                    class="btn btn-outline-danger"
                    type="button"
                    @click="confirmDelete(role)"
                    :disabled="isProtected(role)"
                  >
                    Șterge
                  </button>
                </div>
              </li>

              <li v-if="!roles.length" class="list-group-item text-muted small">
                Nu există roluri definite.
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Detalii rol / form -->
      <div class="col-md-8">
        <div class="card">
          <div class="card-header py-2">
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-semibold small">
                {{ currentRole?.id ? 'Editează rol' : 'Rol nou' }}
              </span>
              <span v-if="saving" class="small text-muted">
                Salvare...
              </span>
            </div>
          </div>

          <div class="card-body">
            <form @submit.prevent="saveRole">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label form-label-sm">Nume rol</label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="form-control form-control-sm"
                    required
                  >
                </div>
                <div class="col-md-6">
                  <label class="form-label form-label-sm">
                    Slug (identificator intern)
                  </label>
                  <input
                    v-model="form.slug"
                    type="text"
                    class="form-control form-control-sm"
                    :readonly="isProtected(currentRole)"
                    required
                  >
                  <div class="form-text small">
                    Exemplu: <code>admin</code>, <code>customer_b2b</code>.
                  </div>
                </div>
              </div>

              <hr class="my-3">

              <!-- Permisiuni -->
              <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h2 class="h6 mb-0">Permisiuni pentru rol</h2>
                  <button
                    class="btn btn-sm btn-outline-secondary"
                    type="button"
                    @click="toggleAllPermissions"
                    v-if="permissions.length"
                  >
                    {{ allSelected ? 'Debifează tot' : 'Selectează tot' }}
                  </button>
                </div>

                <div class="row g-2">
                  <div
                    v-for="perm in permissions"
                    :key="perm.id"
                    class="col-md-4 col-sm-6"
                  >
                    <div class="form-check small">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        :id="`perm-${perm.id}`"
                        :value="perm.id"
                        v-model="form.permission_ids"
                      >
                      <label
                        class="form-check-label"
                        :for="`perm-${perm.id}`"
                      >
                        {{ perm.name }}
                      </label>
                      <div class="text-muted small">
                        <code>{{ perm.slug }}</code>
                      </div>
                    </div>
                  </div>

                  <div v-if="!permissions.length" class="col-12 small text-muted">
                    Nu există permisiuni definite în sistem.
                  </div>
                </div>
              </div>

              <div class="mt-3 d-flex justify-content-between">
                <div>
                  <button
                    class="btn btn-sm btn-primary me-2"
                    type="submit"
                    :disabled="saving"
                  >
                    Salvează rolul
                  </button>
                  <button
                    class="btn btn-sm btn-outline-secondary"
                    type="button"
                    @click="resetForm"
                    :disabled="saving"
                  >
                    Reset
                  </button>
                </div>

                <div class="small text-muted" v-if="currentRole?.id">
                  ID intern: {{ currentRole.id }}
                </div>
              </div>
            </form>
          </div>

          <div v-if="formError" class="card-footer py-2">
            <div class="text-danger small">
              {{ formError }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal confirmare ștergere simplu (dacă vrei, poți înlocui cu un modal complet Bootstrap) -->
    <div
      v-if="roleToDelete"
      class="modal-backdrop fade show"
    ></div>
    <div
      v-if="roleToDelete"
      class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
      style="z-index: 1050;"
    >
      <div class="card shadow" style="max-width: 400px; width: 100%;">
        <div class="card-header py-2">
          <strong class="small">Confirmare ștergere rol</strong>
        </div>
        <div class="card-body small">
          Ești sigur că vrei să ștergi rolul
          <strong>{{ roleToDelete.name }}</strong>?
        </div>
        <div class="card-footer d-flex justify-content-end gap-2 py-2">
          <button
            class="btn btn-sm btn-secondary"
            type="button"
            @click="roleToDelete = null"
          >
            Anulează
          </button>
          <button
            class="btn btn-sm btn-danger"
            type="button"
            @click="deleteRoleConfirmed"
          >
            Șterge
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  fetchRoles,
  createRole,
  updateRole,
  deleteRole
} from '@/services/admin/roles'
import { fetchPermissions } from '@/services/admin/permissions'

const roles = ref([])
const permissions = ref([])
const currentRole = ref(null)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const formError = ref('')

const form = ref({
  name: '',
  slug: '',
  permission_ids: []
})

const roleToDelete = ref(null)

const protectedSlugs = ['admin', 'customer_b2c', 'customer_b2b']

const allSelected = computed(() => {
  if (!permissions.value.length) return false
  return (
    form.value.permission_ids.length === permissions.value.length
  )
})

const isProtected = (role) => {
  if (!role) return false
  return protectedSlugs.includes(role.slug)
}

const resetForm = () => {
  currentRole.value = null
  form.value = {
    name: '',
    slug: '',
    permission_ids: []
  }
  formError.value = ''
}

const selectRole = (role) => {
  currentRole.value = role
  form.value = {
    name: role.name,
    slug: role.slug,
    permission_ids: (role.permissions || role.permission_ids || []).map(
      (p) => (typeof p === 'object' ? p.id : p)
    )
  }
  formError.value = ''
}

const startCreateRole = () => {
  resetForm()
}

const toggleAllPermissions = () => {
  if (allSelected.value) {
    form.value.permission_ids = []
  } else {
    form.value.permission_ids = permissions.value.map((p) => p.id)
  }
}

const loadData = async () => {
  loading.value = true
  error.value = ''

  try {
    const [rolesResp, permsResp] = await Promise.all([
      fetchRoles(),
      fetchPermissions()
    ])

    // Tolerant la structuri paginate / simple
    roles.value = rolesResp.data || rolesResp || []
    permissions.value = permsResp.data || permsResp || []
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca rolurile și permisiunile.'
  } finally {
    loading.value = false
  }
}

const saveRole = async () => {
  formError.value = ''
  saving.value = true

  try {
    const payload = {
      name: form.value.name,
      slug: form.value.slug,
      permissions: form.value.permission_ids
    }

    if (currentRole.value?.id) {
      await updateRole(currentRole.value.id, payload)
    } else {
      await createRole(payload)
    }

    await loadData()

    // realimentezi currentRole după save
    const updated = roles.value.find((r) => r.slug === form.value.slug)
    if (updated) {
      selectRole(updated)
    } else {
      resetForm()
    }
  } catch (e) {
    console.error(e)
    formError.value =
      e?.response?.data?.message ||
      'A apărut o eroare la salvarea rolului.'
  } finally {
    saving.value = false
  }
}

const confirmDelete = (role) => {
  if (isProtected(role)) return
  roleToDelete.value = role
}

const deleteRoleConfirmed = async () => {
  if (!roleToDelete.value) return
  try {
    await deleteRole(roleToDelete.value.id)
    roleToDelete.value = null
    // dacă ștergi rolul curent, resetezi formularul
    if (currentRole.value?.id === roleToDelete.value?.id) {
      resetForm()
    }
    await loadData()
  } catch (e) {
    console.error(e)
    alert('Nu s-a putut șterge rolul.')
  }
}

onMounted(loadData)
</script>
