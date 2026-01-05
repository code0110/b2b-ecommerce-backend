<template>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Cererile Mele de Ofertă</h5>
      <router-link :to="{ name: 'account-quote-requests-new' }" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Cerere Nouă
      </router-link>
    </div>
    <div class="card-body p-0">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Se încarcă...</span>
        </div>
      </div>

      <div v-else-if="requests.length === 0" class="text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1 d-block opacity-25 mb-3"></i>
        Nu ai trimis nicio cerere de ofertă.
      </div>

      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">ID</th>
              <th>Data</th>
              <th>Status</th>
              <th>Notițe</th>
              <th class="text-end pe-4">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="req in requests" :key="req.id">
              <td class="ps-4 fw-bold">#{{ req.id }}</td>
              <td>{{ new Date(req.created_at).toLocaleDateString('ro-RO') }}</td>
              <td>
                <span class="badge" :class="statusBadge(req.status)">
                  {{ statusLabel(req.status) }}
                </span>
              </td>
              <td>
                <div class="text-truncate" style="max-width: 250px;" :title="req.customer_notes">
                  {{ req.customer_notes || '-' }}
                </div>
              </td>
              <td class="text-end pe-4">
                <router-link :to="{ name: 'account-quote-requests-show', params: { id: req.id } }" class="btn btn-sm btn-outline-primary">
                  Detalii
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="p-3 d-flex justify-content-center">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                    <button class="page-link" @click="loadRequests(pagination.current_page - 1)">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                </li>
                <li class="page-item active">
                    <span class="page-link">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                </li>
                <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                    <button class="page-link" @click="loadRequests(pagination.current_page + 1)">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </li>
            </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/store/auth';
import axios from 'axios';

const requests = ref([]);
const loading = ref(true);
const pagination = ref({
    current_page: 1,
    last_page: 1
});

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
        'new': 'bg-info text-dark',
        'processed': 'bg-primary',
        'converted': 'bg-success',
        'rejected': 'bg-danger'
    };
    return map[s] || 'bg-secondary';
};

const loadRequests = async (page = 1) => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/account/offers?page=${page}`); // Using existing 'offers' route which points to QuoteController
        requests.value = data.data;
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page
        };
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadRequests();
});
</script>
