<template>
  <div class="container-fluid">
    <PageHeader
      title="Clienți - listă"
      subtitle="Administrare clienți B2B/B2C, status, condiții comerciale și solduri."
    >
      <button type="button" class="btn btn-primary btn-sm" disabled>
        + Client nou (template)
      </button>
    </PageHeader>

    <!-- Filtre -->
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <form class="row g-3 align-items-end" @submit.prevent>
          <div class="col-md-3">
            <label class="form-label small text-muted">Căutare client</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control form-control-sm"
              placeholder="Denumire client, cod, email..."
            />
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Tip client</label>
            <select v-model="filters.clientType" class="form-select form-select-sm">
              <option value="">Toți</option>
              <option value="B2B">B2B</option>
              <option value="B2C">B2C</option>
              <option value="Agent">Agent</option>
              <option value="Director">Director</option>
              <option value="Operator">Operator</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Status</label>
            <select v-model="filters.status" class="form-select form-select-sm">
              <option value="">Toți</option>
              <option value="active">Activ</option>
              <option value="blocked">Blocat</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Grup client</label>
            <input
              v-model="filters.group"
              type="text"
              class="form-control form-control-sm"
              placeholder="Ex.: Distribuitori"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label small text-muted">Agent / director</label>
            <input
              v-model="filters.representative"
              type="text"
              class="form-control form-control-sm"
              placeholder="Nume agent sau director"
            />
          </div>
        </form>
      </div>
    </div>

    <!-- Lista clienți -->
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Denumire client</th>
                <th>Tip</th>
                <th>Contact</th>
                <th>Grup</th>
                <th>Sold / credit</th>
                <th>Termen plată</th>
                <th>Status</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredCustomers.length === 0">
                <td colspan="8" class="text-center text-muted py-4">
                  Nu există clienți pentru filtrele selectate.
                </td>
              </tr>
              <tr v-for="c in filteredCustomers" :key="c.id">
                <td>
                  <div class="fw-semibold">{{ c.name }}</div>
                  <div class="small text-muted">
                    Cod: {{ c.customerCode }} · ERP: {{ c.erpId }}
                  </div>
                </td>
                <td class="small">
                  <span class="badge bg-light text-dark">
                    {{ c.clientType }}
                  </span>
                </td>
                <td class="small">
                  <div>{{ c.email }}</div>
                  <div class="text-muted">{{ c.phone }}</div>
                </td>
                <td class="small">
                  <span v-if="c.group" class="badge bg-secondary">
                    {{ c.group }}
                  </span>
                </td>
                <td class="small">
                  <div>
                    Sold: <strong>{{ c.currentBalance.toLocaleString('ro-RO') }} {{ c.currency }}</strong>
                  </div>
                  <div>
                    Limită credit:
                    <strong>{{ c.creditLimit.toLocaleString('ro-RO') }} {{ c.currency }}</strong>
                  </div>
                  <div v-if="c.overdueBalance > 0" class="text-danger">
                    Restanțe: {{ c.overdueBalance.toLocaleString('ro-RO') }} {{ c.currency }}
                  </div>
                </td>
                <td class="small">
                  {{ c.paymentTermDays }} zile
                </td>
                <td class="small">
                  <span
                    :class="['badge', c.status === 'active' ? 'bg-success' : 'bg-danger']"
                  >
                    {{ c.status === 'active' ? 'Activ' : 'Blocat' }}
                  </span>
                </td>
                <td class="text-end">
                  <RouterLink
                    :to="{ name: 'admin-customer-details', params: { id: c.id } }"
                    class="btn btn-outline-primary btn-sm"
                  >
                    Fișă client
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
import { computed, reactive, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import { useCustomersStore } from '@/store/customers'

const store = useCustomersStore()

const filters = reactive({
  search: '',
  clientType: '',
  status: '',
  group: '',
  representative: ''
})

const filteredCustomers = computed(() => {
  return store.all.filter((c) => {
    if (filters.search) {
      const s = filters.search.toLowerCase()
      if (
        !(
          c.name.toLowerCase().includes(s) ||
          (c.customerCode && c.customerCode.toLowerCase().includes(s)) ||
          (c.email && c.email.toLowerCase().includes(s))
        )
      ) {
        return false
      }
    }

    if (filters.clientType && c.clientType !== filters.clientType) return false
    if (filters.status && c.status !== filters.status) return false
    if (filters.group && !(c.group || '').toLowerCase().includes(filters.group.toLowerCase())) {
      return false
    }

    if (filters.representative) {
      const rep = filters.representative.toLowerCase()
      const inAgent = (c.assignedAgent || '').toLowerCase().includes(rep)
      const inDirector = (c.assignedDirector || '').toLowerCase().includes(rep)
      if (!inAgent && !inDirector) return false
    }

    return true
  })
})
</script>
