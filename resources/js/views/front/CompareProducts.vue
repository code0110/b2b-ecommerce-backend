<template>
  <div>
    <div class="dd-page-header py-3 mb-3">
      <div class="container">
        <nav aria-label="breadcrumb" class="small mb-2">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <RouterLink :to="{ name: 'home' }">Acasă</RouterLink>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              Compară produse
            </li>
          </ol>
        </nav>
        <h1 class="h4 mb-1">Compară produse</h1>
        <p class="text-muted small mb-0">
          Compară specificațiile tehnice și prețurile pentru mai multe produse.
        </p>
      </div>
    </div>

    <div class="container pb-4">

    <div v-if="!products.length" class="alert alert-info small">
      Nu ai selectat încă produse pentru comparare.
      Poți adăuga produse din paginile de detaliu prin butonul „Adaugă la comparare” sau direct din listări.
    </div>

    <div v-else class="card shadow-sm">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <div>
          <strong>
            {{ products.length }} produs{{ products.length === 1 ? '' : 'e' }} în comparație
          </strong>
        </div>
        <button
          type="button"
          class="btn btn-outline-secondary btn-sm"
          @click="clearCompare"
        >
          Golește compararea
        </button>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-sm mb-0 align-middle">
            <tbody>
              <tr>
                <th style="width: 20%">Produs</th>
                <td
                  v-for="p in products"
                  :key="'name-' + p.id"
                  class="small"
                >
                  <div class="fw-semibold">
                    <RouterLink :to="{ name: 'product-details', params: { slug: 'produs-demo-' + p.id } }">
                      {{ p.name }}
                    </RouterLink>
                  </div>
                  <div class="text-muted">
                    Cod intern: {{ p.internalCode }}
                  </div>
                </td>
              </tr>
              <tr>
                <th>Brand</th>
                <td
                  v-for="p in products"
                  :key="'brand-' + p.id"
                  class="small"
                >
                  {{ p.brand || '-' }}
                </td>
              </tr>
              <tr>
                <th>Categorie</th>
                <td
                  v-for="p in products"
                  :key="'cat-' + p.id"
                  class="small"
                >
                  {{ p.mainCategory || '-' }}
                </td>
              </tr>
              <tr>
                <th>Preț</th>
                <td
                  v-for="p in products"
                  :key="'price-' + p.id"
                  class="small"
                >
                  <div v-if="p.promoPrice || p.promo_price || p.price">
                    <span v-if="p.list_price && p.list_price > (p.promoPrice || p.promo_price || p.price)" class="text-decoration-line-through text-muted small d-block">
                      {{ formatPriceGlobal(p.list_price, p.vat_rate, p.vat_included) }}
                    </span>
                    <span :class="(p.discountPercent || p.discount_percent || (p.list_price > (p.promoPrice || p.promo_price || p.price))) ? 'fw-bold text-danger' : 'fw-bold'">
                      {{ formatPriceGlobal(p.promoPrice || p.promo_price || p.price, p.vat_rate, p.vat_included) }}
                    </span>
                  </div>
                  <div v-else>
                    {{ formatPriceGlobal(p.price || p.list_price || p.listPrice || 0, p.vat_rate, p.vat_included) }}
                  </div>
                </td>
              </tr>
              <tr>
                <th>Stoc</th>
                <td
                  v-for="p in products"
                  :key="'stock-' + p.id"
                  class="small"
                >
<<<<<<< HEAD
                  <template v-if="showNumericStock">
                    <span v-if="p.stockQty > 0" class="text-success fw-bold">
                        Stoc: {{ p.stockQty }} buc.
                    </span>
                    <span v-else class="text-danger fw-bold">
                        Stoc: 0
                    </span>
                  </template>
                  <template v-else>
                    <span v-if="p.stockStatus === 'in_stock' || p.stockQty > 0" class="text-success fw-bold">
                        În stoc
                    </span>
                    <span v-else-if="p.stockStatus === 'low_stock'" class="text-orange fw-bold">
                        Stoc limitat
                    </span>
                    <span v-else-if="p.stockStatus === 'out_of_stock'" class="text-danger fw-bold">
                        Stoc epuizat
                    </span>
                     <span v-else-if="p.stockStatus && p.stockStatus.includes('furnizor')" class="text-info fw-bold">
                        Stoc furnizor
                    </span>
                    <span v-else class="fw-bold">
                         {{ p.stockStatus || 'La comandă' }}
                    </span>
                  </template>
=======
                  <span v-if="p.stockStatus === 'in_stock'">
                    În stoc ({{ p.stockQty }} buc)
                  </span>
                  <span
                    v-else-if="p.stockStatus === 'low_stock'"
                    class="text-orange"
                  >
                    Stoc limitat ({{ p.stockQty }} buc)
                  </span>
                  <span
                    v-else-if="p.stockStatus === 'out_of_stock'"
                    class="text-danger"
                  >
                    Stoc epuizat
                  </span>
                  <span v-else>
                    La comandă
                  </span>
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
                </td>
              </tr>
              <tr>
                <th>Atribute (demo)</th>
                <td
                  v-for="p in products"
                  :key="'attrs-' + p.id"
                  class="small"
                >
                  <div v-if="!p.attributes" class="text-muted">
                    Fără atribute definite.
                  </div>
                  <ul
                    v-else
                    class="list-unstyled mb-0"
                  >
                    <li
                      v-for="(value, key) in p.attributes"
                      :key="key"
                    >
                      <strong>{{ key }}:</strong> {{ value }}
                    </li>
                  </ul>
                </td>
              </tr>
              <tr>
                <th>Acțiuni</th>
                <td
                  v-for="p in products"
                  :key="'actions-' + p.id"
                  class="small"
                >
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm mb-1"
                    @click="removeFromCompare(p.id)"
                  >
                    Elimină
                  </button>
                  <RouterLink
                    :to="{ name: 'product-details', params: { slug: 'produs-demo-' + p.id } }"
                    class="btn btn-outline-secondary btn-sm ms-1"
                  >
                    Vezi produs
                  </RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-muted small">
        Template: în proiectul real, coloanele de mai sus se pot extinde cu atribute specifice
        categoriei (ex. dimensiuni, material, clasă de rezistență), precum și cu prețuri B2B vs B2C,
        promoții active și condiții comerciale personalizate.
      </div>
    </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useProductNavigationStore } from '@/store/productNavigation'
import { useAuthStore } from '@/store/auth'
import { usePrice } from '@/composables/usePrice'

const navStore = useProductNavigationStore()
const authStore = useAuthStore()
const { formatPrice: formatPriceGlobal } = usePrice()


const products = computed(() => navStore.compareProducts)

const showNumericStock = computed(() => {
  const roles = (authStore.user?.roles || []).map(r => (r.slug || r.code || '').toLowerCase());
  return roles.some(r => ['admin', 'sales_agent', 'sales_director', 'operator', 'manager'].includes(r));
});

const removeFromCompare = (id) => {
  navStore.removeFromCompare(id)
}

const clearCompare = () => {
  navStore.clearCompare()
}
</script>
