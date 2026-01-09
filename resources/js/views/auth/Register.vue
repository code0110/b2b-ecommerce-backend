<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/store/auth';
import { apiRegisterB2C, apiRegisterB2B } from '@/services/auth';

const router = useRouter();
const authStore = useAuthStore();

// tab curent: 'b2c' sau 'b2b'
const activeTab = ref('b2c');

// ---------- B2C ----------
const b2c = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  accept_terms: false,
});

const b2cError = ref('');
const b2cLoading = ref(false);

// ---------- B2B ----------
const b2b = ref({
  company_name: '',
  cui: '',
  reg_com: '',
  iban: '',
  contact_name: '',
  email: '',
  phone: '',
  main_address: '',
  want_to_be_partner: false,
});

const b2bError = ref('');
const b2bLoading = ref(false);

// Submit B2C
const submitB2C = async () => {
  b2cError.value = '';

  if (!b2c.value.accept_terms) {
    b2cError.value = 'Trebuie să accepți Termenii și Condițiile.';
    return;
  }

  b2cLoading.value = true;

  try {
    // 1) creăm cont B2C în backend
    await apiRegisterB2C({ ...b2c.value });

    // 2) auto-login
    const user = await authStore.login({
      email: b2c.value.email,
      password: b2c.value.password,
      remember: true,
    });

    // 3) redirect în funcție de rol
    if (authStore.role === 'admin') {
      router.push({ name: 'admin-dashboard' });
    } else {
      router.push({ name: 'account-dashboard' });
    }
  } catch (e) {
    console.error('Register B2C error', e);
    b2cError.value =
      e.response?.data?.message || 'Înregistrarea B2C a eșuat.';
  } finally {
    b2cLoading.value = false;
  }
};

// Submit B2B
const submitB2B = async () => {
  b2bError.value = '';
  b2bLoading.value = true;

  try {
    await apiRegisterB2B({ ...b2b.value });

    // De obicei, contul B2B e aprobat manual → du-l la login
    router.push({ name: 'login' });
  } catch (e) {
    console.error('Register B2B error', e);
    b2bError.value =
      e.response?.data?.message || 'Înregistrarea B2B a eșuat.';
  } finally {
    b2bLoading.value = false;
  }
};
</script>

<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-7">
        <div class="card shadow-sm">
          <div class="card-body p-4 p-md-5">
            <h1 class="h4 mb-3 text-center">Creează cont</h1>
            <p class="text-muted text-center mb-4">
              Alege tipul de cont și completează datele necesare.
            </p>

            <!-- Tabs B2C / B2B -->
            <ul class="nav nav-tabs mb-4">
              <li class="nav-item">
                <button
                  class="nav-link"
                  :class="{ active: activeTab === 'b2c' }"
                  type="button"
                  @click="activeTab = 'b2c'"
                >
                  Persoană fizică (B2C)
                </button>
              </li>
              <li class="nav-item">
                <button
                  class="nav-link"
                  :class="{ active: activeTab === 'b2b' }"
                  type="button"
                  @click="activeTab = 'b2b'"
                >
                  Companie (B2B)
                </button>
              </li>
            </ul>

            <!-- B2C FORM -->
            <div v-if="activeTab === 'b2c'">
              <div v-if="b2cError" class="alert alert-danger">
                {{ b2cError }}
              </div>

              <form @submit.prevent="submitB2C" novalidate>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Nume</label>
                    <input
                      v-model="b2c.last_name"
                      type="text"
                      class="form-control"
                      required
                    />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Prenume</label>
                    <input
                      v-model="b2c.first_name"
                      type="text"
                      class="form-control"
                      required
                    />
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input
                    v-model="b2c.email"
                    type="email"
                    class="form-control"
                    required
                    autocomplete="email"
                  />
                </div>

                <div class="mb-3">
                  <label class="form-label">Telefon</label>
                  <input
                    v-model="b2c.phone"
                    type="text"
                    class="form-control"
                    autocomplete="tel"
                  />
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Parolă</label>
                    <input
                      v-model="b2c.password"
                      type="password"
                      class="form-control"
                      required
                      autocomplete="new-password"
                    />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Confirmare parolă</label>
                    <input
                      v-model="b2c.password_confirmation"
                      type="password"
                      class="form-control"
                      required
                      autocomplete="new-password"
                    />
                  </div>
                </div>

                <div class="form-check mb-3">
                  <input
                    id="b2c_terms"
                    v-model="b2c.accept_terms"
                    class="form-check-input"
                    type="checkbox"
                  />
               

                  <label class="form-check-label" for="b2c_terms">
                    Sunt de acord cu Termenii și Condițiile
                  </label>
                </div>

                <button
                  class="btn btn-orange w-100"
                  type="submit"
                  :disabled="b2cLoading"
                >
                  <span v-if="!b2cLoading">Creează cont B2C</span>
                  <span v-else>Se procesează...</span>
                </button>
              </form>
            </div>

            <!-- B2B FORM -->
            <div v-else>
              <div v-if="b2bError" class="alert alert-danger">
                {{ b2bError }}
              </div>

              <form @submit.prevent="submitB2B" novalidate>
                <div class="mb-3">
                  <label class="form-label">Denumire firmă</label>
                  <input
                    v-model="b2b.company_name"
                    type="text"
                    class="form-control"
                    required
                  />
                </div>

                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">CUI</label>
                    <input
                      v-model="b2b.cui"
                      type="text"
                      class="form-control"
                    />
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Nr. Reg. Com</label>
                    <input
                      v-model="b2b.reg_com"
                      type="text"
                      class="form-control"
                    />
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">IBAN</label>
                    <input
                      v-model="b2b.iban"
                      type="text"
                      class="form-control"
                    />
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Persoană de contact</label>
                  <input
                    v-model="b2b.contact_name"
                    type="text"
                    class="form-control"
                  />
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input
                      v-model="b2b.email"
                      type="email"
                      class="form-control"
                      required
                      autocomplete="email"
                    />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Telefon</label>
                    <input
                      v-model="b2b.phone"
                      type="text"
                      class="form-control"
                      autocomplete="tel"
                    />
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Adresă principală</label>
                  <input
                    v-model="b2b.main_address"
                    type="text"
                    class="form-control"
                  />
                </div>

                <div class="form-check mb-3">
                  <input
                    id="b2b_partner"
                    v-model="b2b.want_to_be_partner"
                    class="form-check-input"
                    type="checkbox"
                  />
                  <label class="form-check-label" for="b2b_partner">
                    Vreau să devin partener
                  </label>
                </div>

                <button
                  class="btn btn-orange w-100"
                  type="submit"
                  :disabled="b2bLoading"
                >
                  <span v-if="!b2bLoading">Creează cont B2B</span>
                  <span v-else>Se procesează...</span>
                </button>
              </form>
            </div>

            <hr class="my-4" />

            <div class="text-center">
              <span class="text-muted">Ai deja cont?</span>
              <router-link
                :to="{ name: 'login' }"
                class="ms-1 text-decoration-none"
              >
                Autentifică-te
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
