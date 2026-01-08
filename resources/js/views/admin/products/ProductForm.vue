<template>
  <div class="d-flex flex-column min-vh-100 bg-gray-50">
    <!-- Top Navigation Bar (Sticky) -->
    <header class="sticky-top bg-white border-bottom shadow-sm z-30">
      <div class="container-fluid px-4 py-3">
        <div class="d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-3">
            <button @click="router.push({ name: 'admin-products' })" class="btn btn-icon btn-light rounded-circle shadow-sm">
              <i class="bi bi-arrow-left"></i>
            </button>
            <div>
              <div class="d-flex align-items-center gap-2">
                <h1 class="h5 mb-0 fw-bold text-dark">{{ isEdit ? 'Editare Produs' : 'Produs Nou' }}</h1>
                <span class="badge rounded-pill" :class="statusBadgeClass">{{ statusLabel }}</span>
              </div>
              <small class="text-muted" v-if="form.name">{{ form.name }}</small>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-light border fw-medium" @click="router.push({ name: 'admin-products' })">
              Anulează
            </button>
            <button class="btn btn-primary fw-semibold px-4 d-flex align-items-center gap-2 shadow-sm" :disabled="saving" @click="submit">
              <span v-if="saving" class="spinner-border spinner-border-sm"></span>
              {{ saving ? 'Se salvează...' : 'Salvează Modificările' }}
            </button>
          </div>
        </div>
      </div>
    </header>

    <div class="flex-grow-1 container-fluid px-4 py-4">
      <div v-if="loading" class="d-flex justify-content-center align-items-center min-vh-50">
        <div class="spinner-border text-primary" role="status"></div>
      </div>

      <div v-else class="row g-4">
        <!-- Sidebar Navigation (Spy Scroll) -->
        <div class="col-lg-2 d-none d-lg-block">
          <nav class="sticky-top" style="top: 100px;">
            <div class="nav flex-column nav-pills" id="v-pills-tab">
              <a class="nav-link fw-medium mb-1" :class="{ active: activeSection === 'general' }" href="#general" @click.prevent="scrollTo('general')">
                <i class="bi bi-info-circle me-2"></i>General
              </a>
              <a class="nav-link fw-medium mb-1" :class="{ active: activeSection === 'media' }" href="#media" @click.prevent="scrollTo('media')">
                <i class="bi bi-images me-2"></i>Media
              </a>
              <a class="nav-link fw-medium mb-1" :class="{ active: activeSection === 'pricing' }" href="#pricing" @click.prevent="scrollTo('pricing')">
                <i class="bi bi-tag me-2"></i>Prețuri
              </a>
              <a class="nav-link fw-medium mb-1" :class="{ active: activeSection === 'inventory' }" href="#inventory" @click.prevent="scrollTo('inventory')">
                <i class="bi bi-box-seam me-2"></i>Stoc
              </a>
              <a class="nav-link fw-medium mb-1" :class="{ active: activeSection === 'variants' }" href="#variants" @click.prevent="scrollTo('variants')">
                <i class="bi bi-grid-3x3 me-2"></i>Variante
              </a>
              <a class="nav-link fw-medium mb-1" :class="{ active: activeSection === 'seo' }" href="#seo" @click.prevent="scrollTo('seo')">
                <i class="bi bi-google me-2"></i>SEO
              </a>
            </div>
          </nav>
        </div>

        <!-- Main Content -->
        <div class="col-lg-7">
          
          <!-- SECTION: GENERAL -->
          <div id="general" class="card border-0 shadow-sm mb-5 scroll-section">
            <div class="card-body p-4">
              <h5 class="card-title fw-bold mb-4">Informații de Bază</h5>
              
              <div class="mb-4">
                <label class="form-label fw-bold">Nume Produs <span class="text-danger">*</span></label>
                <input v-model="form.name" type="text" class="form-control form-control-lg" placeholder="Ex: Laptop Gaming..." :class="{ 'is-invalid': fieldError('name') }" @input="generateSlug">
                <div class="invalid-feedback">{{ fieldError('name') }}</div>
              </div>

              <div class="row g-4 mb-4">
                <div class="col-md-6">
                  <label class="form-label text-muted small fw-bold text-uppercase">Cod Intern (SKU)</label>
                  <input v-model="form.internal_code" type="text" class="form-control">
                </div>
                <div class="col-md-6">
                   <label class="form-label text-muted small fw-bold text-uppercase">Brand</label>
                   <select v-model="form.brand_id" class="form-select">
                      <option :value="null">-- Fără Brand --</option>
                      <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                   </select>
                </div>
              </div>

              <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                   <label class="form-label fw-bold mb-0">Descriere Scurtă</label>
                </div>
                <textarea v-model="form.short_description" class="form-control" rows="3" placeholder="Rezumat pentru listări..."></textarea>
              </div>

              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-bold mb-0">Descriere Detaliată</label>
                    <button 
                       class="btn btn-primary btn-sm d-flex align-items-center gap-2 shadow-sm" 
                       @click="handleGenerateSeo('descriptions')"
                       :disabled="generatingSeo || !form.name"
                    >
                       <span v-if="generatingSeo" class="spinner-border spinner-border-sm"></span>
                       <i v-else class="bi bi-stars"></i>
                       Formatează & Optimizează Textul
                    </button>
                </div>

                <div class="alert alert-info py-2 small border-0 bg-info-subtle text-info-emphasis mb-2">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Cum funcționează:</strong> Introdu sau lipește textul brut al produsului în editorul de mai jos, apoi apasă butonul din dreapta sus pentru a-l structura automat (tabele, liste, subtitluri).
                </div>
                
                <div v-if="!quillError" class="quill-wrapper">
                  <div ref="editorContainer"></div>
                </div>
                
                <div v-else>
                   <div class="alert alert-warning py-2 small">
                      <i class="bi bi-exclamation-triangle me-1"></i>
                      Editorul vizual a întâmpinat o eroare. S-a trecut automat la modul HTML simplu.
                   </div>
                   <textarea 
                      v-model="form.long_description" 
                      class="form-control font-monospace bg-light" 
                      rows="12" 
                      placeholder="Conținut HTML..."
                   ></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION: MEDIA -->
          <div id="media" class="card border-0 shadow-sm mb-5 scroll-section">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title fw-bold mb-0">Imagini & Media</h5>
                <span class="badge bg-light text-dark border">{{ form.images.length }} imagini</span>
              </div>

              <!-- Upload Area -->
              <div 
                class="upload-zone mb-4"
                @dragover.prevent="dragOver = true"
                @dragleave.prevent="dragOver = false"
                @drop.prevent="handleDrop"
                :class="{ 'active': dragOver }"
              >
                <input type="file" ref="fileInput" multiple accept="image/*" class="d-none" @change="handleFileSelect">
                <div class="text-center py-5 cursor-pointer" @click="$refs.fileInput.click()">
                  <i class="bi bi-cloud-arrow-up text-primary display-4 mb-3"></i>
                  <h6 class="fw-bold mb-1">Click sau trage imaginile aici</h6>
                  <p class="text-muted small mb-0">JPG, PNG, WEBP (Max 10MB)</p>
                </div>
                <div v-if="uploading" class="progress mt-3 mx-4" style="height: 4px;">
                   <div class="progress-bar progress-bar-striped progress-bar-animated" :style="{ width: uploadProgress + '%' }"></div>
                </div>
              </div>

              <!-- Image Grid -->
              <div class="row g-3" v-if="form.images.length > 0">
                 <div v-for="(img, idx) in form.images" :key="idx" class="col-6 col-md-4 col-xl-3">
                    <div class="card h-100 border shadow-sm position-relative image-card">
                       <div class="ratio ratio-1x1 bg-light border-bottom">
                          <img :src="img.path" class="object-fit-cover" alt="Product Image">
                       </div>
                       <div class="card-body p-2">
                          <div class="d-flex justify-content-between align-items-center">
                             <div class="form-check form-switch small mb-0">
                                <input class="form-check-input" type="checkbox" :checked="img.is_main" @change="setMainImage(idx)" :id="'img-' + idx">
                                <label class="form-check-label small" :for="'img-' + idx">Cover</label>
                             </div>
                             <button class="btn btn-link text-danger p-0" @click="removeImage(idx)">
                                <i class="bi bi-trash"></i>
                             </button>
                          </div>
                          <div class="mt-2 border-top pt-2">
                             <div class="d-flex align-items-center gap-2">
                                <label class="small text-muted mb-0">Ordine:</label>
                                <input type="number" class="form-control form-control-sm" v-model.number="img.sort_order" style="width: 70px;">
                             </div>
                          </div>
                       </div>
                       <div v-if="img.is_main" class="position-absolute top-0 start-0 m-2 badge bg-success shadow-sm">Main</div>
                    </div>
                 </div>
              </div>
            </div>
          </div>

          <!-- SECTION: PRICING -->
          <div id="pricing" class="card border-0 shadow-sm mb-5 scroll-section">
            <div class="card-body p-4">
               <h5 class="card-title fw-bold mb-4">Prețuri</h5>
               <div class="row g-4">
                 <div class="col-md-6">
                    <label class="form-label fw-bold">Preț Listă (B2B)</label>
                    <div class="input-group input-group-lg">
                       <span class="input-group-text bg-white text-muted">RON</span>
                       <input v-model.number="form.list_price" type="number" step="0.01" class="form-control fw-bold">
                    </div>
                 </div>
                 <div class="col-md-6">
                    <label class="form-label fw-bold">Preț RRP (Recomandat)</label>
                    <div class="input-group input-group-lg">
                       <span class="input-group-text bg-white text-muted">RON</span>
                       <input v-model.number="form.rrp_price" type="number" step="0.01" class="form-control">
                    </div>
                 </div>
                 <div class="col-md-4">
                    <label class="form-label small text-uppercase fw-bold text-muted">Cota TVA (%)</label>
                    <input v-model.number="form.vat_rate" type="number" class="form-control" placeholder="19">
                 </div>
                 <div class="col-md-8">
                    <label class="form-label small text-uppercase fw-bold text-muted">Preț Promo (Override)</label>
                    <div class="input-group">
                       <span class="input-group-text bg-white text-danger fw-bold">RON</span>
                       <input v-model.number="form.price_override" type="number" step="0.01" class="form-control text-danger fw-bold">
                    </div>
                 </div>
               </div>
            </div>
          </div>

          <!-- SECTION: INVENTORY -->
          <div id="inventory" class="card border-0 shadow-sm mb-5 scroll-section">
            <div class="card-body p-4">
              <h5 class="card-title fw-bold mb-4">Inventar & Logistică</h5>
              <div class="row g-4">
                 <div class="col-md-6">
                    <label class="form-label fw-bold">Stoc Fizic</label>
                    <input v-model.number="form.stock_qty" type="number" class="form-control">
                 </div>
                 <div class="col-md-6">
                    <label class="form-label fw-bold">Stoc Furnizor</label>
                    <input v-model.number="form.supplier_stock_qty" type="number" class="form-control">
                 </div>
                 <div class="col-md-6">
                    <label class="form-label fw-bold">Status Stoc</label>
                    <select v-model="form.stock_status" class="form-select">
                       <option :value="null">Automat</option>
                       <option value="in_stock">În Stoc</option>
                       <option value="limited">Stoc Limitat</option>
                       <option value="out_of_stock">Stoc Epuizat</option>
                       <option value="on_order">La Comandă</option>
                    </select>
                 </div>
              </div>
            </div>
          </div>

          <!-- SECTION: VARIANTS -->
          <div id="variants" class="card border-0 shadow-sm mb-5 scroll-section">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
              <h5 class="card-title fw-bold mb-0">Variante Produs</h5>
              <button class="btn btn-outline-primary btn-sm" @click="addVariant"><i class="bi bi-plus-lg"></i> Adaugă</button>
            </div>
            <div class="card-body p-0 table-responsive">
              <table class="table table-hover align-middle mb-0" v-if="form.variants.length > 0">
                <thead class="bg-light">
                   <tr>
                      <th class="ps-4">SKU</th>
                      <th>Atribute</th>
                      <th>Preț</th>
                      <th>Stoc</th>
                      <th class="text-end pe-4">Acțiuni</th>
                   </tr>
                </thead>
                <tbody>
                   <tr v-for="(v, k) in form.variants" :key="k">
                      <td class="ps-4"><input v-model="v.sku" class="form-control form-control-sm" placeholder="SKU"></td>
                      <td>
                         <div class="d-flex gap-1">
                            <input v-model="v.attributes.size" class="form-control form-control-sm" placeholder="Mărime">
                            <input v-model="v.attributes.color" class="form-control form-control-sm" placeholder="Culoare">
                         </div>
                      </td>
                      <td><input v-model.number="v.price" type="number" class="form-control form-control-sm" placeholder="Preț"></td>
                      <td><input v-model.number="v.stock_qty" type="number" class="form-control form-control-sm" placeholder="Stoc"></td>
                      <td class="text-end pe-4">
                         <button class="btn btn-sm btn-link text-danger" @click="removeVariant(k)"><i class="bi bi-trash"></i></button>
                      </td>
                   </tr>
                </tbody>
              </table>
              <div v-else class="text-center py-5 text-muted">
                 Nu există variante definite.
              </div>
            </div>
          </div>
          
           <!-- SECTION: SEO -->
           <div id="seo" class="card border-0 shadow-sm mb-5 scroll-section">
             <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h5 class="card-title fw-bold mb-0">Optimizare SEO</h5>
                  <button 
                     class="btn btn-primary btn-sm d-flex align-items-center gap-2" 
                     @click="handleGenerateSeo('seo')"
                     :disabled="generatingSeo || !form.name"
                  >
                     <span v-if="generatingSeo" class="spinner-border spinner-border-sm"></span>
                     <i v-else class="bi bi-search"></i>
                     Generează Meta-uri SEO
                  </button>
               </div>

                <div class="alert alert-light border border-info border-start-4 text-muted small mb-4">
                   <i class="bi bi-info-circle-fill text-info me-2"></i>
                   Modulul generează automat titluri, descrieri și cuvinte cheie unice bazate pe specificațiile produsului.
                </div>

                <div class="mb-3">
                   <label class="form-label fw-bold">Meta Titlu</label>
                   <div class="input-group">
                      <input v-model="form.meta_title" type="text" class="form-control" placeholder="Titlul afișat în Google">
                      <span class="input-group-text small" :class="form.meta_title.length > 60 ? 'text-danger' : 'text-muted'">
                         {{ form.meta_title.length }}/60
                      </span>
                   </div>
                </div>

                <div class="mb-3">
                   <label class="form-label fw-bold">Meta Descriere</label>
                   <textarea v-model="form.meta_description" class="form-control" rows="2" placeholder="Descrierea afișată în Google"></textarea>
                   <div class="text-end small mt-1" :class="form.meta_description.length > 160 ? 'text-danger' : 'text-muted'">
                      {{ form.meta_description.length }}/160
                   </div>
                </div>

                <div class="mb-3">
                   <label class="form-label fw-bold">Cuvinte Cheie</label>
                   <input v-model="form.keywords" type="text" class="form-control" placeholder="laptop, gaming, i7, 16gb...">
                </div>

                <div class="mb-3">
                   <label class="form-label fw-bold">Slug URL</label>
                   <div class="input-group">
                      <span class="input-group-text bg-light">/produs/</span>
                      <input v-model="form.slug" type="text" class="form-control">
                   </div>
                </div>
                
                <div class="seo-preview bg-white border rounded p-3 mt-4">
                   <h6 class="fw-bold text-muted small text-uppercase mb-3">Previzualizare Google</h6>
                   <div class="d-flex align-items-center gap-2 mb-1">
                      <div class="bg-light border rounded-circle" style="width: 24px; height: 24px;"></div>
                      <span class="small text-dark">b2b.example.com › produs › {{ form.slug || 'nume-produs' }}</span>
                   </div>
                   <h5 class="text-primary mb-1 text-truncate" style="font-family: arial, sans-serif;">
                      {{ form.meta_title || form.name || 'Titlu Produs' }}
                   </h5>
                   <p class="small text-muted mb-0" style="font-family: arial, sans-serif; line-height: 1.4;">
                      {{ form.meta_description || form.short_description || 'Descrierea produsului va apărea aici...' }}
                   </p>
                </div>
             </div>
           </div>

        </div>

        <!-- Right Column: Settings -->
        <div class="col-lg-3">
           <div class="sticky-top" style="top: 100px;">
              <!-- Status Card -->
              <div class="card border-0 shadow-sm mb-4">
                 <div class="card-body p-4">
                    <h6 class="fw-bold text-uppercase text-muted small mb-3">Publicare</h6>
                    <select v-model="form.status" class="form-select mb-3">
                       <option value="published">Publicat</option>
                       <option value="draft">Draft</option>
                       <option value="hidden">Ascuns</option>
                    </select>
                    
                    <div class="d-flex flex-column gap-2">
                       <div class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" id="isNew" v-model="form.is_new">
                          <label class="form-check-label" for="isNew">Produs Nou</label>
                       </div>
                       <div class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" id="isPromo" v-model="form.is_promo">
                          <label class="form-check-label" for="isPromo">Promoție</label>
                       </div>
                       <div class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" id="isBest" v-model="form.is_best_seller">
                          <label class="form-check-label" for="isBest">Best Seller</label>
                       </div>
                    </div>
                 </div>
              </div>

              <!-- Category Card -->
              <div class="card border-0 shadow-sm mb-4">
                 <div class="card-body p-4">
                    <h6 class="fw-bold text-uppercase text-muted small mb-3">Organizare</h6>
                    <div class="mb-3">
                       <label class="form-label small fw-bold">Categorie Principală</label>
                       <select v-model="form.main_category_id" class="form-select">
                          <option :value="null">Selectează...</option>
                          <option v-for="cat in flatCategories" :key="cat.id" :value="cat.id">{{ cat.indented_name }}</option>
                       </select>
                    </div>
                    
                    <div class="mb-3">
                       <label class="form-label small fw-bold">Ordine Afișare</label>
                       <input v-model.number="form.sort_order" type="number" class="form-control">
                    </div>
                 </div>
              </div>
           </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, onErrorCaptured } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import { fetchProduct, createProduct, updateProduct, fetchProducts, generateSeo } from '@/services/admin/products';
import { fetchCategories, fetchCategory } from '@/services/admin/categories';
import { fetchBrands } from '@/services/admin/brands';
import { adminApi } from '@/services/http';
import { Quill } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const route = useRoute();
const router = useRouter();
const toast = useToast();

// State
const isEdit = computed(() => !!route.params.id);
const loading = ref(false);
const saving = ref(false);
const generatingSeo = ref(false);
const errors = ref({});
const activeSection = ref('general');
const dragOver = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const quillError = ref(false);
const editorContainer = ref(null);
let quillInstance = null;

// Error Handling for Quill
onErrorCaptured((err) => {
  if (err && (err.toString().includes('Quill') || err.toString().includes('emit') || err.toString().includes('Scroll2'))) {
     console.warn('Quill Error captured:', err);
     // We don't switch to textarea automatically here anymore as we use native Quill, 
     // but we keep the error state just in case
     return false; 
  }
});

// Data
const categories = ref([]);
const brands = ref([]);
const categoryAttributes = ref([]);

const form = reactive({
  id: null,
  name: '',
  slug: '',
  internal_code: '',
  short_description: '',
  long_description: '',
  main_category_id: null,
  brand_id: null,
  status: 'published',
  sort_order: 0,
  list_price: 0,
  rrp_price: null,
  vat_rate: null,
  price_override: null,
  stock_status: null,
  stock_qty: 0,
  supplier_stock_qty: 0,
  lead_time_days: null,
  is_new: false,
  is_promo: false,
  is_best_seller: false,
  category_ids: [],
  attribute_values: [],
  images: [],
  variants: [],
  related_products: [],
  documents: [],
  meta_title: '',
  meta_description: '',
  keywords: '',
});

// Computed
const statusLabel = computed(() => {
  const map = { published: 'Publicat', draft: 'Draft', hidden: 'Ascuns' };
  return map[form.status] || form.status;
});

const statusBadgeClass = computed(() => {
  const map = { published: 'bg-success', draft: 'bg-warning text-dark', hidden: 'bg-secondary' };
  return map[form.status] || 'bg-light text-dark';
});

const flatCategories = computed(() => {
  const result = [];
  const build = (nodes, level = 0) => {
    nodes.forEach(cat => {
      result.push({ 
         id: cat.id, 
         indented_name: '— '.repeat(level) + cat.name,
         name: cat.name 
      });
      if (cat.children?.length) build(cat.children, level + 1);
    });
  };
  build(categories.value);
  return result;
});

const fieldError = (field) => errors.value[field]?.[0] || '';

// Logic
const generateSlug = () => {
  if (!isEdit.value && form.name) {
    form.slug = form.name.toLowerCase().replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-');
  }
};

const scrollTo = (id) => {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    activeSection.value = id;
  }
};

// Scroll Spy
const checkScroll = () => {
  const sections = ['general', 'media', 'pricing', 'inventory', 'variants', 'seo'];
  for (const id of sections) {
    const el = document.getElementById(id);
    if (el) {
       const rect = el.getBoundingClientRect();
       if (rect.top <= 150 && rect.bottom >= 150) {
          activeSection.value = id;
          break;
       }
    }
  }
};

onMounted(() => {
  window.addEventListener('scroll', checkScroll);
  loadInitialData();
  
  // Initialize Native Quill
  nextTick(() => {
      if (editorContainer.value) {
          quillInstance = new Quill(editorContainer.value, {
              theme: 'snow',
              modules: {
                  toolbar: [
                      ['bold', 'italic', 'underline', 'strike'],
                      ['blockquote', 'code-block'],
                      [{ 'header': 1 }, { 'header': 2 }],
                      [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                      [{ 'script': 'sub'}, { 'script': 'super' }],
                      [{ 'indent': '-1'}, { 'indent': '+1' }],
                      [{ 'direction': 'rtl' }],
                      [{ 'size': ['small', false, 'large', 'huge'] }],
                      [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                      [{ 'color': [] }, { 'background': [] }],
                      [{ 'font': [] }],
                      [{ 'align': [] }],
                      ['clean'],
                      ['link', 'image', 'video']
                  ]
              }
          });
          
          // Initial content if exists (e.g. from reload or fast fetch)
          if (form.long_description) {
              quillInstance.root.innerHTML = form.long_description;
          }
          
          // Listen for changes
          quillInstance.on('text-change', () => {
              form.long_description = quillInstance.root.innerHTML;
          });
      }
  });
});
onUnmounted(() => window.removeEventListener('scroll', checkScroll));

// Upload Logic
const handleFileSelect = (e) => uploadFiles(e.target.files);
const handleDrop = (e) => {
  dragOver.value = false;
  uploadFiles(e.dataTransfer.files);
};

const uploadFiles = async (files) => {
  if (!files.length) return;
  uploading.value = true;
  uploadProgress.value = 0;
  
  const total = files.length;
  let done = 0;

  for (const file of files) {
     const fd = new FormData();
     fd.append('file', file);
     fd.append('folder', 'products');
     
     try {
        const res = await adminApi.post('/upload', fd);
        form.images.push({
           id: null,
           path: res.data.path,
           is_main: form.images.length === 0,
           sort_order: form.images.length
        });
     } catch (e) {
        console.error('Upload failed', e);
        // Toast error here
     }
     done++;
     uploadProgress.value = (done / total) * 100;
  }
  uploading.value = false;
};

const removeImage = (idx) => form.images.splice(idx, 1);
const setMainImage = (idx) => {
  form.images.forEach((img, i) => img.is_main = i === idx);
};

// Variants
const addVariant = () => form.variants.push({ sku: '', price: null, stock_qty: 0, attributes: { color: '', size: '' } });
const removeVariant = (idx) => form.variants.splice(idx, 1);

// SEO Generation
const handleGenerateSeo = async (mode = 'all') => {
   if (!form.name) {
      toast.warning('Te rog completează numele produsului înainte de generare.');
      return;
   }
   
   generatingSeo.value = true;
   try {
      // Force sync: Get latest content from Quill if available
      if (quillInstance) {
          form.long_description = quillInstance.root.innerHTML;
      }

      console.log('Sending SEO Payload with Description:', form.long_description);

      // Prepare payload manually to ensure all relevant data is sent even if not saved yet
      const payload = {
         name: form.name,
         brand: form.brand_id ? (brands.value.find(b => b.id === form.brand_id) || null) : null,
         main_category: form.main_category_id ? (flatCategories.value.find(c => c.id === form.main_category_id) || null) : null,
         list_price: form.list_price,
         attribute_values: form.attribute_values,
         short_description: form.short_description, 
         long_description: form.long_description
      };
      
      const result = await generateSeo(payload);
      
      if (mode === 'seo' || mode === 'all') {
         form.meta_title = result.meta_title || form.meta_title;
         form.meta_description = result.meta_description || form.meta_description;
         form.keywords = result.keywords || form.keywords;
      }
      
      if (mode === 'descriptions' || mode === 'all') {
         if (result.short_description) {
            form.short_description = result.short_description;
         }
         if (result.long_description) {
            // Update form data
            form.long_description = result.long_description;
            
            // Directly update Native Quill content
            if (quillInstance) {
                quillInstance.root.innerHTML = result.long_description;
            }

            console.log('Generated Long Description Length:', result.long_description.length);
         }
      }
      
      if (mode === 'seo') {
         toast.success('Meta Titlu, Descriere și Cuvinte Cheie au fost actualizate!');
      } else if (mode === 'descriptions') {
         toast.success('Descrierile (Scurtă și Detaliată) au fost generate!');
      } else {
         toast.success('Tot conținutul SEO și Descrierile au fost generate!');
      }
   } catch (e) {
      console.error('SEO Generation Error Full:', e);
      if (e.response) {
         console.error('Response Status:', e.response.status);
         console.error('Response Data:', e.response.data);
      }
      const msg = e.response?.data?.message || e.message || 'Eroare necunoscută';
      toast.error(`Eroare la generarea SEO: ${msg}`);
   } finally {
      generatingSeo.value = false;
   }
};

// Data Loading & Submit
const loadInitialData = async () => {
  try {
     const [cats, brs] = await Promise.all([fetchCategories({ per_page: 1000 }), fetchBrands({ per_page: 1000 })]);
     categories.value = cats.data || cats;
     brands.value = brs.data || brs;
     
     if (isEdit.value) {
        loading.value = true;
        const p = await fetchProduct(route.params.id);
        Object.assign(form, {
           ...p,
           is_new: !!p.is_new,
           is_promo: !!p.is_promo,
           is_best_seller: !!p.is_best_seller,
           list_price: p.list_price ?? 0,
           attribute_values: (p.attribute_values || []).map(av => ({ attribute_id: av.attribute_id, value: av.value })),
           images: (p.images || []).map(img => ({ id: img.id, path: img.path, is_main: !!img.is_main, sort_order: img.sort_order })),
           variants: (p.variants || []).map(v => ({ ...v, attributes: v.attributes || {} }))
        });
        
        // Update Quill if initialized
        if (quillInstance && form.long_description) {
            quillInstance.root.innerHTML = form.long_description;
        }

        loading.value = false;
     }
  } catch (e) {
     console.error(e);
  }
};

const submit = async () => {
   saving.value = true;
   errors.value = {};
   try {
      const payload = { ...form };
      if (isEdit.value) await updateProduct(form.id, payload);
      else await createProduct(payload);
      toast.success('Produsul a fost salvat cu succes!');
      router.push({ name: 'admin-products' });
   } catch (e) {
      if (e.response?.status === 422) {
         errors.value = e.response.data.errors;
         toast.error('Verifică erorile din formular.');
      } else {
         toast.error('Eroare la salvarea produsului.');
      }
   } finally {
      saving.value = false;
   }
};

</script>

<style scoped>
.nav-pills .nav-link {
  color: #64748b;
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  transition: all 0.2s;
}
.nav-pills .nav-link:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}
.nav-pills .nav-link.active {
  background-color: #e0e7ff;
  color: #4f46e5;
  font-weight: 600;
}
.upload-zone {
  border: 2px dashed #cbd5e1;
  border-radius: 0.75rem;
  transition: all 0.2s;
  background-color: #f8fafc;
}
.upload-zone:hover, .upload-zone.active {
  border-color: #4f46e5;
  background-color: #eef2ff;
}
.image-card:hover {
  border-color: #4f46e5 !important;
}
.cursor-pointer { cursor: pointer; }
.quill-wrapper {
  background: white;
  border-radius: 0.5rem;
}
:deep(.ql-toolbar) {
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
  border-color: #dee2e6;
}
:deep(.ql-container) {
  border-bottom-left-radius: 0.5rem;
  border-bottom-right-radius: 0.5rem;
  border-color: #dee2e6;
  min-height: 200px;
}
</style>
