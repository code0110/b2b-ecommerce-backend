<template>
  <div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <h1 class="h4 mb-3 text-center">Creează cont</h1>

          <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
              <button
                class="nav-link"
                :class="{ active: activeTab === 'b2c' }"
                type="button"
                role="tab"
                @click="activeTab = 'b2c'"
              >
                Client B2C
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button
                class="nav-link"
                :class="{ active: activeTab === 'b2b' }"
                type="button"
                role="tab"
                @click="activeTab = 'b2b'"
              >
                Client B2B / Companie
              </button>
            </li>
          </ul>

          <div class="tab-content">
            <!-- B2C -->
            <div
              id="b2c"
              class="tab-pane fade"
              :class="{ 'show active': activeTab === 'b2c' }"
              role="tabpanel"
            >
              <form @submit.prevent="onSubmitB2C">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Nume</label>
                    <input v-model="b2c.lastName" type="text" class="form-control" required />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Prenume</label>
                    <input v-model="b2c.firstName" type="text" class="form-control" required />
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input v-model="b2c.email" type="email" class="form-control" required />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Telefon</label>
                    <input v-model="b2c.phone" type="tel" class="form-control" />
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Parolă</label>
                    <input
                      v-model="b2c.password"
                      type="password"
                      class="form-control"
                      required
                      minlength="6"
                    />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Confirmare parolă</label>
                    <input
                      v-model="b2c.passwordConfirm"
                      type="password"
                      class="form-control"
                      required
                    />
                  </div>
                </div>

                <div class="form-check mb-3">
                  <input
                    id="b2cTerms"
                    v-model="b2c.acceptTerms"
                    class="form-check-input"
                    type="checkbox"
                    required
                  />
                  <label class="form-check-label small" for="b2cTerms">
                    Sunt de acord cu Termenii și Condițiile
                  </label>
                </div>

                <button type="submit" class="btn btn-primary">
                  Creează cont B2C
                </button>
              </form>
            </div>

            <!-- B2B -->
            <div
              id="b2b"
              class="tab-pane fade"
              :class="{ 'show active': activeTab === 'b2b' }"
              role="tabpanel"
            >
              <form @submit.prevent="onSubmitB2B">
                <div class="mb-3">
                  <label class="form-label">Denumire firmă</label>
                  <input v-model="b2b.companyName" type="text" class="form-control" required />
                </div>

                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">CUI</label>
                    <input v-model="b2b.cui" type="text" class="form-control" required />
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Nr. Reg. Com.</label>
                    <input v-model="b2b.regCom" type="text" class="form-control" />
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">IBAN</label>
                    <input v-model="b2b.iban" type="text" class="form-control" />
                  </div>
                </div>

                <h6 class="mt-3">Persoană de contact</h6>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Nume și prenume</label>
                    <input v-model="b2b.contactName" type="text" class="form-control" required />
                  </div>
                  <div class="col-md-3 mb-3">
                    <label class="form-label">Email</label>
                    <input v-model="b2b.contactEmail" type="email" class="form-control" required />
                  </div>
                  <div class="col-md-3 mb-3">
                    <label class="form-label">Telefon</label>
                    <input v-model="b2b.contactPhone" type="tel" class="form-control" />
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Adresă principală</label>
                  <textarea v-model="b2b.mainAddress" class="form-control" rows="2" />
                </div>

                <div class="form-check mb-3">
                  <input
                    id="wantPartner"
                    v-model="b2b.wantPartner"
                    class="form-check-input"
                    type="checkbox"
                  />
                  <label class="form-check-label small" for="wantPartner">
                    Vreau să devin partener (trimite flux B2B către echipa internă)
                  </label>
                </div>

                <div class="form-check mb-3">
                  <input
                    id="b2bTerms"
                    v-model="b2b.acceptTerms"
                    class="form-check-input"
                    type="checkbox"
                    required
                  />
                  <label class="form-check-label small" for="b2bTerms">
                    Sunt de acord cu Termenii și Condițiile
                  </label>
                </div>

                <button type="submit" class="btn btn-primary">
                  Creează cont B2B
                </button>
              </form>
            </div>
          </div>

          <p class="text-center small mt-3 mb-0">
            Ai deja cont?
            <RouterLink :to="{ name: 'login' }">Autentifică-te</RouterLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const router = useRouter()
const authStore = useAuthStore()

const activeTab = ref('b2c')

const b2c = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  password: '',
  passwordConfirm: '',
  acceptTerms: false
})

const b2b = reactive({
  companyName: '',
  cui: '',
  regCom: '',
  iban: '',
  contactName: '',
  contactEmail: '',
  contactPhone: '',
  mainAddress: '',
  wantPartner: false,
  acceptTerms: false
})

const onSubmitB2C = () => {
  // TODO: apel API real de înregistrare B2C.
  authStore.loginDummy({ email: b2c.email, role: 'b2c' })
  router.push({ name: 'account-dashboard' })
}

const onSubmitB2B = () => {
  // TODO: apel API real de înregistrare B2B + flux "devino partener" dacă b2b.wantPartner.
  authStore.loginDummy({ email: b2b.contactEmail, role: 'b2b' })
  router.push({ name: 'account-dashboard' })
}
</script>
