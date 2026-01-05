<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1 text-gray-800">Categorii Produse</h1>
        <p class="text-muted small mb-0">Gestionează ierarhia de categorii pentru magazin.</p>
      </div>
      <RouterLink
        class="btn btn-primary shadow-sm"
        :to="{ name: 'admin-categories-new' }"
      >
        <i class="bi bi-plus-lg me-2"></i>Adaugă Categorie
      </RouterLink>
    </div>

    <div v-if="error" class="alert alert-danger py-2 mb-4 shadow-sm border-0">
      {{ error }}
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Se încarcă...</span>
          </div>
        </div>

        <div v-else-if="!categories.length" class="text-center py-5">
          <div class="mb-3">
            <i class="bi bi-folder text-muted" style="font-size: 3rem;"></i>
          </div>
          <h5 class="text-muted">Nu există categorii definite</h5>
          <p class="text-muted small">Începe prin a adăuga o categorie nouă.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 py-3 text-muted small text-uppercase fw-bold border-0">Denumire</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Slug</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Părinte</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Ordine</th>
                <th class="py-3 text-muted small text-uppercase fw-bold border-0">Status</th>
                <th class="pe-4 py-3 text-muted small text-uppercase fw-bold border-0 text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in categories" :key="cat.id">
                <td class="ps-4">
                  <RouterLink
                    class="fw-semibold text-dark text-decoration-none"
                    :to="{ name: 'admin-categories-edit', params: { id: cat.id } }"
                  >
                    {{ cat.name }}
                  </RouterLink>
                </td>
                <td><code class="text-muted bg-light px-2 py-1 rounded small">{{ cat.slug }}</code></td>
                <td class="text-muted small">
                  <span v-if="cat.parent_name || cat.parent?.name">
                    <i class="bi bi-arrow-return-right me-1 text-muted"></i>
                    {{ cat.parent_name || cat.parent?.name }}
                  </span>
                  <span v-else class="text-muted opacity-50">-</span>
                </td>
                <td class="text-muted">{{ cat.sort_order ?? '-' }}</td>
                <td>
                  <span class="badge rounded-pill" :class="cat.is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary'">
                    {{ cat.is_active ? 'Publicată' : 'Ascunsă' }}
                  </span>
                </td>
                <td class="pe-4 text-end">
                  <div class="btn-group">
                    <RouterLink
                      class="btn btn-sm btn-light border"
                      :to="{ name: 'admin-categories-edit', params: { id: cat.id } }"
                      title="Editează"
                    >
                      <i class="bi bi-pencil"></i>
                    </RouterLink>
                    <button
                      class="btn btn-sm btn-light border text-danger"
                      type="button"
                      @click="removeCategory(cat)"
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  fetchCategories,
  deleteCategory
} from '@/services/admin/categories'

const router = useRouter()
const categories = ref([])
const loading = ref(false)
const error = ref('')

const loadCategories = async () => {
  loading.value = true
  error.value = ''
  try {
    const resp = await fetchCategories({ per_page: 1000 })
    categories.value = resp.data || resp || []
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca categoriile.'
  } finally {
    loading.value = false
  }
}

const removeCategory = async (cat) => {
  if (
    !confirm(
      `Ești sigur că vrei să ștergi categoria "${cat.name}"?\nDacă are produse asociate, backend-ul poate refuza ștergerea.`
    )
  ) {
    return
  }

  try {
    await deleteCategory(cat.id)
    await loadCategories()
  } catch (e) {
    console.error(e)
    alert('Nu s-a putut șterge categoria. Verifică dacă nu are produse asociate.')
  }
}

onMounted(loadCategories)
</script>
