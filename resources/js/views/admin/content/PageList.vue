<template>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Pagini Statice</h5>
      <RouterLink :to="{ name: 'admin-pages-create' }" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Adaugă Pagină
      </RouterLink>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">Titlu</th>
              <th>Slug (URL)</th>
              <th>Status</th>
              <th>Data Creării</th>
              <th class="text-end pe-4">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="page in pages" :key="page.id">
              <td class="ps-4 fw-semibold">{{ page.title }}</td>
              <td class="font-monospace small text-muted">/{{ page.slug }}</td>
              <td>
                <span v-if="page.is_published" class="badge bg-success">Publicat</span>
                <span v-else class="badge bg-secondary">Draft</span>
              </td>
              <td class="small text-muted">{{ new Date(page.created_at).toLocaleDateString('ro-RO') }}</td>
              <td class="text-end pe-4">
                <a :href="`/pagina/${page.slug}`" target="_blank" class="btn btn-sm btn-outline-secondary me-2" title="Previzualizare">
                  <i class="bi bi-eye"></i>
                </a>
                <RouterLink :to="{ name: 'admin-pages-edit', params: { id: page.id } }" class="btn btn-sm btn-outline-primary me-2" title="Editează">
                  <i class="bi bi-pencil"></i>
                </RouterLink>
                <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(page)" title="Șterge">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="pages.length === 0">
              <td colspan="5" class="text-center py-5 text-muted">
                Nu există pagini create.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Pagination if needed -->
    <div class="card-footer bg-white border-top-0 d-flex justify-content-end py-3" v-if="pagination.total > pagination.per_page">
      <!-- Simple pagination controls could go here -->
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from '@/services/http';
import { useToast } from 'vue-toastification';

const pages = ref([]);
const pagination = ref({});
const toast = useToast();

const fetchPages = async (page = 1) => {
  try {
    const response = await axios.get(`/admin/pages?page=${page}`);
    pages.value = response.data.data;
    pagination.value = {
        total: response.data.total,
        per_page: response.data.per_page,
        current_page: response.data.current_page,
        last_page: response.data.last_page
    };
  } catch (error) {
    console.error('Error fetching pages:', error);
    toast.error('Nu s-au putut încărca paginile.');
  }
};

const confirmDelete = async (page) => {
  if (confirm(`Sigur vrei să ștergi pagina "${page.title}"?`)) {
    try {
      await axios.delete(`/admin/pages/${page.id}`);
      toast.success('Pagină ștearsă cu succes!');
      fetchPages();
    } catch (error) {
      console.error('Error deleting page:', error);
      toast.error('Nu s-a putut șterge pagina.');
    }
  }
};

onMounted(() => {
  fetchPages();
});
</script>
