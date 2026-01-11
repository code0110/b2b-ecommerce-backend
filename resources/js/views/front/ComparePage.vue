<template>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Comparare Produse</h1>
                <p class="text-muted mb-0">Compară specificațiile tehnice pentru a lua cea mai bună decizie.</p>
            </div>
            <div v-if="compareStore.items.length > 0" class="d-flex gap-2">
                <div class="form-check form-switch d-flex align-items-center gap-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="diffSwitch" v-model="showDifferencesOnly">
                    <label class="form-check-label small" for="diffSwitch">Arată doar diferențele</label>
                </div>
                <button class="btn btn-outline-danger btn-sm" @click="compareStore.clearCompare">
                    <i class="bi bi-trash"></i> Golește lista
                </button>
            </div>
        </div>

        <div v-if="compareStore.loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>

        <div v-else-if="compareStore.items.length === 0" class="text-center py-5 bg-light rounded-3">
            <i class="bi bi-arrow-left-right text-muted display-1 mb-3"></i>
            <h2 class="h5">Nu ai produse selectate pentru comparare</h2>
            <p class="text-muted mb-4">Adaugă produse din catalog pentru a vedea diferențele.</p>
            <RouterLink to="/produse" class="btn btn-primary">
                Mergi la Catalog
            </RouterLink>
        </div>

        <div v-else class="table-responsive shadow-sm rounded border bg-white">
            <table class="table table-bordered mb-0 align-middle compare-table">
                <thead>
                    <tr class="bg-light">
                        <th style="min-width: 200px; width: 20%;">Specificații</th>
                        <th v-for="product in compareStore.items" :key="product.id" style="min-width: 250px; width: 20%;">
                            <div class="position-relative p-2">
                                <button class="btn-close position-absolute top-0 end-0 m-1" @click="compareStore.removeFromCompare(product.id)" title="Elimină"></button>
                                <div class="text-center mb-2">
                                    <div class="ratio ratio-1x1 mb-2 mx-auto" style="width: 120px;">
                                        <img :src="product.main_image_url || '/images/placeholder.png'" class="object-fit-contain" :alt="product.name">
                                    </div>
                                    <h6 class="mb-1 text-truncate-2 small fw-bold">
                                        <RouterLink :to="`/produs/${product.slug}`" class="text-decoration-none text-dark">
                                            {{ product.name }}
                                        </RouterLink>
                                    </h6>
                                    <div class="text-danger fw-bold mb-2">
                                        {{ formatPrice(product) }}
                                    </div>
                                    
                                    <div class="d-flex justify-content-center gap-2 mb-2">
                                        <div class="input-group input-group-sm" style="max-width: 120px;">
                                            <button class="btn btn-outline-secondary" type="button" @click="decrement(product.id)">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <input type="text" class="form-control text-center px-1" :value="qtys[product.id] || 1" readonly>
                                            <button class="btn btn-outline-secondary" type="button" @click="increment(product.id)">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <button class="btn btn-primary btn-sm w-100" @click="addToCart(product)" :disabled="loading === product.id">
                                        <span v-if="loading === product.id" class="spinner-border spinner-border-sm me-1"></span>
                                        <i v-else class="bi bi-cart-plus me-1"></i> Adaugă
                                    </button>
                                </div>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- General Info -->
                    <template v-if="!showDifferencesOnly">
                        <tr>
                            <th class="bg-light text-secondary small text-uppercase px-3">Informații Generale</th>
                            <td v-for="product in compareStore.items" :key="product.id" class="bg-light"></td>
                        </tr>
                        <tr>
                            <td class="fw-bold px-3">Brand</td>
                            <td v-for="product in compareStore.items" :key="product.id" class="text-center">
                                {{ product.brand?.name || '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold px-3">Cod Produs</td>
                            <td v-for="product in compareStore.items" :key="product.id" class="text-center font-monospace small">
                                {{ product.internal_code || '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold px-3">Disponibilitate</td>
                            <td v-for="product in compareStore.items" :key="product.id" class="text-center">
                                <span :class="getStockClass(product)">
                                    {{ getStockLabel(product) }}
                                </span>
                            </td>
                        </tr>
                    </template>

                    <!-- Dynamic Technical Specs -->
                    <template v-if="compareStore.allAttributes.length > 0">
                        <tr>
                            <th class="bg-light text-secondary small text-uppercase px-3" :colspan="compareStore.items.length + 1">
                                Specificații Tehnice
                            </th>
                        </tr>
                        <template v-for="attr in compareStore.allAttributes" :key="attr">
                            <tr v-if="!showDifferencesOnly || isDifferent(attr)">
                                <td class="px-3 text-muted">{{ attr }}</td>
                                <td v-for="product in compareStore.items" :key="product.id" class="text-center" :class="{ 'bg-warning bg-opacity-10': isDifferent(attr) }">
                                    {{ getAttributeValue(product, attr) }}
                                </td>
                            </tr>
                        </template>
                    </template>
                    <tr v-else>
                         <td :colspan="compareStore.items.length + 1" class="text-center text-muted py-4">
                             Nu există specificații tehnice detaliate disponibile pentru aceste produse.
                         </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useCompareStore } from '@/store/compare';
import { useCartStore } from '@/store/cart';
import { useAuthStore } from '@/store/auth';
import { useToast } from 'vue-toastification';

const compareStore = useCompareStore();
const cartStore = useCartStore();
const authStore = useAuthStore();
const toast = useToast();

const qtys = ref({});
const loading = ref(null);
const showDifferencesOnly = ref(false);

const showNumericStock = computed(() => {
    const roles = (authStore.user?.roles || []).map(r => (r.slug || r.code || '').toLowerCase());
    return roles.some(r => ['admin', 'sales_agent', 'sales_director', 'operator', 'manager'].includes(r));
});

const increment = (id) => {
    qtys.value[id] = (qtys.value[id] || 1) + 1;
};

const decrement = (id) => {
    if ((qtys.value[id] || 1) > 1) {
        qtys.value[id]--;
    }
};

const formatPrice = (product) => {
    const price = product.price_override || product.list_price || 0;
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(price);
};

const getStockLabel = (product) => {
    if (showNumericStock.value) {
        return product.stock_qty > 0 ? `Stoc: ${product.stock_qty} buc.` : 'Stoc: 0';
    }
    
    if (product.stock_qty > 0 || (product.stock_status && product.stock_status.toLowerCase().includes('in stoc'))) return 'În stoc';
    if (product.stock_status && product.stock_status.toLowerCase().includes('furnizor')) return 'Stoc furnizor';
    if (product.stock_status) return product.stock_status;
    return 'Stoc epuizat';
};

const getStockClass = (product) => {
    if (showNumericStock.value) {
        return product.stock_qty > 0 ? 'badge bg-success' : 'badge bg-danger';
    }

    if (product.stock_qty > 0 || (product.stock_status && product.stock_status.toLowerCase().includes('in stoc'))) return 'badge bg-success';
    if (product.stock_status && product.stock_status.toLowerCase().includes('furnizor')) return 'badge bg-info';
    if (product.stock_status) return 'badge bg-warning text-dark';
    return 'badge bg-danger';
};

const getAttributeValue = (product, attrKey) => {
    if (!product.technical_specs) return '-';
    // Check if technical_specs is array or object. Based on Laravel cast, it's array/json.
    // If it's Key-Value object:
    return product.technical_specs[attrKey] || '-';
};

const isDifferent = (attrKey) => {
    if (compareStore.items.length < 2) return false;
    const firstVal = getAttributeValue(compareStore.items[0], attrKey);
    return compareStore.items.some(p => getAttributeValue(p, attrKey) !== firstVal);
};

const addToCart = async (product) => {
    loading.value = product.id;
    try {
        const qty = qtys.value[product.id] || 1;
        await cartStore.addItem({ product_id: product.id, quantity: qty });
        toast.success('Produs adăugat în coș');
        qtys.value[product.id] = 1;
    } catch (e) {
        toast.error('Eroare la adăugare în coș');
    } finally {
        loading.value = null;
    }
};

onMounted(() => {
    compareStore.loadComparison();
});
</script>

<style scoped>
.compare-table th, .compare-table td {
    vertical-align: middle;
}
</style>
