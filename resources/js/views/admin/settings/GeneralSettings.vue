<template>
  <div class="container-fluid py-4">
    <PageHeader
      title="Setări Generale"
      subtitle="Configurează informațiile de bază, aspectul și funcționalitățile site-ului."
    />

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <div v-else class="row g-4">
      <div class="col-lg-3">
        <div class="card shadow-sm sticky-top" style="top: 100px; z-index: 1;">
          <div class="list-group list-group-flush">
            <a
              href="#"
              class="list-group-item list-group-item-action d-flex align-items-center gap-2"
              :class="{ active: activeTab === 'general' }"
              @click.prevent="activeTab = 'general'"
            >
              <i class="bi bi-gear-fill"></i>
              General & SEO
            </a>
            <a
              href="#"
              class="list-group-item list-group-item-action d-flex align-items-center gap-2"
              :class="{ active: activeTab === 'appearance' }"
              @click.prevent="activeTab = 'appearance'"
            >
              <i class="bi bi-palette-fill"></i>
              Aspect & Logo
            </a>
            <a
              href="#"
              class="list-group-item list-group-item-action d-flex align-items-center gap-2"
              :class="{ active: activeTab === 'contact' }"
              @click.prevent="activeTab = 'contact'"
            >
              <i class="bi bi-telephone-fill"></i>
              Contact & Social
            </a>
            <a
              href="#"
              class="list-group-item list-group-item-action d-flex align-items-center gap-2"
              :class="{ active: activeTab === 'features' }"
              @click.prevent="activeTab = 'features'"
            >
              <i class="bi bi-toggle-on"></i>
              Funcționalități
            </a>
          </div>
        </div>
      </div>

      <div class="col-lg-9">
        <form @submit.prevent="saveAll">
          <!-- General Tab -->
          <div v-show="activeTab === 'general'" class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
              <h5 class="mb-0">Informații Generale</h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label fw-bold">Nume Site</label>
                <input
                  v-model="form.site_name"
                  type="text"
                  class="form-control"
                  placeholder="ex: MB2B Industry"
                />
                <div class="form-text">Afișat în titlul paginii și în header.</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Descriere Site (SEO)</label>
                <textarea
                  v-model="form.site_description"
                  class="form-control"
                  rows="3"
                  placeholder="Descriere scurtă a activității..."
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Appearance Tab -->
          <div v-show="activeTab === 'appearance'" class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
              <h5 class="mb-0">Aspect & Branding</h5>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-8">
                  <div class="mb-3">
                    <label class="form-label fw-bold">Logo Site</label>
                    <div class="input-group mb-2">
                      <input
                        type="file"
                        class="form-control"
                        accept="image/*"
                        @change="handleLogoUpload"
                        :disabled="uploadingLogo"
                      />
                    </div>
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-light text-muted">URL</span>
                      <input
                        v-model="form.site_logo"
                        type="text"
                        class="form-control bg-light text-muted"
                        readonly
                      />
                    </div>
                    <div v-if="uploadingLogo" class="progress mt-2" style="height: 3px;">
                      <div class="progress-bar progress-bar-striped progress-bar-animated w-100" role="progressbar"></div>
                    </div>
                    <div class="form-text">Încarcă un fișier imagine (PNG, JPG, SVG). Se recomandă fundal transparent.</div>
                  </div>
                </div>
                <div class="col-md-4 text-center">
                  <label class="form-label fw-bold d-block">Previzualizare</label>
                  <div class="border rounded p-3 bg-light d-inline-block">
                    <img
                      :src="form.site_logo"
                      alt="Logo Preview"
                      class="img-fluid"
                      style="max-height: 80px;"
                      @error="handleImageError"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Contact Tab -->
          <div v-show="activeTab === 'contact'" class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
              <h5 class="mb-0">Date de Contact</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold">Telefon Contact</label>
                  <input
                    v-model="form.contact_phone"
                    type="text"
                    class="form-control"
                    placeholder="07xx xxx xxx"
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Email Contact</label>
                  <input
                    v-model="form.contact_email"
                    type="email"
                    class="form-control"
                    placeholder="contact@example.com"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Features Tab -->
          <div v-show="activeTab === 'features'" class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
              <h5 class="mb-0">Control Funcționalități</h5>
            </div>
            <div class="card-body">
              <div class="form-check form-switch mb-3">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="vatToggle"
                  v-model="form.show_vat_toggle"
                />
                <label class="form-check-label" for="vatToggle">
                  Afișează selector TVA în header
                </label>
                <div class="form-text">
                  Permite utilizatorilor să comute între prețuri cu/fără TVA.
                </div>
              </div>

              <div class="form-check form-switch mb-3">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="regToggle"
                  v-model="form.enable_registration"
                />
                <label class="form-check-label" for="regToggle">
                  Permite înregistrarea utilizatorilor
                </label>
              </div>
            </div>
          </div>

          <!-- Sticky Actions Footer -->
          <div class="d-flex justify-content-end gap-2 mt-4 sticky-bottom bg-white p-3 border-top shadow-lg" style="bottom: 0; z-index: 10;">
            <button type="button" class="btn btn-outline-secondary" @click="resetForm">
              Anulează
            </button>
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-save me-1"></i> Salvează Modificările
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import PageHeader from '@/components/common/PageHeader.vue';
import { useSettingsStore } from '@/store/settings';
import { uploadFile } from '@/services/admin/settings';
import { useToast } from 'vue-toastification';

const settingsStore = useSettingsStore();
const activeTab = ref('general');
const loading = computed(() => settingsStore.loading);
const toast = useToast();
const uploadingLogo = ref(false);

// Local form state
const form = ref({
  site_name: '',
  site_description: '',
  site_logo: '',
  contact_phone: '',
  contact_email: '',
  show_vat_toggle: true,
  enable_registration: true
});

// Map flat form to API structure
const mapSettingsToForm = (settings) => {
  if (!Array.isArray(settings)) {
    console.error('Settings is not an array:', settings);
    return;
  }
  settings.forEach(s => {
    if (s.key in form.value) {
      // Cast booleans
      if (s.type === 'boolean') {
        form.value[s.key] = s.value === '1' || s.value === 'true' || s.value === true;
      } else {
        form.value[s.key] = s.value;
      }
    }
  });
};

const mapFormToSettings = () => {
  return Object.keys(form.value).map(key => ({
    key: key,
    value: form.value[key]
  }));
};

const handleImageError = (e) => {
  // Use a data URI for a simple placeholder (gray box with "No Image")
  e.target.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='50' viewBox='0 0 100 50'%3E%3Crect width='100' height='50' fill='%23eee'/%3E%3Ctext x='50' y='25' font-family='Arial' font-size='12' fill='%23999' text-anchor='middle' dy='.3em'%3ENo Image%3C/text%3E%3C/svg%3E";
};

const handleLogoUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('file', file);
  formData.append('folder', 'logos');

  uploadingLogo.value = true;
  try {
    const response = await uploadFile(formData);
    form.value.site_logo = response.data.path;
    toast.success('Logo încărcat cu succes!');
  } catch (error) {
    console.error('Upload failed:', error);
    toast.error('Eroare la încărcarea logo-ului.');
  } finally {
    uploadingLogo.value = false;
  }
};

const resetForm = () => {
  mapSettingsToForm(settingsStore.settings);
};

const saveAll = async () => {
  const settingsPayload = mapFormToSettings();
  await settingsStore.saveSettings(settingsPayload);
};

onMounted(async () => {
  await settingsStore.loadSettings();
  mapSettingsToForm(settingsStore.settings);
});
</script>

<style scoped>
.sticky-bottom {
  position: sticky;
  bottom: 0;
}
</style>
