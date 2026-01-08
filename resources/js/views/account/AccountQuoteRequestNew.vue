<template>
  <div class="account-quote-new">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <div>
        <h1 class="h3 mb-1 fw-bold">Cerere de Ofertă Nouă</h1>
        <p class="text-muted mb-0">
          Completează formularul pentru a primi o ofertă personalizată.
        </p>
      </div>
      <router-link :to="{ name: 'account-quote-requests' }" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i>
        <span>Înapoi la listă</span>
      </router-link>
    </div>

    <!-- Process Steps (Horizontal) -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4">
        <div class="row text-center g-4">
          <div class="col-md-4 position-relative">
            <div class="d-flex flex-column align-items-center position-relative z-1">
              <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 48px; height: 48px;">
                <i class="bi bi-pencil-square fs-5"></i>
              </div>
              <h6 class="fw-bold mb-1">1. Trimite Cererea</h6>
              <p class="small text-muted mb-0">Specifică produsele și cantitățile</p>
            </div>
            <!-- Connector Line (Desktop) -->
            <div class="d-none d-md-block position-absolute top-0 start-50 w-100 translate-middle-y" style="height: 2px; background-color: #e9ecef; top: 24px;"></div>
          </div>
          <div class="col-md-4 position-relative">
            <div class="d-flex flex-column align-items-center position-relative z-1">
              <div class="rounded-circle bg-light text-primary d-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                <i class="bi bi-person-gear fs-5"></i>
              </div>
              <h6 class="fw-bold mb-1">2. Analiză Agent</h6>
              <p class="small text-muted mb-0">Verificare stoc și prețuri</p>
            </div>
            <!-- Connector Line (Desktop) -->
            <div class="d-none d-md-block position-absolute top-0 start-50 w-100 translate-middle-y" style="height: 2px; background-color: #e9ecef; top: 24px;"></div>
          </div>
          <div class="col-md-4 position-relative">
            <div class="d-flex flex-column align-items-center position-relative z-1">
              <div class="rounded-circle bg-light text-primary d-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                <i class="bi bi-envelope-check fs-5"></i>
              </div>
              <h6 class="fw-bold mb-1">3. Primire Ofertă</h6>
              <p class="small text-muted mb-0">Ofertă finală pe email și în cont</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Form -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent py-3 border-bottom">
        <h5 class="mb-0 fw-semibold">Detalii Solicitare</h5>
      </div>
      <div class="card-body p-4">
        <form @submit.prevent="submitRequest" id="quoteForm">
          
          <!-- Alert Info -->
          <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis mb-4 d-flex align-items-start gap-3">
            <i class="bi bi-info-circle-fill fs-5 mt-1"></i>
            <div>
              <strong>Ce să incluzi?</strong>
              <p class="mb-0 small opacity-75">
                Pentru o ofertă cât mai precisă, te rugăm să menționezi:
                <ul class="mb-0 ps-3 mt-1">
                  <li>Codul produselor (dacă este cunoscut)</li>
                  <li>Cantitatea dorită pentru fiecare produs</li>
                  <li>Eventuale preferințe de livrare sau termene limită</li>
                </ul>
              </p>
            </div>
          </div>

          <div class="mb-4">
            <label for="requestNotes" class="form-label fw-semibold">Mesaj / Listă produse <span class="text-danger">*</span></label>
            <textarea 
              id="requestNotes"
              v-model="form.notes" 
              class="form-control" 
              rows="8"
              placeholder="Exemplu:
1. Produs X (Cod: 1234) - 50 buc
2. Produs Y - 100 buc
..."
              required
              minlength="10"
            ></textarea>
            <div class="form-text d-flex justify-content-between mt-1">
              <span>Minim 10 caractere.</span>
              <span :class="{'text-success': form.notes.length >= 10}">
                {{ form.notes.length }} caractere
              </span>
            </div>
          </div>

          <!-- File Upload Placeholder -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Atașamente (opțional)</label>
            <div class="border rounded p-4 text-center bg-light border-dashed position-relative">
              <input type="file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" disabled title="Indisponibil momentan">
              <i class="bi bi-cloud-upload fs-3 text-muted mb-2 d-block"></i>
              <div class="fw-semibold text-muted">Trage fișierele aici sau click pentru a încărca</div>
              <div class="small text-muted mt-1">(Funcționalitate în curând - momentan vă rugăm să includeți link-uri externe dacă este necesar)</div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center pt-3 border-top">
            <div class="d-flex align-items-center gap-2 text-muted small">
              <i class="bi bi-headset"></i>
              <span>Suport: 0722 123 456</span>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-light" @click="$router.back()">
                Anulează
              </button>
              <button 
                type="submit" 
                class="btn btn-primary px-4 d-flex align-items-center gap-2"
                :disabled="submitting || !isValid"
              >
                <span v-if="submitting" class="spinner-border spinner-border-sm"></span>
                <i v-else class="bi bi-send"></i>
                Trimite Solicitarea
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import axios from 'axios';

const router = useRouter();
const toast = useToast();

const submitting = ref(false);
const form = reactive({
    notes: ''
});

const isValid = computed(() => {
  return form.notes && form.notes.trim().length >= 10;
});

const submitRequest = async () => {
    if (!isValid.value) return;
    
    submitting.value = true;
    try {
        await axios.post('/api/account/quotes', form);
        toast.success('Cererea a fost trimisă cu succes!');
        router.push({ name: 'account-quote-requests' });
    } catch (e) {
        console.error(e);
        toast.error('Eroare la trimiterea cererii. Vă rugăm să încercați din nou.');
    } finally {
        submitting.value = false;
    }
};
</script>

<style scoped>
.border-dashed {
  border-style: dashed !important;
}
/* Mobile-first tweaks if Bootstrap defaults aren't enough */
@media (max-width: 576px) {
  .card-body {
    padding: 1rem !important;
  }
}
</style>
