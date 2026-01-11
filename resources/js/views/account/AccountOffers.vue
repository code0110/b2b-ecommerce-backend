<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Ofertele mele</h1>
        <p class="small text-muted mb-0">
          Cereri de ofertă și oferte comerciale legate de comenzi.
        </p>
      </div>
      <RouterLink to="/cont/cere-oferta" class="btn btn-orange btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Cere Ofertă
      </RouterLink>
    </div>

    <div v-if="error" class="alert alert-danger small mb-3">
      {{ error }}
    </div>

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-body p-3">
        <div class="row g-2">
          <div class="col-12 col-md-4">
            <input 
              v-model="filters.search" 
              type="text" 
              class="form-control form-control-sm" 
              placeholder="Caută după număr..." 
              @keyup.enter="loadOffers"
            >
          </div>
          <div class="col-6 col-md-4">
            <select v-model="filters.status" class="form-select form-select-sm" @change="loadOffers">
              <option value="">Toate statusurile</option>
              <option value="draft">Draft</option>
              <option value="sent">Trimisă</option>
              <option value="accepted">Acceptată</option>
              <option value="rejected">Respinsă</option>
              <option value="completed">Finalizată</option>
            </select>
          </div>
          <div class="col-6 col-md-4">
             <button class="btn btn-sm btn-outline-secondary w-100" @click="loadOffers">
               <i class="bi bi-filter me-1"></i> Filtrează
             </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border spinner-border-sm" role="status" />
      <div class="small text-muted mt-2">
        Se încarcă ofertele...
      </div>
    </div>

    <div v-else>
      <div v-if="offers.length === 0" class="alert alert-info small">
        Nu există oferte înregistrate.
      </div>
      <div v-else class="card shadow-sm border-0">
        <div class="card-body">
          <div class="row row-cols-1 g-3">
            <div class="col" v-for="offer in offers" :key="offer.id">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="fw-semibold">#{{ offer.number }}</div>
                      <div class="small text-muted">{{ formatDate(offer.created_at) }}</div>
                      <div class="mt-1">
                        <span class="badge bg-light text-dark border">
                          {{ offer.status_label || offer.status }}
                        </span>
                        <span v-if="offer.order_id" class="badge bg-light text-dark ms-1">
                          Comandă #{{ offer.order_number || offer.order_id }}
                        </span>
                      </div>
                    </div>
                    <div class="text-end">
                      <div class="fw-bold">{{ formatMoney(offer.total) }}</div>
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm mt-2"
                        @click="viewOffer(offer)"
                      >
                        Detalii
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="offers.length === 0" class="col">
              <div class="text-center py-4 text-muted">Nu există oferte înregistrate.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal simplu pentru detalii ofertă -->
      <div
        v-if="selectedOffer"
        class="modal fade show"
        style="display: block; background-color: rgba(0,0,0,.4);"
        tabindex="-1"
        role="dialog"
      >
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header py-2">
              <h5 class="modal-title small">
                Ofertă #{{ selectedOffer.number }}
              </h5>
              <button
                type="button"
                class="btn-close"
                @click="selectedOffer = null"
              ></button>
            </div>
            <div class="modal-body small">
              <p class="mb-1">
                <strong>Status:</strong>
                {{ selectedOffer.status_label || selectedOffer.status }}
              </p>
              <p class="mb-2">
                <strong>Data:</strong>
                {{ formatDate(selectedOffer.created_at) }}
              </p>
              <div v-if="selectedOffer.lines && selectedOffer.lines.length">
                <div class="list-group">
                  <div
                    class="list-group-item d-flex justify-content-between align-items-start"
                    v-for="line in selectedOffer.lines"
                    :key="line.id"
                  >
                    <div>
                      <div class="fw-semibold small">{{ line.product_name }}</div>
                      <div class="small text-muted">{{ line.product_code }}</div>
                    </div>
                    <div class="text-end">
                      <div class="small">Cant: <strong>{{ line.quantity }}</strong></div>
                      <div class="small">Preț: <strong>{{ formatMoney(line.unit_price) }}</strong></div>
                      <div class="small">Total: <strong>{{ formatMoney(line.line_total) }}</strong></div>
                    </div>
                  </div>
                </div>
                <div class="text-end mt-2">
                  <div>
                    Subtotal:
                    <strong>
                      {{ formatMoney(selectedOffer.subtotal) }}
                    </strong>
                  </div>
                  <div>
                    Total:
                    <strong>
                      {{ formatMoney(selectedOffer.total) }}
                    </strong>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer py-2">
              <button
                type="button"
                class="btn btn-secondary btn-sm"
                @click="selectedOffer = null"
              >
                Închide
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- backdrop bootstrap-style -->
      <div
        v-if="selectedOffer"
        class="modal-backdrop fade show"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fetchOffers, fetchOffer } from '@/services/account/offers';

import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(false);
const error = ref('');
const offers = ref([]);
const selectedOffer = ref(null);
const filters = ref({
  search: '',
  status: ''
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

const loadOffers = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchOffers(filters.value);
    offers.value = data.data ?? data;
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut încărca ofertele.';
  } finally {
    loading.value = false;
  }
};

const viewOffer = async (offer) => {
    // Navigate to dedicated offer details page for full functionality (negotiation, etc)
    router.push({ name: 'account-offer-details', params: { id: offer.id } });
};

onMounted(loadOffers);
</script>
