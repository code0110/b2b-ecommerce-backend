<template>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Cererile Mele de Ofertă</h5>
      <router-link :to="{ name: 'account-quote-requests-new' }" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Cerere Nouă
      </router-link>
    </div>
    <div class="card-body p-0">
      
      <!-- Filters -->
      <div class="p-3 bg-light border-bottom">
        <div class="row g-2">
            <div class="col-6 col-md-4">
                <select v-model="filters.status" class="form-select form-select-sm" @change="loadRequests(1)">
                    <option value="">Toate statusurile</option>
                    <option value="new">Nouă</option>
                    <option value="processed">Procesată</option>
                    <option value="converted">Ofertată</option>
                    <option value="rejected">Respinsă</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                 <!-- Future date filter or search -->
            </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Se încarcă...</span>
        </div>
      </div>

      <div v-else-if="requests.length === 0" class="text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1 d-block opacity-25 mb-3"></i>
        Nu ai trimis nicio cerere de ofertă.
      </div>

      <div v-else class="p-3">
        <div class="row row-cols-1 g-3">
          <div class="col" v-for="req in requests" :key="req.id">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="fw-bold">Cerere #{{ req.id }}</div>
                    <div class="small text-muted">{{ new Date(req.created_at).toLocaleDateString('ro-RO') }}</div>
                    <div class="mt-1">
                      <span class="badge" :class="statusBadge(req.status)">
                        {{ statusLabel(req.status) }}
                      </span>
                    </div>
                  </div>
                  <div class="text-end">
                    <router-link
                      :to="{ name: 'account-quote-requests-show', params: { id: req.id } }"
                      class="btn btn-sm btn-outline-primary"
                    >
                      Detalii
                    </router-link>
                  </div>
                </div>
                <div class="small text-muted mt-2">
                  {{ req.customer_notes || '-' }}
                </div>
              </div>
            </div>
          </div>
          <div v-if="requests.length === 0" class="col">
            <div class="text-center py-4 text-muted">Nu ai trimis nicio cerere de ofertă.</div>
          </div>
        </div>
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
const filters = ref({
    status: ''
});
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
        const { data } = await axios.get(`/api/account/offers`, {
            params: {
                page,
                status: filters.value.status
            }
        });
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
