<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1 text-gray-800">Cereri Parteneri</h1>
        <p class="text-muted small mb-0">Gestionează cererile de înregistrare ca partener.</p>
      </div>
      <button
        class="btn btn-outline-primary shadow-sm"
        type="button"
        @click="loadRequests"
      >
        <i class="bi bi-arrow-clockwise me-1"></i> Reîncarcă
      </button>
    </div>

    <div v-if="error" class="alert alert-danger shadow-sm border-0 mb-4">
      <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ error }}
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <div v-else-if="!requests.length" class="text-center py-5">
      <div class="mb-3">
        <i class="bi bi-inbox text-muted opacity-25" style="font-size: 3rem;"></i>
      </div>
      <h5 class="text-muted">Nu există cereri</h5>
      <p class="text-muted small">Momentan nu sunt cereri de parteneriat în așteptare.</p>
    </div>

    <div v-else class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
      <div v-for="r in requests" :key="r.id" class="col">
        <div class="card h-100 border shadow-sm hover-shadow transition-all">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
             <div class="d-flex align-items-center overflow-hidden">
                <div class="avatar-circle me-3 bg-light text-primary fw-bold d-flex align-items-center justify-content-center rounded-circle border" style="width: 40px; height: 40px; min-width: 40px;">
                  <i class="bi bi-building"></i>
                </div>
                <div class="text-truncate">
                  <h6 class="fw-bold mb-0 text-dark text-truncate">{{ r.company_name || r.firm_name }}</h6>
                  <div class="small text-muted">CUI: {{ r.cui || '—' }}</div>
                </div>
             </div>
             <span class="badge rounded-pill ms-2" 
                :class="{
                  'bg-success bg-opacity-10 text-success': r.status === 'approved',
                  'bg-danger bg-opacity-10 text-danger': r.status === 'rejected',
                  'bg-warning bg-opacity-10 text-warning': !r.status || r.status === 'new'
                }">
                {{ r.status === 'approved' ? 'Acceptat' : (r.status === 'rejected' ? 'Respins' : 'Nou') }}
             </span>
          </div>
          
          <div class="card-body">
             <div class="mb-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">CONTACT</small>
                <div class="d-flex align-items-center mt-1">
                   <i class="bi bi-person me-2 text-muted"></i>
                   <span class="fw-semibold text-dark">{{ r.contact_name || '—' }}</span>
                </div>
                <div class="d-flex align-items-center mt-1 text-muted small" v-if="r.contact_email">
                   <i class="bi bi-envelope me-2"></i>
                   <a :href="'mailto:' + r.contact_email" class="text-decoration-none text-muted">{{ r.contact_email }}</a>
                </div>
             </div>

             <div class="row g-2">
                <div class="col-6">
                   <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">REGIUNE</small>
                   <div class="d-flex align-items-center mt-1 text-dark small">
                      <i class="bi bi-geo-alt me-2 text-muted"></i>
                      {{ r.region || r.county || '—' }}
                   </div>
                </div>
                <div class="col-6">
                   <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">DATA</small>
                   <div class="d-flex align-items-center mt-1 text-dark small">
                      <i class="bi bi-calendar3 me-2 text-muted"></i>
                      {{ formatDate(r.created_at).split(',')[0] }}
                   </div>
                </div>
             </div>
          </div>

          <div class="card-footer bg-white py-3 d-flex justify-content-end gap-2" v-if="!r.status || r.status === 'new'">
             <button
                type="button"
                class="btn btn-sm btn-outline-danger flex-grow-1"
                @click="changeStatus(r, 'rejected')"
             >
                <i class="bi bi-x-lg me-1"></i> Respinge
             </button>
             <button
                type="button"
                class="btn btn-sm btn-success text-white flex-grow-1"
                @click="changeStatus(r, 'approved')"
             >
                <i class="bi bi-check-lg me-1"></i> Acceptă
             </button>
          </div>
           <div class="card-footer bg-white py-2 text-center text-muted small" v-else>
              Cerere procesată
           </div>
        </div>
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
