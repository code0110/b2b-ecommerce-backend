<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h4 mb-0">Setări Notificări</h1>
      <RouterLink :to="{ name: 'account-notifications' }" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la notificări
      </RouterLink>
    </div>

    <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-orange" role="status">
                <span class="visually-hidden">Se încarcă...</span>
            </div>
        </div>

    <div v-else class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-4">Preferințe notificări</h5>
        
        <form @submit.prevent="savePreferences">
          <div class="mb-4">
            <h6 class="fw-bold mb-3">Actualizări Comenzi</h6>
            <div class="form-check form-switch mb-2">
              <input 
                class="form-check-input" 
                type="checkbox" 
                id="order_updates_database" 
                v-model="preferences.order_updates_database"
              >
              <label class="form-check-label" for="order_updates_database">
                Primește notificări în aplicație la schimbarea statusului comenzii
              </label>
            </div>
            <div class="form-check form-switch">
              <input 
                class="form-check-input" 
                type="checkbox" 
                id="order_updates_email" 
                v-model="preferences.order_updates_email"
              >
              <label class="form-check-label" for="order_updates_email">
                Primește email la schimbarea statusului comenzii
              </label>
            </div>
          </div>

          <div class="mb-4">
            <h6 class="fw-bold mb-3">Oferte și Promoții</h6>
            <div class="form-check form-switch mb-2">
              <input 
                class="form-check-input" 
                type="checkbox" 
                id="promotions_database" 
                v-model="preferences.promotions_database"
              >
              <label class="form-check-label" for="promotions_database">
                Notificări în aplicație despre oferte noi
              </label>
            </div>
            <div class="form-check form-switch">
              <input 
                class="form-check-input" 
                type="checkbox" 
                id="promotions_email" 
                v-model="preferences.promotions_email"
              >
              <label class="form-check-label" for="promotions_email">
                Email cu oferte și promoții
              </label>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-orange" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              {{ saving ? 'Se salvează...' : 'Salvează preferințele' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from '@/services/http';
import { useToast } from 'vue-toastification';

const toast = useToast();
const loading = ref(true);
const saving = ref(false);

const preferences = ref({
  order_updates_database: true,
  order_updates_email: true,
  promotions_database: true,
  promotions_email: true
});

onMounted(async () => {
  try {
    const response = await axios.get('/notifications/preferences');
    if (response.data) {
      // Merge defaults with saved preferences
      preferences.value = { ...preferences.value, ...response.data };
    }
  } catch (error) {
    console.error('Failed to load preferences:', error);
    toast.error('Nu s-au putut încărca preferințele.');
  } finally {
    loading.value = false;
  }
});

const savePreferences = async () => {
  saving.value = true;
  try {
    await axios.post('/notifications/preferences', { preferences: preferences.value });
    toast.success('Preferințele au fost salvate cu succes.');
  } catch (error) {
    console.error('Failed to save preferences:', error.response?.data || error.message);
    toast.error('Nu s-au putut salva preferințele.');
  } finally {
    saving.value = false;
  }
};
</script>
