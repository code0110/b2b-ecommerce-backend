import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import { useAuthStore } from './auth';
import http from '@/services/http';
import { useToast } from 'vue-toastification';

export const useCompareStore = defineStore('compare', () => {
    const authStore = useAuthStore();
    const toast = useToast();

    // State
    const items = ref([]); // Full product objects
    const allAttributes = ref([]); // List of unique spec keys
    const loading = ref(false);
    
    // Guest IDs (just IDs for local tracking before fetch)
    const guestIds = ref(JSON.parse(localStorage.getItem('guest_compare_ids') || '[]'));

    // Getters
    const count = computed(() => {
        if (authStore.isAuthenticated) return items.value.length;
        return guestIds.value.length;
    });

    const isInCompare = (productId) => {
        if (authStore.isAuthenticated) {
            return items.value.some(p => p.id === productId);
        }
        return guestIds.value.includes(productId);
    };

    // Actions
    const loadComparison = async () => {
        loading.value = true;
        try {
            // For guests, we might need to send IDs to get details if not authenticated
            // But the backend route /api/products/compare uses X-Session-Key or User Auth.
            // If we are guest, we rely on X-Session-Key logic in backend or passing IDs manually.
            // The current backend implementation relies on DB storage for both.
            // So we just call the API.
            
            const response = await http.get('/products/compare');
            
            // Backend returns { products: [], all_attributes: [] }
            // or just [] if older version. We updated it to return object.
            if (response.data && response.data.products) {
                items.value = response.data.products;
                allAttributes.value = response.data.all_attributes || [];
            } else if (Array.isArray(response.data)) {
                // Fallback
                items.value = response.data;
                allAttributes.value = [];
            }

            // Sync guestIds for consistency
            if (!authStore.isAuthenticated) {
                guestIds.value = items.value.map(p => p.id);
                localStorage.setItem('guest_compare_ids', JSON.stringify(guestIds.value));
            }

        } catch (e) {
            console.error('Failed to load comparison', e);
        } finally {
            loading.value = false;
        }
    };

    const addToCompare = async (product) => {
        if (count.value >= 4) {
            toast.warning('Poți compara maxim 4 produse.');
            return;
        }

        const productId = product.id;

        // Optimistic update
        if (!authStore.isAuthenticated) {
            if (!guestIds.value.includes(productId)) {
                guestIds.value.push(productId);
                localStorage.setItem('guest_compare_ids', JSON.stringify(guestIds.value));
            }
        }

        try {
            await http.post('/products/compare', { product_id: productId });
            toast.success('Adăugat la comparare');
            await loadComparison();
        } catch (e) {
            console.error(e);
            toast.error('Eroare la adăugare');
            // Revert
            if (!authStore.isAuthenticated) {
                guestIds.value = guestIds.value.filter(id => id !== productId);
                localStorage.setItem('guest_compare_ids', JSON.stringify(guestIds.value));
            }
        }
    };

    const removeFromCompare = async (productId) => {
        // Optimistic
        if (!authStore.isAuthenticated) {
            guestIds.value = guestIds.value.filter(id => id !== productId);
            localStorage.setItem('guest_compare_ids', JSON.stringify(guestIds.value));
        } else {
             items.value = items.value.filter(p => p.id !== productId);
        }

        try {
            await http.delete(`/products/compare/${productId}`);
            toast.info('Eliminat din comparare');
            await loadComparison();
        } catch (e) {
            console.error(e);
        }
    };

    const clearCompare = async () => {
        // Not directly supported by backend bulk delete yet, so we loop or just clear local
        // Ideally backend should have a clear endpoint.
        // For now, loop remove (inefficient but works) or just reset UI if backend not critical.
        // Let's implement one-by-one for now or just clear local state.
        
        for (const p of items.value) {
            await http.delete(`/products/compare/${p.id}`);
        }
        items.value = [];
        guestIds.value = [];
        localStorage.removeItem('guest_compare_ids');
        toast.info('Lista de comparare a fost golită');
    };

    // Watch for auth changes
    watch(() => authStore.isAuthenticated, () => {
        loadComparison();
    });

    return {
        items,
        allAttributes,
        loading,
        count,
        isInCompare,
        loadComparison,
        addToCompare,
        removeFromCompare,
        clearCompare
    };
});
