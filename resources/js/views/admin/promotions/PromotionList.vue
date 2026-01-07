<template>
  <div class="container-fluid py-4 bg-gray-50">
    <!-- Header & Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold mb-1 text-dark">Promoții</h1>
        <p class="text-muted small mb-0">Gestionează campaniile și regulile de discount active.</p>
      </div>
      <div class="d-flex gap-2">
         <!-- Search (Future Impl) -->
         <div class="input-group shadow-sm" style="max-width: 300px;">
            <span class="input-group-text bg-white border-end-0 ps-3">
                <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" class="form-control border-start-0" placeholder="Caută promoții..." v-model="searchQuery" @input="debouncedSearch">
         </div>

         <RouterLink
            :to="{ name: 'admin-promotions-new' }"
            class="btn btn-primary shadow-sm d-flex align-items-center gap-2 px-3"
         >
            <i class="bi bi-plus-lg"></i>
            <span class="d-none d-sm-inline">Promoție Nouă</span>
         </RouterLink>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !promotions.length" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger shadow-sm border-0 rounded-3 d-flex align-items-center">
      <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
      <div>{{ error }}</div>
    </div>

    <div v-else>
      <!-- Empty State -->
      <div v-if="!promotions.length" class="text-center py-5 bg-white rounded-3 shadow-sm border border-dashed">
        <div class="mb-3">
          <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
             <i class="bi bi-tags text-muted opacity-50 display-4"></i>
          </div>
        </div>
        <h5 class="text-dark fw-bold">Nu există promoții</h5>
        <p class="text-muted small mb-4">Începe prin a crea prima ta campanie promoțională.</p>
        <RouterLink :to="{ name: 'admin-promotions-new' }" class="btn btn-primary btn-sm">
            Creează Promoție
        </RouterLink>
      </div>

      <!-- Data Table -->
      <div v-else class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-uppercase text-muted small fw-bold" style="width: 50px;">#</th>
                        <th class="py-3 text-uppercase text-muted small fw-bold">Nume Promoție</th>
                        <th class="py-3 text-uppercase text-muted small fw-bold">Tip & Valoare</th>
                        <th class="py-3 text-uppercase text-muted small fw-bold">Perioadă</th>
                        <th class="py-3 text-uppercase text-muted small fw-bold text-center">Prioritate</th>
                        <th class="py-3 text-uppercase text-muted small fw-bold text-center">Status</th>
                        <th class="pe-4 py-3 text-uppercase text-muted small fw-bold text-end">Acțiuni</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <tr v-for="(promo, index) in promotions" :key="promo.id">
                        <td class="ps-4 text-muted small">{{ (pagination.current_page - 1) * 15 + index + 1 }}</td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-dark">{{ promo.name }}</span>
                                <span class="small text-muted font-monospace">{{ promo.slug }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="badge bg-light text-dark border w-auto align-self-start mb-1">
                                    {{ formatType(promo.type) }}
                                </span>
                                <span class="small fw-bold text-primary">
                                    {{ formatValueSummary(promo) }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column small">
                                <span class="text-muted mb-1">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    {{ formatDate(promo.start_at) }} &mdash; {{ formatDate(promo.end_at) }}
                                </span>
                                <span v-if="isExpired(promo)" class="text-danger fst-italic" style="font-size: 0.75rem;">
                                    Expirată
                                </span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-secondary border rounded-pill px-3">
                                {{ promo.priority }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill px-3 py-2" :class="statusBadgeClass(promo.status)">
                                <i class="bi me-1" :class="statusIcon(promo.status)"></i>
                                {{ statusLabel(promo.status) }}
                            </span>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="btn-group">
                                <RouterLink
                                    :to="{ name: 'admin-promotions-edit', params: { id: promo.id } }"
                                    class="btn btn-sm btn-white border text-secondary shadow-sm"
                                    title="Editează"
                                >
                                    <i class="bi bi-pencil"></i>
                                </RouterLink>
                                <button
                                    class="btn btn-sm btn-white border text-danger shadow-sm"
                                    @click="confirmDelete(promo)"
                                    title="Șterge"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center" v-if="pagination.last_page > 1">
            <span class="text-muted small">
                Afișare {{ (pagination.current_page - 1) * 15 + 1 }} - {{ Math.min(pagination.current_page * 15, pagination.total) }} din {{ pagination.total }} rezultate
            </span>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                        <button class="page-link border-0 text-secondary" @click="changePage(pagination.current_page - 1)">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                    </li>
                    <li class="page-item disabled">
                        <span class="page-link border-0 fw-bold text-dark bg-transparent">
                            {{ pagination.current_page }}
                        </span>
                    </li>
                    <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                        <button class="page-link border-0 text-secondary" @click="changePage(pagination.current_page + 1)">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fetchPromotions, deletePromotion } from '@/services/admin/promotions';

const loading = ref(false);
const error = ref('');
const promotions = ref([]);
const searchQuery = ref('');
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
});

// Simple debounce for search
let timeout = null;
const debouncedSearch = () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        loadPromotions(1);
    }, 500);
};

const loadPromotions = async (page = 1) => {
  loading.value = true;
  error.value = '';

  try {
    // Pass search query if backend supports it (even if not, it won't hurt)
    const params = { page };
    if (searchQuery.value) params.search = searchQuery.value;

    const response = await fetchPromotions(params);
    promotions.value = response.data || [];
    const meta = response.meta || {};
    pagination.value = {
      current_page: meta.current_page || 1,
      last_page: meta.last_page || 1,
      total: meta.total || promotions.value.length
    };
  } catch (e) {
    console.error('Promotions load error', e);
    error.value = 'Nu s-a putut încărca lista de promoții.';
  } finally {
    loading.value = false;
  }
};

const changePage = page => {
  if (page < 1 || page > pagination.value.last_page) return;
  loadPromotions(page);
};

const confirmDelete = async promotion => {
  if (!confirm(`Sigur vrei să ștergi promoția "${promotion.name}"?`)) {
    return;
  }

  try {
    await deletePromotion(promotion.id);
    promotions.value = promotions.value.filter(p => p.id !== promotion.id);
    if (promotions.value.length === 0 && pagination.value.current_page > 1) {
        changePage(pagination.value.current_page - 1);
    }
  } catch (e) {
    console.error('Promotion delete error', e);
    alert('Nu s-a putut șterge promoția.');
  }
};

const statusLabel = status => {
  if (status === 'active') return 'Activă';
  if (status === 'inactive') return 'Inactivă';
  return 'Draft';
};

const statusBadgeClass = status => {
  if (status === 'active') return 'bg-success bg-opacity-10 text-success';
  if (status === 'inactive') return 'bg-secondary bg-opacity-10 text-secondary';
  return 'bg-warning bg-opacity-10 text-warning';
};

const statusIcon = status => {
    if (status === 'active') return 'bi-check-circle-fill';
    if (status === 'inactive') return 'bi-x-circle-fill';
    return 'bi-circle-fill';
};

const formatDate = value => {
  if (!value) return '...';
  return value.substring(0, 10);
};

const isExpired = (promo) => {
    if (!promo.end_at) return false;
    return new Date(promo.end_at) < new Date();
};

const formatType = (type) => {
  const map = {
    standard: 'Standard',
    volume: 'Volum (Tiers)',
    bundle: 'Pachet',
    shipping: 'Livrare Gratuită',
    special_price: 'Preț Special',
    gift: 'Produs Cadou'
  };
  return map[type] || type;
};

const formatValueSummary = (promo) => {
    if (promo.type === 'volume') {
        const tiers = promo.tiers || [];
        return `${tiers.length} praguri definite`;
    }
    if (promo.type === 'shipping') return '0 RON';
    
    // For standard types
    const val = Number(promo.value);
    if (promo.value_type === 'percent') return `-${val}%`;
    if (promo.value_type === 'fixed_amount') return `-${val} RON`;
    if (promo.value_type === 'fixed_price') return `${val} RON (Fix)`;
    
    return val;
};

onMounted(() => loadPromotions(1));
</script>