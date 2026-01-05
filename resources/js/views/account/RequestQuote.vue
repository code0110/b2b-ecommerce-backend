<template>
  <div class="request-quote-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 mb-0">Cere Ofertă Personalizată</h1>
    </div>

    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4">
            <p class="text-muted mb-4">
              Completează formularul de mai jos pentru a solicita o ofertă personalizată. Un agent te va contacta în cel mai scurt timp.
            </p>

            <form @submit.prevent="submitRequest">
              <div class="mb-3">
                <label class="form-label fw-bold">Detalii Solicitare <span class="text-danger">*</span></label>
                <textarea 
                  v-model="form.notes" 
                  class="form-control" 
                  rows="6" 
                  placeholder="Descrie produsele dorite, cantitățile sau alte detalii relevante..."
                  required
                  minlength="10"
                ></textarea>
                <div class="form-text">Minim 10 caractere.</div>
              </div>

              <!-- Future: File Upload -->
              <!-- <div class="mb-3">
                <label class="form-label fw-bold">Atașează Fișier (opțional)</label>
                <input type="file" class="form-control">
              </div> -->

              <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-light border" @click="$router.back()">Anulează</button>
                <button type="submit" class="btn btn-primary px-4" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                  Trimite Cererea
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
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { accountApi } from '@/src/account'; // Or direct axios if accountApi doesn't cover it
import axios from 'axios';
import { useToast } from 'vue-toastification';

const router = useRouter();
const toast = useToast();
const submitting = ref(false);

const form = reactive({
  notes: ''
});

const submitRequest = async () => {
  if (form.notes.length < 10) return;

  submitting.value = true;
  try {
    // Use the route mapped to Front\QuoteController::store
    await axios.post('/api/quotes', { notes: form.notes });
    toast.success('Cererea de ofertă a fost trimisă cu succes!');
    router.push({ name: 'account-offers' });
  } catch (e) {
    console.error(e);
    toast.error('Eroare la trimiterea cererii.');
  } finally {
    submitting.value = false;
  }
};
</script>
