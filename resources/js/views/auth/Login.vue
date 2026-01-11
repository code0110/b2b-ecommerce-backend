<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/store/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const remember = ref(false);
const localError = ref('');

const loading = computed(() => authStore.loading);

const handleSubmit = async () => {
  localError.value = '';

  try {
    const user = await authStore.login({
      email: email.value,
      password: password.value,
      remember: remember.value,
    });

    // Redirect explicit, dacă avem ?redirect=/...
    const redirect = route.query.redirect;
    if (redirect) {
      return router.push(redirect.toString());
    }

    // Redirect în funcție de rol
    if (authStore.role === 'admin') {
      router.push({ name: 'admin-dashboard' });
    } else {
      router.push({ name: 'account-dashboard' });
    }
  } catch (e) {
    localError.value = authStore.error || 'Autentificare eșuată.';
  }
};
</script>

<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h1 class="h4 mb-3 text-center">Autentificare</h1>
            <p class="text-muted text-center mb-4">
              Intră în contul tău pentru a vedea prețuri, comenzi și documente.
            </p>

            <div v-if="localError" class="alert alert-danger">
              {{ localError }}
            </div>

            <form @submit.prevent="handleSubmit" novalidate>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input
                  v-model="email"
                  type="email"
                  class="form-control"
                  required
                  autocomplete="email"
                />
              </div>

              <div class="mb-3">
                <label class="form-label">Parolă</label>
                <input
                  v-model="password"
                  type="password"
                  class="form-control"
                  required
                  autocomplete="current-password"
                />
              </div>

              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                  <input
                    id="remember"
                    v-model="remember"
                    class="form-check-input"
                    type="checkbox"
                  />
                  <label class="form-check-label" for="remember">
                    Ține-mă minte
                  </label>
                </div>

                <!-- Placeholder pentru "Ai uitat parola?" -->
                <router-link
                  :to="{ name: 'login' }"
                  class="small text-decoration-none"
                >
                  Ai uitat parola?
                </router-link>
              </div>

              <button
                class="btn btn-orange w-100"
                type="submit"
                :disabled="loading"
              >
                <span v-if="!loading">Autentificare</span>
                <span v-else>Se autentifică...</span>
              </button>
            </form>

            <hr class="my-4" />

            <div class="text-center">
              <span class="text-muted">Nu ai cont?</span>
              <router-link
                :to="{ name: 'register' }"
                class="ms-1 text-decoration-none"
              >
                Creează cont
              </router-link>
            </div>

            <!-- Placeholder pentru login social -->
            <!--
            <div class="mt-3 d-grid gap-2">
              <button type="button" class="btn btn-outline-secondary btn-sm">
                Login cu Google
              </button>
              <button type="button" class="btn btn-outline-secondary btn-sm">
                Login cu Facebook
              </button>
            </div>
            -->
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
