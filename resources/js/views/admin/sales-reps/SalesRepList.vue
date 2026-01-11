<template>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Reprezentanți Vânzări</h5>
      <RouterLink :to="{ name: 'admin-sales-reps-create' }" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Adaugă Reprezentant
      </RouterLink>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">Nume</th>
              <th>Contact</th>
              <th>Regiune</th>
              <th>Status</th>
              <th>Ordine</th>
              <th class="text-end pe-4">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rep in reps" :key="rep.id">
              <td class="ps-4 fw-semibold">{{ rep.name }}</td>
              <td class="small">
                <div v-if="rep.email"><i class="bi bi-envelope me-1 text-muted"></i> {{ rep.email }}</div>
                <div v-if="rep.phone"><i class="bi bi-telephone me-1 text-muted"></i> {{ rep.phone }}</div>
              </td>
              <td>
                <span v-if="rep.region" class="badge bg-light text-dark border">{{ rep.region }}</span>
                <span v-else class="text-muted small">-</span>
              </td>
              <td>
                <span v-if="rep.is_active" class="badge bg-success">Activ</span>
                <span v-else class="badge bg-secondary">Inactiv</span>
              </td>
              <td>{{ rep.sort_order }}</td>
              <td class="text-end pe-4">
                <RouterLink :to="{ name: 'admin-sales-reps-edit', params: { id: rep.id } }" class="btn btn-sm btn-outline-primary me-2" title="Editează">
                  <i class="bi bi-pencil"></i>
                </RouterLink>
                <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(rep)" title="Șterge">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="reps.length === 0">
              <td colspan="6" class="text-center py-5 text-muted">
                Nu există reprezentanți de vânzări.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fetchSalesRepresentatives, deleteSalesRepresentative } from '@/services/admin/salesReps';
import { useToast } from 'vue-toastification';

const reps = ref([]);
const toast = useToast();

const fetchReps = async () => {
  try {
    reps.value = await fetchSalesRepresentatives();
  } catch (error) {
    console.error('Error fetching sales reps:', error);
    toast.error('Nu s-au putut încărca reprezentanții.');
  }
};

const confirmDelete = async (rep) => {
  if (confirm(`Sigur vrei să ștergi reprezentantul "${rep.name}"?`)) {
    try {
      await deleteSalesRepresentative(rep.id);
      toast.success('Reprezentant șters cu succes!');
      fetchReps();
    } catch (error) {
      console.error('Error deleting rep:', error);
      toast.error('Nu s-a putut șterge reprezentantul.');
    }
  }
};

onMounted(() => {
  fetchReps();
});
</script>
