<template>
  <div class="card shadow-sm">
    <div class="card-header bg-white py-3">
      <h5 class="mb-0">Configurare Oferte B2B</h5>
    </div>
    <div class="card-body">
      <form @submit.prevent="saveSettings">
        <div class="mb-4">
          <label class="form-label fw-bold">Prag Discount Aprobare Director (%)</label>
          <div class="form-text text-muted mb-2">
            Discount-urile sub acest prag pot fi acordate de agenți fără aprobare.
            Discount-urile care depășesc acest prag necesită aprobarea unui director.
          </div>
          <div class="input-group" style="max-width: 200px;">
            <input
              type="number"
              v-model="settings.offer_discount_threshold_approval"
              class="form-control"
              min="0"
              max="100"
              step="0.1"
            />
            <span class="input-group-text">%</span>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-bold">Discount Maxim Admisibil (%)</label>
          <div class="form-text text-muted mb-2">
            Discount-ul maxim care poate fi acordat pe o linie de ofertă.
            Niciun utilizator (inclusiv directorii) nu poate depăși acest prag în mod normal.
          </div>
          <div class="input-group" style="max-width: 200px;">
            <input
              type="number"
              v-model="settings.offer_discount_max"
              class="form-control"
              min="0"
              max="100"
              step="0.1"
            />
            <span class="input-group-text">%</span>
          </div>
        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                Salvează Configurarea
            </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { adminApi } from '@/services/http';
import { useToast } from 'vue-toastification';

const toast = useToast();
const loading = ref(false);
const settings = ref({
    offer_discount_threshold_approval: 15,
    offer_discount_max: 20
});

onMounted(async () => {
    try {
        loading.value = true;
        const { data } = await adminApi.get('/settings');
        // data is array of objects { id, key, value, type, ... }
        data.forEach(s => {
             if (settings.value.hasOwnProperty(s.key)) {
                 settings.value[s.key] = Number(s.value);
             }
        });
    } catch (e) {
        console.error('Failed to load settings', e);
        toast.error('Nu s-au putut încărca setările.');
    } finally {
        loading.value = false;
    }
});

const saveSettings = async () => {
    loading.value = true;
    try {
        const payload = {
            settings: Object.keys(settings.value).map(k => ({
                key: k,
                value: settings.value[k]
            }))
        };
        await adminApi.put('/settings', payload);
        toast.success('Setările au fost actualizate cu succes!');
    } catch (e) {
        console.error(e);
        toast.error('Eroare la salvarea setărilor.');
    } finally {
        loading.value = false;
    }
};
</script>
