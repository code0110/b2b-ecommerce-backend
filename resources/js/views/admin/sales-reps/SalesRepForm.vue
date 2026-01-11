<template>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ isEdit ? 'Editează Reprezentant' : 'Adaugă Reprezentant Nou' }}</h5>
      <RouterLink :to="{ name: 'admin-sales-reps' }" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la Listă
      </RouterLink>
    </div>
    <div class="card-body">
      <form @submit.prevent="saveRep">
        <div class="row g-3">
          <div class="col-md-8">
            <div class="card bg-light border-0 mb-3">
              <div class="card-body">
                <h6 class="card-title fw-bold mb-3">Informații Generale</h6>
                
                <div class="mb-3">
                  <label class="form-label">Nume Complet <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="form.name" required>
                </div>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" v-model="form.email">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Telefon</label>
                    <input type="text" class="form-control" v-model="form.phone">
                  </div>
                </div>
              </div>
            </div>

            <div class="card bg-light border-0">
              <div class="card-body">
                <h6 class="card-title fw-bold mb-3">Zona de Acoperire</h6>
                
                <div class="mb-3">
                  <label class="form-label">Regiune (Etichetă)</label>
                  <input type="text" class="form-control" v-model="form.region" placeholder="Ex: Transilvania, Moldova, Sud-Muntenia">
                  <div class="form-text">Numele regiunii generale (opțional).</div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Județe Acoperite</label>
                  <div class="border rounded p-3 bg-white" style="max-height: 300px; overflow-y: auto;">
                    <div class="row g-2">
                      <div class="col-6 col-md-4 col-lg-3" v-for="county in allCounties" :key="county">
                        <div class="form-check">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            :value="county" 
                            :id="'county-' + county" 
                            v-model="form.counties"
                          >
                          <label class="form-check-label small" :for="'county-' + county">
                            {{ county }}
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="form-text mt-2">
                    Selectați județele unde acest reprezentant este activ.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card bg-light border-0 mb-3">
              <div class="card-body">
                <h6 class="card-title fw-bold mb-3">Setări</h6>
                
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" id="activeSwitch" v-model="form.is_active">
                  <label class="form-check-label" for="activeSwitch">Activ</label>
                </div>

                <div class="mb-3">
                  <label class="form-label">Ordine Afișare</label>
                  <input type="number" class="form-control" v-model="form.sort_order">
                  <div class="form-text">Număr mai mic = prioritate mai mare.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                  {{ isEdit ? 'Actualizează' : 'Salvează' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getSalesRepresentative, createSalesRepresentative, updateSalesRepresentative } from '@/services/admin/salesReps';
import { useToast } from 'vue-toastification';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const loading = ref(false);
const isEdit = ref(false);

const form = reactive({
  name: '',
  email: '',
  phone: '',
  region: '',
  counties: [],
  is_active: true,
  sort_order: 0
});

const allCounties = [
  'Alba', 'Arad', 'Argeș', 'Bacău', 'Bihor', 'Bistrița-Năsăud', 'Botoșani', 'Brăila', 'Brașov', 
  'București', 'Buzău', 'Călărași', 'Caraș-Severin', 'Cluj', 'Constanța', 'Covasna', 'Dâmbovița', 
  'Dolj', 'Galați', 'Giurgiu', 'Gorj', 'Harghita', 'Hunedoara', 'Ialomița', 'Iași', 'Ilfov', 
  'Maramureș', 'Mehedinți', 'Mureș', 'Neamț', 'Olt', 'Prahova', 'Sălaj', 'Satu Mare', 'Sibiu', 
  'Suceava', 'Teleorman', 'Timiș', 'Tulcea', 'Vâlcea', 'Vaslui', 'Vrancea'
];

const loadRep = async (id) => {
  loading.value = true;
  try {
    const data = await getSalesRepresentative(id);
    form.name = data.name;
    form.email = data.email;
    form.phone = data.phone;
    form.region = data.region;
    form.counties = data.counties || [];
    form.is_active = !!data.is_active;
    form.sort_order = data.sort_order;
  } catch (error) {
    console.error('Error loading sales rep:', error);
    toast.error('Nu s-a putut încărca reprezentantul.');
    router.push({ name: 'admin-sales-reps' });
  } finally {
    loading.value = false;
  }
};

const saveRep = async () => {
  loading.value = true;
  try {
    const payload = { ...form };
    
    if (isEdit.value) {
      await updateSalesRepresentative(route.params.id, payload);
      toast.success('Reprezentant actualizat cu succes.');
    } else {
      await createSalesRepresentative(payload);
      toast.success('Reprezentant adăugat cu succes.');
    }
    router.push({ name: 'admin-sales-reps' });
  } catch (error) {
    console.error('Error saving sales rep:', error);
    if (error.response && error.response.data && error.response.data.errors) {
      // Display validation errors if available
      const errors = Object.values(error.response.data.errors).flat();
      errors.forEach(err => toast.error(err));
    } else {
      toast.error('A apărut o eroare la salvare.');
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (route.params.id) {
    isEdit.value = true;
    loadRep(route.params.id);
  }
});
</script>
