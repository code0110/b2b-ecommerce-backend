<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-1">Ofertele mele</h1>
        <p class="small text-muted mb-0">
          Cereri de ofertă și oferte comerciale legate de comenzi.
        </p>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger small mb-3">
      {{ error }}
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

      <div v-else class="card shadow-sm">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr class="small text-muted">
                <th>Nr. ofertă</th>
                <th>Data</th>
                <th>Status</th>
                <th>Comandă asociată</th>
                <th class="text-end">Valoare</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="offer in offers" :key="offer.id">
                <td>{{ offer.number }}</td>
                <td>{{ formatDate(offer.created_at) }}</td>
                <td>
                  <span class="badge bg-light text-dark border">
                    {{ offer.status_label || offer.status }}
                  </span>
                </td>
                <td>
                  <RouterLink
                    v-if="offer.order_id"
                    :to="`/cont/comenzi/${offer.order_id}`"
                    class="text-decoration-none"
                  >
                    #{{ offer.order_number || offer.order_id }}
                  </RouterLink>
                  <span v-else class="text-muted small">–</span>
                </td>
                <td class="text-end">
                  {{ formatMoney(offer.total) }}
                </td>
                <td class="text-end">
                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    @click="viewOffer(offer)"
                  >
                    Detalii
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
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
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr class="text-muted">
                        <th>Produs</th>
                        <th>Cod</th>
                        <th class="text-end">Cantitate</th>
                        <th class="text-end">Preț</th>
                        <th class="text-end">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="line in selectedOffer.lines"
                        :key="line.id"
                      >
                        <td>{{ line.product_name }}</td>
                        <td>{{ line.product_code }}</td>
                        <td class="text-end">{{ line.quantity }}</td>
                        <td class="text-end">
                          {{ formatMoney(line.unit_price) }}
                        </td>
                        <td class="text-end">
                          {{ formatMoney(line.line_total) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
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

const loading = ref(false);
const error = ref('');
const offers = ref([]);
const selectedOffer = ref(null);

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
    const data = await fetchOffers();
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
  try {
    const data = await fetchOffer(offer.id);
    selectedOffer.value = data.offer ?? data;
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ||
      'Nu am putut încărca detaliile ofertei.';
  }
};

onMounted(loadOffers);
</script>
