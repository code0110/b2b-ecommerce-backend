<template>
  <div class="container-fluid py-4">
    <PageHeader
      title="Audit log"
      subtitle="Jurnal de acțiuni critice: modificări de preț, promoții, condiții comerciale, credit și intervenții manuale."
    >
      <!-- Slot pentru butoane de acțiune (ex: Export CSV, Filtre avansate etc.) -->
    </PageHeader>

    <div class="card shadow-sm mb-3">
      <div class="card-header py-2">
        <strong class="small text-uppercase">Filtre</strong>
      </div>
      <div class="card-body small">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label mb-1">Căutare</label>
            <input
              v-model="filterText"
              type="text"
              class="form-control form-control-sm"
              placeholder="utilizator, client, produs, promoție..."
            />
          </div>
          <div class="col-md-2">
            <label class="form-label mb-1">Tip acțiune</label>
            <select v-model="filterActionType" class="form-select form-select-sm">
              <option value="all">Toate</option>
              <option value="price_change">Modificare preț</option>
              <option value="promotion_toggle">Promoții</option>
              <option value="commercial_terms">Condiții comerciale</option>
              <option value="credit_limit">Credit</option>
              <option value="manual_balance">Intervenție manuală</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label mb-1">Tip obiect</label>
            <select v-model="filterEntityType" class="form-select form-select-sm">
              <option value="all">Toate</option>
              <option value="product">Produs</option>
              <option value="promotion">Promoție</option>
              <option value="customer">Client</option>
              <option value="order">Comandă</option>
              <option value="settings">Setări</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label mb-1">Rol utilizator</label>
            <select v-model="filterUserRole" class="form-select form-select-sm">
              <option value="all">Toate</option>
              <option value="admin">Administrator</option>
              <option value="director">Director</option>
              <option value="agent">Agent</option>
              <option value="operator">Operator</option>
              <option value="marketer">Marketer</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label mb-1">Perioadă (demo)</label>
            <div class="d-flex gap-1">
              <input
                v-model="filterFromDate"
                type="date"
                class="form-control form-control-sm"
              />
              <input
                v-model="filterToDate"
                type="date"
                class="form-control form-control-sm"
              />
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer small text-muted d-flex justify-content-between">
        <span>
          Într-o implementare reală, filtrarea s-ar face în backend, cu paginare și export.
        </span>
        <span>
          Înregistrări afișate: <strong>{{ filteredLogs.length }}</strong> / {{ logs.length }}
        </span>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header py-2 d-flex justify-content-between align-items-center small">
        <strong class="text-uppercase">Ultimele acțiuni în sistem</strong>
        <span class="text-muted">
          Date demo, generate local în UI.
        </span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 160px;">Data / ora</th>
                <th style="width: 220px;">Utilizator</th>
                <th style="width: 160px;">Tip acțiune</th>
                <th style="width: 200px;">Obiect</th>
                <th>Detalii</th>
                <th style="width: 220px;">Înainte / după</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="log in filteredLogs"
                :key="log.id"
                :class="{
                  'table-warning': log.critical && !log.reverted,
                  'table-success': log.reverted
                }"
              >
                <td>
                  <div class="fw-semibold">{{ log.timestamp }}</div>
                  <div class="small text-muted">{{ log.context }}</div>
                </td>
                <td>
                  <div class="fw-semibold">
                    {{ log.userName }}
                    <span class="badge bg-light text-dark ms-1">
                      {{ log.userRoleLabel }}
                    </span>
                  </div>
                  <div class="small text-muted">{{ log.userEmail }}</div>
                </td>
                <td>
                  <div class="fw-semibold">
                    {{ log.actionLabel }}
                  </div>
                  <div class="small text-muted">
                    {{ log.channelLabel }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold">
                    {{ log.entityTypeLabel }}
                  </div>
                  <div class="small text-muted">
                    {{ log.entityDisplay }}
                  </div>
                </td>
                <td class="small">
                  <div>{{ log.description }}</div>
                  <div v-if="log.critical" class="text-danger">
                    Acțiune critică – necesită verificare.
                  </div>
                  <div v-if="log.reverted" class="text-success">
                    Acțiune anulată / reversată ulterior.
                  </div>
                </td>
                <td class="small">
                  <div v-if="log.before" class="mb-1">
                    <span class="text-muted">Înainte:</span>
                    <div class="border rounded bg-light px-2 py-1">
                      {{ log.before }}
                    </div>
                  </div>
                  <div v-if="log.after">
                    <span class="text-muted">După:</span>
                    <div class="border rounded bg-light px-2 py-1">
                      {{ log.after }}
                    </div>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredLogs.length === 0">
                <td colspan="6" class="text-center small text-muted py-3">
                  Nu există înregistrări pentru filtrele selectate. Încearcă să lărgești perioada sau să golești căutarea.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer small text-muted">
        Pentru conformitate, logul de audit ar trebui păstrat pe termen lung, eventual într-un storage separat, și corelat cu log-urile de aplicație și acces (login, SSO, schimbare roluri).
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'

const logs = ref([
  {
    id: 1,
    timestamp: '2025-03-20 09:15',
    context: 'Admin / setări prețuri',
    userName: 'Admin Principal',
    userEmail: 'admin@example.com',
    userRole: 'admin',
    userRoleLabel: 'Administrator',
    actionType: 'price_change',
    actionLabel: 'Modificare preț produs',
    entityType: 'product',
    entityTypeLabel: 'Produs',
    entityDisplay: 'COD-PRD-001 · Adeziv profesional 25kg',
    description: 'A fost modificat prețul de listă pentru produs, cu aplicare imediată în catalog.',
    before: 'Preț listă: 120,00 RON',
    after: 'Preț listă: 135,00 RON',
    channelLabel: 'Acțiune efectuată din panoul de administrare',
    critical: true,
    reverted: false
  },
  {
    id: 2,
    timestamp: '2025-03-19 16:40',
    context: 'Marketing / promoții',
    userName: 'Maria Marketer',
    userEmail: 'maria.marketer@example.com',
    userRole: 'marketer',
    userRoleLabel: 'Marketer',
    actionType: 'promotion_toggle',
    actionLabel: 'Activare promoție',
    entityType: 'promotion',
    entityTypeLabel: 'Promoție',
    entityDisplay: 'Campanie „Primăvara B2B” (slug: primavara-b2b-2025)',
    description: 'Promoția a fost activată pentru clienți B2B, cu discount procentual pe anumite categorii.',
    before: 'Status: inactivă',
    after: 'Status: activă (B2B)',
    channelLabel: 'Gestionare promoții în admin',
    critical: false,
    reverted: false
  },
  {
    id: 3,
    timestamp: '2025-03-19 10:05',
    context: 'Fișă client B2B',
    userName: 'Ionescu Adrian',
    userEmail: 'a.ionescu@example.com',
    userRole: 'director',
    userRoleLabel: 'Director vânzări',
    actionType: 'commercial_terms',
    actionLabel: 'Modificare condiții comerciale',
    entityType: 'customer',
    entityTypeLabel: 'Client B2B',
    entityDisplay: 'SC Construcții Delta SRL · CUI RO12345678',
    description: 'Actualizare termen de plată și discount standard conform noului contract semnat.',
    before: 'Termen plată: 30 zile, Discount standard: 5%',
    after: 'Termen plată: 45 zile, Discount standard: 7%',
    channelLabel: 'Setări comerciale client',
    critical: true,
    reverted: false
  },
  {
    id: 4,
    timestamp: '2025-03-18 14:22',
    context: 'Control credit',
    userName: 'Ionescu Adrian',
    userEmail: 'a.ionescu@example.com',
    userRole: 'director',
    userRoleLabel: 'Director vânzări',
    actionType: 'credit_limit',
    actionLabel: 'Modificare limită de credit',
    entityType: 'customer',
    entityTypeLabel: 'Client B2B',
    entityDisplay: 'SC Industrial Tech SRL · CUI RO99887766',
    description: 'Limită de credit ajustată pentru a permite procesarea unei comenzi mari, cu aprobarea managementului financiar.',
    before: 'Limită credit: 50.000 RON',
    after: 'Limită credit: 75.000 RON',
    channelLabel: 'Ecran „Fișă client – condiții comerciale”',
    critical: true,
    reverted: false
  },
  {
    id: 5,
    timestamp: '2025-03-18 11:10',
    context: 'Încasări / reconciliere',
    userName: 'Popescu Mihai',
    userEmail: 'm.popescu@example.com',
    userRole: 'agent',
    userRoleLabel: 'Agent vânzări',
    actionType: 'manual_balance',
    actionLabel: 'Înregistrare încasare numerar (CHS)',
    entityType: 'customer',
    entityTypeLabel: 'Client B2B',
    entityDisplay: 'SC Retail Market SRL · CUI RO87654321',
    description: 'A fost înregistrată o încasare numerar CHS și alocată pe o factură scadentă.',
    before: 'Sold restant client: 18.450,00 RON',
    after: 'Sold restant client: 12.450,00 RON (încasare: 6.000,00 RON)',
    channelLabel: 'Modul „Încasări” din admin',
    critical: true,
    reverted: false
  },
  {
    id: 6,
    timestamp: '2025-03-17 09:30',
    context: 'Setări sistem',
    userName: 'Admin Principal',
    userEmail: 'admin@example.com',
    userRole: 'admin',
    userRoleLabel: 'Administrator',
    actionType: 'settings_change',
    actionLabel: 'Modificare configurare plăți online',
    entityType: 'settings',
    entityTypeLabel: 'Setări platformă',
    entityDisplay: 'Procesator plăți online · modul 3D Secure',
    description: 'A fost schimbată configurația pentru plățile online, cu activarea obligatorie a 3D Secure.',
    before: '3D Secure: opțional',
    after: '3D Secure: obligatoriu',
    channelLabel: 'Ecran „Setări plăți”',
    critical: false,
    reverted: false
  }
])

const filterText = ref('')
const filterActionType = ref('all')
const filterEntityType = ref('all')
const filterUserRole = ref('all')
const filterFromDate = ref('')
const filterToDate = ref('')

const normalizedFilterText = computed(() => filterText.value.trim().toLowerCase())

const filteredLogs = computed(() => {
  return logs.value.filter((log) => {
    if (filterActionType.value !== 'all') {
      if (log.actionType !== filterActionType.value) return false
    }

    if (filterEntityType.value !== 'all') {
      if (log.entityType !== filterEntityType.value) return false
    }

    if (filterUserRole.value !== 'all') {
      if (log.userRole !== filterUserRole.value) return false
    }

    if (filterFromDate.value) {
      const from = filterFromDate.value
      if (!log.timestamp.startsWith(from)) {
        if (log.timestamp < from) return false
      }
    }

    if (filterToDate.value) {
      const to = filterToDate.value
      const logDate = log.timestamp.slice(0, 10)
      if (logDate > to) return false
    }

    if (normalizedFilterText.value) {
      const haystack = (
        log.userName +
        ' ' +
        log.userEmail +
        ' ' +
        log.entityDisplay +
        ' ' +
        log.description +
        ' ' +
        log.actionLabel
      )
        .toLowerCase()
      if (!haystack.includes(normalizedFilterText.value)) {
        return false
      }
    }

    return true
  })
})
</script>
