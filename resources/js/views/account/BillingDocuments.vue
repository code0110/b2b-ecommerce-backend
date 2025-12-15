<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Documente financiare</h1>
        <p class="small text-muted mb-0">
          Facturi și proforme asociate comenzilor tale.
        </p>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body small">
        <form class="row g-2" @submit.prevent="reload">
          <div class="col-md-3">
            <label class="form-label form-label-sm">Număr document</label>
            <input
              v-model="filters.number"
              type="text"
              class="form-control form-control-sm"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Tip</label>
            <select
              v-model="filters.type"
              class="form-select form-select-sm"
            >
              <option value="">Toate</option>
              <option value="invoice">Factură</option>
              <option value="proforma">Proformă</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">De la</label>
            <input
              v-model="filters.from"
              type="date"
              class="form-control form-control-sm"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Până la</label>
            <div class="input-group input-group-sm">
              <input
                v-model="filters.to"
                type="date"
                class="form-control form-control-sm"
              />
              <button class="btn btn-primary btn-sm" type="submit">
                Filtrează
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger small mb-3">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border spinner-border-sm" role="status" />
      <div class="small text-muted mt-2">
        Se încarcă documentele...
      </div>
    </div>

    <div v-else>
      <div v-if="documents.length === 0" class="alert alert-info small">
        Nu există documente pentru criteriile selectate.
      </div>

      <div v-else class="card shadow-sm">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr class="small text-muted">
                <th>Tip</th>
                <th>Număr</th>
                <th>Data</th>
                <th>Comandă</th>
                <th class="text-end">Total</th>
                <th class="text-end">Status plată</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doc in documents" :key="doc.id">
                <td class="text-uppercase">
                  {{ doc.type }}
                </td>
                <td>{{ doc.number }}</td>
                <td>{{ formatDate(doc.issue_date) }}</td>
                <td>
                  <RouterLink
                    v-if="doc.order_id"
                    :to="`/cont/comenzi/${doc.order_id}`"
                    class="text-decoration-none"
                  >
                    #{{ doc.order_number || doc.order_id }}
                  </RouterLink>
                </td>
                <td class="text-end">
                  {{ formatMoney(doc.total) }}
                </td>
                <td class="text-end">
                  <span class="badge bg-light text-dark border">
                    {{ doc.payment_status_label || doc.payment_status }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a
                      v-if="doc.pdf_url"
                      :href="doc.pdf_url"
                      target="_blank"
                      rel="noopener"
                      class="btn btn-outline-secondary btn-sm"
                    >
                      PDF
                    </a>
                    <button
                      v-if="doc.can_pay_online"
                      type="button"
                      class="btn btn-outline-primary btn-sm"
                      @click="payOnline(doc)"
                    >
                      Plătește
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fetchInvoices } from '@/services/account/documents';

const loading = ref(false);
const error = ref('');
const documents = ref([]);

const filters = ref({
  number: '',
  type: '',
  from: '',
  to: '',
});

const formatMoney = (value) => {
  if (!value) value = 0;
  return Number(value).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const formatDate = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleDateString('ro-RO');
};

const loadDocs = async () => {
  loading.value = true;
  error.value = '';

  try {
    const params = {};
    if (filters.value.number) params.number = filters.value.number;
    if (filters.value.type) params.type = filters.value.type;
    if (filters.value.from) params.from = filters.value.from;
    if (filters.value.to) params.to = filters.value.to;

    const data = await fetchInvoices(params);
    documents.value = data.data ?? data;
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut încărca documentele.';
  } finally {
    loading.value = false;
  }
};

const reload = () => loadDocs();

const payOnline = (doc) => {
  if (doc.payment_url) {
    window.location.href = doc.payment_url;
    return;
  }
  alert('Linkul de plată nu este disponibil.');
};

onMounted(loadDocs);
</script>
