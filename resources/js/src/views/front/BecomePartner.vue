<template>
  <div class="container">
    <PageHeader
      title="Devino partener"
      subtitle="Completează formularul pentru a deveni client B2B și a beneficia de condiții comerciale dedicate."
    />

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-body small">
            <form @submit.prevent="onSubmit">
              <h6 class="mb-2">Date firmă</h6>
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label text-muted">Denumire firmă</label>
                  <input
                    v-model="form.companyName"
                    type="text"
                    class="form-control form-control-sm"
                    required
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label text-muted">CUI</label>
                  <input
                    v-model="form.cui"
                    type="text"
                    class="form-control form-control-sm"
                    required
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label text-muted">Nr. Reg. Com.</label>
                  <input
                    v-model="form.regCom"
                    type="text"
                    class="form-control form-control-sm"
                  />
                </div>
              </div>

              <div class="row g-2 mt-1">
                <div class="col-md-4">
                  <label class="form-label text-muted">IBAN (opțional)</label>
                  <input
                    v-model="form.iban"
                    type="text"
                    class="form-control form-control-sm"
                  />
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted">Regiune</label>
                  <select
                    v-model="form.region"
                    class="form-select form-select-sm"
                    required
                  >
                    <option value="">Alege regiunea</option>
                    <option value="Sud / București - Ilfov">Sud / București - Ilfov</option>
                    <option value="Transilvania">Transilvania</option>
                    <option value="Moldova">Moldova</option>
                    <option value="Dobrogea">Dobrogea</option>
                    <option value="Banat / Crișana">Banat / Crișana</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted">Tip activitate</label>
                  <input
                    v-model="form.activityType"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="ex: Distribuitor, Antreprenor..."
                  />
                </div>
              </div>

              <h6 class="mt-3 mb-2">Persoană de contact</h6>
              <div class="row g-2">
                <div class="col-md-4">
                  <label class="form-label text-muted">Nume persoană contact</label>
                  <input
                    v-model="form.contactPerson"
                    type="text"
                    class="form-control form-control-sm"
                    required
                  />
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted">Email</label>
                  <input
                    v-model="form.email"
                    type="email"
                    class="form-control form-control-sm"
                    required
                  />
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted">Telefon</label>
                  <input
                    v-model="form.phone"
                    type="tel"
                    class="form-control form-control-sm"
                  />
                </div>
              </div>

              <h6 class="mt-3 mb-2">Informații suplimentare</h6>
              <div class="mb-2">
                <label class="form-label text-muted">Mesaj / detalii proiect</label>
                <textarea
                  v-model="form.message"
                  class="form-control form-control-sm"
                  rows="3"
                  placeholder="Descrie pe scurt volumul estimat, tipurile de produse de interes, proiecte în derulare etc."
                />
              </div>

              <div class="form-check mb-2">
                <input
                  id="terms"
                  v-model="form.acceptTerms"
                  class="form-check-input"
                  type="checkbox"
                  required
                />
                <label class="form-check-label small" for="terms">
                  Sunt de acord cu prelucrarea datelor conform politicii de confidențialitate.
                </label>
              </div>

              <div class="d-flex justify-content-end">
                <button
                  type="submit"
                  class="btn btn-primary btn-sm"
                  :disabled="submitting"
                >
                  Trimite solicitare
                </button>
              </div>
              <p v-if="submitInfo" class="small text-muted mt-2 mb-0">
                {{ submitInfo }}
              </p>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2">
            <strong>Ce se întâmplă după trimiterea formularului?</strong>
          </div>
          <div class="card-body small text-muted">
            <ol class="mb-0">
              <li>Solicitarea ajunge în backend în lista de „cereri parteneri”.</li>
              <li>Este alocată automat unui <strong>agent de vânzări</strong> în funcție de regiune.</li>
              <li>Agentul te contactează pentru clarificări și configurare cont B2B.</li>
              <li>După aprobare, primești pe email datele de autentificare și condițiile comerciale.</li>
            </ol>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-header py-2">
            <strong>Beneficii cont B2B</strong>
          </div>
          <div class="card-body small text-muted">
            <ul class="mb-0">
              <li>Prețuri contractuale și discounturi personalizate.</li>
              <li>Limită de credit și termene de plată negociate.</li>
              <li>Istoric comenzi și șabloane de comenzi recurente.</li>
              <li>Suport dedicat prin agentul de vânzări alocat.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import { usePartnerRequestsStore } from '@/store/partnerRequests'

const store = usePartnerRequestsStore()

const form = reactive({
  companyName: '',
  cui: '',
  regCom: '',
  iban: '',
  region: '',
  activityType: '',
  contactPerson: '',
  email: '',
  phone: '',
  message: '',
  acceptTerms: false
})

const submitting = ref(false)
const submitInfo = ref('')

const onSubmit = async () => {
  if (!form.companyName || !form.cui || !form.region || !form.contactPerson || !form.email) {
    submitInfo.value = 'Te rugăm să completezi toate câmpurile obligatorii.'
    return
  }
  if (!form.acceptTerms) {
    submitInfo.value = 'Trebuie să accepți prelucrarea datelor pentru a continua.'
    return
  }

  submitting.value = true
  try {
    const payload = { ...form }
    const request = store.submitRequest(payload)
    submitInfo.value =
      'Template: cererea ta a fost înregistrată cu ID #' +
      request.id +
      '. În implementarea reală se va trimite un e-mail către echipa internă, iar cererea va apărea în lista din backend pentru alocare pe agent.'
    // opțional: resetare parțială form
    form.message = ''
  } finally {
    submitting.value = false
  }
}
</script>
