<template>
  <div class="agent-dashboard">
    <h1 class="h3 mb-4">Panou Control Vânzări</h1>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <template v-else>
      <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
          <button 
            class="nav-link" 
            :class="{ active: activeTab === 'clients' }" 
            @click="activeTab = 'clients'"
          >
            Clienți
          </button>
        </li>
        <li class="nav-item" v-if="isDirector">
          <button 
            class="nav-link" 
            :class="{ active: activeTab === 'agents' }" 
            @click="activeTab = 'agents'"
          >
            Agenți
          </button>
        </li>
      </ul>

      <!-- Tab Clienti -->
      <div v-if="activeTab === 'clients'">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <input 
            type="text" 
            class="form-control w-auto" 
            placeholder="Caută client..." 
            v-model="searchClient"
          >
          <div class="form-check ms-2" v-if="isDirector">
            <input class="form-check-input" type="checkbox" id="agentOnly" v-model="showAgentClientsOnly">
            <label class="form-check-label small" for="agentOnly">
              Doar clienții agenților mei
            </label>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Nume Client</th>
                <th>CUI / CIF</th>
                <th>Sold Curent</th>
                <th>Limită Credit</th>
                <th v-if="isDirector">Agent</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="client in filteredClients" :key="client.id">
                <td>
                  <div class="fw-bold">{{ client.name }}</div>
                  <small class="text-muted">{{ client.email }}</small>
                </td>
                <td>{{ client.cif }}</td>
                <td :class="client.current_balance > 0 ? 'text-danger' : 'text-success'">
                  {{ formatPrice(client.current_balance, client.currency) }}
                </td>
                <td>{{ formatPrice(client.credit_limit, client.currency) }}</td>
                <td v-if="isDirector">
                    <span v-if="client.agent_user_id === authStore.user.id" class="badge bg-info">Eu</span>
                    <span v-else-if="client.agent" class="badge bg-secondary">
                      {{ [client.agent.first_name, client.agent.last_name].filter(Boolean).join(' ') }}
                    </span>
                    <span v-else class="text-muted">-</span>
                </td>
                <td class="text-end">
                  <button 
                    class="btn btn-sm btn-success me-2"
                    @click="openPaymentModal(client)"
                    title="Adaugă Încasare"
                  >
                    <i class="bi bi-cash-stack"></i> Încasare
                  </button>
                  <button 
                    class="btn btn-sm btn-primary"
                    @click="impersonateClient(client)"
                    title="Plasează Comandă"
                  >
                    <i class="bi bi-cart-plus"></i> Comandă
                  </button>
                </td>
              </tr>
              <tr v-if="filteredClients.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">
                  Nu s-au găsit clienți.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab Agenti -->
      <div v-if="activeTab === 'agents'">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Nume Agent</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Clienți Asignați</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="agent in agents" :key="agent.id">
                <td>{{ agent.first_name }} {{ agent.last_name }}</td>
                <td>{{ agent.email }}</td>
                <td>{{ agent.phone }}</td>
                <td>{{ agent.customers_count || 0 }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Modal Incasare -->
    <div v-if="showPaymentModal" class="modal-backdrop fade show"></div>
    <div 
      class="modal fade" 
      :class="{ show: showPaymentModal }" 
      style="display: block;" 
      v-if="showPaymentModal"
      tabindex="-1"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Înregistrează Încasare</h5>
            <button type="button" class="btn-close" @click="closePaymentModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="!receiptBook && paymentForm.instrument === 'numerar'" class="alert alert-danger">
                Nu aveți un chitanțier activ asignat. Contactați administratorul pentru încasări numerar.
            </div>

            <div v-if="selectedClient" class="mb-3 p-2 bg-light rounded">
              <div class="row">
                  <div class="col-md-6">
                      <strong>Client:</strong> {{ selectedClient.name }}
                  </div>
                  <div class="col-md-6 text-end">
                      <strong>Sold Total:</strong> {{ formatPrice(selectedClient.current_balance, selectedClient.currency) }}
                  </div>
              </div>
              <div class="row mt-2" v-if="receiptBook && paymentForm.instrument === 'numerar'">
                  <div class="col-12 d-flex justify-content-between align-items-center">
                      <div class="text-muted small">
                        <strong>Chitanțier Activ:</strong> {{ receiptBook.series }} (Următorul nr: {{ receiptBook.current_number }})
                      </div>
                      <button class="btn btn-sm btn-outline-danger" type="button" @click="cancelCurrentReceipt">
                        <i class="bi bi-x-circle me-1"></i>Anulează Chitanța Curentă
                      </button>
                  </div>
              </div>
            </div>

            <form @submit.prevent="submitPayment" v-if="receiptBook || paymentForm.instrument !== 'numerar'">
              <div class="row">
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Instrument de Plată</label>
                      <select v-model="paymentForm.instrument" class="form-select" required>
                          <option value="numerar">Numerar (Chitanță)</option>
                          <option value="bo">Bilet la Ordin (BO)</option>
                          <option value="cec">CEC</option>
                      </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Data Înregistrării</label>
                    <input 
                      type="date" 
                      v-model="paymentForm.payment_date" 
                      class="form-control" 
                      required
                    >
                  </div>
              </div>

              <!-- BO/CEC Fields -->
              <div class="row p-2 mb-3 bg-light border rounded" v-if="['bo', 'cec'].includes(paymentForm.instrument)">
                  <h6 class="mb-3">Detalii Instrument</h6>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Serie</label>
                      <input type="text" class="form-control" v-model="paymentForm.series" required placeholder="Ex: BTRL">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Număr</label>
                      <input type="text" class="form-control" v-model="paymentForm.number" required placeholder="Ex: 012345">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Banca Emitentă</label>
                      <input type="text" class="form-control" v-model="paymentForm.bank" required placeholder="Ex: Banca Transilvania">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Data Scadenței</label>
                      <input type="date" class="form-control" v-model="paymentForm.due_date" required>
                  </div>
              </div>

              <div class="row">
                  <div class="col-md-12 mb-3">
                    <label class="form-label">Mod Alocare</label>
                    <select v-model="paymentForm.type" class="form-select" required @change="onPaymentTypeChange">
                      <option value="facturi">Facturi (Sumă calculată)</option>
                      <option value="avans">Avans (Fără facturi)</option>
                      <option value="valoare">Valoare (Cu selecție facturi)</option>
                    </select>
                    <div class="form-text small" v-if="paymentForm.type === 'facturi'">
                        Selectați facturile. Suma se calculează automat.
                    </div>
                    <div class="form-text small" v-if="paymentForm.type === 'avans'">
                        Introduceți suma manual. Nu selectați facturi.
                    </div>
                    <div class="form-text small" v-if="paymentForm.type === 'valoare'">
                        Introduceți suma și selectați facturi pentru acoperire (Sold facturi >= Sumă).
                    </div>
                  </div>
              </div>

              <!-- Invoice Selection Area -->
              <div class="mb-3" v-if="paymentForm.type !== 'avans'">
                  <label class="form-label d-flex justify-content-between">
                      <span>Selectează Facturi (Sold > 0)</span>
                      <span v-if="loadingInvoices" class="spinner-border spinner-border-sm text-primary"></span>
                  </label>
                  <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                      <div v-if="invoices.length === 0 && !loadingInvoices" class="text-center text-muted py-2">
                          Nu există facturi cu sold pozitiv.
                      </div>
                      <table class="table table-sm table-hover mb-0" v-else>
                          <thead>
                              <tr>
                                  <th style="width: 30px;">
                                      <input type="checkbox" @change="toggleAllInvoices" :checked="areAllInvoicesSelected" :disabled="invoices.length === 0">
                                  </th>
                                  <th>Factura</th>
                                  <th>Data Scad.</th>
                                  <th class="text-end">Sold</th>
                              </tr>
                          </thead>
                          <tbody>
                              <tr v-for="inv in invoices" :key="inv.id" :class="{'table-active': selectedInvoiceIds.includes(inv.id)}">
                                  <td>
                                      <input type="checkbox" :value="inv.id" v-model="selectedInvoiceIds">
                                  </td>
                                  <td>{{ inv.series }} {{ inv.number }}</td>
                                  <td>{{ inv.due_date }}</td>
                                  <td class="text-end">{{ formatPrice(inv.balance, inv.currency) }}</td>
                              </tr>
                          </tbody>
                      </table>
                  </div>
                  <div class="text-end mt-1 small text-muted">
                      Total Sold Selectat: <strong>{{ formatPrice(totalSelectedBalance, selectedClient?.currency) }}</strong>
                  </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Sumă Încasată</label>
                <div class="input-group">
                  <input 
                    type="number" 
                    step="0.01" 
                    v-model="paymentForm.amount" 
                    class="form-control" 
                    required
                    :readonly="paymentForm.type === 'facturi'"
                    min="0.01"
                  >
                  <span class="input-group-text">{{ selectedClient?.currency || 'RON' }}</span>
                </div>
                <div class="text-danger small mt-1" v-if="validationError">
                    {{ validationError }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Observații</label>
                <textarea 
                  v-model="paymentForm.notes" 
                  class="form-control" 
                  rows="2"
                  placeholder="Detalii suplimentare..."
                ></textarea>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" @click="closePaymentModal">Anulează</button>
                <button type="submit" class="btn btn-primary" :disabled="submitting || !!validationError">
                  {{ submitting ? 'Se salvează...' : 'Emite Chitanță' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Anulare Chitanță -->
    <div v-if="showCancelModal" class="modal-backdrop fade show" style="z-index: 1060;"></div>
    <div 
      class="modal fade" 
      :class="{ show: showCancelModal }" 
      style="display: block; z-index: 1070;" 
      v-if="showCancelModal"
      tabindex="-1"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title text-danger">Anulare Chitanță</h5>
            <button type="button" class="btn-close" @click="closeCancelModal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Această acțiune este ireversibilă! Chitanța <strong>{{ receiptBook?.series }} {{ receiptBook?.current_number }}</strong> va fi marcată ca anulată.
            </div>
            <div class="mb-3">
                <label class="form-label">Motivul Anulării <span class="text-danger">*</span></label>
                <textarea 
                    v-model="cancelReason" 
                    class="form-control" 
                    rows="3" 
                    placeholder="Ex: completată greșit, clientul a refuzat, etc."
                    required
                ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeCancelModal">Renunță</button>
            <button type="button" class="btn btn-danger" @click="confirmCancelReceipt" :disabled="!cancelReason || submitting">
                {{ submitting ? 'Se anulează...' : 'Confirmă Anularea' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '@/store/auth';
import agentService from '@/services/account/agent';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const activeTab = ref('clients');
const clients = ref([]);
const agents = ref([]);
const loading = ref(true);
const searchClient = ref('');
const showAgentClientsOnly = ref(false);

const showPaymentModal = ref(false);
const showCancelModal = ref(false);
const cancelReason = ref('');
const selectedClient = ref(null);
const submitting = ref(false);
const invoices = ref([]);
const loadingInvoices = ref(false);
const selectedInvoiceIds = ref([]);
const receiptBook = ref(null);

const paymentForm = ref({
  instrument: 'numerar', // numerar, bo, cec
  type: 'facturi',
  amount: 0,
  payment_date: new Date().toISOString().split('T')[0],
  notes: '',
  series: '',
  number: '',
  bank: '',
  due_date: ''
});

const isDirector = computed(() => {
  const roles = (authStore.user?.roles || []).map(r => r.slug || r.code);
  return roles.includes('sales_director');
});

const filteredClients = computed(() => {
  let list = clients.value;
  if (isDirector.value && showAgentClientsOnly.value) {
    const myId = authStore.user?.id;
    list = list.filter(c => c.agent_user_id && c.agent_user_id !== myId);
  }
  if (!searchClient.value) return list;
  const q = searchClient.value.toLowerCase();
  return list.filter(c => 
    c.name.toLowerCase().includes(q) || 
    (c.cif && c.cif.toLowerCase().includes(q))
  );
});

const totalSelectedBalance = computed(() => {
    return invoices.value
        .filter(inv => selectedInvoiceIds.value.includes(inv.id))
        .reduce((sum, inv) => sum + parseFloat(inv.balance), 0);
});

const validationError = computed(() => {
    if (paymentForm.value.instrument === 'bo' || paymentForm.value.instrument === 'cec') {
        if (!paymentForm.value.series || !paymentForm.value.number || !paymentForm.value.bank || !paymentForm.value.due_date) {
            return 'Completați toate câmpurile obligatorii pentru instrument (Serie, Număr, Bancă, Scadență).';
        }
    }

    if (paymentForm.value.type === 'valoare') {
        if (paymentForm.value.amount > totalSelectedBalance.value) {
            return `Suma introdusă (${paymentForm.value.amount}) este mai mare decât totalul soldurilor selectate (${totalSelectedBalance.value}).`;
        }
        if (selectedInvoiceIds.value.length === 0 && paymentForm.value.amount > 0) {
             return 'Selectați cel puțin o factură pentru a acoperi suma.';
        }
    }
    return null;
});

const areAllInvoicesSelected = computed(() => {
    return invoices.value.length > 0 && selectedInvoiceIds.value.length === invoices.value.length;
});

watch(selectedInvoiceIds, () => {
    if (paymentForm.value.type === 'facturi') {
        paymentForm.value.amount = totalSelectedBalance.value;
    }
});

const loadData = async () => {
  loading.value = true;
  try {
    const clientsRes = await agentService.getClients({ page: 1, limit: 100 });
    clients.value = clientsRes.data.data; // Paginated response

    try {
        const rbRes = await agentService.getActiveReceiptBook();
        receiptBook.value = rbRes.data;
    } catch (e) {
        console.warn('No active receipt book found', e);
        receiptBook.value = null;
    }

    if (isDirector.value) {
      const agentsRes = await agentService.getAgents();
      agents.value = agentsRes.data;
    }
  } catch (err) {
    console.error('Failed to load agent dashboard data', err);
  } finally {
    loading.value = false;
  }
};

const formatPrice = (value, currency = 'RON') => {
  return new Intl.NumberFormat('ro-RO', {
    style: 'currency',
    currency: currency
  }).format(value || 0);
};

const openPaymentModal = async (client) => {
  selectedClient.value = client;
  paymentForm.value = {
    instrument: 'numerar',
    type: 'facturi',
    amount: 0,
    payment_date: new Date().toISOString().split('T')[0],
    notes: '',
    series: '',
    number: '',
    bank: '',
    due_date: ''
  };
  selectedInvoiceIds.value = [];
  invoices.value = [];
  showPaymentModal.value = true;
  
  loadingInvoices.value = true;
  try {
      const res = await agentService.getClientInvoices(client.id);
      invoices.value = res.data;
  } catch (e) {
      console.error(e);
      alert('Nu s-au putut încărca facturile clientului.');
  } finally {
      loadingInvoices.value = false;
  }
};

const closePaymentModal = () => {
  showPaymentModal.value = false;
  selectedClient.value = null;
  invoices.value = [];
  selectedInvoiceIds.value = [];
};

const closeCancelModal = () => {
    showCancelModal.value = false;
    cancelReason.value = '';
};

const onPaymentTypeChange = () => {
    selectedInvoiceIds.value = [];
    paymentForm.value.amount = 0;
};

const cancelCurrentReceipt = () => {
    showCancelModal.value = true;
};

const confirmCancelReceipt = async () => {
    if (!cancelReason.value) return;

    submitting.value = true;
    try {
        await agentService.cancelReceipt({ notes: cancelReason.value });
        alert('Chitanța a fost anulată cu succes.');
        
        // Refresh data
        const rbRes = await agentService.getActiveReceiptBook();
        receiptBook.value = rbRes.data;
        closeCancelModal();
    } catch (e) {
        console.error(e);
        alert(e.response?.data?.message || 'Eroare la anularea chitanței.');
    } finally {
        submitting.value = false;
    }
};

const toggleAllInvoices = () => {
    if (areAllInvoicesSelected.value) {
        selectedInvoiceIds.value = [];
    } else {
        selectedInvoiceIds.value = invoices.value.map(inv => inv.id);
    }
};

const submitPayment = async () => {
  if (!selectedClient.value) return;
  if (validationError.value) return;
  
  submitting.value = true;
  try {
    await agentService.storePayment({
      customer_id: selectedClient.value.id,
      ...paymentForm.value,
      selected_invoices: selectedInvoiceIds.value
    });
    alert('Chitanța a fost emisă cu succes!');
    closePaymentModal();
    // Refresh clients to update balance if backend supports it immediately
    loadData();
  } catch (err) {
    console.error(err);
    alert('Eroare la emitere chitanță: ' + (err.response?.data?.message || err.message));
  } finally {
    submitting.value = false;
  }
};

const impersonateClient = (client) => {
  if (confirm(`Sunteți sigur că doriți să plasați comenzi în numele clientului ${client.name}?`)) {
    localStorage.setItem('impersonated_client_id', client.id);
    localStorage.setItem('impersonated_client_name', client.name);
    // Folosim window.location pentru a forța reîncărcarea și aplicarea interceptorilor
    window.location.href = '/'; 
  }
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
.modal-backdrop {
  opacity: 0.5;
}
</style>
