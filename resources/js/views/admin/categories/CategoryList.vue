<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h5 mb-0">Categorii produse</h1>
      <RouterLink
        class="btn btn-sm btn-primary"
        :to="{ name: 'admin-categories-new' }"
      >
        Adaugă categorie
      </RouterLink>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Denumire</th>
              <th>Slug</th>
              <th>Categorie părinte</th>
              <th>Ordine</th>
              <th>Status</th>
              <th style="width: 140px;">Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="text-center text-muted py-3">
                Se încarcă...
              </td>
            </tr>
            <tr v-if="!loading && !categories.length">
              <td colspan="6" class="text-center text-muted py-3">
                Nu există categorii definite.
              </td>
            </tr>
            <tr
              v-for="cat in categories"
              :key="cat.id"
            >
              <td class="small">
                <RouterLink
                  class="fw-semibold text-decoration-none"
                  :to="{ name: 'admin-categories-edit', params: { id: cat.id } }"
                >
                  {{ cat.name }}
                </RouterLink>
              </td>
              <td class="small">
                <code>{{ cat.slug }}</code>
              </td>
              <td class="small">
                {{ cat.parent_name || cat.parent?.name || '-' }}
              </td>
              <td class="small">
                {{ cat.sort_order ?? '-' }}
              </td>
              <td class="small">
                <span
                  class="badge"
                  :class="cat.is_active ? 'bg-success' : 'bg-secondary'"
                >
                  {{ cat.is_active ? 'Publicată' : 'Ascunsă' }}
                </span>
              </td>
              <td class="small">
                <div class="btn-group btn-group-sm">
                  <RouterLink
                    class="btn btn-outline-secondary"
                    :to="{ name: 'admin-categories-edit', params: { id: cat.id } }"
                  >
                    Editează
                  </RouterLink>
                  <button
                    class="btn btn-outline-danger"
                    type="button"
                    @click="removeCategory(cat)"
                  >
                    Șterge
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
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
