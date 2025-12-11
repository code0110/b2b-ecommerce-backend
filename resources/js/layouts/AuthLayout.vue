<template>
  <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="position-absolute top-0 start-0 w-100">
      <nav class="navbar navbar-light bg-white border-bottom shadow-sm">
        <div class="container">
          <RouterLink class="navbar-brand fw-bold" :to="{ name: 'home' }">
            B2B/B2C Shop
          </RouterLink>
        </div>
      </nav>
    </div>

    <div class="container" style="margin-top: 80px;">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>
