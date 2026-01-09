<template>
  <div>
    <!-- Header de secțiune cont -->
    <div class="dd-page-header py-3 mb-3">
      <div class="container">
        <nav aria-label="breadcrumb" class="small mb-2">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <RouterLink to="/">Acasă</RouterLink>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              Contul meu
            </li>
          </ol>
        </nav>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <h1 class="h4 mb-1">Cont client</h1>
            <p class="text-muted mb-0 small">
              Gestionează comenzile, documentele, datele de facturare, utilizatorii de companie și notificările.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Layout 2 coloane: sidebar + conținut -->
    <div class="container mb-4">
      <div class="row">
        <aside class="col-lg-3 mb-4 mb-lg-0">
          <AccountSidebar />
        </aside>
        <section class="col-lg-9">
          <RouterView />
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink, RouterView } from 'vue-router';
import AccountSidebar from '@/components/account/AccountSidebar.vue';
import { useVisitStore } from '@/store/visit';

const visitStore = useVisitStore();

onMounted(() => {
  visitStore.checkActiveVisit();
});
</script>
