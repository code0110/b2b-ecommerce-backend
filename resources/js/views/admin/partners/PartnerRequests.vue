<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Cereri „Devino partener”</h1>
      <button
        class="btn btn-sm btn-outline-secondary"
        type="button"
        @click="loadRequests"
      >
        Reîncarcă
      </button>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Companie</th>
              <th>Persoană contact</th>
              <th>Regiune</th>
              <th>Status</th>
              <th>Creat la</th>
              <th style="width: 180px;">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr v-if="!loading && !requests.length">
              <td colspan="6" class="text-center text-muted py-3">
                Nu există cereri sau endpoint-ul nu este încă implementat.
              </td>
            </tr>
            <tr
              v-for="r in requests"
              :key="r.id"
            >
              <td class="small">
                <div class="fw-semibold">{{ r.company_name || r.firm_name }}</div>
                <div class="text-muted">
                  CUI: {{ r.cui || '—' }}
                </div>
              </td>
              <td class="small">
                {{ r.contact_name || '—' }}<br>
                <span class="text-muted">{{ r.contact_email || '' }}</span>
              </td>
              <td class="small">
                {{ r.region || r.county || '—' }}
              </td>
              <td class="small">
                <span class="badge bg-light text-dark">
                  {{ r.status || 'new' }}
                </span>
              </td>
              <td class="small">
                {{ formatDate(r.created_at) }}
              </td>
              <td class="small">
                <div class="btn-group btn-group-sm">
                  <button
                    type="button"
                    class="btn btn-outline-success"
                    @click="changeStatus(r, 'approved')"
                  >
                    Acceptă
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-danger"
                    @click="changeStatus(r, 'rejected')"
                  >
                    Respinge
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  fetchPartnerRequests,
  updatePartnerRequestStatus
} from '@/services/admin/partners'

const requests = ref([])
const loading = ref(false)
const error = ref('')

const formatDate = (val) => {
  if (!val) return ''
  const d = new Date(val)
  return d.toLocaleString('ro-RO')
}

const loadRequests = async () => {
  loading.value = true
  error.value = ''
  try {
    const resp = await fetchPartnerRequests()
    requests.value = resp.data || resp || []
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca cererile (sau endpoint-ul nu există încă).'
  } finally {
    loading.value = false
  }
}

const changeStatus = async (req, status) => {
  if (!confirm(`Schimbi statusul cererii pentru "${req.company_name}" în "${status}"?`)) {
    return
  }
  try {
    await updatePartnerRequestStatus(req.id, { status })
    await loadRequests()
  } catch (e) {
    console.error(e)
    alert('Nu s-a putut actualiza statusul cererii.')
  }
}

onMounted(loadRequests)
</script>
