<template>
  <div class="container">
    <PageHeader title="Pagini" subtitle="Gestionează conținutul site-ului (homepage, despre, termeni).">
      <template #actions>
        <RouterLink :to="{ name: 'admin-pages-new' }" class="btn btn-primary btn-sm">
          <i class="bi bi-plus-lg me-1"></i> Adaugă pagină
        </RouterLink>
      </template>
    </PageHeader>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <input v-model="q" @input="loadPages" type="search" class="form-control form-control-sm" placeholder="Caută după titlu sau slug..." style="max-width: 320px;">
          <div class="small text-muted">Total: {{ pagination.total }}</div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th style="width: 35%">Titlu</th>
                <th style="width: 20%">Slug</th>
                <th style="width: 15%">Publicată</th>
                <th style="width: 15%">Actualizată</th>
                <th style="width: 15%"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="page in pages" :key="page.id">
                <td class="fw-semibold">{{ page.title }}</td>
                <td><span class="badge bg-light text-dark">/{{ page.slug }}</span></td>
                <td>
                  <span :class="page.is_published ? 'badge bg-success' : 'badge bg-secondary'">
                    {{ page.is_published ? 'da' : 'nu' }}
                  </span>
                </td>
                <td class="small text-muted">{{ new Date(page.updated_at).toLocaleString('ro-RO') }}</td>
                <td class="text-end">
                  <RouterLink :to="{ name: 'admin-pages-edit', params: { id: page.id } }" class="btn btn-outline-primary btn-sm me-2">
                    Editează
                  </RouterLink>
                  <button class="btn btn-outline-danger btn-sm" @click="remove(page)">
                    Șterge
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
.</template>

<script setup>
import { ref } from 'vue'
import { adminApi } from '@/services/http'
import PageHeader from '@/components/common/PageHeader.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()
const pages = ref([])
const q = ref('')
const pagination = ref({ total: 0 })

const loadPages = async () => {
  try {
    const { data } = await adminApi.get('/pages', { params: { q: q.value } })
    pages.value = data.data || data
    pagination.value = data.meta || { total: pages.value.length }
  } catch (e) {
    toast.error('Nu s-a putut încărca lista de pagini.')
  }
}

const remove = async (page) => {
  if (!confirm(`Sigur vrei să ștergi pagina "${page.title}"?`)) return
  try {
    await adminApi.delete(`/pages/${page.id}`)
    toast.success('Pagina a fost ștearsă.')
    loadPages()
  } catch (e) {
    toast.error('Ștergerea a eșuat.')
  }
}

loadPages()
</script>

