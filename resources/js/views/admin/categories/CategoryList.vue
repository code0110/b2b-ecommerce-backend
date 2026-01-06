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

    <!-- Categories Grid -->
    <div v-if="!loading && !categories.length" class="text-center py-5">
      <div class="mb-3">
        <i class="bi bi-folder text-muted" style="font-size: 3rem;"></i>
      </div>
      <h5 class="text-muted">Nu există categorii definite</h5>
      <p class="text-muted small">Începe prin a adăuga o categorie nouă.</p>
    </div>

    <div v-else class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
      <div v-for="cat in categories" :key="cat.id" class="col">
        <div class="card h-100 border shadow-sm category-card">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <RouterLink
              class="fw-bold text-dark text-decoration-none hover-link"
              :to="{ name: 'admin-categories-edit', params: { id: cat.id } }"
            >
              {{ cat.name }}
            </RouterLink>
            <span class="badge rounded-pill" :class="cat.is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary'">
              {{ cat.is_active ? 'Publicată' : 'Ascunsă' }}
            </span>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">SLUG</small>
              <div class="font-monospace bg-light rounded px-2 py-1 small text-truncate">{{ cat.slug }}</div>
            </div>
            <div class="mb-2">
               <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">PĂRINTE</small>
               <div v-if="cat.parent_name || cat.parent?.name" class="d-flex align-items-center text-dark small">
                  <i class="bi bi-arrow-return-right me-2 text-muted"></i>
                  {{ cat.parent_name || cat.parent?.name }}
               </div>
               <div v-else class="text-muted small opacity-50">-</div>
            </div>
            <div>
               <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">ORDINE</small>
               <div class="fw-bold text-dark">{{ cat.sort_order ?? '-' }}</div>
            </div>
          </div>
          <div class="card-footer bg-white py-2 d-flex justify-content-end gap-2">
            <RouterLink
              class="btn btn-sm btn-outline-primary"
              :to="{ name: 'admin-categories-edit', params: { id: cat.id } }"
              title="Editează"
            >
              <i class="bi bi-pencil me-1"></i> Editează
            </RouterLink>
            <button
              class="btn btn-sm btn-outline-danger"
              type="button"
              @click="removeCategory(cat)"
              title="Șterge"
            >
              <i class="bi bi-trash"></i>
            </button>
          </div>
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
