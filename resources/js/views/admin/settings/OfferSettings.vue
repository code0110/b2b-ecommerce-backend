<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0 text-gray-800">Reguli Discount & Aprobare</h1>
        <p class="text-muted small mb-0">Gestionează limitele de discount și pragurile de aprobare pentru oferte.</p>
      </div>
      <button class="btn btn-primary" @click="openModal()">
        <i class="bi bi-plus-lg me-2"></i> Adaugă Regulă
      </button>
    </div>

    <!-- Rules Table -->
    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4">Nume Regulă</th>
                <th>Tip</th>
                <th>Target</th>
                <th>Limită (%)</th>
                <th>Opțiuni</th>
                <th>Status</th>
                <th class="text-end pe-4">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading" class="text-center">
                <td colspan="7" class="py-4 text-muted">Se încarcă regulile...</td>
              </tr>
              <tr v-else-if="rules.length === 0" class="text-center">
                <td colspan="7" class="py-4 text-muted">Nu există reguli definite.</td>
              </tr>
              <tr v-for="rule in rules" :key="rule.id">
                <td class="ps-4 fw-semibold">{{ rule.name }}</td>
                <td>
                  <span v-if="rule.rule_type === 'approval_threshold'" class="badge bg-info text-dark">Prag Aprobare</span>
                  <span v-else class="badge bg-danger">Max Discount</span>
                </td>
                <td>
                  <div v-if="rule.target_type === 'global'" class="badge bg-primary">Global</div>
                  <div v-else-if="rule.target_type === 'role'" class="badge bg-warning text-dark">
                    Rol: {{ getRoleName(rule.target_id) }}
                  </div>
                  <div v-else class="badge bg-success">
                    User: {{ getUserName(rule.target_id) }}
                  </div>
                </td>
                <td class="fw-bold text-primary">{{ Number(rule.limit_percent) }}%</td>
                <td class="small">
                  <div v-if="!rule.apply_to_total" class="text-muted"><i class="bi bi-layers me-1"></i>Doar manual</div>
                  <div v-else class="text-success"><i class="bi bi-layers-fill me-1"></i>Manual + Promo</div>
                </td>
                <td>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" :checked="rule.active" @change="toggleActive(rule)">
                  </div>
                </td>
                <td class="text-end pe-4">
                  <button class="btn btn-sm btn-outline-secondary me-2" @click="openModal(rule)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteRule(rule)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingRule ? 'Editare Regulă' : 'Adăugare Regulă Nouă' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveRule">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label">Nume Regulă</label>
                  <input type="text" class="form-control" v-model="form.name" required placeholder="Ex: Limită Junior Sales">
                </div>

                <div class="col-md-6">
                  <label class="form-label">Tip Regulă</label>
                  <select class="form-select" v-model="form.rule_type" required>
                    <option value="approval_threshold">Prag Aprobare Director</option>
                    <option value="max_discount">Discount Maxim Admisibil</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Valoare Limită (%)</label>
                  <div class="input-group">
                    <input type="number" class="form-control" v-model="form.limit_percent" required min="0" max="100" step="0.01">
                    <span class="input-group-text">%</span>
                  </div>
                </div>

                <div class="col-12">
                  <hr class="my-2">
                  <label class="form-label fw-bold">Targetare</label>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Aplică pentru</label>
                  <select class="form-select" v-model="form.target_type">
                    <option value="global">Global (Toți)</option>
                    <option value="role">Rol / Grup</option>
                    <option value="user">Utilizator Specific</option>
                  </select>
                </div>

                <div class="col-md-8" v-if="form.target_type === 'role'">
                  <label class="form-label">Selectează Rolul</label>
                  <select class="form-select" v-model="form.target_id">
                    <option v-for="role in options.roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                  </select>
                </div>

                <div class="col-md-8" v-if="form.target_type === 'user'">
                  <label class="form-label">Selectează Utilizatorul</label>
                  <select class="form-select" v-model="form.target_id">
                    <option v-for="user in options.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                  </select>
                </div>

                <div class="col-12">
                  <hr class="my-2">
                  <label class="form-label fw-bold">Setări Avansate</label>
                </div>

                <div class="col-md-12">
                  <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="applyTotal" v-model="form.apply_to_total">
                    <label class="form-check-label" for="applyTotal">
                      Include Promoțiile existente?
                      <div class="text-muted small">
                        Dacă este activat, limita se aplică la suma (Discount Manual + Promoție Produs).
                        Dacă este dezactivat, limita se aplică doar la Discountul Manual.
                      </div>
                    </label>
                  </div>

                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="active" v-model="form.active">
                    <label class="form-check-label" for="active">Activă</label>
                  </div>
                </div>
              </div>

              <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" @click="closeModal">Anulează</button>
                <button type="submit" class="btn btn-primary">Salvează</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { adminApi } from '@/services/http';
import { useToast } from 'vue-toastification';

const toast = useToast();
const rules = ref([]);
const loading = ref(false);
const showModal = ref(false);
const editingRule = ref(null);
const options = reactive({
  roles: [],
  users: []
});

const form = reactive({
  name: '',
  rule_type: 'approval_threshold',
  target_type: 'global',
  target_id: null,
  limit_percent: 0,
  apply_to_total: true,
  active: true
});

onMounted(async () => {
  await loadOptions();
  await loadRules();
});

const loadOptions = async () => {
  try {
    const { data } = await adminApi.get('/discount-rules/options');
    options.roles = data.roles;
    options.users = data.users;
  } catch (e) {
    console.error(e);
  }
};

const loadRules = async () => {
  loading.value = true;
  try {
    const { data } = await adminApi.get('/discount-rules');
    rules.value = data;
  } catch (e) {
    toast.error('Nu s-au putut încărca regulile.');
  } finally {
    loading.value = false;
  }
};

const getRoleName = (id) => options.roles.find(r => r.id === id)?.name || id;
const getUserName = (id) => options.users.find(u => u.id === id)?.name || id;

const openModal = (rule = null) => {
  if (rule) {
    editingRule.value = rule;
    Object.assign(form, { ...rule });
    // Ensure numeric values are numbers
    form.limit_percent = Number(form.limit_percent);
    // Convert boolean 1/0 to true/false if coming from DB as int
    form.apply_to_total = !!rule.apply_to_total;
    form.active = !!rule.active;
  } else {
    editingRule.value = null;
    Object.assign(form, {
      name: '',
      rule_type: 'approval_threshold',
      target_type: 'global',
      target_id: null,
      limit_percent: 0,
      apply_to_total: true,
      active: true
    });
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingRule.value = null;
};

const saveRule = async () => {
  try {
    // Validate target_id
    if (form.target_type !== 'global' && !form.target_id) {
        toast.error('Selectează Rolul sau Utilizatorul țintă.');
        return;
    }

    if (editingRule.value) {
      await adminApi.put(`/discount-rules/${editingRule.value.id}`, form);
      toast.success('Regula a fost actualizată.');
    } else {
      await adminApi.post('/discount-rules', form);
      toast.success('Regula a fost creată.');
    }
    closeModal();
    loadRules();
  } catch (e) {
    console.error(e);
    toast.error('Eroare la salvare.');
  }
};

const deleteRule = async (rule) => {
  if (!confirm('Sigur vrei să ștergi această regulă?')) return;
  try {
    await adminApi.delete(`/discount-rules/${rule.id}`);
    toast.success('Regulă ștearsă.');
    loadRules();
  } catch (e) {
    toast.error('Eroare la ștergere.');
  }
};

const toggleActive = async (rule) => {
    try {
        await adminApi.put(`/discount-rules/${rule.id}`, {
            ...rule,
            active: !rule.active
        });
        rule.active = !rule.active;
        toast.success('Status actualizat.');
    } catch (e) {
        toast.error('Eroare la actualizare status.');
    }
}
</script>
