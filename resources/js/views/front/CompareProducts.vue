<template>
  <div class="container">
    <PageHeader
      title="Compară produse"
      subtitle="Compară specificațiile tehnice și prețurile pentru mai multe produse."
    >
      <template #breadcrumbs>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <RouterLink :to="{ name: 'home' }">Acasă</RouterLink>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              Compară produse
            </li>
          </ol>
        </nav>
      </template>
    </PageHeader>

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
                      {{ formatMoney(p.list_price) }}
                    </span>
                    <span :class="(p.discountPercent || p.discount_percent || (p.list_price > (p.promoPrice || p.promo_price || p.price))) ? 'fw-bold text-danger' : 'fw-bold'">
                      {{ formatMoney(p.promoPrice || p.promo_price || p.price) }}
                    </span>
                  </div>
                  <div v-else>
                    {{ formatMoney(p.price || p.list_price || p.listPrice || 0) }}
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
                  <span v-if="p.stockStatus === 'in_stock'">
                    În stoc ({{ p.stockQty }} buc)
                  </span>
                  <span
                    v-else-if="p.stockStatus === 'low_stock'"
                    class="text-warning"
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
                    class="btn btn-outline-primary btn-sm ms-1"
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
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { useProductNavigationStore } from '@/store/productNavigation'

const navStore = useProductNavigationStore()

const products = computed(() => navStore.compareProducts)

const formatMoney = (value) => {
  const v = Number(value || 0)
  return (
    v.toLocaleString('ro-RO', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' RON'
  )
}

const removeFromCompare = (id) => {
  navStore.removeFromCompare(id)
}

const clearCompare = () => {
  navStore.clearCompare()
}
</script>
