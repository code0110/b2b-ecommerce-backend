<!-- resources/js/views/admin/promotions/PromotionList.vue -->
<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1 text-gray-800">Promoții</h1>
        <p class="text-muted small mb-0">Gestionează campaniile și regulile de discount.</p>
      </div>
      <RouterLink
        :to="{ name: 'admin-promotions-new' }"
        class="btn btn-primary shadow-sm"
      >
        <i class="bi bi-plus-lg me-1"></i> Promoție nouă
      </RouterLink>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>
    
    <div v-else-if="error" class="alert alert-danger shadow-sm border-0">
      <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ error }}
    </div>

    <div v-else>
      <div v-if="!promotions.length" class="text-center py-5">
        <div class="mb-3">
          <i class="bi bi-tags text-muted opacity-25" style="font-size: 3rem;"></i>
        </div>
        <h5 class="text-muted">Nu există promoții definite</h5>
        <p class="text-muted small">Folosește butonul de mai sus pentru a adăuga prima promoție.</p>
      </div>

      <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
        <div v-for="promotion in promotions" :key="promotion.id" class="col">
          <div class="card h-100 border shadow-sm promotion-card hover-shadow transition-all">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-start">
              <div class="pe-2">
                <h6 class="fw-bold mb-1 text-dark text-truncate">{{ promotion.name }}</h6>
                <div class="small text-muted text-truncate" style="max-width: 200px;" v-if="promotion.short_description">
                  {{ promotion.short_description }}
                </div>
              </div>
              <div class="d-flex flex-column align-items-end gap-1">
                 <span class="badge rounded-pill" :class="statusBadgeClass(promotion.status)">
                  {{ statusLabel(promotion.status) }}
                </span>
                <span v-if="promotion.is_exclusive" class="badge bg-warning text-dark border border-warning" style="font-size: 0.65rem;">
                  <i class="bi bi-star-fill me-1"></i>Exclusivă
                </span>
              </div>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">SLUG</small>
                <div class="font-monospace bg-light rounded px-2 py-1 small text-truncate border">{{ promotion.slug }}</div>
              </div>
              
              <div class="mb-3">
                 <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">PERIOADĂ DE VALABILITATE</small>
                 <div class="d-flex align-items-center small mt-1">
                    <div class="d-flex align-items-center text-nowrap">
                      <i class="bi bi-calendar-check me-1 text-success"></i>
                      <span v-if="promotion.start_at" class="fw-semibold">{{ formatDate(promotion.start_at) }}</span>
                      <span v-else class="text-muted fst-italic">Nedefinit</span>
                    </div>
                    <i class="bi bi-arrow-right mx-2 text-muted"></i>
                    <div class="d-flex align-items-center text-nowrap">
                      <i class="bi bi-calendar-x me-1 text-danger"></i>
                      <span v-if="promotion.end_at" class="fw-semibold">{{ formatDate(promotion.end_at) }}</span>
                      <span v-else class="text-muted fst-italic">Nedefinit</span>
                    </div>
                 </div>
              </div>

              <div class="row g-2">
                 <div class="col-6">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">TIP CLIENT</small>
                    <div class="mt-1">
                        <span v-if="promotion.customer_type === 'b2b'" class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 w-100 py-2">
                          <i class="bi bi-building me-1"></i>B2B
                        </span>
                        <span v-else-if="promotion.customer_type === 'b2c'" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 w-100 py-2">
                          <i class="bi bi-person me-1"></i>B2C
                        </span>
                        <span v-else class="badge bg-secondary bg-opacity-10 text-secondary border w-100 py-2">
                          <i class="bi bi-people me-1"></i>Toți
                        </span>
                    </div>
                    <div v-if="promotion.logged_in_only" class="text-xs text-muted mt-1 text-center">
                      <i class="bi bi-lock-fill me-1"></i>Doar logați
                    </div>
                 </div>
                 <div class="col-6">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">BONUS</small>
                    <div class="mt-1 bg-light rounded border p-1 text-center small fw-semibold text-primary h-100 d-flex align-items-center justify-content-center">
                        <span v-if="promotion.bonus_type === 'discount_percent'">
                          <i class="bi bi-percent me-1"></i>Discount %
                        </span>
                        <span v-else-if="promotion.bonus_type === 'discount_value'">
                          <i class="bi bi-currency-euro me-1"></i>Valoric
                        </span>
                        <span v-else>
                          <i class="bi bi-gift me-1"></i>Produs
                        </span>
                    </div>
                 </div>
              </div>
            </div>
            <div class="card-footer bg-white py-2 d-flex justify-content-end gap-2">
               <RouterLink
                  :to="{ name: 'admin-promotions-edit', params: { id: promotion.id } }"
                  class="btn btn-sm btn-outline-primary"
                  title="Editează"
                >
                  <i class="bi bi-pencil me-1"></i> Editează
                </RouterLink>
                <button
                  class="btn btn-sm btn-outline-danger"
                  @click="confirmDelete(promotion)"
                  title="Șterge"
                >
                  <i class="bi bi-trash"></i>
                </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="pagination.last_page > 1" class="d-flex justify-content-center mt-4">
         <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm shadow-sm">
               <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                  <button class="page-link border-0" @click="changePage(pagination.current_page - 1)" aria-label="Previous">
                     <i class="bi bi-chevron-left"></i>
                  </button>
               </li>
               <li class="page-item disabled">
                  <span class="page-link border-0 text-muted bg-transparent">
                     Pagina {{ pagination.current_page }} din {{ pagination.last_page }}
                  </span>
               </li>
               <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                  <button class="page-link border-0" @click="changePage(pagination.current_page + 1)" aria-label="Next">
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
import { fetchPromotions, deletePromotion } from '@/services/admin/promotions';

const loading = ref(false);
const error = ref('');
const promotions = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
});

const loadPromotions = async (page = 1) => {
  loading.value = true;
  error.value = '';

  try {
    const response = await fetchPromotions({ page });
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
  if (status === 'active') return 'bg-success';
  if (status === 'inactive') return 'bg-secondary';
  return 'bg-warning text-dark';
};

const formatDate = value => {
  if (!value) return '';
  // putem primi "YYYY-MM-DD" sau "YYYY-MM-DD HH:MM:SS"
  const date = value.substring(0, 10);
  return date;
};

onMounted(() => loadPromotions(1));
</script>
