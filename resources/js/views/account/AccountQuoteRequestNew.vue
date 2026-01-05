<template>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
      <h5 class="mb-0">Cerere de Ofertă Nouă</h5>
    </div>
    <div class="card-body">
      <form @submit.prevent="submitRequest">
        <div class="mb-3">
          <label class="form-label fw-bold">Mesaj / Detalii Solicitare <span class="text-danger">*</span></label>
          <textarea 
            v-model="form.notes" 
            class="form-control" 
            rows="5" 
            placeholder="Descrie produsele dorite, cantitățile sau alte detalii..."
            required
            minlength="10"
          ></textarea>
          <div class="form-text">Te rugăm să fii cât mai specific pentru a primi o ofertă corectă.</div>
        </div>

        <div class="alert alert-info small">
          <i class="bi bi-info-circle me-2"></i>
          Poți solicita oferte și direct din pagina produsului sau din coșul de cumpărături.
        </div>

        <div class="d-flex justify-content-end gap-2">
            <router-link :to="{ name: 'account-quote-requests' }" class="btn btn-outline-secondary">
                Anulează
            </router-link>
            <button type="submit" class="btn btn-primary" :disabled="submitting || !form.notes.trim()">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                Trimite Solicitarea
            </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import axios from 'axios';

const router = useRouter();
const toast = useToast();

const submitting = ref(false);
const form = reactive({
    notes: ''
});

const submitRequest = async () => {
    if (!form.notes.trim()) return;
    
    submitting.value = true;
    try {
        await axios.post('/api/account/quotes', form);
        toast.success('Cererea a fost trimisă cu succes!');
        router.push({ name: 'account-quote-requests' });
    } catch (e) {
        console.error(e);
        toast.error('Eroare la trimiterea cererii.');
    } finally {
        submitting.value = false;
    }
};
</script>
