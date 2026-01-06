<template>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ isEdit ? 'Editează Pagina' : 'Adaugă Pagină Nouă' }}</h5>
      <RouterLink :to="{ name: 'admin-pages' }" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Înapoi la Listă
      </RouterLink>
    </div>
    <div class="card-body">
      <form @submit.prevent="savePage">
        <div class="row g-3">
          <div class="col-md-8">
            <div class="mb-3">
              <label class="form-label">Titlu Pagină <span class="text-danger">*</span></label>
              <input type="text" class="form-control" v-model="form.title" @input="generateSlug" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Slug (URL) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light">/</span>
                <input type="text" class="form-control" v-model="form.slug" required>
              </div>
              <div class="form-text">Adresa URL a paginii. Se generează automat din titlu.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Conținut</label>
              <QuillEditor 
                v-model:content="form.content" 
                contentType="html" 
                theme="snow" 
                toolbar="full"
                style="height: 400px;"
              />
            </div>
          </div>

          <div class="col-md-4">
            <div class="card bg-light border-0 mb-3">
              <div class="card-body">
                <h6 class="card-title fw-bold mb-3">Publicare</h6>
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" id="publishSwitch" v-model="form.is_published">
                  <label class="form-check-label" for="publishSwitch">Publicat</label>
                </div>
                <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                  {{ isEdit ? 'Actualizează' : 'Publică' }}
                </button>
              </div>
            </div>

            <div class="card bg-light border-0">
              <div class="card-body">
                <h6 class="card-title fw-bold mb-3">SEO</h6>
                <div class="mb-3">
                  <label class="form-label small">Meta Titlu</label>
                  <input type="text" class="form-control form-control-sm" v-model="form.meta_title">
                </div>
                <div class="mb-3">
                  <label class="form-label small">Meta Descriere</label>
                  <textarea class="form-control form-control-sm" rows="3" v-model="form.meta_description"></textarea>
                </div>
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
import axios from '@/services/http';
import { useToast } from 'vue-toastification';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const loading = ref(false);
const isEdit = ref(false);

const form = reactive({
  title: '',
  slug: '',
  content: '',
  is_published: true,
  meta_title: '',
  meta_description: ''
});

const generateSlug = () => {
  if (!isEdit.value) { // Only auto-generate on create to avoid accidental URL changes
    form.slug = form.title
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-');
  }
};

const fetchPage = async (id) => {
  try {
    const response = await axios.get(`/admin/pages/${id}`);
    Object.assign(form, response.data);
  } catch (error) {
    console.error('Error fetching page:', error);
    toast.error('Nu s-a putut încărca pagina.');
    router.push({ name: 'admin-pages' });
  }
};

const savePage = async () => {
  loading.value = true;
  try {
    if (isEdit.value) {
      await axios.put(`/admin/pages/${route.params.id}`, form);
      toast.success('Pagină actualizată cu succes!');
    } else {
      await axios.post('/admin/pages', form);
      toast.success('Pagină creată cu succes!');
      router.push({ name: 'admin-pages' });
    }
  } catch (error) {
    console.error('Error saving page:', error);
    if (error.response?.data?.errors) {
       // Display validation errors
       const errors = Object.values(error.response.data.errors).flat().join('\n');
       toast.error(errors);
    } else {
       toast.error('Eroare la salvarea paginii.');
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (route.params.id) {
    isEdit.value = true;
    fetchPage(route.params.id);
  }
});
</script>

<style>
/* Fix Quill Editor Height in Bootstrap Card */
.ql-container {
  min-height: 200px;
  font-size: 1rem;
}
</style>
