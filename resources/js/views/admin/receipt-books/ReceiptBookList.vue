<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Administrare Chitanțiere</h4>
      <button class="btn btn-primary" @click="openModal">
        <i class="bi bi-plus-lg me-2"></i>Adaugă Chitanțier
      </button>
    </div>

    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Agent / Director</th>
                <th>Serie</th>
                <th>Interval</th>
                <th>Număr Curent</th>
                <th>Status</th>
                <th>Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="book in receiptBooks" :key="book.id">
                <td>#{{ book.id }}</td>
                <td>
                  <div v-if="book.user">
                    <div class="fw-semibold">{{ book.user.first_name }} {{ book.user.last_name }}</div>
                    <div class="small text-muted">{{ book.user.email }}</div>
                  </div>
                  <span v-else class="text-muted fst-italic">Neasignat</span>
                </td>
                <td class="fw-bold">{{ book.series }}</td>
                <td>{{ book.start_number }} - {{ book.end_number }}</td>
                <td>
                  <span :class="{'text-danger': book.current_number > book.end_number}">
                    {{ book.current_number }}
                  </span>
                </td>
                <td>
                  <span class="badge" :class="book.is_active ? 'bg-success' : 'bg-secondary'">
                    {{ book.is_active ? 'Activ' : 'Inactiv' }}
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-2" @click="editBook(book)" title="Editează">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(book)" title="Șterge">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="receiptBooks.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">
                  Nu există chitanțiere definite.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end" v-if="pagination.total > pagination.per_page">
         <button class="btn btn-sm btn-outline-secondary me-2" :disabled="pagination.current_page === 1" @click="changePage(pagination.current_page - 1)">Previous</button>
         <button class="btn btn-sm btn-outline-secondary" :disabled="pagination.current_page === pagination.last_page" @click="changePage(pagination.current_page + 1)">Next</button>
      </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="receiptBookModal" tabindex="-1" ref="modalRef">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEditing ? 'Editare Chitanțier' : 'Adăugare Chitanțier' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveBook">
              <div class="mb-3">
                <label class="form-label">Agent / Director</label>
                <select class="form-select" v-model="form.user_id">
                  <option :value="null">Selectează utilizator</option>
                  <option v-for="user in agents" :key="user.id" :value="user.id">
                    {{ user.first_name }} {{ user.last_name }} ({{ user.email }})
                  </option>
                </select>
                <div class="form-text">Opțional. Poate fi asignat ulterior.</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Serie</label>
                <input type="text" class="form-control" v-model="form.series" required maxlength="10">
                <div class="form-text">Ex: B2B, IS, CT</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Număr Start</label>
                <input type="number" class="form-control" v-model="form.start_number" required :disabled="isEditing" min="1">
                <div class="form-text" v-if="!isEditing">Se vor genera automat 50 de chitanțe (ex: 101 - 150).</div>
              </div>
              <div class="mb-3 form-check" v-if="isEditing">
                <input type="checkbox" class="form-check-input" id="isActive" v-model="form.is_active">
                <label class="form-check-label" for="isActive">Activ</label>
              </div>
              <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" @click="closeModal">Anulează</button>
                <button type="submit" class="btn btn-primary">Salvează</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { Modal } from 'bootstrap'
import { fetchReceiptBooks, createReceiptBook, updateReceiptBook, deleteReceiptBook, fetchAgents } from '@/services/admin/receiptBooks'

const receiptBooks = ref([])
const agents = ref([])
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 20
})
const isEditing = ref(false)
const modalRef = ref(null)
let modalInstance = null

const form = reactive({
    id: null,
    user_id: null,
    series: '',
    start_number: '',
    is_active: true
})

const loadData = async (page = 1) => {
    try {
        const data = await fetchReceiptBooks({ page })
        receiptBooks.value = data.data
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            total: data.total,
            per_page: data.per_page
        }
    } catch (e) {
        console.error(e)
    }
}

const loadAgents = async () => {
    try {
        agents.value = await fetchAgents()
    } catch (e) {
        console.error(e)
    }
}

const openModal = () => {
    isEditing.value = false
    form.id = null
    form.user_id = null
    form.series = ''
    form.start_number = ''
    form.is_active = true
    modalInstance.show()
}

const closeModal = () => {
    modalInstance.hide()
}

const editBook = (book) => {
    isEditing.value = true
    form.id = book.id
    form.user_id = book.user_id
    form.series = book.series
    form.start_number = book.start_number
    form.is_active = !!book.is_active
    modalInstance.show()
}

const saveBook = async () => {
    try {
        if (isEditing.value) {
            await updateReceiptBook(form.id, {
                user_id: form.user_id,
                is_active: form.is_active
            })
        } else {
            await createReceiptBook({
                user_id: form.user_id,
                series: form.series,
                start_number: form.start_number
            })
        }
        closeModal()
        loadData()
    } catch (e) {
        alert(e.response?.data?.message || 'Eroare la salvare')
    }
}

const confirmDelete = async (book) => {
    if (confirm('Sigur doriți să ștergeți acest chitanțier?')) {
        try {
            await deleteReceiptBook(book.id)
            loadData()
        } catch (e) {
            alert(e.response?.data?.message || 'Eroare la ștergere')
        }
    }
}

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        loadData(page)
    }
}

onMounted(() => {
    modalInstance = new Modal(modalRef.value)
    loadData()
    loadAgents()
})
</script>
