<template>
  <div class="container-fluid">
    <PageHeader
      title="Promoții & discounturi"
      subtitle="Administrare campanii promoționale, reguli de discount și segmentare."
    >
      <RouterLink :to="{ name: 'admin-promotions-new' }" class="btn btn-primary btn-sm">
        + Promoție nouă
      </RouterLink>
    </PageHeader>

    <!-- Filtre -->
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <form class="row g-3 align-items-end" @submit.prevent>
          <div class="col-md-3">
            <label class="form-label small text-muted">Căutare promoție</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="Denumire promoție, slug..."
            />
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Status</label>
            <select v-model="filters.status" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="active">Activă</option>
              <option value="upcoming">În curând</option>
              <option value="inactive">Inactivă</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Tip promoție</label>
            <select v-model="filters.type" class="form-select form-select-sm">
              <option value="">Toate</option>
              <option value="discount_percent">Discount procentual</option>
              <option value="discount_fixed">Discount valoric</option>
              <option value="x_get_y">Cumperi X → primești Y</option>
              <option value="bundle">Pachet (bundle)</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Tip client</label>
            <select v-model="filters.clientType" class="form-select form-select-sm">
              <option value="">B2B & B2C</option>
              <option value="B2B">B2B</option>
              <option value="B2C">B2C</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small text-muted">Grup / segment</label>
            <input
              v-model="filters.segment"
              type="text"
              class="form-control form-control-sm"
              placeholder="Nume grup client sau categorie"
            />
          </div>
        </form>
      </div>
    </div>

    <!-- Lista promoții -->
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Denumire promoție</th>
                <th>Perioadă</th>
                <th>Tip promoție</th>
                <th>Tip client</th>
                <th>Segment</th>
                <th>Status</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredPromotions.length === 0">
                <td colspan="7" class="text-center text-muted py-4">
                  Nu există promoții pentru filtrele selectate.
                </td>
              </tr>
              <tr v-for="promo in filteredPromotions" :key="promo.id">
                <td>
                  <div class="fw-semibold">{{ promo.name }}</div>
                  <div class="small text-muted">
                    /promotii/{{ promo.slug }}
                  </div>
                </td>
                <td class="small">
                  <div>{{ promo.startDate }} – {{ promo.endDate }}</div>
                </td>
                <td class="small">
                  {{ typeLabel(promo.type) }}
                </td>
                <td class="small">
                  <span v-for="ct in promo.clientTypes" :key="ct" class="badge bg-light text-dark me-1">
                    {{ ct }}
                  </span>
                </td>
                <td class="small">
                  <span
                    v-for="group in promo.customerGroups"
                    :key="'g-' + group"
                    class="badge bg-secondary me-1"
                  >
                    {{ group }}
                  </span>
                  <span
                    v-for="cat in promo.categories"
                    :key="'c-' + cat"
                    class="badge bg-info text-dark me-1"
                  >
                    {{ cat }}
                  </span>
                  <span
                    v-for="brand in promo.brands"
                    :key="'b-' + brand"
                    class="badge bg-warning text-dark me-1"
                  >
                    {{ brand }}
                  </span>
                </td>
                <td class="small">
                  <span :class="['badge', statusBadgeClass(promo.status)]">
                    {{ statusLabel(promo.status) }}
                  </span>
                </td>
                <td class="text-end">
                  <RouterLink
                    :to="{ name: 'admin-promotions-edit', params: { id: promo.id } }"
                    class="btn btn-outline-primary btn-sm"
                  >
                    Editează
                  </RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { RouterLink } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { usePromotionsStore } from '@/store/promotions'

const store = usePromotionsStore()

const filters = reactive({
  search: '',
  status: '',
  type: '',
  clientType: '',
  segment: ''
})

const filteredPromotions = computed(() => {
  return store.all.filter((p) => {
    if (filters.search) {
      const s = filters.search.toLowerCase()
      if (
        !(
          p.name.toLowerCase().includes(s) ||
          (p.slug && p.slug.toLowerCase().includes(s))
        )
      ) {
        return false
      }
    }

    if (filters.status && p.status !== filters.status) return false
    if (filters.type && p.type !== filters.type) return false

    if (filters.clientType && !p.clientTypes.includes(filters.clientType)) return false

    if (filters.segment) {
      const seg = filters.segment.toLowerCase()
      const inGroups = (p.customerGroups || []).some((g) => g.toLowerCase().includes(seg))
      const inCats = (p.categories || []).some((c) => c.toLowerCase().includes(seg))
      const inBrands = (p.brands || []).some((b) => b.toLowerCase().includes(seg))
      if (!inGroups && !inCats && !inBrands) return false
    }

    return true
  })
})

const typeLabel = (t) => {
  switch (t) {
    case 'discount_percent':
      return 'Discount procentual'
    case 'discount_fixed':
      return 'Discount valoric'
    case 'x_get_y':
      return 'Cumperi X → primești Y'
    case 'bundle':
      return 'Pachet (bundle)'
    default:
      return t
  }
}

const statusLabel = (s) => {
  switch (s) {
    case 'active':
      return 'Activă'
    case 'upcoming':
      return 'În curând'
    case 'inactive':
      return 'Inactivă'
    default:
      return s
  }
}

const statusBadgeClass = (s) => {
  switch (s) {
    case 'active':
      return 'bg-success'
    case 'upcoming':
      return 'bg-info text-dark'
    case 'inactive':
      return 'bg-secondary'
    default:
      return 'bg-secondary'
  }
}
</script>
