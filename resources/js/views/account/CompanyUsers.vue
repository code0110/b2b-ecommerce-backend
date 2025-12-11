<template>
  <div class="container">
    <PageHeader
      title="Utilizatori companie"
      subtitle="Administrează utilizatorii asociați contului tău B2B și rolurile acestora."
    />

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Utilizatori activi</strong>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="showInvite = !showInvite"
            >
              + Invită utilizator
            </button>
          </div>
          <div class="card-body small">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Nume</th>
                    <th>Email</th>
                    <th>Rol intern</th>
                    <th class="text-center">Trebuie aprobare</th>
                    <th>Status</th>
                    <th>Invitat / activat</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="companyUsers.length === 0">
                    <td colspan="6" class="text-center text-muted py-4">
                      Nu există utilizatori definiți pentru această companie.
                    </td>
                  </tr>
                  <tr
                    v-for="user in companyUsers"
                    :key="user.id"
                  >
                    <td class="small">{{ user.name }}</td>
                    <td class="small">
                      <a :href="'mailto:' + user.email">
                        {{ user.email }}
                      </a>
                    </td>
                    <td class="small">
                      {{ roleLabel(user.role) }}
                    </td>
                    <td class="text-center">
                      <span
                        v-if="user.mustApprove"
                        class="badge bg-warning text-dark"
                      >
                        Da
                      </span>
                      <span
                        v-else
                        class="badge bg-success"
                      >
                        Nu
                      </span>
                    </td>
                    <td class="small">
                      <span
                        :class="['badge', statusBadgeClass(user.status)]"
                      >
                        {{ statusLabel(user.status) }}
                      </span>
                    </td>
                    <td class="small">
                      <div>
                        Invitat: {{ formatDate(user.invitedAt) }}
                      </div>
                      <div v-if="user.activatedAt">
                        Activat: {{ formatDate(user.activatedAt) }}
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p class="small text-muted mt-2 mb-0">
              Rolurile interne (administrator, cumpărător, aprobator) pot controla cine poate plasa
              comenzi și cine trebuie să le aprobe înainte de trimitere către furnizor.
            </p>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div
          v-if="showInvite"
          class="card shadow-sm mb-3"
        >
          <div class="card-header py-2">
            <strong>Invită utilizator nou</strong>
          </div>
          <div class="card-body small">
            <form @submit.prevent="onInvite">
              <div class="mb-2">
                <label class="form-label text-muted">Nume</label>
                <input
                  v-model="inviteForm.name"
                  type="text"
                  class="form-control form-control-sm"
                  required
                />
              </div>
              <div class="mb-2">
                <label class="form-label text-muted">Email</label>
                <input
                  v-model="inviteForm.email"
                  type="email"
                  class="form-control form-control-sm"
                  required
                />
              </div>
              <div class="mb-2">
                <label class="form-label text-muted">Rol intern</label>
                <select
                  v-model="inviteForm.role"
                  class="form-select form-select-sm"
                >
                  <option value="buyer">Poate plasa comenzi</option>
                  <option value="approver">Trebuie să aprobe comenzi</option>
                  <option value="admin">Administrator cont</option>
                </select>
              </div>
              <div class="form-check mb-2">
                <input
                  id="mustApprove"
                  v-model="inviteForm.mustApprove"
                  class="form-check-input"
                  type="checkbox"
                />
                <label class="form-check-label small" for="mustApprove">
                  Comenzile acestui utilizator trebuie aprobate de un administrator / director.
                </label>
              </div>
              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary btn-sm">
                  Trimite invitație
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body small text-muted">
            <h6>Flux recomandat multi-user B2B</h6>
            <ol class="mb-0">
              <li>Administratorul contului invită utilizatorii interni.</li>
              <li>Utilizatorii primesc un e-mail cu link de activare.</li>
              <li>În funcție de rol, pot plasa comenzi sau trebuie să le trimită spre aprobare.</li>
              <li>Istoricul acțiunilor utilizatorilor se regăsește în audit log și fișa clientului.</li>
            </ol>
          </div>
        </div>

        <p v-if="infoMessage" class="small text-muted mt-2 mb-0">
          {{ infoMessage }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import { useAccountProfileStore } from '@/store/accountProfile'

const store = useAccountProfileStore()

const companyUsers = computed(() => store.companyUsers)
const showInvite = ref(false)
const infoMessage = ref('')

const inviteForm = reactive({
  name: '',
  email: '',
  role: 'buyer',
  mustApprove: false
})

const roleLabel = (role) => {
  switch (role) {
    case 'admin':
      return 'Administrator cont'
    case 'approver':
      return 'Aprobator comenzi'
    case 'buyer':
    default:
      return 'Cumpărător'
  }
}

const statusLabel = (status) => {
  switch (status) {
    case 'active':
      return 'Activ'
    case 'pending':
      return 'În așteptare'
    case 'disabled':
      return 'Dezactivat'
    default:
      return status
  }
}

const statusBadgeClass = (status) => {
  switch (status) {
    case 'active':
      return 'bg-success'
    case 'pending':
      return 'bg-warning text-dark'
    case 'disabled':
      return 'bg-secondary'
    default:
      return 'bg-light text-dark'
  }
}

const formatDate = (iso) => {
  if (!iso) return '-'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleDateString('ro-RO', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

const onInvite = () => {
  if (!inviteForm.name || !inviteForm.email) {
    infoMessage.value = 'Completează numele și e-mailul pentru a invita un utilizator nou.'
    return
  }
  const user = store.inviteUser({ ...inviteForm })
  infoMessage.value =
    'Template: invitația pentru ' +
    user.email +
    ' a fost generată. În implementarea reală se va trimite un e-mail cu link de activare și se va înregistra statusul în backend.'
  // resetare parțială formular
  inviteForm.name = ''
  inviteForm.email = ''
  inviteForm.mustApprove = false
}
</script>
