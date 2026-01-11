<template>
  <div class="container-fluid" v-if="request">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1">Cerere Ofertă #{{ request.id }}</h1>
        <p class="text-muted small mb-0">
            Creată la {{ new Date(request.created_at).toLocaleDateString('ro-RO') }}
        </p>
      </div>
      <button class="btn btn-outline-secondary" @click="router.back()">
        <i class="bi bi-arrow-left me-2"></i> Înapoi
      </button>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Detalii Solicitare</h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
                <label class="small text-muted fw-bold">Notițe / Mesaj:</label>
                <div class="p-3 bg-light rounded text-break">
                    {{ request.customer_notes || 'Fără notițe.' }}
                </div>
            </div>
            
            <div class="mb-3">
                <label class="small text-muted fw-bold">Status:</label>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge" :class="statusBadge(request.status)">{{ statusLabel(request.status) }}</span>
                    <button 
                        v-if="request.offer" 
                        class="btn btn-sm btn-orange" 
                        @click="router.push({ name: 'account-offer-details', params: { id: request.offer.id } })"
                    >
                        <i class="bi bi-file-earmark-check me-1"></i> Vezi Oferta Primită
                    </button>
                </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0" v-if="request.items && request.items.length > 0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Produse Solicitate</h6>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <div 
                        class="list-group-item d-flex justify-content-between align-items-start"
                        v-for="item in request.items" 
                        :key="item.id"
                    >
                        <div>
                            <div class="fw-bold small">{{ item.product?.name || 'Produs șters' }}</div>
                            <div class="small text-muted">{{ item.product?.internal_code }}</div>
                        </div>
                        <div class="text-end">
                            <div class="small">Cant: <strong>{{ item.quantity }}</strong></div>
                            <div class="small">Preț listă: <strong>{{ formatPrice(item.list_price) }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>
      
      <div class="col-lg-4">
          <div class="card shadow-sm border-0 bg-light">
              <div class="card-body">
                  <h6 class="fw-bold mb-3">Informații Suplimentare</h6>
                  <p class="small text-muted mb-1">
                      Această cerere va fi analizată de un agent de vânzări. Vei primi o notificare când oferta comercială este pregătită.
                  </p>
                  <hr>
                  <div v-if="request.assigned_agent">
                      <div class="small fw-bold text-muted">Agent Alocat:</div>
                      <div>{{ request.assigned_agent.name }}</div>
                      <div class="small text-muted">{{ request.assigned_agent.email }}</div>
                  </div>
              </div>
          </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const request = ref(null);

const formatPrice = (val) => new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(val);

const statusLabel = (s) => {
    const map = {
        'new': 'Nouă',
        'processed': 'Procesată',
        'converted': 'Ofertată',
        'rejected': 'Respinsă'
    };
    return map[s] || s;
};

const statusBadge = (s) => {
    const map = {
        'new': 'bg-warning text-dark',
        'processed': 'bg-dd-blue',
        'converted': 'bg-success',
        'rejected': 'bg-danger'
    };
    return map[s] || 'bg-secondary';
};

onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/quotes/${route.params.id}`);
        request.value = data;
    } catch (e) {
        console.error(e);
        router.push({ name: 'account-quote-requests' });
    }
});
</script>
