<template>
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h1 class="h4 mb-3 text-center">Autentificare</h1>

          <form @submit.prevent="onSubmit" novalidate>
            <div class="mb-3">
              <label for="email" class="form-label">Email / Username</label>
              <input
                id="email"
                v-model="email"
                type="email"
                class="form-control"
                required
                autocomplete="username"
              />
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">Parolă</label>
              <input
                id="password"
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
                  type="checkbox"
                  class="form-check-input"
                />
                <label class="form-check-label" for="remember">
                  Ține-mă minte
                </label>
              </div>

              <button type="button" class="btn btn-link btn-sm p-0">
                Ai uitat parola?
              </button>
            </div>

            <div class="mb-3">
              <label class="form-label small">Rol demo (doar pentru template)</label>
              <select v-model="role" class="form-select form-select-sm">
                <option value="b2c">Client B2C</option>
                <option value="b2b">Client B2B</option>
                <option value="agent">Agent vânzări</option>
                <option value="director">Director vânzări</option>
                <option value="admin">Administrator</option>
              </select>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
              Autentificare
            </button>

            <div class="d-grid gap-2 mb-3">
              <button type="button" class="btn btn-outline-secondary" disabled>
                Login cu Google
              </button>
              <button type="button" class="btn btn-outline-secondary" disabled>
                Login cu Facebook
              </button>
            </div>

            <p class="text-center small mb-0">
              Nu ai cont?
              <RouterLink :to="{ name: 'register' }">Creează cont</RouterLink>
            </p>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const remember = ref(false)
const role = ref('b2c')

const onSubmit = () => {
  // TODO: înlocuiește cu apel real la API.
  authStore.loginDummy({ email: email.value, role: role.value })

  if (authStore.role === 'admin') {
    router.push({ name: 'admin-dashboard' })
  } else {
    router.push({ name: 'account-dashboard' })
  }
}
</script>
