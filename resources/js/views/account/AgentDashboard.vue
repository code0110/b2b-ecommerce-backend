<template>
  <div class="agent-dashboard">
    <h1 class="h3 mb-4">Panou Control Vânzări</h1>

    <!-- Active Visit Panel -->
    <div v-if="visitStore.hasActiveVisit" class="card border-primary shadow-sm mb-4">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-geo-alt-fill me-2"></i>Vizită Activă</h5>
        <span class="badge bg-white text-primary">{{ visitStore.activeVisit.customer?.name }}</span>
      </div>
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-8">
            <p class="mb-1"><strong>Client:</strong> {{ visitStore.activeVisit.customer?.name }}</p>
            <p class="mb-1"><strong>CUI:</strong> {{ visitStore.activeVisit.customer?.cif }}</p>
            <p class="mb-0 text-muted small">Vizită începută la: {{ new Date(visitStore.activeVisit.start_time).toLocaleTimeString() }}</p>
          </div>
          <div class="col-md-4 text-end">
            <button class="btn btn-outline-primary me-2" @click="impersonateClient(visitStore.activeVisit.customer)">
              <i class="bi bi-cart-plus me-1"></i> Comandă
            </button>
            <button class="btn btn-danger" @click="handleEndVisit" :disabled="visitStore.loading">
              <span v-if="visitStore.loading" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-stop-circle me-1"></i> Încheie Vizita
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Se încarcă...</span>
      </div>
    </div>

    <template v-else>
      <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
          <button 
            class="nav-link" 
            :class="{ active: activeTab === 'routes' }" 
            @click="activeTab = 'routes'"
          >
            Planificare Rute
          </button>
        </li>
        <li class="nav-item">
          <button 
            class="nav-link" 
            :class="{ active: activeTab === 'clients' }" 
            @click="activeTab = 'clients'"
          >
            Clienți
          </button>
        </li>
        <li class="nav-item">
          <button 
            class="nav-link" 
            :class="{ active: activeTab === 'history' }" 
            @click="loadHistory"
          >
            Istoric Vizite
          </button>
        </li>
        <li class="nav-item" v-if="isDirector">
          <button 
            class="nav-link" 
            :class="{ active: activeTab === 'agents' }" 
            @click="activeTab = 'agents'"
          >
            Agenți
          </button>
        </li>
      </ul>

      <!-- Tab Planificare Rute -->
      <div v-if="activeTab === 'routes'">
        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0">
                         <div class="btn-group w-100" role="group">
                             <input type="radio" class="btn-check" name="weekType" id="wtAll" value="all" v-model="selectedWeekType">
                             <label class="btn btn-outline-secondary" for="wtAll">Toate</label>

                             <input type="radio" class="btn-check" name="weekType" id="wtOdd" value="odd" v-model="selectedWeekType">
                             <label class="btn btn-outline-secondary" for="wtOdd">Săptămâni Impare</label>

                             <input type="radio" class="btn-check" name="weekType" id="wtEven" value="even" v-model="selectedWeekType">
                             <label class="btn btn-outline-secondary" for="wtEven">Săptămâni Pare</label>
                         </div>
                    </div>
                    <div class="col-md-6">
                         <ul class="nav nav-pills justify-content-center justify-content-md-end">
                            <li class="nav-item" v-for="day in days" :key="day.value">
                                <a 
                                    class="nav-link py-1 px-2 small" 
                                    :class="{ active: currentDay === day.value }" 
                                    href="#" 
                                    @click.prevent="currentDay = day.value"
                                >
                                    {{ day.label.substring(0, 3) }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Stats -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="text-uppercase small opacity-75 mb-1">Total Clienți Rută</h6>
                        <h2 class="display-6 fw-bold mb-0">{{ kpiStats.total }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card bg-success text-white h-100">
                     <div class="card-body text-center">
                        <h6 class="text-uppercase small opacity-75 mb-1">Vizitați</h6>
                        <h2 class="display-6 fw-bold mb-0">{{ kpiStats.visited }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light text-dark h-100 border">
                     <div class="card-body text-center">
                        <h6 class="text-uppercase small text-muted mb-1">De Vizitat</h6>
                        <h2 class="display-6 fw-bold mb-0 text-muted">{{ kpiStats.unvisited }}</h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="progress mb-4" style="height: 10px;">
            <div 
                class="progress-bar bg-success" 
                role="progressbar" 
                :style="{ width: kpiStats.percent + '%' }" 
                :aria-valuenow="kpiStats.percent" 
                aria-valuemin="0" 
                aria-valuemax="100"
            ></div>
        </div>

        <!-- Route List -->
        <div class="card border-0 shadow-sm">
             <div class="card-header bg-white py-3">
                 <h6 class="mb-0 fw-bold text-uppercase">
                     Lista Clienți - {{ days.find(d => d.value === currentDay)?.label }}
                 </h6>
             </div>
             <div class="card-body p-0">
                 <div v-if="filteredRoutes.length === 0" class="p-5 text-center text-muted">
                     <i class="bi bi-calendar-x fs-1 mb-3 d-block opacity-25"></i>
                     Nu sunt vizite planificate pentru această zi.
                 </div>
                 <div class="list-group list-group-flush" v-else>
                     <div 
                        v-for="(item, index) in filteredRoutes" 
                        :key="item.id" 
                        class="list-group-item p-3"
                        :class="{'bg-light': item.customer?.visits_count > 0}"
                     >
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="fw-bold text-muted h4 mb-0 opacity-50" style="width: 30px;">
                                    {{ index + 1 }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                        {{ item.customer.name }}
                                        <span v-if="item.customer?.visits_count > 0" class="badge bg-success rounded-pill">
                                            <i class="bi bi-check-lg"></i> Vizitat
                                        </span>
                                    </div>
                                    <div class="small text-muted">
                                        {{ item.customer.address || 'Fără adresă' }}
                                    </div>
                                    <div class="small text-muted mt-1" v-if="item.customer.phone">
                                        <i class="bi bi-telephone me-1"></i> {{ item.customer.phone }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button 
                                    v-if="!item.customer?.visits_count && (!visitStore.activeVisit || visitStore.activeVisit.customer_id !== item.customer.id)"
                                    class="btn btn-outline-primary btn-sm"
                                    @click="handleStartVisit(item.customer)"
                                    :disabled="visitStore.loading"
                                >
                                    <i class="bi bi-geo-alt me-1"></i> Începe Vizita
                                </button>
                                <button 
                                    v-if="visitStore.activeVisit?.customer_id === item.customer.id"
                                    class="btn btn-primary btn-sm"
                                    disabled
                                >
                                    <i class="bi bi-geo-alt-fill me-1"></i> În curs...
                                </button>
                                
                                <button 
                                    class="btn btn-light border btn-sm"
                                    @click="activeTab = 'clients'; searchClient = item.customer.name"
                                    title="Vezi Detalii Client"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                     </div>
                 </div>
             </div>
        </div>

      </div>

      <!-- Tab Clienti -->
      <div v-if="activeTab === 'clients'">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <input 
            type="text" 
            class="form-control w-auto" 
            placeholder="Caută client..." 
            v-model="searchClient"
          >
          <div class="form-check ms-2" v-if="isDirector">
            <input class="form-check-input" type="checkbox" id="agentOnly" v-model="showAgentClientsOnly">
            <label class="form-check-label small" for="agentOnly">
              Doar clienții agenților mei
            </label>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Nume Client</th>
                <th>CUI / CIF</th>
                <th>Sold Curent</th>
                <th>Limită Credit</th>
                <th v-if="isDirector">Agent</th>
                <th class="text-end">Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="client in filteredClients" :key="client.id" :class="{'table-primary': visitStore.activeVisit?.customer_id === client.id}">
                <td>
                  <div class="fw-bold">
                    {{ client.name }}
                    <span v-if="!isDirector && client.agent_user_id !== authStore.user.id" class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem;">Acces Echipă</span>
                  </div>
                  <small class="text-muted">{{ client.email }}</small>
                </td>
                <td>{{ client.cif }}</td>
                <td :class="client.current_balance > 0 ? 'text-danger' : 'text-success'">
                  {{ formatPrice(client.current_balance, client.currency) }}
                </td>
                <td>{{ formatPrice(client.credit_limit, client.currency) }}</td>
                <td v-if="isDirector">
                    <span v-if="client.agent_user_id === authStore.user.id" class="badge bg-info">Eu</span>
                    <span v-else-if="client.agent" class="badge bg-secondary">
                      {{ [client.agent.first_name, client.agent.last_name].filter(Boolean).join(' ') }}
                    </span>
                    <span v-else class="text-muted">-</span>
                </td>
                <td class="text-end">
                   <!-- Start/End Visit Buttons -->
                   <button 
                    v-if="!visitStore.activeVisit || visitStore.activeVisit.customer_id !== client.id"
                    class="btn btn-sm btn-outline-primary me-2"
                    @click="handleStartVisit(client)"
                    :disabled="visitStore.loading || (visitStore.activeVisit && visitStore.activeVisit.customer_id !== client.id)"
                    title="Începe Vizită"
                  >
                    <i class="bi bi-geo-alt"></i> Vizită
                  </button>

                  <button 
                    class="btn btn-sm btn-success me-2"
                    @click="openPaymentModal(client)"
                    title="Adaugă Încasare"
                    :disabled="!canPerformAction(client)"
                  >
                    <i class="bi bi-cash-stack"></i> Încasare
                  </button>
                  <button 
                    class="btn btn-sm btn-primary"
                    @click="impersonateClient(client)"
                    title="Plasează Comandă"
                    :disabled="!canPerformAction(client)"
                  >
                    <i class="bi bi-cart-plus"></i> Comandă
                  </button>
                </td>
              </tr>
              <tr v-if="filteredClients.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">
                  Nu s-au găsit clienți.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab Istoric -->
      <div v-if="activeTab === 'history'">
        <div v-if="loadingHistory" class="text-center py-5">
             <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Se încarcă istoricul...</span>
            </div>
        </div>
        <div v-else>
             <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Data/Ora</th>
                            <th>Client</th>
                            <th>Durata</th>
                            <th>Acțiuni</th>
                            <th>Rezultat</th>
                            <th>Notițe</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="visit in historyVisits" :key="visit.id">
                            <td>
                                {{ new Date(visit.start_time).toLocaleDateString() }} <br>
                                <span class="text-muted small">{{ new Date(visit.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                            </td>
                            <td>
                                <div class="fw-bold">{{ visit.customer?.name }}</div>
                                <div class="small text-muted">{{ visit.customer?.address }}</div>
                            </td>
                            <td>
                                {{ calculateDuration(visit.start_time, visit.end_time) }}
                            </td>
                            <td>
                                <div v-if="visit.orders && visit.orders.length > 0">
                                    <span class="badge bg-primary">
                                        {{ visit.orders.length }} Comenzi
                                        ({{ formatPrice(visit.orders.reduce((acc, o) => acc + parseFloat(o.total), 0)) }})
                                    </span>
                                </div>
                                <div v-if="visit.payments && visit.payments.length > 0" class="mt-1">
                                    <span class="badge bg-success">
                                        {{ visit.payments.length }} Încasări
                                        ({{ formatPrice(visit.payments.reduce((acc, p) => acc + parseFloat(p.amount), 0)) }})
                                    </span>
                                </div>
                                <div v-if="(!visit.orders || visit.orders.length === 0) && (!visit.payments || visit.payments.length === 0)" class="text-muted small">
                                    -
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark" v-if="visit.outcome">
                                    {{ visitOutcomes.find(o => o.value === visit.outcome)?.label || visit.outcome }}
                                </span>
                                <span v-else class="text-muted small">-</span>
                            </td>
                            <td>
                                <span v-if="visit.notes" class="text-truncate d-inline-block" style="max-width: 200px;" :title="visit.notes">
                                    {{ visit.notes }}
                                </span>
                                <span v-else class="text-muted small">-</span>
                            </td>
                            <td>
                                <span class="badge" :class="{
                                    'bg-success': visit.status === 'completed',
                                    'bg-primary': visit.status === 'in_progress',
                                    'bg-secondary': visit.status === 'planned',
                                    'bg-danger': visit.status === 'cancelled'
                                }">
                                    {{ visit.status === 'completed' ? 'Finalizată' : 
                                       visit.status === 'in_progress' ? 'În desfășurare' : visit.status }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="historyVisits.length === 0">
                            <td colspan="5" class="text-center py-4 text-muted">
                                Nu există istoric de vizite.
                            </td>
                        </tr>
                    </tbody>
                </table>
             </div>
             <!-- Pagination if needed -->
             <div class="d-flex justify-content-center mt-3" v-if="historyMeta.last_page > 1">
                <nav>
                  <ul class="pagination">
                    <li class="page-item" :class="{ disabled: historyMeta.current_page === 1 }">
                      <button class="page-link" @click="loadHistory(historyMeta.current_page - 1)">Înapoi</button>
                    </li>
                    <li class="page-item disabled">
                        <span class="page-link">Pagina {{ historyMeta.current_page }} din {{ historyMeta.last_page }}</span>
                    </li>
                    <li class="page-item" :class="{ disabled: historyMeta.current_page === historyMeta.last_page }">
                      <button class="page-link" @click="loadHistory(historyMeta.current_page + 1)">Înainte</button>
                    </li>
                  </ul>
                </nav>
             </div>
        </div>
      </div>



      <!-- Tab Agenti -->
      <div v-if="activeTab === 'agents'">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Nume Agent</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Clienți Asignați</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="agent in agents" :key="agent.id">
                <td>{{ agent.first_name }} {{ agent.last_name }}</td>
                <td>{{ agent.email }}</td>
                <td>{{ agent.phone }}</td>
                <td>{{ agent.customers_count || 0 }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Modal Incasare -->
    <div v-if="showPaymentModal" class="modal-backdrop fade show"></div>
    <div 
      class="modal fade" 
      :class="{ show: showPaymentModal }" 
      style="display: block;" 
      v-if="showPaymentModal"
      tabindex="-1"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Înregistrează Încasare</h5>
            <button type="button" class="btn-close" @click="closePaymentModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="!receiptBook && paymentForm.instrument === 'numerar'" class="alert alert-danger">
                Nu aveți un chitanțier activ asignat. Contactați administratorul pentru încasări numerar.
            </div>

            <div v-if="selectedClient" class="mb-3 p-2 bg-light rounded">
              <div class="row">
                  <div class="col-md-6">
                      <strong>Client:</strong> {{ selectedClient.name }}
                  </div>
                  <div class="col-md-6 text-end">
                      <strong>Sold Total:</strong> {{ formatPrice(selectedClient.current_balance, selectedClient.currency) }}
                  </div>
              </div>
              <div class="row mt-2" v-if="receiptBook && paymentForm.instrument === 'numerar'">
                  <div class="col-12 d-flex justify-content-between align-items-center">
                      <div class="text-muted small">
                        <strong>Chitanțier Activ:</strong> {{ receiptBook.series }} (Următorul nr: {{ receiptBook.current_number }})
                      </div>
                      <button class="btn btn-sm btn-outline-danger" type="button" @click="cancelCurrentReceipt">
                        <i class="bi bi-x-circle me-1"></i>Anulează Chitanța Curentă
                      </button>
                  </div>
              </div>
            </div>

            <form @submit.prevent="submitPayment" v-if="receiptBook || paymentForm.instrument !== 'numerar'">
              <div class="row">
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Instrument de Plată</label>
                      <select v-model="paymentForm.instrument" class="form-select" required>
                          <option value="numerar">Numerar (Chitanță)</option>
                          <option value="bo">Bilet la Ordin (BO)</option>
                          <option value="cec">CEC</option>
                      </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Data Înregistrării</label>
                    <input 
                      type="date" 
                      v-model="paymentForm.payment_date" 
                      class="form-control" 
                      required
                    >
                  </div>
              </div>

              <!-- BO/CEC Fields -->
              <div class="row p-2 mb-3 bg-light border rounded" v-if="['bo', 'cec'].includes(paymentForm.instrument)">
                  <h6 class="mb-3">Detalii Instrument</h6>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Serie</label>
                      <input type="text" class="form-control" v-model="paymentForm.series" required placeholder="Ex: BTRL">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Număr</label>
                      <input type="text" class="form-control" v-model="paymentForm.number" required placeholder="Ex: 012345">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Banca Emitentă</label>
                      <input type="text" class="form-control" v-model="paymentForm.bank" required placeholder="Ex: Banca Transilvania">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label">Data Scadenței</label>
                      <input type="date" class="form-control" v-model="paymentForm.due_date" required>
                  </div>
              </div>

              <div class="row">
                  <div class="col-md-12 mb-3">
                    <label class="form-label">Mod Alocare</label>
                    <select v-model="paymentForm.type" class="form-select" required @change="onPaymentTypeChange">
                      <option value="facturi">Facturi (Sumă calculată)</option>
                      <option value="avans">Avans (Fără facturi)</option>
                      <option value="valoare">Valoare (Cu selecție facturi)</option>
                    </select>
                    <div class="form-text small" v-if="paymentForm.type === 'facturi'">
                        Selectați facturile. Suma se calculează automat.
                    </div>
                    <div class="form-text small" v-if="paymentForm.type === 'avans'">
                        Introduceți suma manual. Nu selectați facturi.
                    </div>
                    <div class="form-text small" v-if="paymentForm.type === 'valoare'">
                        Introduceți suma și selectați facturi pentru acoperire (Sold facturi >= Sumă).
                    </div>
                  </div>
              </div>

              <!-- Invoice Selection Area -->
              <div class="mb-3" v-if="paymentForm.type !== 'avans'">
                  <label class="form-label d-flex justify-content-between">
                      <span>Selectează Facturi (Sold > 0)</span>
                      <span v-if="loadingInvoices" class="spinner-border spinner-border-sm text-primary"></span>
                  </label>
                  <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                      <div v-if="invoices.length === 0 && !loadingInvoices" class="text-center text-muted py-2">
                          Nu există facturi cu sold pozitiv.
                      </div>
                      <table class="table table-sm table-hover mb-0" v-else>
                          <thead>
                              <tr>
                                  <th style="width: 30px;">
                                      <input type="checkbox" @change="toggleAllInvoices" :checked="areAllInvoicesSelected" :disabled="invoices.length === 0">
                                  </th>
                                  <th>Factura</th>
                                  <th>Data Scad.</th>
                                  <th class="text-end">Sold</th>
                              </tr>
                          </thead>
                          <tbody>
                              <tr v-for="inv in invoices" :key="inv.id" :class="{'table-active': selectedInvoiceIds.includes(inv.id)}">
                                  <td>
                                      <input type="checkbox" :value="inv.id" v-model="selectedInvoiceIds">
                                  </td>
                                  <td>{{ inv.series }} {{ inv.number }}</td>
                                  <td>{{ inv.due_date }}</td>
                                  <td class="text-end">{{ formatPrice(inv.balance, inv.currency) }}</td>
                              </tr>
                          </tbody>
                      </table>
                  </div>
                  <div class="text-end mt-1 small text-muted">
                      Total Sold Selectat: <strong>{{ formatPrice(totalSelectedBalance, selectedClient?.currency) }}</strong>
                  </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Sumă Încasată</label>
                <div class="input-group">
                  <input 
                    type="number" 
                    step="0.01" 
                    v-model="paymentForm.amount" 
                    class="form-control" 
                    required
                    :readonly="paymentForm.type === 'facturi'"
                    min="0.01"
                  >
                  <span class="input-group-text">{{ selectedClient?.currency || 'RON' }}</span>
                </div>
                <div class="text-danger small mt-1" v-if="validationError">
                    {{ validationError }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Observații</label>
                <textarea 
                  v-model="paymentForm.notes" 
                  class="form-control" 
                  rows="2"
                  placeholder="Detalii suplimentare..."
                ></textarea>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" @click="closePaymentModal">Anulează</button>
                <button type="submit" class="btn btn-primary" :disabled="submitting || !!validationError">
                  {{ submitting ? 'Se salvează...' : 'Emite Chitanță' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Finalizare Vizită -->
    <div v-if="showEndVisitModal" class="modal-backdrop fade show" style="z-index: 1060;"></div>
    <div 
      class="modal fade" 
      :class="{ show: showEndVisitModal }" 
      style="display: block; z-index: 1070;" 
      v-if="showEndVisitModal"
      tabindex="-1"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Finalizare Vizită</h5>
            <button type="button" class="btn-close" @click="showEndVisitModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Rezultat Vizită <span class="text-danger">*</span></label>
                <select v-model="endVisitForm.outcome" class="form-select" required>
                    <option v-for="outcome in visitOutcomes" :key="outcome.value" :value="outcome.value">
                        {{ outcome.label }}
                    </option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Notițe / Observații</label>
                <textarea 
                    v-model="endVisitForm.notes" 
                    class="form-control" 
                    rows="3" 
                    placeholder="Detalii despre vizită..."
                ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showEndVisitModal = false">Anulează</button>
            <button type="button" class="btn btn-primary" @click="confirmEndVisit" :disabled="visitStore.loading">
                {{ visitStore.loading ? 'Se salvează...' : 'Confirmă și Închide' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Anulare Chitanță -->
    <div v-if="showCancelModal" class="modal-backdrop fade show" style="z-index: 1060;"></div>
    <div 
      class="modal fade" 
      :class="{ show: showCancelModal }" 
      style="display: block; z-index: 1070;" 
      v-if="showCancelModal"
      tabindex="-1"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title text-danger">Anulare Chitanță</h5>
            <button type="button" class="btn-close" @click="closeCancelModal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Această acțiune este ireversibilă! Chitanța <strong>{{ receiptBook?.series }} {{ receiptBook?.current_number }}</strong> va fi marcată ca anulată.
            </div>
            <div class="mb-3">
                <label class="form-label">Motivul Anulării <span class="text-danger">*</span></label>
                <textarea 
                    v-model="cancelReason" 
                    class="form-control" 
                    rows="3" 
                    placeholder="Ex: completată greșit, clientul a refuzat, etc."
                    required
                ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeCancelModal">Renunță</button>
            <button type="button" class="btn btn-danger" @click="confirmCancelReceipt" :disabled="!cancelReason || submitting">
                {{ submitting ? 'Se anulează...' : 'Confirmă Anularea' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '@/store/auth';
import agentService from '@/services/account/agent';
import { useRouter } from 'vue-router';
import { useVisitStore } from '@/store/visit';
import { useToast } from 'vue-toastification';

const authStore = useAuthStore();
const router = useRouter();
const visitStore = useVisitStore();
const toast = useToast();

const activeTab = ref('routes'); // Default to routes
const clients = ref([]);
const agents = ref([]);
const routes = ref([]); // Store all routes
const loading = ref(true);
const searchClient = ref('');
const showAgentClientsOnly = ref(false);

const days = [
  { value: 'Monday', label: 'Luni' },
  { value: 'Tuesday', label: 'Marți' },
  { value: 'Wednesday', label: 'Miercuri' },
  { value: 'Thursday', label: 'Joi' },
  { value: 'Friday', label: 'Vineri' },
  { value: 'Saturday', label: 'Sâmbătă' },
  { value: 'Sunday', label: 'Duminică' },
];

const currentDay = ref(new Date().toLocaleDateString('en-US', { weekday: 'long' }));
const selectedWeekType = ref('all'); // Will be set to current week type

// Helper to get week number and type
const getWeekType = () => {
    const d = new Date();
    const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    date.setUTCDate(date.getUTCDate() + 4 - (date.getUTCDay()||7));
    const yearStart = new Date(Date.UTC(date.getUTCFullYear(),0,1));
    const weekNo = Math.ceil(( ( (date - yearStart) / 86400000) + 1)/7);
    return weekNo % 2 === 0 ? 'even' : 'odd';
};

selectedWeekType.value = getWeekType();

const showPaymentModal = ref(false);
const showCancelModal = ref(false);
const showEndVisitModal = ref(false);

const endVisitForm = ref({
    outcome: 'presentation',
    notes: ''
});

const visitOutcomes = [
    { value: 'order_placed', label: 'Comandă preluată' },
    { value: 'payment_collected', label: 'Încasare' },
    { value: 'stock_check', label: 'Verificare stoc' },
    { value: 'presentation', label: 'Prezentare produse' },
    { value: 'no_interest', label: 'Nu este interesat' },
    { value: 'client_closed', label: 'Client închis' },
    { value: 'other', label: 'Altele' }
];

const cancelReason = ref('');
const selectedClient = ref(null);
const submitting = ref(false);
const invoices = ref([]);
const loadingInvoices = ref(false);
const selectedInvoiceIds = ref([]);
const receiptBook = ref(null);

const loadingHistory = ref(false);
const historyVisits = ref([]);
const historyMeta = ref({ current_page: 1, last_page: 1 });

const paymentForm = ref({
  instrument: 'numerar', // numerar, bo, cec
  type: 'facturi',
  amount: 0,
  payment_date: new Date().toISOString().split('T')[0],
  notes: '',
  series: '',
  number: '',
  bank: '',
  due_date: ''
});

const isDirector = computed(() => {
  const roles = (authStore.user?.roles || []).map(r => r.slug || r.code);
  return roles.includes('sales_director');
});

const filteredRoutes = computed(() => {
  return routes.value.filter(r => {
      // Filter by Day
      if (r.day_of_week !== currentDay.value) return false;
      
      // Filter by Week Type
      // If route is 'all', it shows every week.
      // If route is 'odd', it shows only on odd weeks.
      // If route is 'even', it shows only on even weeks.
      // BUT, we are viewing a specific week type (selectedWeekType).
      // So we show:
      // 1. Routes that are 'all'
      // 2. Routes that match the selected week type
      if (r.week_type === 'all') return true;
      return r.week_type === selectedWeekType.value;
  });
});

const kpiStats = computed(() => {
    const total = filteredRoutes.value.length;
    const visited = filteredRoutes.value.filter(r => r.customer?.visits_count > 0).length;
    const unvisited = total - visited;
    const percent = total > 0 ? Math.round((visited / total) * 100) : 0;
    
    return { total, visited, unvisited, percent };
});

const filteredClients = computed(() => {
  let list = clients.value;
  if (isDirector.value && showAgentClientsOnly.value) {
    const myId = authStore.user?.id;
    list = list.filter(c => c.agent_user_id && c.agent_user_id !== myId);
  }
  if (!searchClient.value) return list;
  const q = searchClient.value.toLowerCase();
  return list.filter(c => 
    c.name.toLowerCase().includes(q) || 
    (c.cif && c.cif.toLowerCase().includes(q))
  );
});

const totalSelectedBalance = computed(() => {
    return invoices.value
        .filter(inv => selectedInvoiceIds.value.includes(inv.id))
        .reduce((sum, inv) => sum + parseFloat(inv.balance), 0);
});

const validationError = computed(() => {
    if (paymentForm.value.instrument === 'bo' || paymentForm.value.instrument === 'cec') {
        if (!paymentForm.value.series || !paymentForm.value.number || !paymentForm.value.bank || !paymentForm.value.due_date) {
            return 'Completați toate câmpurile obligatorii pentru instrument (Serie, Număr, Bancă, Scadență).';
        }
    }

    if (paymentForm.value.type === 'valoare') {
        if (paymentForm.value.amount > totalSelectedBalance.value) {
            return `Suma introdusă (${paymentForm.value.amount}) este mai mare decât totalul soldurilor selectate (${totalSelectedBalance.value}).`;
        }
        if (selectedInvoiceIds.value.length === 0 && paymentForm.value.amount > 0) {
             return 'Selectați cel puțin o factură pentru a acoperi suma.';
        }
    }
    return null;
});

const areAllInvoicesSelected = computed(() => {
    return invoices.value.length > 0 && selectedInvoiceIds.value.length === invoices.value.length;
});

watch(selectedInvoiceIds, () => {
    if (paymentForm.value.type === 'facturi') {
        paymentForm.value.amount = totalSelectedBalance.value;
    }
});

const loadData = async () => {
  loading.value = true;
  try {
    const clientsRes = await agentService.getClients({ page: 1, limit: 100 });
    clients.value = clientsRes.data.data; // Paginated response

    try {
        const routesRes = await agentService.getRoutes();
        routes.value = routesRes.data;
    } catch (e) {
        console.warn('Failed to load routes', e);
    }

    try {
        const rbRes = await agentService.getActiveReceiptBook();
        receiptBook.value = rbRes.data;
    } catch (e) {
        console.warn('No active receipt book found', e);
        receiptBook.value = null;
    }

    if (isDirector.value) {
      const agentsRes = await agentService.getAgents();
      agents.value = agentsRes.data;
    }
  } catch (err) {
    console.error('Failed to load agent dashboard data', err);
  } finally {
    loading.value = false;
  }
};

const formatPrice = (value, currency = 'RON') => {
  return new Intl.NumberFormat('ro-RO', {
    style: 'currency',
    currency: currency
  }).format(value || 0);
};

const openPaymentModal = async (client) => {
  selectedClient.value = client;
  paymentForm.value = {
    instrument: 'numerar',
    type: 'facturi',
    amount: 0,
    payment_date: new Date().toISOString().split('T')[0],
    notes: '',
    series: '',
    number: '',
    bank: '',
    due_date: ''
  };
  selectedInvoiceIds.value = [];
  invoices.value = [];
  showPaymentModal.value = true;
  
  loadingInvoices.value = true;
  try {
      const res = await agentService.getClientInvoices(client.id);
      invoices.value = res.data;
  } catch (e) {
      console.error(e);
      alert('Nu s-au putut încărca facturile clientului.');
  } finally {
      loadingInvoices.value = false;
  }
};

const closePaymentModal = () => {
  showPaymentModal.value = false;
  selectedClient.value = null;
  invoices.value = [];
  selectedInvoiceIds.value = [];
};

const closeCancelModal = () => {
    showCancelModal.value = false;
    cancelReason.value = '';
};

const onPaymentTypeChange = () => {
    selectedInvoiceIds.value = [];
    paymentForm.value.amount = 0;
};

const cancelCurrentReceipt = () => {
    showCancelModal.value = true;
};

const loadHistory = async (page = 1) => {
    loadingHistory.value = true;
    try {
        const res = await agentService.getVisits({ 
            page, 
            limit: 10,
            status: 'completed', // Only completed visits? Or all? Let's show all except cancelled maybe? Or just sort by desc
        });
        historyVisits.value = res.data.data;
        historyMeta.value = {
            current_page: res.data.current_page,
            last_page: res.data.last_page
        };
    } catch (e) {
        console.error('Failed to load visit history', e);
        toast.error('Nu s-a putut încărca istoricul vizitelor.');
    } finally {
        loadingHistory.value = false;
    }
};

const calculateDuration = (start, end) => {
    if (!start || !end) return '-';
    const s = new Date(start);
    const e = new Date(end);
    const diffMs = e - s;
    const diffMins = Math.round(diffMs / 60000);
    
    if (diffMins < 60) return `${diffMins} min`;
    const hours = Math.floor(diffMins / 60);
    const mins = diffMins % 60;
    return `${hours}h ${mins}m`;
};

const confirmCancelReceipt = async () => {
    if (!cancelReason.value) return;

    submitting.value = true;
    try {
        await agentService.cancelReceipt({ notes: cancelReason.value });
        alert('Chitanța a fost anulată cu succes.');
        
        // Refresh data
        const rbRes = await agentService.getActiveReceiptBook();
        receiptBook.value = rbRes.data;
        closeCancelModal();
    } catch (e) {
        console.error(e);
        alert(e.response?.data?.message || 'Eroare la anularea chitanței.');
    } finally {
        submitting.value = false;
    }
};

const toggleAllInvoices = () => {
    if (areAllInvoicesSelected.value) {
        selectedInvoiceIds.value = [];
    } else {
        selectedInvoiceIds.value = invoices.value.map(inv => inv.id);
    }
};

const submitPayment = async () => {
  if (!selectedClient.value) return;
  if (validationError.value) return;
  
  submitting.value = true;
  try {
    await agentService.storePayment({
      customer_id: selectedClient.value.id,
      ...paymentForm.value,
      selected_invoices: selectedInvoiceIds.value,
      customer_visit_id: visitStore.activeVisit?.id
    });
    alert('Chitanța a fost emisă cu succes!');
    closePaymentModal();
    // Refresh clients to update balance if backend supports it immediately
    loadData();
  } catch (err) {
    console.error(err);
    alert('Eroare la emitere chitanță: ' + (err.response?.data?.message || err.message));
  } finally {
    submitting.value = false;
  }
};

const handleStartVisit = async (customer) => {
  if (visitStore.activeVisit) {
    if (!confirm('Aveți deja o vizită activă. Doriți să o încheiați pe cea curentă și să începeți una nouă?')) {
      return;
    }
    await visitStore.endVisit();
  }
  
  try {
    await visitStore.startVisit(customer.id);
    toast.success(`Vizită începută cu ${customer.name}`);
  } catch (e) {
    console.error(e);
    toast.error('Nu s-a putut începe vizita.');
  }
};

const handleEndVisit = () => {
  // Reset form
  endVisitForm.value = {
      outcome: 'presentation',
      notes: ''
  };
  showEndVisitModal.value = true;
};

const confirmEndVisit = async () => {
  try {
    await visitStore.endVisit(endVisitForm.value);
    toast.success('Vizită încheiată cu succes.');
    showEndVisitModal.value = false;
    // Reload history if on that tab
    if (activeTab.value === 'history') {
        loadHistory();
    }
  } catch (e) {
    console.error(e);
    toast.error('Eroare la încheierea vizitei.');
  }
};

const canPerformAction = (customer) => {
  // Can perform action only if there is an active visit with THIS customer
  return visitStore.activeVisit && visitStore.activeVisit.customer_id === customer.id;
};

const impersonateClient = (client) => {
  if (!canPerformAction(client)) {
    toast.warning('Trebuie să începeți o vizită pentru a plasa comenzi!');
    return;
  }
  
  if (confirm(`Sunteți sigur că doriți să plasați comenzi în numele clientului ${client.name}?`)) {
    localStorage.setItem('impersonated_client_id', client.id);
    localStorage.setItem('impersonated_client_name', client.name);
    // Folosim window.location pentru a forța reîncărcarea și aplicarea interceptorilor
    window.location.href = '/'; 
  }
};

onMounted(() => {
  loadData();
  visitStore.checkActiveVisit();
});
</script>

<style scoped>
.modal-backdrop {
  opacity: 0.5;
}
</style>
