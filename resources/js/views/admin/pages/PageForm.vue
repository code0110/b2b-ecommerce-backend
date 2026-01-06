<template>
  <div class="container">
    <PageHeader :title="isEdit ? 'Editează pagină' : 'Pagină nouă'" subtitle="Conținut editabil pentru homepage și alte secțiuni.">
      <template #actions>
        <RouterLink :to="{ name: 'admin-pages' }" class="btn btn-outline-secondary btn-sm">
          Înapoi la listă
        </RouterLink>
      </template>
    </PageHeader>

    <div class="card shadow-sm">
      <div class="card-body">
        <form @submit.prevent="save">
          <div class="mb-3">
            <label class="form-label">Titlu</label>
            <input v-model="form.title" type="text" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Slug (adresă URL)</label>
            <input v-model="form.slug" type="text" class="form-control" required />
            <div class="form-text">Ex.: home, despre-noi, termeni-si-conditii</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Conținut HTML</label>
            <textarea v-model="form.content" class="form-control" rows="10" placeholder="<h1>...</h1><p>...</p>"></textarea>
            <div class="form-text">Acceptă HTML. Pentru WYSIWYG, putem integra un editor ulterior.</div>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Publicată</label>
              <select v-model="form.is_published" class="form-select">
                <option :value="true">da</option>
                <option :value="false">nu</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Meta Title</label>
              <input v-model="form.meta_title" type="text" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Meta Description</label>
              <input v-model="form.meta_description" type="text" class="form-control" />
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              Salvează
            </button>
            <RouterLink :to="{ name: 'admin-pages' }" class="btn btn-outline-secondary">
              Anulează
            </RouterLink>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { adminApi } from '@/services/http'
import PageHeader from '@/components/common/PageHeader.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()
const route = useRoute()
const router = useRouter()

const isEdit = computed(() => !!route.params.id)
const form = ref({
  title: '',
  slug: '',
  content: '',
  is_published: true,
  meta_title: '',
  meta_description: ''
})

onMounted(async () => {
  if (isEdit.value) {
    try {
      const { data } = await adminApi.get(`/pages/${route.params.id}`)
      form.value = {
        title: data.title || '',
        slug: data.slug || '',
        content: data.content || '',
        is_published: !!data.is_published,
        meta_title: data.meta_title || '',
        meta_description: data.meta_description || ''
      }
    } catch (e) {
      toast.error('Nu s-a putut încărca pagina.')
    }
  }
})

const save = async () => {
  try {
    if (isEdit.value) {
      await adminApi.put(`/pages/${route.params.id}`, form.value)
      toast.success('Pagina a fost actualizată.')
    } else {
      await adminApi.post('/pages', form.value)
      toast.success('Pagina a fost creată.')
    }
    router.push({ name: 'admin-pages' })
  } catch (e) {
    toast.error(e.response?.data?.message || 'Salvarea a eșuat.')
  }
}
</script>

