<template>
  <div class="container py-4">
    <div class="row">
      <!-- Lista tichete -->
      <div class="col-lg-4 mb-3 mb-lg-0">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h1 class="h5 mb-0">Tichete suport</h1>
          <button
            type="button"
            class="btn btn-primary btn-sm"
            @click="startNewTicket"
          >
            Tichet nou
          </button>
        </div>

        <div v-if="loadingList" class="text-muted small py-3">
          Se încarcă tichetele...
        </div>

        <div
          v-else-if="tickets.length === 0"
          class="text-muted small py-3"
        >
          Nu ai tichete deschise încă.
        </div>

        <ul
          v-else
          class="list-group list-group-flush small"
        >
          <li
            v-for="t in tickets"
            :key="t.id"
            class="list-group-item list-group-item-action"
            :class="{
              active:
                currentTicket &&
                currentTicket.id === t.id,
            }"
            role="button"
            @click="openTicket(t)"
          >
            <div class="d-flex justify-content-between">
              <span class="fw-semibold">
                {{ t.subject }}
              </span>
              <span
                class="badge bg-secondary text-uppercase"
              >
                {{ t.status }}
              </span>
            </div>
            <div class="text-muted">
              {{ t.category || 'General' }} ·
              <span v-if="t.last_message_at">
                actualizat
                {{ formatDate(t.last_message_at) }}
              </span>
            </div>
          </li>
        </ul>
      </div>

      <!-- Detalii / formular -->
      <div class="col-lg-8">
        <div v-if="loadingTicket" class="text-muted small py-3">
          Se încarcă detaliile tichetului...
        </div>

        <!-- Formular tichet nou -->
        <div v-else-if="mode === 'new'">
          <div class="card shadow-sm">
            <div class="card-body">
              <h2 class="h6 mb-3">Tichet nou</h2>

              <div
                v-if="error"
                class="alert alert-danger small"
              >
                {{ error }}
              </div>

              <form
                @submit.prevent="submitNewTicket"
                class="vstack gap-3 small"
              >
                <div>
                  <label class="form-label">Subiect *</label>
                  <input
                    v-model="newTicket.subject"
                    type="text"
                    class="form-control form-control-sm"
                    required
                  />
                </div>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Categorie</label>
                    <input
                      v-model="newTicket.category"
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="ex: Comenzi, Facturare, Livrare"
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Prioritate</label>
                    <select
                      v-model="newTicket.priority"
                      class="form-select form-select-sm"
                    >
                      <option value="low">Scăzută</option>
                      <option value="normal">Normală</option>
                      <option value="high">Ridicată</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="form-label">Mesaj *</label>
                  <textarea
                    v-model="newTicket.message"
                    rows="4"
                    class="form-control form-control-sm"
                    required
                  ></textarea>
                </div>

                <div class="d-flex gap-2">
                  <button
                    type="submit"
                    class="btn btn-primary btn-sm"
                    :disabled="submitting"
                  >
                    <span
                      v-if="submitting"
                      class="spinner-border spinner-border-sm me-1"
                    ></span>
                    Trimite tichet
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    :disabled="submitting"
                    @click="
                      mode = currentTicket ? 'view' : 'idle'
                    "
                  >
                    Renunță
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Detalii tichet -->
        <div
          v-else-if="currentTicket"
          class="card shadow-sm"
        >
          <div class="card-body small">
            <div
              class="d-flex justify-content-between align-items-start mb-2"
            >
              <div>
                <h2 class="h6 mb-1">
                  {{ currentTicket.subject }}
                </h2>
                <div class="small text-muted">
                  {{ currentTicket.category || 'General' }} ·
                  <span class="text-uppercase">
                    {{ currentTicket.status }}
                  </span>
                  ·
                  <span v-if="currentTicket.created_at">
                    creat
                    {{
                      formatDate(
                        currentTicket.created_at
                      )
                    }}
                  </span>
                </div>
              </div>
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                @click="startNewTicket"
              >
                Tichet nou
              </button>
            </div>

            <div
              v-if="error"
              class="alert alert-danger small"
            >
              {{ error }}
            </div>

            <div
              class="border rounded p-2 mb-3 bg-light"
              style="max-height: 320px; overflow-y: auto"
            >
              <div
                v-for="msg in currentTicket.messages || []"
                :key="msg.id"
                class="mb-3"
              >
                <div class="small fw-semibold">
                  {{
                    msg.sender
                      ? `${msg.sender.first_name} ${
                          msg.sender.last_name || ''
                        }`
                      : 'Sistem'
                  }}
                  <span class="text-muted fw-normal">
                    ·
                    {{ formatDateTime(msg.created_at) }}
                  </span>
                </div>
                <div class="small">
                  {{ msg.message }}
                </div>
              </div>

              <div
                v-if="(currentTicket.messages || []).length === 0"
                class="small text-muted"
              >
                Nu există mesaje în acest tichet încă.
              </div>
            </div>

            <form
              @submit.prevent="submitReply"
              class="vstack gap-2 small"
            >
              <label class="form-label">Răspuns</label>
              <textarea
                v-model="replyMessage"
                rows="3"
                class="form-control form-control-sm"
                placeholder="Scrie un mesaj pentru echipa de suport..."
              ></textarea>
              <div class="d-flex justify-content-end gap-2">
                <button
                  type="submit"
                  class="btn btn-primary btn-sm"
                  :disabled="
                    submitting || !replyMessage.trim()
                  "
                >
                  <span
                    v-if="submitting"
                    class="spinner-border spinner-border-sm me-1"
                  ></span>
                  Trimite răspuns
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Nimic selectat -->
        <div
          v-else
          class="text-muted small py-3"
        >
          Selectează un tichet din listă sau creează unul
          nou.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import {
  fetchTickets,
  fetchTicket,
  createTicket,
  addTicketMessage,
} from '@/services/account/tickets';

const tickets = ref([]);
const currentTicket = ref(null);

const loadingList = ref(false);
const loadingTicket = ref(false);
const submitting = ref(false);
const error = ref('');

const mode = ref('idle'); // idle | new | view

const newTicket = reactive({
  subject: '',
  category: '',
  priority: 'normal',
  message: '',
});

const replyMessage = ref('');

const formatDate = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleDateString('ro-RO');
};

const formatDateTime = (value) => {
  if (!value) return '';
  return new Date(value).toLocaleString('ro-RO');
};

const loadTickets = async () => {
  loadingList.value = true;
  error.value = '';
  try {
    const data = await fetchTickets();
    tickets.value = data.data ?? data;
  } catch (e) {
    console.error(e);
    error.value = 'Nu am putut încărca lista de tichete.';
  } finally {
    loadingList.value = false;
  }
};

const openTicket = async (ticket) => {
  mode.value = 'view';
  currentTicket.value = null;
  loadingTicket.value = true;
  error.value = '';

  try {
    const data = await fetchTicket(ticket.id);
    currentTicket.value = data;
  } catch (e) {
    console.error(e);
    error.value = 'Nu am putut încărca tichetul.';
  } finally {
    loadingTicket.value = false;
  }
};

const startNewTicket = () => {
  mode.value = 'new';
  error.value = '';
  currentTicket.value = null;
  Object.assign(newTicket, {
    subject: '',
    category: '',
    priority: 'normal',
    message: '',
  });
};

const submitNewTicket = async () => {
  submitting.value = true;
  error.value = '';

  try {
    const created = await createTicket({ ...newTicket });
    mode.value = 'view';
    await loadTickets();
    await openTicket(created);
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ??
      'Nu am putut crea tichetul.';
  } finally {
    submitting.value = false;
  }
};

const submitReply = async () => {
  if (!replyMessage.value.trim() || !currentTicket.value) {
    return;
  }

  submitting.value = true;
  error.value = '';

  try {
    await addTicketMessage(currentTicket.value.id, {
      message: replyMessage.value,
    });
    replyMessage.value = '';
    const fresh = await fetchTicket(currentTicket.value.id);
    currentTicket.value = fresh;
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ??
      'Nu am putut trimite mesajul.';
  } finally {
    submitting.value = false;
  }
};

onMounted(loadTickets);
</script>
