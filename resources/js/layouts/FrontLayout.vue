<template>
  <div>
    <!-- Banner pentru modul de lucru în numele unui client -->
    <div
      v-if="authStore.impersonatedCustomer"
      class="bg-warning text-dark py-1 small"
    >
      <div class="container d-flex justify-content-between align-items-center">
        <div>
          Lucrezi în numele clientului
          <strong>{{ authStore.impersonatedCustomer.name }}</strong>
          <span v-if="authStore.impersonatedCustomer.clientType" class="ms-1">
            ({{ authStore.impersonatedCustomer.clientType }})
          </span>
        </div>
        <button
          type="button"
          class="btn btn-sm btn-outline-dark"
          @click="handleStopImpersonation"
        >
          Ieși din modul client
        </button>
      </div>
    </div>

    <header class="border-bottom bg-white">
      <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
          <RouterLink class="navbar-brand fw-semibold" :to="{ name: 'home' }">
            B2B/B2C Demo
          </RouterLink>

          <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#frontNavbar"
            aria-controls="frontNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
          >
            <span class="navbar-toggler-icon"></span>
          </button>

          <div class="collapse navbar-collapse" id="frontNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              <li class="nav-item">
                <RouterLink class="nav-link" :to="{ name: 'home' }">
                  Acasă
                </RouterLink>
              </li>
              <!-- Link-uri front folosind path-uri simple, pentru a evita erori de nume de rută inexistente -->
              <li class="nav-item">
                <RouterLink class="nav-link" to="/promotii">
                  Promoții
                </RouterLink>
              </li>
              <li class="nav-item">
                <RouterLink class="nav-link" to="/noutati">
                  Noutăți
                </RouterLink>
              </li>
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
              <li class="nav-item me-2" v-if="authStore.user">
                <RouterLink
                  class="btn btn-outline-secondary btn-sm"
                  :to="{ name: 'account-dashboard' }"
                >
                  Contul meu
                </RouterLink>
              </li>
              <li class="nav-item" v-if="!authStore.user">
                <RouterLink
                  class="btn btn-outline-primary btn-sm me-2"
                  :to="{ name: 'login' }"
                >
                  Autentificare
                </RouterLink>
                <RouterLink
                  class="btn btn-primary btn-sm"
                  :to="{ name: 'register' }"
                >
                  Creează cont
                </RouterLink>
              </li>
              <li class="nav-item d-flex align-items-center" v-else>
                <span class="me-2 small text-muted">
                  Logat ca: <strong>{{ authStore.user.name }}</strong>
                </span>
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  @click="handleLogout"
                >
                  Delogare
                </button>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </header>

    <main>
      <RouterView />
    </main>

    <footer class="border-top bg-white py-4 mt-5">
      <div class="container">
        <div class="row">
          <div class="col-md-6 small text-muted">
            &copy; {{ new Date().getFullYear() }} B2B/B2C Demo – Template e-commerce.
          </div>
          <div class="col-md-6 text-md-end small">
            <!-- Link-uri informative simple pe path-uri, pentru a nu depinde de nume de rută -->
            <RouterLink class="text-muted me-3" to="/despre-noi">
              Despre noi
            </RouterLink>
            <RouterLink class="text-muted me-3" to="/termeni-conditii">
              Termeni & condiții
            </RouterLink>
            <RouterLink class="text-muted" to="/gdpr">
              GDPR
            </RouterLink>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const router = useRouter()
const authStore = useAuthStore()

const handleStopImpersonation = () => {
  if (authStore.stopImpersonation) {
    authStore.stopImpersonation()
  }
  router.push({ name: 'home' })
}

const handleLogout = () => {
  if (authStore.logout) {
    authStore.logout()
  }
  router.push({ name: 'home' })
}
</script>
