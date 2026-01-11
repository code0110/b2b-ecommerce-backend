import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import { useAuthStore } from './auth';
import * as wishlistApi from '@/services/wishlist';
import { useToast } from 'vue-toastification';

export const useWishlistStore = defineStore('wishlist', () => {
  const authStore = useAuthStore();
  const toast = useToast();
  
  // State
  const wishlists = ref([]);
  const favorites = ref([]); // Array of product IDs for quick lookup (from default list)
  const loading = ref(false);
  
  // Guest wishlist
  const guestFavorites = ref(JSON.parse(localStorage.getItem('guest_favorites') || '[]'));

  // Getters
  const isFavorite = (productId) => {
    if (authStore.isAuthenticated) {
      return favorites.value.includes(productId);
    }
    return guestFavorites.value.includes(productId);
  };

  const count = computed(() => {
    if (authStore.isAuthenticated) {
      return favorites.value.length;
    }
    return guestFavorites.value.length;
  });

  // Actions
  const loadWishlists = async () => {
    if (!authStore.isAuthenticated) return;
    
    loading.value = true;
    try {
      const response = await wishlistApi.fetchWishlists();
      wishlists.value = response.data;
      
      // Update favorites from default list
      const defaultList = wishlists.value.find(w => w.is_default);
      if (defaultList && defaultList.items) {
          favorites.value = defaultList.items.map(item => item.product_id);
      }
    } catch (e) {
      console.error('Failed to load wishlists', e);
    } finally {
      loading.value = false;
    }
  };

  const toggleProduct = async (product) => {
    const productId = product.id;
    
    if (!authStore.isAuthenticated) {
      // Guest logic
      const index = guestFavorites.value.indexOf(productId);
      if (index === -1) {
        guestFavorites.value.push(productId);
        toast.success('Produs adăugat la favorite (Local)');
      } else {
        guestFavorites.value.splice(index, 1);
        toast.info('Produs eliminat de la favorite');
      }
      localStorage.setItem('guest_favorites', JSON.stringify(guestFavorites.value));
      return;
    }

    // Auth logic
    try {
      // Optimistic update
      const isFav = favorites.value.includes(productId);
      if (isFav) {
        favorites.value = favorites.value.filter(id => id !== productId);
      } else {
        favorites.value.push(productId);
      }

      const response = await wishlistApi.toggleWishlistItem(productId);
      
      if (response.data.status === 'added') {
        toast.success('Produs adăugat la favorite');
        if (!favorites.value.includes(productId)) favorites.value.push(productId);
      } else {
        toast.info('Produs eliminat de la favorite');
        favorites.value = favorites.value.filter(id => id !== productId);
      }
    } catch (e) {
      console.error(e);
      toast.error('Eroare la actualizarea favoritelor');
      // Revert optimistic update
      loadWishlists();
    }
  };

  const mergeGuestWishlist = async () => {
    if (guestFavorites.value.length === 0) return;
    
    try {
      await wishlistApi.mergeWishlist(guestFavorites.value);
      guestFavorites.value = [];
      localStorage.removeItem('guest_favorites');
      await loadWishlists();
      toast.success('Favoritele au fost sincronizate în contul tău!');
    } catch (e) {
      console.error('Merge failed', e);
    }
  };

  const deleteList = async (id) => {
    try {
      await wishlistApi.deleteWishlist(id);
      wishlists.value = wishlists.value.filter(w => w.id !== id);
      toast.success('Listă ștearsă');
    } catch (e) {
      console.error(e);
      toast.error(e.response?.data?.message || 'Eroare la ștergere');
    }
  };

  const removeItemFromList = async (listId, productId) => {
    try {
        await wishlistApi.toggleWishlistItem(productId, listId);
        const list = wishlists.value.find(w => w.id === listId);
        if (list) {
            list.items = list.items.filter(i => i.product_id !== productId);
            list.items_count--;
            // If this was default list, update favorites array
            if (list.is_default) {
                favorites.value = favorites.value.filter(id => id !== productId);
            }
        }
        toast.success('Produs eliminat din listă');
    } catch (e) {
        console.error(e);
        toast.error('Eroare la eliminare');
    }
  };

  const createList = async (name) => {
    try {
      const response = await wishlistApi.createWishlist({ name });
      const newList = { ...response.data, items: [], items_count: 0 };
      wishlists.value.push(newList);
      toast.success('Listă creată cu succes');
      return newList;
    } catch (e) {
      console.error(e);
      toast.error('Eroare la crearea listei');
      throw e;
    }
  };

  const updateList = async (id, data) => {
    try {
      const response = await wishlistApi.updateWishlist(id, data);
      const index = wishlists.value.findIndex(w => w.id === id);
      if (index !== -1) {
        const items = wishlists.value[index].items; 
        wishlists.value[index] = { ...wishlists.value[index], ...response.data, items };
      }
      toast.success('Listă actualizată');
    } catch (e) {
      console.error(e);
      toast.error('Eroare la actualizare');
    }
  };

  const moveItem = async (fromListId, toListId, productId) => {
      try {
          const targetList = wishlists.value.find(w => w.id === toListId);
          const sourceList = wishlists.value.find(w => w.id === fromListId);
          if (!targetList || !sourceList) return;

          // Check if already in target
          const isInTarget = targetList.items.some(i => i.product_id === productId);
          
          if (!isInTarget) {
              await wishlistApi.toggleWishlistItem(productId, toListId);
              // Optimistic add to target
              const item = sourceList.items.find(i => i.product_id === productId);
              if (item) {
                  targetList.items.push(item);
                  targetList.items_count++;
              }
          }
          
          // Remove from source
          await wishlistApi.toggleWishlistItem(productId, fromListId);
          sourceList.items = sourceList.items.filter(i => i.product_id !== productId);
          sourceList.items_count--;

          toast.success('Produs mutat cu succes');
      } catch (e) {
          console.error(e);
          toast.error('Eroare la mutarea produsului');
          await loadWishlists(); // Reload on error to ensure sync
      }
  };

  // Watch for login
  watch(() => authStore.isAuthenticated, (isAuth) => {
    if (isAuth) {
      mergeGuestWishlist().then(() => loadWishlists());
    } else {
      favorites.value = [];
      wishlists.value = [];
    }
  }, { immediate: true });

  return {
    wishlists,
    favorites,
    loading,
    isFavorite,
    count,
    loadWishlists,
    toggleProduct,
    mergeGuestWishlist,
    createList,
    updateList,
    deleteList,
    removeItemFromList,
    moveItem,
    guestFavorites
  };
});
