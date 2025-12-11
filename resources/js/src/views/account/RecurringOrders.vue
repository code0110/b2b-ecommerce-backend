<template>
  <div class="container">
    <PageHeader
      title="Comenzi recurente"
      subtitle="Gestionează șabloane de comenzi pe care le poți reutiliza periodic."
    />

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-header py-2">
            <strong>Șabloane disponibile</strong>
          </div>
          <div class="card-body small p-0">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Denumire șablon</th>
                    <th>Descriere</th>
                    <th class="text-end">Total estimat</th>
                    <th>Ultima utilizare</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="templates.length === 0">
                    <td colspan="5" class="text-center text-muted py-4">
                      Nu ai definite încă șabloane de comenzi recurente.
                    </td>
                  </tr>
                  <tr
                    v-for="tpl in templates"
                    :key="tpl.id"
                  >
                    <td class="small">
                      <div class="fw-semibold">{{ tpl.name }}</div>
                    </td>
                    <td class="small text-muted">
                      {{ tpl.description }}
                    </td>
                    <td class="text-end small">
                      <strong>{{ formatMoney(tpl.totalGross, tpl.currency) }}</strong>
                    </td>
                    <td class="small">
                      {{ formatDate(tpl.lastUsedAt) }}
                    </td>
                    <td class="text-end">
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm me-1"
                        @click="selectedTemplate = tpl"
                      >
                        Detalii
                      </button>
                      <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        @click="applyTemplateToCart(tpl)"
                      >
                        Folosește în coș
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer small text-muted">
            Șabloanele pot fi generate manual sau pornind de la comenzi existente (ex.: „Salvează
            comanda ca șablon”). În backend se poate configura și o recurență automată (lunar,
            săptămânal etc.).
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div v-if="selectedTemplate" class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Detalii șablon</strong>
          </div>
          <div class="card-body small">
            <h6 class="mb-1">{{ selectedTemplate.name }}</h6>
            <p class="text-muted mb-2">
              {{ selectedTemplate.description }}
            </p>
            <div class="table-responsive mb-2">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Produs</th>
                    <th>Cod</th>
                    <th class="text-center">Cantitate</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="line in selectedTemplate.lines"
                    :key="line.productId + '-' + line.code"
                  >
                    <td class="small">{{ line.name }}</td>
                    <td class="small text-muted">{{ line.code }}</td>
                    <td class="text-center small">
                      {{ line.qty }} {{ line.unit }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="small text-muted mb-0">
              În implementarea reală, la aplicarea șablonului se vor verifica automat stocurile și
              prețurile actuale și, unde este cazul, se vor propune alternative pentru produsele
              indisponibile.
            </p>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body small text-muted">
            <h6>Utilizare practică</h6>
            <ul class="mb-0">
              <li>Recomandat pentru clienți B2B cu comenzi recurente (lunare/săptămânale).</li>
              <li>Poate fi folosit de agenți pentru a reînnoi rapid comenzile clienților lor.</li>
              <li>Corelat cu condițiile comerciale și limitele de credit ale clientului.</li>
            </ul>
          </div>
        </div>

        <p v-if="infoMessage" class="small text-muted mt-2 mb-0">
          {{ infoMessage }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import { useAccountProfileStore } from '@/store/accountProfile'
import { useCartStore } from '@/store/cart'
import { useProductsStore } from '@/store/products'

const profileStore = useAccountProfileStore()
const cartStore = useCartStore()
const productsStore = useProductsStore()

const templates = computed(() => profileStore.allTemplates)
const selectedTemplate = ref(null)
const infoMessage = ref('')

const formatMoney = (value, currency = 'RON') => {
  const val = Number(value || 0)
  return val.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + currency
}

const formatDate = (iso) => {
  if (!iso) return '-'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleDateString('ro-RO', { year: 'numeric', month: '2-digit', day: '2-digit' })
}

const applyTemplateToCart = (tpl) => {
  if (!tpl || !tpl.lines || !tpl.lines.length) return

  tpl.lines.forEach((line) => {
    const prod = productsStore.getById(line.productId)
    if (prod) {
      cartStore.addItem(prod.id, Number(line.qty || 0))
    }
  })

  infoMessage.value =
    'Template: șablonul "' +
    tpl.name +
    '" a fost aplicat în coș. În implementarea reală se va crea fie un draft de comandă, fie o comandă programată recurent.'
}
</script>
