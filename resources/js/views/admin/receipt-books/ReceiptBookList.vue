<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Administrare Chitanțiere</h4>
      <button class="btn btn-primary" @click="openModal">
        <i class="bi bi-plus-lg me-2"></i>Adaugă Chitanțier
      </button>
    </div>

    <div v-if="receiptBooks.length === 0" class="text-center py-5">
      <div class="mb-3">
        <i class="bi bi-journal-album text-muted opacity-25" style="font-size: 3rem;"></i>
      </div>
      <h5 class="text-muted">Nu există chitanțiere</h5>
      <p class="text-muted small">Adaugă primul chitanțier pentru a începe.</p>
    </div>

    <div v-else class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 mb-4">
      <div v-for="book in receiptBooks" :key="book.id" class="col">
        <div class="card h-100 border shadow-sm hover-shadow transition-all">
           <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
              <div>
                 <span class="badge bg-light text-dark border me-2">#{{ book.id }}</span>
                 <span class="fw-bold text-primary">{{ book.series }}</span>
              </div>
              <span class="badge rounded-pill" :class="book.is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary'">
                 {{ book.is_active ? 'Activ' : 'Inactiv' }}
              </span>
           </div>
           <div class="card-body">
              <div class="mb-3">
                 <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">AGENT / DIRECTOR</small>
                 <div v-if="book.user" class="d-flex align-items-center mt-1">
                    <div class="avatar-circle-sm me-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.7rem;">
                       {{ book.user.first_name?.[0] }}{{ book.user.last_name?.[0] }}
                    </div>
                    <div class="text-truncate">
                       <div class="fw-semibold text-dark small text-truncate">{{ book.user.first_name }} {{ book.user.last_name }}</div>
                       <div class="text-muted text-truncate" style="font-size: 0.75rem;">{{ book.user.email }}</div>
                    </div>
                 </div>
                 <div v-else class="mt-1 text-muted small fst-italic">
                    <i class="bi bi-person-x me-1"></i> Neasignat
                 </div>
              </div>

              <div class="row g-2">
                 <div class="col-6">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">INTERVAL</small>
                    <div class="fw-semibold text-dark">{{ book.start_number }} - {{ book.end_number }}</div>
                 </div>
                 <div class="col-6">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">NUMĂR CURENT</small>
                    <div class="fw-bold" :class="book.current_number > book.end_number ? 'text-danger' : 'text-success'">
                       {{ book.current_number }}
                       <i v-if="book.current_number > book.end_number" class="bi bi-exclamation-circle-fill ms-1 small"></i>
                    </div>
                 </div>
              </div>
              
              <div class="mt-3">
                 <div class="progress" style="height: 6px;">
                    <div class="progress-bar" role="progressbar" 
                       :style="{ width: Math.min(((book.current_number - book.start_number) / (book.end_number - book.start_number)) * 100, 100) + '%' }"
                       :class="book.current_number > book.end_number ? 'bg-danger' : 'bg-primary'"
                    ></div>
                 </div>
                 <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted" style="font-size: 0.7rem;">Progres</small>
                    <small class="text-muted" style="font-size: 0.7rem;">{{ Math.min(Math.round(((book.current_number - book.start_number) / (book.end_number - book.start_number)) * 100), 100) }}%</small>
                 </div>
              </div>
           </div>
           <div class="card-footer bg-white py-2 d-flex justify-content-end gap-2">
              <button class="btn btn-sm btn-outline-primary" @click="editBook(book)">
                 <i class="bi bi-pencil me-1"></i> Editează
              </button>
              <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(book)">
                 <i class="bi bi-trash me-1"></i> Șterge
              </button>
           </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center" v-if="pagination.total > pagination.per_page">
         <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm shadow-sm">
                <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                   <button class="page-link border-0" @click="changePage(pagination.current_page - 1)">
                      <i class="bi bi-chevron-left"></i>
                   </button>
                </li>
                <li class="page-item disabled">
                   <span class="page-link border-0 text-muted bg-transparent">
                      Pagina {{ pagination.current_page }} din {{ pagination.last_page }}
                   </span>
                </li>
                <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                   <button class="page-link border-0" @click="changePage(pagination.current_page + 1)">
                      <i class="bi bi-chevron-right"></i>
                   </button>
                </li>
            </ul>
         </nav>
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
        // console.error(e)
    }
}

const loadAgents = async () => {
    try {
        agents.value = await fetchAgents()
    } catch (e) {
        // console.error(e)
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
