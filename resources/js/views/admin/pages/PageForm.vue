<template>
  <div class="container">
    <PageHeader :title="isEdit ? 'Editează pagină' : 'Pagină nouă'" subtitle="Gestionează conținutul și optimizarea SEO.">
      <template #actions>
        <RouterLink :to="{ name: 'admin-pages' }" class="btn btn-outline-secondary btn-sm me-2">
          Înapoi la listă
        </RouterLink>
        <button type="button" class="btn btn-primary btn-sm" @click="save">
          <i class="bi bi-save me-1"></i> Salvează
        </button>
      </template>
    </PageHeader>

    <div class="row">
      <div class="col-md-9">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white pt-3 pb-0 border-bottom-0">
            <ul class="nav nav-tabs card-header-tabs" id="pageTabs" role="tablist">
              <li class="nav-item">
                <button class="nav-link active" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab">Conținut</button>
              </li>
              <li class="nav-item">
                <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab">SEO & Meta</button>
              </li>
              <li class="nav-item">
                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">Setări</button>
              </li>
            </ul>
          </div>
          <div class="card-body">
            <div class="tab-content" id="pageTabsContent">
              <!-- Content Tab -->
              <div class="tab-pane fade show active" id="content" role="tabpanel">
                <div class="mb-4">
                  <label class="form-label fw-bold">Titlu Pagină</label>
                  <input v-model="form.title" type="text" class="form-control form-control-lg" placeholder="Introduceți titlul paginii" required @input="generateSlug" />
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold mb-2">Conținut</label>
                    <div class="quill-wrapper">
                         <QuillEditor v-model:content="form.content" contentType="html" theme="snow" toolbar="full" />
                    </div>
                </div>
              </div>

              <!-- SEO Tab -->
              <div class="tab-pane fade" id="seo" role="tabpanel">
                <div class="row">
                  <div class="col-lg-7">
                    <div class="mb-4">
                      <label class="form-label fw-bold">Meta Title</label>
                      <input v-model="form.meta_title" type="text" class="form-control" :class="{'is-invalid': metaTitleLength > 70, 'is-valid': metaTitleLength > 0 && metaTitleLength <= 60}" />
                      <div class="d-flex justify-content-between form-text mt-1">
                        <span>Titlul care apare în tab-ul browserului și în rezultatele Google.</span>
                        <span :class="metaTitleColor" class="fw-bold">{{ metaTitleLength }}/60</span>
                      </div>
                    </div>

                    <div class="mb-4">
                      <label class="form-label fw-bold">Meta Description</label>
                      <textarea v-model="form.meta_description" class="form-control" rows="3" :class="{'is-invalid': metaDescLength > 170, 'is-valid': metaDescLength > 0 && metaDescLength <= 160}"></textarea>
                      <div class="d-flex justify-content-between form-text mt-1">
                        <span>Descrierea scurtă pentru motoarele de căutare.</span>
                        <span :class="metaDescColor" class="fw-bold">{{ metaDescLength }}/160</span>
                      </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Slug (URL)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">/</span>
                            <input v-model="form.slug" type="text" class="form-control" />
                        </div>
                        <div class="form-text">Adresa URL a paginii. Se generează automat din titlu dacă este gol.</div>
                    </div>
                  </div>

                  <div class="col-lg-5">
                    <div class="card bg-light border-0">
                      <div class="card-body">
                        <h6 class="card-title text-muted mb-3 text-uppercase small fw-bold">Google Search Preview</h6>
                        
                        <!-- Google Preview Card -->
                        <div class="bg-white p-3 rounded shadow-sm border">
                          <div class="mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                    <i class="bi bi-globe text-muted small"></i>
                                </div>
                                <div class="d-flex flex-column" style="line-height: 1.2;">
                                    <span class="text-dark small">My B2B Shop</span>
                                    <span class="text-muted extra-small">https://b2b.demo/{{ form.slug || 'pagina-noua' }}</span>
                                </div>
                            </div>
                          </div>
                          <a href="#" class="text-decoration-none d-block mb-1" @click.prevent>
                            <h3 class="h5 text-primary text-truncate mb-0" style="color: #1a0dab !important; font-size: 1.25rem;">
                                {{ form.meta_title || form.title || 'Titlul Paginii' }}
                            </h3>
                          </a>
                          <div class="small text-muted" style="color: #4d5156 !important; font-size: 0.9rem; line-height: 1.58;">
                            {{ form.meta_description || 'Descrierea paginii va apărea aici. Este recomandat să adăugați o descriere relevantă pentru a atrage utilizatorii.' }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Settings Tab -->
              <div class="tab-pane fade" id="settings" role="tabpanel">
                <div class="mb-3 form-check form-switch p-3 border rounded bg-light">
                  <input v-model="form.is_published" class="form-check-input" type="checkbox" id="publishSwitch" style="cursor: pointer;">
                  <label class="form-check-label fw-bold ms-2" for="publishSwitch" style="cursor: pointer;">Publicată</label>
                  <div class="form-text ms-2">Dacă este dezactivată, pagina nu va fi vizibilă public, ci doar pentru administratori.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Sidebar / Actions Panel -->
      <div class="col-md-3">
         <div class="card shadow-sm mb-3">
             <div class="card-body">
                 <h6 class="card-title text-muted text-uppercase small fw-bold mb-3">Publicare</h6>
                 <div class="d-flex align-items-center justify-content-between mb-2">
                     <span class="text-muted">Status:</span>
                     <span :class="form.is_published ? 'badge bg-success' : 'badge bg-secondary'">
                         {{ form.is_published ? 'Publicat' : 'Ciornă' }}
                     </span>
                 </div>
                 <div class="d-grid gap-2 mt-3">
                     <button class="btn btn-primary" @click="save">
                        <i class="bi bi-check-lg me-1"></i> Salvează
                     </button>
                 </div>
             </div>
         </div>
         
         <div class="card shadow-sm" v-if="isEdit">
             <div class="card-body">
                 <h6 class="card-title text-muted text-uppercase small fw-bold mb-3">Informații</h6>
                 <ul class="list-unstyled small text-muted mb-0">
                     <li class="mb-2">
                         <i class="bi bi-clock me-1"></i> Creat: <br>
                         <span class="text-dark">{{ formatDate(pageData?.created_at) }}</span>
                     </li>
                     <li>
                         <i class="bi bi-pencil me-1"></i> Actualizat: <br>
                         <span class="text-dark">{{ formatDate(pageData?.updated_at) }}</span>
                     </li>
                 </ul>
             </div>
         </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { adminApi } from '@/services/http'
import PageHeader from '@/components/common/PageHeader.vue'
import { useToast } from 'vue-toastification'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const toast = useToast()
const route = useRoute()
const router = useRouter()

const isEdit = computed(() => !!route.params.id)
const pageData = ref(null)
const form = ref({
  title: '',
  slug: '',
  content: '',
  is_published: true,
  meta_title: '',
  meta_description: ''
})

// SEO Helpers
const metaTitleLength = computed(() => form.value.meta_title?.length || 0)
const metaDescLength = computed(() => form.value.meta_description?.length || 0)

const metaTitleColor = computed(() => {
    if (metaTitleLength.value === 0) return 'text-muted'
    if (metaTitleLength.value <= 60) return 'text-success'
    return 'text-danger'
})

const metaDescColor = computed(() => {
    if (metaDescLength.value === 0) return 'text-muted'
    if (metaDescLength.value <= 160) return 'text-success'
    return 'text-danger'
})

const generateSlug = () => {
    if (!isEdit.value && !form.value.slug && form.value.title) {
        form.value.slug = form.value.title
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
    }
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleString('ro-RO')
}

onMounted(async () => {
  if (isEdit.value) {
    try {
      const { data } = await adminApi.get(`/pages/${route.params.id}`)
      pageData.value = data
      form.value = {
        title: data.title || '',
        slug: data.slug || '',
        content: data.content || '',
        is_published: !!data.is_published,
        meta_title: data.meta_title || '',
        meta_description: data.meta_description || ''
      }
    } catch (e) {
      toast.error('Nu s-a putut încărca pagina.')
    }
  }
})

const save = async () => {
  try {
    if (isEdit.value) {
      await adminApi.put(`/pages/${route.params.id}`, form.value)
      toast.success('Pagina a fost actualizată.')
    } else {
      await adminApi.post('/pages', form.value)
      toast.success('Pagina a fost creată.')
    }
    router.push({ name: 'admin-pages' })
  } catch (e) {
    toast.error(e.response?.data?.message || 'Salvarea a eșuat.')
  }
}
</script>

<style>
/* Quill Editor Customization */
.quill-wrapper {
    background: white;
    border-radius: 4px;
}
.ql-toolbar.ql-snow {
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
    border-color: #dee2e6;
}
.ql-container.ql-snow {
    border-bottom-left-radius: 4px;
    border-bottom-right-radius: 4px;
    border-color: #dee2e6;
    height: 500px;
    font-family: inherit;
    font-size: 1rem;
}

.extra-small {
    font-size: 0.75rem;
}
</style>
