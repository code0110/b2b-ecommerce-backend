<template>
  <div class="container">
    <PageHeader
      title="Adrese & date firmă"
      subtitle="Gestionează adresele de livrare și facturare, precum și datele firmei (B2B)."
    />

    <div class="row g-3">
      <!-- Adrese livrare -->
      <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Adrese livrare</strong>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="onAddShippingAddress"
            >
              + Adresă livrare
            </button>
          </div>
          <div class="card-body small">
            <div v-if="shippingAddresses.length === 0" class="text-muted">
              Nu există adrese de livrare definite.
            </div>
            <div
              v-for="addr in shippingAddresses"
              :key="addr.id"
              class="border rounded p-2 mb-2"
            >
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold">
                    {{ addr.label }}
                    <span
                      v-if="addr.isDefault"
                      class="badge bg-primary ms-1"
                    >
                      Implicit
                    </span>
                  </div>
                  <div class="text-muted">
                    <small>{{ addr.companyName }}</small>
                  </div>
                  <div>
                    {{ addr.street }}<br />
                    {{ addr.city }}, {{ addr.county }} {{ addr.zip }}<br />
                    {{ addr.country }}
                  </div>
                  <div class="text-muted small mt-1">
                    Persoană contact: {{ addr.contactName }}
                  </div>
                </div>
                <div class="text-end">
                  <button
                    v-if="!addr.isDefault"
                    type="button"
                    class="btn btn-link btn-sm p-0"
                    @click="setDefault(addr.id)"
                  >
                    Setează implicit
                  </button>
                </div>
              </div>
            </div>

            <p class="text-muted small mb-0">
              În proiectul real, adresele sunt gestionate pe client în ERP și sincronizate aici.
              Setarea adresei implicite influențează adresa propusă automat la checkout.
            </p>
          </div>
        </div>
      </div>

      <!-- Adrese facturare + date firmă -->
      <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Adrese facturare</strong>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="onAddBillingAddress"
            >
              + Adresă facturare
            </button>
          </div>
          <div class="card-body small">
            <div v-if="billingAddresses.length === 0" class="text-muted">
              Nu există adrese de facturare definite.
            </div>
            <div
              v-for="addr in billingAddresses"
              :key="addr.id"
              class="border rounded p-2 mb-2"
            >
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold">
                    {{ addr.label }}
                    <span
                      v-if="addr.isDefault"
                      class="badge bg-primary ms-1"
                    >
                      Implicit
                    </span>
                  </div>
                  <div class="text-muted">
                    <small>{{ addr.companyName }}</small>
                  </div>
                  <div>
                    {{ addr.street }}<br />
                    {{ addr.city }}, {{ addr.county }} {{ addr.zip }}<br />
                    {{ addr.country }}
                  </div>
                  <div class="text-muted small mt-1">
                    Persoană contact: {{ addr.contactName }}
                  </div>
                </div>
                <div class="text-end">
                  <button
                    v-if="!addr.isDefault"
                    type="button"
                    class="btn btn-link btn-sm p-0"
                    @click="setDefault(addr.id)"
                  >
                    Setează implicit
                  </button>
                </div>
              </div>
            </div>

            <p class="text-muted small mb-0">
              De obicei există o singură adresă de facturare (sediu social), dar platforma poate
              gestiona și multiple sedii pentru facturare separată.
            </p>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-header py-2">
            <strong>Date firmă (B2B)</strong>
          </div>
          <div class="card-body small">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label text-muted">Denumire firmă</label>
                <input
                  v-model="companyData.name"
                  type="text"
                  class="form-control form-control-sm"
                  disabled
                />
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted">CUI</label>
                <input
                  v-model="companyData.cui"
                  type="text"
                  class="form-control form-control-sm"
                  disabled
                />
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted">Nr. Reg. Com</label>
                <input
                  v-model="companyData.regCom"
                  type="text"
                  class="form-control form-control-sm"
                  disabled
                />
              </div>
            </div>
            <div class="row g-2 mt-1">
              <div class="col-md-6">
                <label class="form-label text-muted">IBAN</label>
                <input
                  v-model="companyData.iban"
                  type="text"
                  class="form-control form-control-sm"
                  disabled
                />
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted">Persoană contact</label>
                <input
                  v-model="companyData.contactPerson"
                  type="text"
                  class="form-control form-control-sm"
                  disabled
                />
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted">Telefon</label>
                <input
                  v-model="companyData.phone"
                  type="text"
                  class="form-control form-control-sm"
                  disabled
                />
              </div>
            </div>

            <div class="d-flex justify-content-end mt-2">
              <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                @click="requestCompanyDataChange"
              >
                Propune modificare date firmă
              </button>
            </div>

            <p class="text-muted small mt-2 mb-0">
              Pentru siguranță, modificarea datelor firmei (CUI, Nr. Reg. Com, IBAN) se face de obicei
              printr-un flux de aprobare intern: solicitarea ajunge la un operator care verifică noile
              date și le validează în ERP.
            </p>
          </div>
        </div>
      </div>
    </div>

    <p v-if="infoMessage" class="small text-muted mt-2 mb-0">
      {{ infoMessage }}
    </p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import { useAccountProfileStore } from '@/store/accountProfile'

const store = useAccountProfileStore()

const shippingAddresses = computed(() => store.shippingAddresses)
const billingAddresses = computed(() => store.billingAddresses)
const companyData = computed(() => store.companyData)

const infoMessage = ref('')

const setDefault = (id) => {
  store.setDefaultAddress(id)
  infoMessage.value =
    'Template: adresa selectată a fost marcată ca implicită. În implementarea reală se actualizează și în ERP / backend.'
}

const onAddShippingAddress = () => {
  infoMessage.value =
    'Template: aici se poate deschide un formular/modal pentru adăugarea unei noi adrese de livrare.'
}

const onAddBillingAddress = () => {
  infoMessage.value =
    'Template: aici se poate deschide un formular/modal pentru adăugarea unei noi adrese de facturare.'
}

const requestCompanyDataChange = () => {
  infoMessage.value =
    'Template: cererea de modificare a datelor de firmă a fost generată. În implementarea reală se va crea un tichet / task pentru operatorii interni.'
}
</script>
