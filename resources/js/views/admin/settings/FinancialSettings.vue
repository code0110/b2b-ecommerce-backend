<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0 text-gray-800">Setări Risc Financiar</h1>
        <p class="text-muted small mb-0">Gestionează pragurile de alertă și blocare pentru situația financiară a clienților.</p>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else class="row">
      <div class="col-lg-8">
        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Configurare Praguri</h6>
          </div>
          <div class="card-body">
            <form @submit.prevent="saveSettings">
              
              <h5 class="mb-3 border-bottom pb-2">Facturi Restante (Număr)</h5>
              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label fw-bold text-warning">Prag Avertisment</label>
                  <input type="number" v-model.number="settings.warning_threshold_invoices" class="form-control" min="0">
                  <small class="text-muted d-block mt-1">Se trimit notificări, dar se permit comenzi.</small>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold text-orange">Prag Aprobare</label>
                  <input type="number" v-model.number="settings.approval_threshold_invoices" class="form-control" min="0">
                  <small class="text-muted d-block mt-1">Necesită aprobare/derogare de la director.</small>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold text-danger">Prag Blocare</label>
                  <input type="number" v-model.number="settings.block_threshold_invoices" class="form-control" min="0">
                  <small class="text-muted d-block mt-1">Blochează complet comenzile.</small>
                </div>
              </div>

              <h5 class="mb-3 border-bottom pb-2 mt-4">Zile Depășire Scadență</h5>
              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label fw-bold text-warning">Prag Avertisment</label>
                  <div class="input-group">
                    <input type="number" v-model.number="settings.warning_threshold_days" class="form-control" min="0">
                    <span class="input-group-text">zile</span>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold text-orange">Prag Aprobare</label>
                  <div class="input-group">
                    <input type="number" v-model.number="settings.approval_threshold_days" class="form-control" min="0">
                    <span class="input-group-text">zile</span>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold text-danger">Prag Blocare</label>
                  <div class="input-group">
                    <input type="number" v-model.number="settings.block_threshold_days" class="form-control" min="0">
                    <span class="input-group-text">zile</span>
                  </div>
                </div>
              </div>

              <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  Salvează Configurația
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4">
        <div class="card shadow mb-4">
          <div class="card-header py-3 bg-light">
            <h6 class="m-0 font-weight-bold text-dark">Legendă Statusuri</h6>
          </div>
          <div class="card-body">
            <div class="d-flex align-items-start mb-3">
              <div class="badge bg-success me-2 p-2">Safe</div>
              <div>
                <small>Clientul nu a depășit niciun prag. Comenzile sunt procesate normal.</small>
              </div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="badge bg-warning text-dark me-2 p-2">Warning</div>
              <div>
                <small>Clientul a depășit pragul de avertisment. Sistemul afișează alerte, dar permite plasarea comenzilor.</small>
              </div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <div class="badge bg-orange text-white me-2 p-2" style="background-color: #fd7e14;">Approval</div>
              <div>
                <small>Clientul a depășit pragul critic. Agenții NU pot plasa comenzi fără aprobarea unui director.</small>
              </div>
            </div>
            <div class="d-flex align-items-start">
              <div class="badge bg-danger me-2 p-2">Blocked</div>
              <div>
                <small>Clientul este blocat complet. Nicio comandă nu poate fi procesată până la achitarea datoriilor.</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { adminApi } from '@/services/http';
import { useToast } from 'vue-toastification';

const toast = useToast();
const loading = ref(true);
const saving = ref(false);
const settings = ref({
  warning_threshold_invoices: 1,
  approval_threshold_invoices: 3,
  block_threshold_invoices: 5,
  warning_threshold_days: 7,
  approval_threshold_days: 15,
  block_threshold_days: 30,
});

const fetchSettings = async () => {
  loading.value = true;
  try {
    const response = await adminApi.get('/financial-risk-settings');
    settings.value = response.data;
  } catch (error) {
    console.error('Error fetching settings:', error);
    toast.error('Nu s-au putut încărca setările.');
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    const response = await adminApi.put('/financial-risk-settings', settings.value);
    toast.success(response.data.message);
    settings.value = response.data.settings;
  } catch (error) {
    console.error('Error saving settings:', error);
    toast.error('Eroare la salvarea setărilor.');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchSettings();
});
</script>

<style scoped>
.text-orange {
  color: #fd7e14;
}
</style>
