<template>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Blocuri de Conținut</h5>
      <button class="btn btn-primary btn-sm" @click="openCreateModal">
        <i class="bi bi-plus-lg me-1"></i> Adaugă Bloc
      </button>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">Titlu / Cheie</th>
              <th>Grup</th>
              <th>Tip</th>
              <th>Status</th>
              <th class="text-end pe-4">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="block in blocks" :key="block.id">
              <td class="ps-4">
                <div class="fw-bold text-dark">{{ block.title || block.key }}</div>
                <div class="small text-muted font-monospace">{{ block.key }}</div>
              </td>
              <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ block.group }}</span></td>
              <td><span class="badge border fw-normal text-dark">{{ block.type }}</span></td>
              <td>
                <span v-if="block.is_active" class="badge bg-success">Activ</span>
                <span v-else class="badge bg-danger">Inactiv</span>
              </td>
              <td class="text-end pe-4">
                <button class="btn btn-sm btn-outline-primary me-2" @click="editBlock(block)" title="Editează">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(block)" title="Șterge">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="blocks.length === 0">
              <td colspan="5" class="text-center py-5 text-muted">
                Nu există blocuri de conținut.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit/Create Modal -->
    <div class="modal fade" id="blockModal" tabindex="-1" aria-hidden="true" ref="modalRef">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEditing ? 'Editează Bloc' : 'Adaugă Bloc Nou' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveBlock">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Cheie Unică (Key)</label>
                  <input type="text" class="form-control" v-model="form.key" :disabled="isEditing" required>
                  <div class="form-text">Identificator unic pentru cod (ex: home_hero_title)</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Grup</label>
                  <input type="text" class="form-control" v-model="form.group" required>
                  <div class="form-text">Ex: home, footer, contact</div>
                </div>
                <div class="col-md-12">
                  <label class="form-label">Titlu descriptiv (Admin)</label>
                  <input type="text" class="form-control" v-model="form.title">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tip Conținut</label>
                  <select class="form-select" v-model="form.type" required>
                    <option value="text">Text Simplu</option>
                    <option value="html">HTML</option>
                    <option value="image">Imagine (URL)</option>
                    <option value="json">JSON (Structurat)</option>
                  </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                   <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="activeSwitch" v-model="form.is_active">
                    <label class="form-check-label" for="activeSwitch">Activ</label>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label">Conținut</label>
                  <textarea 
                    v-if="form.type === 'json'"
                    class="form-control font-monospace" 
                    rows="10" 
                    v-model="form.content"
                    placeholder="{ ... }"
                  ></textarea>
                  <textarea 
                    v-else-if="form.type === 'html'"
                    class="form-control font-monospace" 
                    rows="10" 
                    v-model="form.content"
                  ></textarea>
                  <input 
                    v-else 
                    type="text" 
                    class="form-control" 
                    v-model="form.content"
                  >
                  <div v-if="form.type === 'json'" class="form-text text-warning">
                    <i class="bi bi-exclamation-triangle"></i> Atenție: Asigură-te că JSON-ul este valid!
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal">Anulează</button>
            <button type="button" class="btn btn-primary" @click="saveBlock" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
              Salvează
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from '@/services/http'; // Use configured axios instance
import { Modal } from 'bootstrap';
import { useToast } from 'vue-toastification';

const blocks = ref([]);
const loading = ref(false);
const modalRef = ref(null);
let modalInstance = null;
const isEditing = ref(false);
const toast = useToast();

const form = reactive({
  id: null,
  key: '',
  group: '',
  type: 'text',
  content: '',
  title: '',
  is_active: true
});

const fetchBlocks = async () => {
  try {
    const response = await axios.get('/admin/content-blocks');
    if (response.data && response.data.data) {
        blocks.value = response.data.data;
    } else {
        blocks.value = response.data;
    }
  } catch (error) {
    console.error('Error fetching blocks:', error);
    toast.error('Nu s-au putut încărca blocurile de conținut.');
  }
};

onMounted(() => {
  fetchBlocks();
  modalInstance = new Modal(modalRef.value);
});

const openCreateModal = () => {
  isEditing.value = false;
  resetForm();
  modalInstance.show();
};

const editBlock = (block) => {
  isEditing.value = true;
  Object.assign(form, block);
  // Ensure content is string for textarea if json
  if (form.type === 'json' && typeof form.content !== 'string') {
      // It might be coming as object if backend casts it?
      // Check backend controller. API usually returns JSON response, so Laravel cast 'array' or 'object' might make it an object in JS.
      // Wait, axios response.data will have parsed JSON if Content-Type is application/json.
      // But the model casts 'content' -> we didn't add a cast for content in Model yet, only meta.
      // Let's check ContentBlock.php.
  }
  
  // If content is stored as string in DB but we want to edit JSON, we just show string.
  // If we cast it in model, we need to stringify it back for editing.
  if (typeof form.content === 'object' && form.content !== null) {
      form.content = JSON.stringify(form.content, null, 2);
  }

  modalInstance.show();
};

const resetForm = () => {
  Object.assign(form, {
    id: null,
    key: '',
    group: '',
    type: 'text',
    content: '',
    title: '',
    is_active: true
  });
};

const closeModal = () => {
  modalInstance.hide();
  resetForm();
};

const saveBlock = async () => {
  loading.value = true;
  try {
    const payload = { ...form };
    
    // Validate JSON if type is json
    if (payload.type === 'json') {
      try {
        JSON.parse(payload.content);
      } catch (e) {
        toast.error('JSON invalid! Verifică sintaxa.');
        loading.value = false;
        return;
      }
    }

    if (isEditing.value) {
      await axios.put(`/admin/content-blocks/${form.id}`, payload);
      toast.success('Bloc actualizat cu succes!');
    } else {
      await axios.post('/admin/content-blocks', payload);
      toast.success('Bloc creat cu succes!');
    }
    closeModal();
    fetchBlocks();
  } catch (error) {
    console.error('Error saving block:', error);
    toast.error('Eroare la salvarea blocului.');
  } finally {
    loading.value = false;
  }
};

const confirmDelete = async (block) => {
  if (confirm(`Sigur vrei să ștergi blocul "${block.title || block.key}"?`)) {
    try {
      await axios.delete(`/admin/content-blocks/${block.id}`);
      toast.success('Bloc șters cu succes!');
      fetchBlocks();
    } catch (error) {
      console.error('Error deleting block:', error);
      toast.error('Nu s-a putut șterge blocul.');
    }
  }
};
</script>
