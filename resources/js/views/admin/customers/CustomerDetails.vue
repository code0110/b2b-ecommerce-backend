<template>
  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h1 class="h5 mb-0">Fișă client</h1>
        <div class="small text-muted">
          <RouterLink :to="{ name: 'admin-customers' }">
            ← Înapoi la listă
          </RouterLink>
        </div>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger py-2">
      {{ error }}
    </div>

    <div v-if="loading" class="alert alert-info py-2">
      Se încarcă datele clientului...
    </div>

    <div v-if="customer" class="row g-3">
      <!-- Date generale -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header py-2 small">
            Date generale
          </div>
          <div class="card-body small">
            <p class="mb-1">
              <strong>Client:</strong><br>
              {{ customer.name || customer.company_name || customer.full_name }}
            </p>
            <p class="mb-1">
              <strong>Tip:</strong>
              {{ customer.type_label || customer.type || '—' }}
            </p>
            <p class="mb-1">
              <strong>Grup:</strong>
              {{ customer.group?.name || customer.group_name || '—' }}
            </p>
            <p class="mb-1">
              <strong>Email:</strong>
              {{ customer.email }}
            </p>
            <p class="mb-1">
              <strong>Telefon:</strong>
              {{ customer.phone || '—' }}
            </p>
            <p class="mb-1">
              <strong>Status:</strong>
              <span
                class="badge"
                :class="customer.status === 'active' ? 'bg-success' : 'bg-secondary'"
              >
                {{ customer.status_label || customer.status || '—' }}
              </span>
            </p>
          </div>
        </div>
      </div>

      <!-- Condiții comerciale B2B -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header py-2 small">
            Condiții comerciale
          </div>
          <div class="card-body small">
            <p class="mb-1">
              <strong>Termen de plată:</strong>
              {{ customer.payment_terms_label || customer.payment_terms || '—' }}
            </p>
            <p class="mb-1">
              <strong>Limită credit:</strong>
              {{ formatMoney(customer.credit_limit || 0) }}
            </p>
            <p class="mb-1">
              <strong>Sold curent:</strong>
              {{ formatMoney(customer.current_balance || customer.balance || 0) }}
            </p>
            <p class="mb-1">
              <strong>Sold restant:</strong>
              {{ formatMoney(customer.overdue_balance || 0) }}
            </p>
            <p class="mb-1">
              <strong>Discount standard:</strong>
              {{ (customer.default_discount ?? 0) }} %
            </p>
          </div>
        </div>
      </div>

      <!-- Puncte de lucru / adrese -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header py-2 small">
            Adrese & puncte de lucru (read-only demo)
          </div>
          <div class="card-body small">
            <div v-if="!addresses.billing?.length && !addresses.shipping?.length">
              <span class="text-muted">Nu există adrese înregistrate.</span>
            </div>

            <div v-if="addresses.billing?.length" class="mb-2">
              <strong>Adrese facturare</strong>
              <ul class="mb-0 small">
                <li v-for="(addr, idx) in addresses.billing" :key="'b' + idx">
                  {{ addr.line1 }}
                  <span v-if="addr.city">, {{ addr.city }}</span>
                  <span v-if="addr.country">, {{ addr.country }}</span>
                </li>
              </ul>
            </div>

            <div v-if="addresses.shipping?.length">
              <strong>Adrese livrare</strong>
              <ul class="mb-0 small">
                <li v-for="(addr, idx) in addresses.shipping" :key="'s' + idx">
                  {{ addr.line1 }}
                  <span v-if="addr.city">, {{ addr.city }}</span>
                  <span v-if="addr.country">, {{ addr.country }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Comenzi recente / activitate simplă -->
      <div class="col-12">
        <div class="card">
          <div class="card-header py-2 small">
            Comenzi recente (dacă backend-ul trimite ceva în relație)
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Data</th>
                  <th>Status</th>
                  <th class="text-end">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!recentOrders.length">
                  <td colspan="4" class="text-center text-muted py-3">
                    Nu avem comenzi recente în payload-ul clientului.
                  </td>
                </tr>
                <tr
                  v-for="o in recentOrders"
                  :key="o.id"
                >
                  <td class="small">#{{ o.number || o.id }}</td>
                  <td class="small">
                    {{ formatDate(o.created_at) }}
                  </td>
                  <td class="small">
                    {{ o.status_label || o.status }}
                  </td>
                  <td class="small text-end">
                    {{ formatMoney(o.total || 0) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { fetchCustomer } from '@/services/admin/customers'

const route = useRoute()

const loading = ref(false)
const error = ref('')
const customer = ref(null)

const addresses = ref({
  billing: [],
  shipping: []
})

const recentOrders = ref([])

const formatMoney = (val) => {
  if (val == null) return '0,00 RON'
  return `${Number(val).toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })} RON`
}

const formatDate = (value) => {
  if (!value) return ''
  const d = new Date(value)
  return d.toLocaleString('ro-RO')
}

const loadCustomer = async () => {
  loading.value = true
  error.value = ''

  try {
    const data = await fetchCustomer(route.params.id)
    const c = data.data || data

    customer.value = c

    addresses.value = {
      billing: c.billing_addresses || c.invoice_addresses || [],
      shipping: c.shipping_addresses || c.delivery_addresses || []
    }

    recentOrders.value = c.recent_orders || c.orders || []
  } catch (e) {
    console.error(e)
    error.value = 'Nu s-au putut încărca datele clientului.'
  } finally {
    loading.value = false
  }
}

onMounted(loadCustomer)
</script>
