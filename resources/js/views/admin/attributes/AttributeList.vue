<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1 text-gray-800">Atribute Produse</h1>
        <p class="text-muted small mb-0">Gestionează atributele și filtrele disponibile pentru produse.</p>
      </div>
      <RouterLink
        class="btn btn-primary shadow-sm"
        :to="{ name: 'admin-attributes-new' }"
      >
        <i class="bi bi-plus-lg me-2"></i>Adaugă Atribut
      </RouterLink>
    </div>

    <div v-if="error" class="alert alert-danger py-2 mb-4 shadow-sm border-0">
      {{ error }}
    </div>

    <!-- Attributes List -->
    <div v-if="!loading && !attributes.length" class="text-center py-5">
      <div class="mb-3">
        <i class="bi bi-list-check text-muted" style="font-size: 3rem;"></i>
      </div>
      <h5 class="text-muted">Nu există atribute definite</h5>
      <p class="text-muted small">Începe prin a adăuga un atribut nou.</p>
    </div>

    <div v-else class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4 py-3 text-uppercase text-muted small fw-bold">Denumire</th>
              <th class="py-3 text-uppercase text-muted small fw-bold">Tip</th>
              <th class="py-3 text-uppercase text-muted small fw-bold">Slug</th>
              <th class="py-3 text-uppercase text-muted small fw-bold text-center">Filtru</th>
              <th class="pe-4 py-3 text-uppercase text-muted small fw-bold text-end">Acțiuni</th>
            </tr>
          </thead>
          <tbody class="border-top-0">
            <tr v-for="attr in attributes" :key="attr.id">
              <td class="ps-4 fw-semibold text-dark">{{ attr.name }}</td>
              <td>
                <span class="badge bg-light text-dark border">{{ attr.type }}</span>
              </td>
              <td class="font-monospace small text-muted">{{ attr.slug }}</td>
              <td class="text-center">
                <i v-if="attr.is_filterable" class="bi bi-check-circle-fill text-success"></i>
                <i v-else class="bi bi-dash-circle text-muted opacity-50"></i>
              </td>
              <td class="pe-4 text-end">
                <div class="btn-group">
                  <RouterLink
                    :to="{ name: 'admin-attributes-edit', params: { id: attr.id } }"
                    class="btn btn-sm btn-white border text-secondary shadow-sm"
                    title="Editează"
                  >
                    <i class="bi bi-pencil"></i>
                  </RouterLink>
                  <button
                    type="button"
                    class="btn btn-sm btn-white border text-danger shadow-sm"
                    @click="removeAttribute(attr)"
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
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { fetchAllAttributes, deleteAttribute } from '@/services/admin/attributes'

const attributes = ref([])
const loading = ref(false)
const error = ref('')

const loadAttributes = async () => {
  loading.value = true
  error.value = ''
  try {
    const resp = await fetchAllAttributes()
    attributes.value = resp.data || resp || []
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca atributele.'
  } finally {
    loading.value = false
  }
}

const removeAttribute = async (attr) => {
  if (!confirm(`Ești sigur că vrei să ștergi atributul "${attr.name}"?`)) {
    return
  }

  try {
    await deleteAttribute(attr.id)
    await loadAttributes()
  } catch (e) {
    console.error(e)
    alert('Nu s-a putut șterge atributul.')
  }
}

onMounted(loadAttributes)
</script>
