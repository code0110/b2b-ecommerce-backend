<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Comenzi recurente</h1>
        <p class="small text-muted mb-0">
          Salvează liste de produse frecvente și adaugă-le rapid în coș.
        </p>
      </div>
      <button
        type="button"
        class="btn btn-orange btn-sm"
        @click="startCreate"
      >
        Template nou
      </button>
    </div>

    <div v-if="error" class="alert alert-danger small mb-3">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border spinner-border-sm" role="status" />
      <div class="small text-muted mt-2">
        Se încarcă template-urile...
      </div>
    </div>

    <div v-else>
      <div v-if="templates.length === 0" class="alert alert-info small">
        Nu există template-uri de comenzi salvate.
      </div>

      <div v-else class="card shadow-sm mb-3">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr class="small text-muted">
                <th>Nume template</th>
                <th>Nr. produse</th>
                <th>Ultima utilizare</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tpl in templates" :key="tpl.id">
                <td>{{ tpl.name }}</td>
                <td>{{ tpl.lines_count ?? (tpl.lines?.length || 0) }}</td>
                <td>
                  {{
                    tpl.last_used_at
                      ? formatDateTime(tpl.last_used_at)
                      : 'Niciodată'
                  }}
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-sm"
                      @click="editTemplate(tpl)"
                    >
                      Editează
                    </button>
                    <button
                      type="button"
                      class="btn btn-orange btn-sm"
                      @click="addToCart(tpl)"
                    >
                      Adaugă în coș
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-sm"
                      @click="confirmDelete(tpl)"
                    >
                      Șterge
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Formular add/edit template -->
      <div v-if="editingTemplate" class="card shadow-sm">
        <div class="card-body small">
          <div class="d-flex justify-content-between mb-2">
            <h2 class="h6 mb-0">
              {{ editingTemplate.id ? 'Editează template' : 'Template nou' }}
            </h2>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="editingTemplate = null"
            >
              Renunță
            </button>
          </div>

          <form class="vstack gap-3" @submit.prevent="submitTemplate">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label form-label-sm">Nume template</label>
                <input
                  v-model="editingTemplate.name"
                  type="text"
                  class="form-control form-control-sm"
                  required
                />
              </div>
              <div class="col-md-6">
                <label class="form-label form-label-sm">Observații interne</label>
                <input
                  v-model="editingTemplate.notes"
                  type="text"
                  class="form-control form-control-sm"
                />
              </div>
            </div>

            <div>
              <label class="form-label form-label-sm">Produse</label>
              <p class="text-muted small mb-2">
                Lista de produse este generată din comenzile tale – editarea linilor se poate
                face în admin sau printr-un ecran dedicat (de implementat separat dacă e
                nevoie).
              </p>
              <ul class="list-unstyled mb-0">
                <li
                  v-for="line in editingTemplate.lines || []"
                  :key="line.id || line.product_id"
                  class="d-flex justify-content-between align-items-center border rounded p-2 mb-1"
                >
                  <div>
                    {{ line.product_name }}
                    <span class="text-muted">
                      ({{ line.product_code }})
                    </span>
                  </div>
                  <div>
                    x {{ line.quantity }}
                  </div>
                </li>
                <li
                  v-if="!editingTemplate.lines || editingTemplate.lines.length === 0"
                  class="text-muted"
                >
                  Momentan nu sunt linii afișate (se preiau de regulă dintr-o comandă
                  existentă).
                </li>
              </ul>
            </div>

            <div class="d-flex justify-content-end">
              <button
                type="submit"
                class="btn btn-orange btn-sm"
                :disabled="saving"
              >
                <span
                  v-if="saving"
                  class="spinner-border spinner-border-sm me-1"
                ></span>
                Salvează template
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import {
  fetchOrderTemplates,
  createOrderTemplate,
  updateOrderTemplate,
  deleteOrderTemplate,
  triggerTemplateToCart,
} from '@/services/account/recurringOrders';

const loading = ref(false);
const saving = ref(false);
const error = ref('');

const templates = ref([]);
const editingTemplate = ref(null);

const formatDateTime = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleString('ro-RO');
};

const loadTemplates = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchOrderTemplates();
    templates.value = data.data ?? data;
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut încărca template-urile.';
  } finally {
    loading.value = false;
  }
};

const startCreate = () => {
  editingTemplate.value = {
    id: null,
    name: '',
    notes: '',
    lines: [],
  };
};

const editTemplate = (tpl) => {
  editingTemplate.value = { ...tpl };
};

const submitTemplate = async () => {
  if (!editingTemplate.value) return;
  saving.value = true;
  error.value = '';

  try {
    const payload = { ...editingTemplate.value };
    if (payload.id) {
      await updateOrderTemplate(payload.id, payload);
    } else {
      await createOrderTemplate(payload);
    }
    editingTemplate.value = null;
    await loadTemplates();
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut salva template-ul.';
  } finally {
    saving.value = false;
  }
};

const addToCart = async (tpl) => {
  try {
    await triggerTemplateToCart(tpl.id);
    alert(
      'Produsele din acest template au fost adăugate în coș.'
    );
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut adăuga în coș.';
  }
};

const confirmDelete = async (tpl) => {
  if (!window.confirm('Sigur dorești să ștergi acest template?')) return;
  try {
    await deleteOrderTemplate(tpl.id);
    await loadTemplates();
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut șterge template-ul.';
  }
};

onMounted(loadTemplates);
</script>
