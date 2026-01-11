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
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0">Conținut</label>
                <div class="btn-group btn-group-sm" role="group" aria-label="Editor mode">
                  <input class="btn-check" type="radio" name="editorMode" id="editorModeSections" value="sections" v-model="editorMode">
                  <label class="btn btn-outline-secondary" for="editorModeSections">Builder</label>
                  <input class="btn-check" type="radio" name="editorMode" id="editorModeHtml" value="content" v-model="editorMode">
                  <label class="btn btn-outline-secondary" for="editorModeHtml">HTML</label>
                </div>
              </div>

              <div v-if="editorMode === 'sections'">
                <div class="d-flex flex-wrap gap-2 mb-3">
                  <button type="button" class="btn btn-outline-primary btn-sm" @click="addSection('hero')">
                    <i class="bi bi-plus-lg me-1"></i> Hero
                  </button>
                  <button type="button" class="btn btn-outline-primary btn-sm" @click="addSection('banners')">
                    <i class="bi bi-plus-lg me-1"></i> Bannere
                  </button>
                  <button type="button" class="btn btn-outline-primary btn-sm" @click="addSection('rich_text')">
                    <i class="bi bi-plus-lg me-1"></i> Text
                  </button>
                  <button type="button" class="btn btn-outline-primary btn-sm" @click="addSection('features')">
                    <i class="bi bi-plus-lg me-1"></i> Beneficii
                  </button>
                </div>

                <div v-if="!form.sections.length" class="alert alert-light border small">
                  Adaugă secțiuni pentru a construi pagina (stil CMS).
                </div>

                <div v-else class="d-flex flex-column gap-3">
                  <div v-for="(section, idx) in form.sections" :key="section._id" class="card border">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                      <div class="fw-semibold">
                        {{ idx + 1 }}. {{ sectionLabel(section.type) }}
                      </div>
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" :disabled="idx === 0" @click="moveSection(idx, -1)" title="Mută sus">
                          <i class="bi bi-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" :disabled="idx === form.sections.length - 1" @click="moveSection(idx, 1)" title="Mută jos">
                          <i class="bi bi-arrow-down"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" @click="removeSection(idx)" title="Șterge secțiunea">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </div>
                    <div class="card-body">
                      <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" :id="`section-active-${section._id}`" v-model="section.is_active">
                        <label class="form-check-label" :for="`section-active-${section._id}`">Activă</label>
                      </div>

                      <div v-if="section.type === 'hero'" class="row g-3">
                        <div class="col-12">
                          <label class="form-label small">Titlu</label>
                          <input type="text" class="form-control" v-model="section.title">
                        </div>
                        <div class="col-12">
                          <label class="form-label small">Subtitlu</label>
                          <textarea class="form-control" rows="2" v-model="section.subtitle"></textarea>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label small">Text buton</label>
                          <input type="text" class="form-control" v-model="section.cta_text">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label small">Link buton</label>
                          <input type="text" class="form-control" v-model="section.cta_url" placeholder="/promotii">
                        </div>
                        <div class="col-12">
                          <label class="form-label small">Imagine (URL)</label>
                          <input type="text" class="form-control" v-model="section.image_url" placeholder="https://...">
                        </div>
                      </div>

                      <div v-else-if="section.type === 'banners'" class="d-flex flex-column gap-3">
                        <div class="d-flex justify-content-between align-items-center">
                          <div class="small text-muted">Bannere</div>
                          <button type="button" class="btn btn-outline-secondary btn-sm" @click="addBannerItem(section)">
                            <i class="bi bi-plus-lg me-1"></i> Adaugă banner
                          </button>
                        </div>

                        <div v-if="!section.items.length" class="alert alert-light border small mb-0">
                          Adaugă unul sau mai multe bannere.
                        </div>

                        <div v-else class="d-flex flex-column gap-3">
                          <div v-for="(item, itemIdx) in section.items" :key="item._id" class="border rounded p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                              <div class="fw-semibold small">Banner {{ itemIdx + 1 }}</div>
                              <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" :disabled="itemIdx === 0" @click="moveBannerItem(section, itemIdx, -1)">
                                  <i class="bi bi-arrow-up"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" :disabled="itemIdx === section.items.length - 1" @click="moveBannerItem(section, itemIdx, 1)">
                                  <i class="bi bi-arrow-down"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" @click="removeBannerItem(section, itemIdx)">
                                  <i class="bi bi-trash"></i>
                                </button>
                              </div>
                            </div>
                            <div class="row g-3">
                              <div class="col-md-6">
                                <label class="form-label small">Titlu</label>
                                <input type="text" class="form-control" v-model="item.title">
                              </div>
                              <div class="col-md-6">
                                <label class="form-label small">Link</label>
                                <input type="text" class="form-control" v-model="item.url" placeholder="/categorie/...">
                              </div>
                              <div class="col-12">
                                <label class="form-label small">Imagine (URL)</label>
                                <input type="text" class="form-control" v-model="item.image_url" placeholder="https://...">
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div v-else-if="section.type === 'rich_text'">
                        <label class="form-label small">Text (HTML)</label>
                        <AppEditor
                          v-model="section.html"
                          height="280px"
                        />
                      </div>

                      <div v-else-if="section.type === 'features'" class="d-flex flex-column gap-3">
                        <div class="row g-3">
                          <div class="col-12">
                            <label class="form-label small">Titlu</label>
                            <input type="text" class="form-control" v-model="section.title">
                          </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                          <div class="small text-muted">Beneficii</div>
                          <button type="button" class="btn btn-outline-secondary btn-sm" @click="addFeatureItem(section)">
                            <i class="bi bi-plus-lg me-1"></i> Adaugă beneficiu
                          </button>
                        </div>
                        <div v-if="!section.items.length" class="alert alert-light border small mb-0">
                          Adaugă elemente de tip beneficiu (icon + titlu + descriere).
                        </div>
                        <div v-else class="d-flex flex-column gap-3">
                          <div v-for="(item, itemIdx) in section.items" :key="item._id" class="border rounded p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                              <div class="fw-semibold small">Beneficiu {{ itemIdx + 1 }}</div>
                              <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" :disabled="itemIdx === 0" @click="moveFeatureItem(section, itemIdx, -1)">
                                  <i class="bi bi-arrow-up"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" :disabled="itemIdx === section.items.length - 1" @click="moveFeatureItem(section, itemIdx, 1)">
                                  <i class="bi bi-arrow-down"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" @click="removeFeatureItem(section, itemIdx)">
                                  <i class="bi bi-trash"></i>
                                </button>
                              </div>
                            </div>
                            <div class="row g-3">
                              <div class="col-md-4">
                                <label class="form-label small">Icon (Bootstrap Icons)</label>
                                <input type="text" class="form-control" v-model="item.icon" placeholder="bi-truck">
                              </div>
                              <div class="col-md-8">
                                <label class="form-label small">Titlu</label>
                                <input type="text" class="form-control" v-model="item.title">
                              </div>
                              <div class="col-12">
                                <label class="form-label small">Descriere</label>
                                <textarea class="form-control" rows="2" v-model="item.description"></textarea>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div v-else class="alert alert-warning small mb-0">
                        Tip de secțiune necunoscut: {{ section.type }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else>
                <AppEditor v-model="form.content" height="400px" />
              </div>
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
import { ref, onMounted, reactive, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '@/services/http';
import { useToast } from 'vue-toastification';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import AppEditor from '@/components/AppEditor.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const loading = ref(false);
const isEdit = ref(false);

const form = reactive({
  title: '',
  slug: '',
  content: '',
  sections: [],
  is_published: true,
  meta_title: '',
  meta_description: ''
});

const editorMode = ref('sections');
// AppEditor handles its own initialization, so we don't need manual Quill logic here anymore.

const uid = () => Math.random().toString(16).slice(2) + '-' + Date.now().toString(16);

const sectionLabel = (type) => {
  const map = {
    hero: 'Hero',
    banners: 'Bannere',
    rich_text: 'Text',
    features: 'Beneficii',
  };
  return map[type] || type;
};

const createSection = (type) => {
  if (type === 'hero') {
    return {
      _id: uid(),
      type,
      is_active: true,
      title: '',
      subtitle: '',
      cta_text: 'Deschide catalogul',
      cta_url: '/',
      image_url: '',
    };
  }
  if (type === 'banners') {
    return {
      _id: uid(),
      type,
      is_active: true,
      items: [],
    };
  }
  if (type === 'rich_text') {
    return {
      _id: uid(),
      type,
      is_active: true,
      html: '',
    };
  }
  if (type === 'features') {
    return {
      _id: uid(),
      type,
      is_active: true,
      title: '',
      items: [],
    };
  }
  return {
    _id: uid(),
    type,
    is_active: true,
  };
};

const addSection = (type) => {
  form.sections.push(createSection(type));
};

const removeSection = (idx) => {
  form.sections.splice(idx, 1);
};

const moveSection = (idx, delta) => {
  const next = idx + delta;
  if (next < 0 || next >= form.sections.length) return;
  const copy = [...form.sections];
  const tmp = copy[idx];
  copy[idx] = copy[next];
  copy[next] = tmp;
  form.sections.splice(0, form.sections.length, ...copy);
};

const addBannerItem = (section) => {
  section.items.push({
    _id: uid(),
    title: '',
    image_url: '',
    url: '',
  });
};

const moveBannerItem = (section, idx, delta) => {
  const next = idx + delta;
  if (next < 0 || next >= section.items.length) return;
  const copy = [...section.items];
  const tmp = copy[idx];
  copy[idx] = copy[next];
  copy[next] = tmp;
  section.items.splice(0, section.items.length, ...copy);
};

const removeBannerItem = (section, idx) => {
  section.items.splice(idx, 1);
};

const addFeatureItem = (section) => {
  section.items.push({
    _id: uid(),
    icon: 'bi-check-circle',
    title: '',
    description: '',
  });
};

const moveFeatureItem = (section, idx, delta) => {
  const next = idx + delta;
  if (next < 0 || next >= section.items.length) return;
  const copy = [...section.items];
  const tmp = copy[idx];
  copy[idx] = copy[next];
  copy[next] = tmp;
  section.items.splice(0, section.items.length, ...copy);
};

const removeFeatureItem = (section, idx) => {
  section.items.splice(idx, 1);
};

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
    form.sections = Array.isArray(response.data?.sections) ? response.data.sections : [];
    form.sections = form.sections.map((s) => ({
      _id: uid(),
      is_active: s?.is_active !== false,
      ...s,
      items: Array.isArray(s?.items) ? s.items.map((it) => ({ _id: uid(), ...it })) : (Array.isArray(s?.items) ? s.items : []),
    }));
    editorMode.value = form.sections.length ? 'sections' : 'content';
  } catch (error) {
    console.error('Error fetching page:', error);
    toast.error('Nu s-a putut încărca pagina.');
    router.push({ name: 'admin-pages' });
  }
};

const savePage = async () => {
  loading.value = true;
  try {
    const payload = {
      ...form,
      sections: editorMode.value === 'sections'
        ? form.sections.map(({ _id, ...s }) => ({
            ...s,
            items: Array.isArray(s.items) ? s.items.map(({ _id: itemId, ...it }) => it) : s.items,
          }))
        : [],
    };
    if (isEdit.value) {
      await axios.put(`/admin/pages/${route.params.id}`, payload);
      toast.success('Pagină actualizată cu succes!');
    } else {
      await axios.post('/admin/pages', payload);
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
  } else {
    editorMode.value = 'sections';
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
