<template>
  <footer class="bg-white border-top text-muted pt-5 pb-3 mt-auto">
    <div class="container">
      <div class="row g-4">
        <!-- Column 1: About & Contact -->
        <div class="col-lg-4 col-md-6">
          <h5 class="text-dark mb-3">{{ contentStore.getBlock('footer_about_title') || 'Despre MB2B' }}</h5>
          <p class="small mb-4">
            {{ contentStore.getBlock('footer_about_text') || 'Partenerul tău de încredere pentru materiale de construcții.' }}
          </p>
          
          <h6 class="text-dark mb-2">{{ contentStore.getBlock('footer_contact_title') || 'Contact' }}</h6>
          <ul class="list-unstyled small mb-0">
            <li class="mb-2 d-flex gap-2" v-if="contentStore.getBlock('footer_contact_address')">
              <i class="bi bi-geo-alt text-orange"></i>
              <span>{{ contentStore.getBlock('footer_contact_address') }}</span>
            </li>
            <li class="mb-2 d-flex gap-2" v-if="contentStore.getBlock('footer_contact_phone')">
              <i class="bi bi-telephone text-orange"></i>
              <a :href="`tel:${contentStore.getBlock('footer_contact_phone')}`" class="text-decoration-none text-muted">{{ contentStore.getBlock('footer_contact_phone') }}</a>
            </li>
            <li class="mb-2 d-flex gap-2" v-if="contentStore.getBlock('footer_contact_email')">
              <i class="bi bi-envelope text-orange"></i>
              <a :href="`mailto:${contentStore.getBlock('footer_contact_email')}`" class="text-decoration-none text-muted">{{ contentStore.getBlock('footer_contact_email') }}</a>
            </li>
            <li class="d-flex gap-2" v-if="contentStore.getBlock('footer_contact_schedule')">
              <i class="bi bi-clock text-orange"></i>
              <span>{{ contentStore.getBlock('footer_contact_schedule') }}</span>
            </li>
          </ul>
        </div>

        <!-- Column 2: Dynamic Links 1 -->
        <div class="col-lg-2 col-md-6" v-if="column1">
          <h6 class="text-dark mb-3">{{ column1.title }}</h6>
          <ul class="list-unstyled small">
            <li v-for="(link, idx) in column1.links" :key="idx" class="mb-2">
              <RouterLink :to="link.url" class="text-decoration-none text-muted hover-primary">
                {{ link.text }}
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- Column 3: Dynamic Links 2 -->
        <div class="col-lg-2 col-md-6" v-if="column2">
          <h6 class="text-dark mb-3">{{ column2.title }}</h6>
          <ul class="list-unstyled small">
            <li v-for="(link, idx) in column2.links" :key="idx" class="mb-2">
              <RouterLink :to="link.url" class="text-decoration-none text-muted hover-primary">
                {{ link.text }}
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- Column 4: Newsletter & Social -->
        <div class="col-lg-4 col-md-6">
          <h6 class="text-dark mb-3">{{ contentStore.getBlock('footer_newsletter_title') || 'Abonează-te la newsletter' }}</h6>
          <p class="small">{{ contentStore.getBlock('footer_newsletter_text') || 'Primește ultimele oferte și noutăți direct pe email.' }}</p>
          <form @submit.prevent="subscribeNewsletter" class="mb-4">
            <div class="input-group">
              <input type="email" class="form-control form-control-sm" :placeholder="contentStore.getBlock('footer_newsletter_placeholder') || 'Adresa ta de email'" required>
              <button class="btn btn-orange btn-sm" type="submit">{{ contentStore.getBlock('footer_newsletter_button') || 'Abonează-te' }}</button>
            </div>
          </form>

          <h6 class="text-dark mb-3">{{ contentStore.getBlock('footer_social_title') || 'Social Media' }}</h6>
          <div class="d-flex gap-3" v-if="socialLinks">
            <a 
              v-for="(social, idx) in socialLinks" 
              :key="idx" 
              :href="social.url" 
              target="_blank" 
              class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center"
              style="width: 32px; height: 32px;"
              :title="social.label"
            >
              <i :class="social.icon"></i>
            </a>
          </div>
        </div>
      </div>

      <hr class="my-4 opacity-10">

      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small gap-2">
        <div class="text-muted">
          {{ contentStore.getBlock('footer_copyright') || `© ${new Date().getFullYear()} MB2B` }}
        </div>
        <div class="d-flex gap-3">
          <RouterLink to="/termeni-conditii" class="text-decoration-none text-muted">Termeni & Condiții</RouterLink>
          <RouterLink to="/gdpr" class="text-decoration-none text-muted">Politica de Confidențialitate (GDPR)</RouterLink>
          <RouterLink to="/cookies" class="text-decoration-none text-muted">Politica Cookies</RouterLink>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useContentStore } from '@/stores/content';

const contentStore = useContentStore();

// Fetch content on mount
onMounted(() => {
  if (Object.keys(contentStore.blocks).length === 0) {
    contentStore.fetchBlocks();
  }
});

const column1 = computed(() => contentStore.getBlock('footer_column_1'));
const column2 = computed(() => contentStore.getBlock('footer_column_2'));
const socialLinks = computed(() => contentStore.getBlock('footer_social_links'));

const subscribeNewsletter = () => {
  alert('Funcționalitatea de newsletter va fi implementată curând!');
};
</script>

<style scoped>
.hover-primary:hover {
  color: var(--dd-orange) !important;
  text-decoration: underline !important;
}
</style>
