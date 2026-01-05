<template>
  <div class="container py-4">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    
    <div v-else-if="offer" class="row">
      <!-- Left Column: Details -->
      <div class="col-lg-8 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Ofertă #{{ offer.id }}</h1>
            <span class="badge" :class="statusBadge(offer.status)">
                {{ statusLabel(offer.status) }}
            </span>
        </div>
        
        <!-- Offer Details Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="small text-muted">Data ofertă</div>
                        <div class="fw-bold">{{ formatDate(offer.created_at) }}</div>
                    </div>
                     <div>
                        <div class="small text-muted">Valabilă până la</div>
                        <div class="fw-bold">{{ offer.valid_until ? formatDate(offer.valid_until) : 'Nedeterminat' }}</div>
                    </div>
                    <div>
                        <div class="small text-muted">Agent</div>
                        <div class="fw-bold">{{ offer.agent?.name || 'N/A' }}</div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Produs</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end">Preț Unitar</th>
                                <th class="text-center">Discount</th>
                                <th class="text-end pe-4">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in offer.items" :key="item.id">
                                <td class="ps-4">
                                    <div class="fw-bold">{{ item.product?.name }}</div>
                                    <div class="small text-muted">{{ item.product?.internal_code }}</div>
                                </td>
                                <td class="text-center">{{ item.quantity }}</td>
                                <td class="text-end">{{ formatPrice(item.unit_price) }}</td>
                                <td class="text-center">
                                    <span v-if="item.discount_percent > 0" class="badge bg-success">-{{ item.discount_percent }}%</span>
                                    <span v-else>-</span>
                                </td>
                                <td class="text-end pe-4 fw-bold">{{ formatPrice(item.final_price * item.quantity) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold pt-3">Total:</td>
                                <td class="text-end pe-4 fw-bold pt-3 fs-5">{{ formatPrice(offer.total_amount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
             <div class="card-footer bg-white border-top-0 py-3 d-flex justify-content-end gap-2" v-if="canAction">
                <button class="btn btn-outline-danger" @click="changeStatus('rejected')">
                    Refuză Oferta
                </button>
                <button class="btn btn-success text-white" @click="changeStatus('accepted')">
                    Acceptă Oferta
                </button>
            </div>
        </div>
      </div>
      
      <!-- Right Column: Chat -->
      <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Discuție / Negociere</h6>
            </div>
            <div class="card-body d-flex flex-column" style="height: 500px;">
                <div class="flex-grow-1 overflow-auto mb-3 pe-2" ref="messagesContainer">
                    <div v-if="!offer.messages || offer.messages.length === 0" class="text-center text-muted my-5 small">
                        Nu există mesaje. Începe o discuție mai jos.
                    </div>
                    <div v-for="msg in sortedMessages" :key="msg.id" class="mb-3 d-flex flex-column" :class="{'align-items-end': isMe(msg.user_id), 'align-items-start': !isMe(msg.user_id)}">
                        <div class="p-2 rounded shadow-sm" style="max-width: 85%;" :class="isMe(msg.user_id) ? 'bg-primary text-white' : 'bg-light text-dark'">
                            <div class="small fw-bold mb-1" v-if="!isMe(msg.user_id)">{{ msg.user?.name }}</div>
                            <div class="text-break">{{ msg.message }}</div>
                        </div>
                        <div class="small text-muted mt-1" style="font-size: 0.7rem;">{{ formatTime(msg.created_at) }}</div>
                    </div>
                </div>
                
                <div class="mt-auto">
                    <textarea 
                        class="form-control mb-2" 
                        rows="2" 
                        placeholder="Scrie un mesaj..." 
                        v-model="newMessage"
                        @keydown.enter.prevent="sendMessage"
                    ></textarea>
                    <button class="btn btn-primary w-100 btn-sm" @click="sendMessage" :disabled="sending || !newMessage.trim()">
                        <i class="bi bi-send me-1"></i> Trimite
                    </button>
                </div>
            </div>
        </div>
      </div>
    </div>
    
    <div v-else class="alert alert-danger">
        Oferta nu a fost găsită sau nu aveți acces.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchOffer, updateOfferStatus, sendOfferMessage } from '@/services/account/offers';
import { useAuthStore } from '@/store/auth';
import { useToast } from 'vue-toastification';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const offer = ref(null);
const loading = ref(false);
const sending = ref(false);
const newMessage = ref('');
const messagesContainer = ref(null);

const sortedMessages = computed(() => {
    if (!offer.value?.messages) return [];
    return [...offer.value.messages].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
});

const isMe = (userId) => authStore.user?.id === userId;

const canAction = computed(() => {
    if (!offer.value) return false;
    return ['sent', 'negotiation', 'approved'].includes(offer.value.status);
});

const loadOffer = async () => {
    loading.value = true;
    try {
        offer.value = await fetchOffer(route.params.id);
        scrollToBottom();
    } catch (e) {
        console.error(e);
        toast.error('Nu am putut încărca oferta.');
    } finally {
        loading.value = false;
    }
};

const changeStatus = async (status) => {
    if (!confirm(status === 'accepted' ? 'Confirmi acceptarea ofertei?' : 'Confirmi refuzarea ofertei?')) return;
    
    try {
        await updateOfferStatus(offer.value.id, status);
        toast.success(status === 'accepted' ? 'Ofertă acceptată!' : 'Ofertă refuzată.');
        loadOffer(); // Reload to refresh status
    } catch (e) {
        toast.error('Eroare la actualizarea statusului.');
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    
    sending.value = true;
    try {
        await sendOfferMessage(offer.value.id, newMessage.value);
        newMessage.value = '';
        await loadOffer(); // Reload to get new message and potential status change
    } catch (e) {
        toast.error('Eroare la trimiterea mesajului.');
    } finally {
        sending.value = false;
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const formatPrice = (val) => new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(val);
const formatDate = (val) => new Date(val).toLocaleDateString('ro-RO');
const formatTime = (val) => new Date(val).toLocaleString('ro-RO');

const statusLabel = (s) => {
    const map = {
        'draft': 'Draft',
        'sent': 'Primită',
        'pending_approval': 'În Așteptare',
        'approved': 'Aprobată',
        'negotiation': 'Negociere',
        'rejected': 'Refuzată',
        'accepted': 'Acceptată',
        'completed': 'Finalizată'
    };
    return map[s] || s;
};

const statusBadge = (s) => {
    const map = {
        'draft': 'bg-secondary',
        'sent': 'bg-primary',
        'pending_approval': 'bg-warning text-dark',
        'approved': 'bg-success',
        'negotiation': 'bg-info text-dark',
        'rejected': 'bg-danger',
        'accepted': 'bg-success',
        'completed': 'bg-success'
    };
    return map[s] || 'bg-secondary';
};

onMounted(loadOffer);

</script>
