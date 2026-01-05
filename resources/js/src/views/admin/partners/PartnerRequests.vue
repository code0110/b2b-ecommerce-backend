<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h5 class="mb-0">Solicitări &quot;Devino partener&quot;</h5>
        <small class="text-muted">
          Aici sunt centralizate cererile de parteneriat trimise din front-office. Pot fi alocate
          automat sau manual pe agenți și transformate ulterior în conturi B2B.
        </small>
      </div>
    </div>

    <div class="row g-3">
      <!-- Lista solicitări -->
      <div class="col-lg-7">
        <div class="card shadow-sm">
          <div class="card-body small">
            <div class="row g-2 mb-2">
              <div class="col-md-3">
                <label class="form-label text-muted">Status</label>
                <select
                  v-model="filters.status"
                  class="form-select form-select-sm"
                >
                  <option value="">Toate</option>
                  <option value="pending">În așteptare</option>
                  <option value="approved">Aprobat</option>
                  <option value="rejected">Respins</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted">Regiune</label>
                <select
                  v-model="filters.region"
                  class="form-select form-select-sm"
                >
                  <option value="">Toate</option>
                  <option
                    v-for="reg in distinctRegions"
                    :key="reg"
                    :value="reg"
                  >
                    {{ reg }}
                  </option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted">Agent alocat</label>
                <select
                  v-model="filters.assignedAgent"
                  class="form-select form-select-sm"
                >
                  <option value="">Toți</option>
                  <option value="__unassigned">Nealocate</option>
                  <option
                    v-for="agent in distinctAgents"
                    :key="agent"
                    :value="agent"
                  >
                    {{ agent }}
                  </option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted">Căutare</label>
                <input
                  v-model="filters.search"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Denumire firmă / CUI / email"
                />
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Firmă</th>
                    <th>Regiune</th>
                    <th>Status</th>
                    <th>Agent</th>
                    <th>Creat la</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredRequests.length === 0">
                    <td colspan="5" class="text-center text-muted py-4">
                      Nu există solicitări pentru filtrele selectate.
                    </td>
                  </tr>
                  <tr
                    v-for="req in filteredRequests"
                    :key="req.id"
                    :class="{ 'table-active': selectedRequest && selectedRequest.id === req.id }"
                    style="cursor: pointer;"
                    @click="selectRequest(req)"
                  >
                    <td class="small">
                      <div class="fw-semibold">{{ req.companyName }}</div>
                      <div class="text-muted">
                        <small>{{ req.cui }}</small>
                      </div>
                    </td>
                    <td class="small">
                      {{ req.region || '-' }}
                    </td>
                    <td class="small">
                      <span :class="['badge', statusBadgeClass(req.status)]">
                        {{ statusLabel(req.status) }}
                      </span>
                    </td>
                    <td class="small">
                      <span v-if="req.assignedAgent">{{ req.assignedAgent }}</span>
                      <span v-else class="text-muted">Nealocat</span>
                    </td>
                    <td class="small">
                      {{ formatDateTime(req.createdAt) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p class="small text-muted mt-2 mb-0">
              În implementarea reală, acest ecran poate fi integrat cu un modul CRM / ERP pentru a
              transforma rapid solicitările în conturi B2B, împreună cu condiții comerciale
              predefinite.
            </p>
          </div>
        </div>
      </div>

      <!-- Detalii solicitare -->
      <div class="col-lg-5">
        <div v-if="selectedRequest" class="card shadow-sm mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>{{ selectedRequest.companyName }}</strong>
            <span :class="['badge', statusBadgeClass(selectedRequest.status)]">
              {{ statusLabel(selectedRequest.status) }}
            </span>
          </div>
          <div class="card-body small">
            <div class="mb-2">
              <h6 class="mb-1">Date firmă</h6>
              <div class="text-muted">
                CUI: {{ selectedRequest.cui || '-' }}<br />
                Nr. Reg. Com: {{ selectedRequest.regCom || '-' }}<br />
                IBAN: {{ selectedRequest.iban || '-' }}
              </div>
            </div>
            <div class="mb-2">
              <h6 class="mb-1">Persoană de contact</h6>
              <div class="text-muted">
                {{ selectedRequest.contactPerson || '-' }}<br />
                <a
                  v-if="selectedRequest.email"
                  :href="'mailto:' + selectedRequest.email"
                >
                  {{ selectedRequest.email }}
                </a>
                <span v-else>-</span><br />
                <span>{{ selectedRequest.phone || '-' }}</span>
              </div>
            </div>
            <div class="mb-2">
              <h6 class="mb-1">Informații suplimentare</h6>
              <div class="text-muted">
                Regiune: {{ selectedRequest.region || '-' }}<br />
                Tip activitate: {{ selectedRequest.activityType || '-' }}<br />
              </div>
              <div v-if="selectedRequest.notes" class="mt-1">
                <strong>Mesaj:</strong>
                <div class="border rounded p-2 mt-1 bg-light">
                  {{ selectedRequest.notes }}
                </div>
              </div>
            </div>

            <div class="mb-2">
              <h6 class="mb-1">Alocare agent</h6>
              <div class="row g-2 align-items-center">
                <div class="col-7">
                  <select
                    v-model="agentSelectionId"
                    class="form-select form-select-sm"
                  >
                    <option :value="null">Nealocat</option>
                    <option
                      v-for="rep in agents"
                      :key="rep.id"
                      :value="rep.id"
                    >
                      {{ getAgentLabel(rep) }}
                    </option>
                  </select>
                </div>
                <div class="col-5 text-end">
                  <button
                    type="button"
                    class="btn btn-outline-primary btn-sm"
                    @click="assignAgentToRequest"
                  >
                    Salvează alocarea
                  </button>
                </div>
              </div>
              <p class="small text-muted mt-1 mb-0">
                În implementarea completă, alocarea poate fi automată în funcție de regiune, tip
                client și capacitatea agentului.
              </p>
            </div>
          </div>
          <div class="card-footer small d-flex justify-content-between">
            <div>
              <button
                type="button"
                class="btn btn-outline-success btn-sm me-1"
                @click="updateStatus('approved')"
              >
                Acceptă ca partener
              </button>
              <button
                type="button"
                class="btn btn-outline-danger btn-sm"
                @click="updateStatus('rejected')"
              >
                Respinge
              </button>
            </div>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="selectedRequest = null"
            >
              Închide detalii
            </button>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body small text-muted">
            <h6>Flux recomandat</h6>
            <ol class="mb-0">
              <li>Solicitarea intră în status &bdquo;În așteptare&rdquo;.</li>
              <li>Se alocă automat / manual pe un agent responsabil.</li>
              <li>Agentul contactează potențialul client și evaluează oportunitatea.</li>
              <li>Dacă se aprobă, se generează un cont B2B cu condiții comerciale dedicate.</li>
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
import { computed, reactive, ref, onMounted } from 'vue'
import { fetchPartnerRequests, updatePartnerRequestStatus } from '@/services/admin/partners'
import { fetchAgents } from '@/services/admin/receiptBooks'

const filters = reactive({
  status: '',
  region: '',
  assignedAgent: '',
  search: ''
})

const selectedRequest = ref(null)
const agentSelectionId = ref(null)
const infoMessage = ref('')

const requests = ref([])
const agents = ref([])

const getAgentLabel = (agent) => {
  const fn = [agent.first_name, agent.last_name].filter(Boolean).join(' ')
  return fn || agent.email || `User #${agent.id}`
}

const normalizeRequest = (r) => {
  return {
    id: r.id,
    companyName: r.company_name,
    cui: r.cif,
    regCom: r.reg_com,
    iban: r.iban,
    contactPerson: r.contact_name,
    email: r.email,
    phone: r.phone,
    region: r.region,
    activityType: r.activity_type,
    notes: r.notes,
    status: r.status || 'pending',
    assignedAgent: r.assigned_agent
      ? [r.assigned_agent.first_name, r.assigned_agent.last_name].filter(Boolean).join(' ')
      : null,
    assignedAgentId: r.assigned_agent_id ?? null,
    createdAt: r.created_at
  }
}

const loadData = async () => {
  try {
    const [reqRes, agentsRes] = await Promise.all([
      fetchPartnerRequests(),
      fetchAgents()
    ])
    const rawList = reqRes?.data ?? reqRes
    requests.value = (rawList?.data ?? rawList ?? []).map(normalizeRequest)
    agents.value = agentsRes ?? []
  } catch (e) {
    console.error(e)
    infoMessage.value = 'Eroare la încărcarea solicitărilor sau a agenților.'
  }
}

const distinctRegions = computed(() => {
  const regs = requests.value
    .map((r) => r.region)
    .filter((r) => !!r)
  return Array.from(new Set(regs)).sort()
})

const distinctAgents = computed(() => {
  const agentNames = requests.value
    .map((r) => r.assignedAgent)
    .filter((a) => !!a)
  return Array.from(new Set(agentNames)).sort()
})

const filteredRequests = computed(() => {
  return requests.value.filter((r) => {
    if (filters.status && r.status !== filters.status) return false
    if (filters.region && r.region !== filters.region) return false
    if (filters.assignedAgent) {
      if (filters.assignedAgent === '__unassigned') {
        if (r.assignedAgent) return false
      } else if (r.assignedAgent !== filters.assignedAgent) {
        return false
      }
    }
    if (filters.search) {
      const term = filters.search.toLowerCase()
      const haystack = [
        r.companyName,
        r.cui,
        r.email
      ]
        .join(' ')
        .toLowerCase()
      if (!haystack.includes(term)) return false
    }
    return true
  })
})

const statusLabel = (status) => {
  switch (status) {
    case 'pending':
      return 'În așteptare'
    case 'approved':
      return 'Aprobat'
    case 'rejected':
      return 'Respins'
    default:
      return status
  }
}

const statusBadgeClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-warning text-dark'
    case 'approved':
      return 'bg-success'
    case 'rejected':
      return 'bg-danger'
    default:
      return 'bg-secondary'
  }
}

const formatDateTime = (iso) => {
  if (!iso) return '-'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  return (
    d.toLocaleDateString('ro-RO', { year: 'numeric', month: '2-digit', day: '2-digit' }) +
    ' ' +
    d.toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit' })
  )
}

const selectRequest = (req) => {
  selectedRequest.value = req
  agentSelectionId.value = req.assignedAgentId || null
  infoMessage.value = ''
}

const assignAgentToRequest = () => {
  if (!selectedRequest.value) return
  const id = selectedRequest.value.id
  const payload = { assigned_agent_id: agentSelectionId.value || null }
  updatePartnerRequestStatus(id, payload)
    .then((updated) => {
      const idx = requests.value.findIndex((r) => r.id === id)
      if (idx !== -1) {
        requests.value[idx] = normalizeRequest(updated)
        // reflect in selection
        selectedRequest.value = requests.value[idx]
      }
      infoMessage.value = 'Alocarea agentului a fost salvată.'
    })
    .catch((e) => {
      console.error(e)
      infoMessage.value = 'Eroare la salvarea alocării agentului.'
    })
}

const updateStatus = (status) => {
  if (!selectedRequest.value) return
  const id = selectedRequest.value.id
  updatePartnerRequestStatus(id, { status })
    .then((updated) => {
      const idx = requests.value.findIndex((r) => r.id === id)
      if (idx !== -1) {
        requests.value[idx] = normalizeRequest(updated)
        selectedRequest.value = requests.value[idx]
      }
      infoMessage.value = 'Statusul solicitării a fost actualizat.'
    })
    .catch((e) => {
      console.error(e)
      infoMessage.value = 'Eroare la actualizarea statusului solicitării.'
    })
}

onMounted(loadData)
</script>
