<!-- resources/js/views/admin/promotions/PromotionList.vue -->
<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Promoții</h1>
      <RouterLink
        :to="{ name: 'admin-promotions-new' }"
        class="btn btn-primary btn-sm"
      >
        + Promoție nouă
      </RouterLink>
    </div>

    <div v-if="loading" class="alert alert-info small py-2">
      Se încarcă lista de promoții...
    </div>
    <div v-else-if="error" class="alert alert-danger small py-2">
      {{ error }}
    </div>

    <div class="card shadow-sm">
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th>Denumire</th>
              <th>Slug</th>
              <th>Perioadă</th>
              <th>Tip client</th>
              <th>Bonus</th>
              <th>Status</th>
              <th class="text-end"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!promotions.length">
              <td colspan="7" class="text-center small text-muted py-3">
                Nu există promoții definite.
              </td>
            </tr>
            <tr v-for="promotion in promotions" :key="promotion.id">
              <td class="small">
                <div class="fw-semibold">{{ promotion.name }}</div>
                <div class="text-muted">
                  {{ promotion.short_description }}
                </div>
              </td>
              <td class="small text-muted">
                {{ promotion.slug }}
              </td>
              <td class="small">
                <span v-if="promotion.start_at">
                  {{ formatDate(promotion.start_at) }}
                </span>
                <span v-else class="text-muted">fără început</span>
                –
                <span v-if="promotion.end_at">
                  {{ formatDate(promotion.end_at) }}
                </span>
                <span v-else class="text-muted">fără sfârșit</span>
              </td>
              <td class="small text-muted">
                <span v-if="promotion.customer_type === 'b2b'">B2B</span>
                <span v-else-if="promotion.customer_type === 'b2c'">B2C</span>
                <span v-else>Ambele</span>
                <span v-if="promotion.logged_in_only" class="badge bg-light text-dark ms-1">
                  doar logați
                </span>
              </td>
              <td class="small text-muted">
                <span v-if="promotion.bonus_type === 'discount_percent'">
                  Discount %
                </span>
                <span v-else-if="promotion.bonus_type === 'discount_value'">
                  Discount valoric
                </span>
                <span v-else>
                  Produs gratuit
                </span>
              </td>
              <td class="small">
                <span
                  class="badge"
                  :class="statusBadgeClass(promotion.status)"
                >
                  {{ statusLabel(promotion.status) }}
                </span>
                <span
                  v-if="promotion.is_exclusive"
                  class="badge bg-warning text-dark ms-1"
                >
                  Exclusivă
                </span>
              </td>
              <td class="text-end">
                <RouterLink
                  :to="{
                    name: 'admin-promotions-edit',
                    params: { id: promotion.id }
                  }"
                  class="btn btn-link btn-sm text-decoration-none"
                >
                  Editează
                </RouterLink>
                <button
                  class="btn btn-link btn-sm text-danger text-decoration-none"
                  @click="confirmDelete(promotion)"
                >
                  Șterge
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="pagination.last_page > 1"
        class="card-footer py-2 d-flex justify-content-between align-items-center small"
      >
        <div>
          Pagina {{ pagination.current_page }} din {{ pagination.last_page }}
        </div>
        <div class="btn-group btn-group-sm">
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="pagination.current_page <= 1 || loading"
            @click="changePage(pagination.current_page - 1)"
          >
            « Înapoi
          </button>
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="pagination.current_page >= pagination.last_page || loading"
            @click="changePage(pagination.current_page + 1)"
          >
            Înainte »
          </button>
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
