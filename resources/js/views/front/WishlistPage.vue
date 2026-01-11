<template>
  <div class="container py-4">
    <!-- GUEST MODE -->
    <div v-if="!authStore.isAuthenticated">
        <div class="d-flex align-items-center justify-content-between mb-4">
          <h1 class="h3 mb-0">Produse Favorite</h1>
          <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
            {{ wishlistStore.count }} produse
          </span>
        </div>
    
        <div v-if="loadingLocal" class="text-center py-5">
          <div class="spinner-border text-orange" role="status"></div>
        </div>
    
        <div v-else-if="items.length === 0" class="text-center py-5 bg-light rounded-3">
          <i class="bi bi-heart text-muted display-1 mb-3"></i>
          <h2 class="h5">Nu ai produse favorite</h2>
          <p class="text-muted mb-4">Salvează produsele care te interesează pentru a le găsi mai ușor.</p>
          <RouterLink to="/produse" class="btn btn-orange text-white">
            Explorează Catalogul
          </RouterLink>
        </div>
    
        <div v-else class="row g-3">
            <div v-for="item in items" :key="item.id" class="col-xl-3 col-lg-4 col-md-6">
                 <ProductCard :product="item" />
            </div>
        </div>
    </div>

    <!-- AUTH MODE -->
    <div v-else class="row">
       <div class="col-md-3 mb-4">
          <!-- Sidebar -->
          <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
             <div class="card-header bg-white fw-bold">Listele mele</div>
             <div class="list-group list-group-flush">
                <button 
                   v-for="list in wishlistStore.wishlists" 
                   :key="list.id"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                   :class="{ 'active': activeListId === list.id }"
                   @click="selectList(list.id)"
                >
                   <span class="text-truncate">{{ list.name }}</span>
                   <span class="badge bg-secondary rounded-pill">{{ list.items_count }}</span>
                </button>
             </div>
             <div class="card-body border-top">
                <div class="input-group">
                   <input v-model="newListName" type="text" class="form-control form-control-sm" placeholder="Listă nouă..." @keyup.enter="createList" />
                   <button class="btn btn-sm btn-outline-primary" @click="createList">
                      <i class="bi bi-plus"></i>
                   </button>
                </div>
             </div>
          </div>
       </div>

       <div class="col-md-9">
          <!-- Main Content -->
          <div v-if="activeList" class="card border-0 shadow-sm mb-4">
             <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                   <div class="d-flex align-items-center gap-2 flex-grow-1">
                      <input 
                        v-if="editingName"
                        v-model="tempName"
                        @blur="saveName"
                        @keyup.enter="saveName"
                        class="form-control form-control-lg fw-bold border-0 px-0"
                        ref="nameInput"
                      />
                      <h1 v-else class="h4 mb-0 fw-bold cursor-pointer" @click="startEditingName" title="Click pentru a redenumi">
                        {{ activeList.name }} <i class="bi bi-pencil-fill fs-6 text-muted ms-2 opacity-50"></i>
                      </h1>
                   </div>
                   
                   <div class="d-flex align-items-center gap-2">
                      <div class="form-check form-switch mb-0">
                         <input class="form-check-input" type="checkbox" id="publicSwitch" :checked="!!activeList.is_public" @change="togglePublic">
                         <label class="form-check-label small" for="publicSwitch">Publică</label>
                      </div>
                      
                      <button v-if="activeList.is_public" class="btn btn-outline-secondary btn-sm" @click="copyLink" title="Copiază link-ul">
                         <i class="bi bi-share"></i> <span class="d-none d-sm-inline">Share</span>
                      </button>
                      
                      <button v-if="!activeList.is_default" class="btn btn-outline-danger btn-sm" @click="confirmDelete" title="Șterge lista">
                         <i class="bi bi-trash"></i>
                      </button>
                   </div>
                </div>
             </div>
             
             <div class="card-body">
                <div v-if="!activeList.items || activeList.items.length === 0" class="text-center py-5">
                   <i class="bi bi-basket text-muted display-4 mb-3"></i>
                   <p class="text-muted">Această listă este goală.</p>
                   <RouterLink to="/produse" class="btn btn-primary btn-sm">Adaugă produse</RouterLink>
                </div>
                
                <div v-else class="row g-3">
                   <div v-for="item in activeList.items" :key="item.id" class="col-xl-4 col-md-6">
                      <div class="position-relative h-100">
                         <button class="btn btn-danger btn-sm position-absolute top-0 start-0 m-2 z-3 rounded-circle shadow-sm" 
                                 style="width:32px; height:32px; padding:0;"
                                 @click="removeItem(item.product_id)" 
                                 title="Elimină din listă">
                            <i class="bi bi-trash"></i>
                         </button>
                         <ProductCard :product="item.product" />
                      </div>
                   </div>
                </div>
             </div>
          </div>
          
          <div v-else-if="wishlistStore.loading" class="text-center py-5">
             <div class="spinner-border text-primary" role="status"></div>
          </div>
       </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useWishlistStore } from '@/store/wishlist';
import { useAuthStore } from '@/store/auth';
import { fetchProductsByIds } from '@/services/catalog';
import { useToast } from 'vue-toastification';
import ProductCard from '@/components/common/ProductCard.vue';

const wishlistStore = useWishlistStore();
const authStore = useAuthStore();
const toast = useToast();

// Guest logic
const guestProducts = ref([]);
const loadingLocal = ref(false);

const items = computed(() => {
    // Used ONLY for guest mode
    return guestProducts.value;
});

const loadGuestProducts = async () => {
    if (wishlistStore.guestFavorites.length === 0) {
        guestProducts.value = [];
        return;
    }
    
    loadingLocal.value = true;
    try {
        const response = await fetchProductsByIds(wishlistStore.guestFavorites);
        guestProducts.value = response.data || [];
    } catch (e) {
        console.error(e);
    } finally {
        loadingLocal.value = false;
    }
};

// Auth logic
const activeListId = ref(null);
const newListName = ref('');
const editingName = ref(false);
const tempName = ref('');
const nameInput = ref(null);

const activeList = computed(() => {
    return wishlistStore.wishlists.find(l => l.id === activeListId.value);
});

const selectList = (id) => {
    activeListId.value = id;
};

const createList = async () => {
    if (!newListName.value.trim()) return;
    try {
        const list = await wishlistStore.createList(newListName.value);
        newListName.value = '';
        activeListId.value = list.id;
    } catch (e) {}
};

const startEditingName = () => {
    tempName.value = activeList.value.name;
    editingName.value = true;
    nextTick(() => {
        nameInput.value?.focus();
    });
};

const saveName = async () => {
    if (!editingName.value) return;
    editingName.value = false;
    if (tempName.value !== activeList.value.name && tempName.value.trim()) {
        await wishlistStore.updateList(activeList.value.id, { name: tempName.value });
    }
};

const togglePublic = async (e) => {
    const isPublic = e.target.checked;
    await wishlistStore.updateList(activeList.value.id, { is_public: isPublic });
};

const copyLink = () => {
    const url = `${window.location.origin}/wishlist/shared/${activeList.value.token}`;
    navigator.clipboard.writeText(url).then(() => {
        toast.success('Link copiat în clipboard!');
    });
};

const confirmDelete = async () => {
    if (confirm('Sigur dorești să ștergi această listă?')) {
        await wishlistStore.deleteList(activeList.value.id);
        // Select default list if available
        if (wishlistStore.wishlists.length > 0) {
            activeListId.value = wishlistStore.wishlists[0].id;
        } else {
            activeListId.value = null;
        }
    }
};

const removeItem = async (productId) => {
    await wishlistStore.removeItemFromList(activeList.value.id, productId);
};

const moveItem = async (productId, targetListId) => {
    await wishlistStore.moveItem(activeList.value.id, targetListId, productId);
};

onMounted(() => {
    if (authStore.isAuthenticated) {
        wishlistStore.loadWishlists();
    } else {
        loadGuestProducts();
    }
});

watch(() => wishlistStore.wishlists, (lists) => {
    if (lists.length > 0 && !activeListId.value) {
        activeListId.value = lists[0].id;
    }
}, { immediate: true });

watch(() => wishlistStore.guestFavorites, () => {
    if (!authStore.isAuthenticated) {
        loadGuestProducts();
    }
}, { deep: true });

watch(() => authStore.isAuthenticated, (newVal) => {
    if (newVal) wishlistStore.loadWishlists();
    else loadGuestProducts();
});
</script>
