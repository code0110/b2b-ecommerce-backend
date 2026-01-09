<template>
  <div class="section-hero mb-4">
    <div class="container">
      <div v-if="data.items && data.items.length > 0" :id="carouselId" class="carousel slide shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
        <div class="carousel-indicators">
          <button
            v-for="(item, index) in data.items"
            :key="index"
            type="button"
            :data-bs-target="'#' + carouselId"
            :data-bs-slide-to="index"
            :class="{ active: index === 0 }"
            :aria-current="index === 0 ? 'true' : 'false'"
            :aria-label="'Slide ' + (index + 1)"
            class="rounded-circle"
            style="width: 10px; height: 10px; margin: 0 4px;"
          ></button>
        </div>
        <div class="carousel-inner">
          <div
            v-for="(item, index) in data.items"
            :key="index"
            class="carousel-item"
            :class="{ active: index === 0 }"
          >
            <div class="position-relative hero-slide" style="height: 450px; background-color: #f0f0f0;">
              <img
                v-if="item.image_url"
                :src="item.image_url"
                class="d-block w-100 h-100 object-fit-cover"
                :alt="item.title"
              />
              <div v-else class="d-flex align-items-center justify-content-center h-100 bg-secondary text-white">
                <i class="bi bi-image fs-1 opacity-50"></i>
              </div>
              
              <!-- Gradient Overlay -->
              <div class="position-absolute top-0 start-0 w-100 h-100" 
                   style="background: linear-gradient(90deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0) 100%); pointer-events: none;">
              </div>

              <div class="carousel-caption d-none d-md-flex flex-column justify-content-center h-100 text-start" style="top: 0; bottom: 0; left: 5%; right: 50%;">
                <div class="animate-fade-up">
                    <span v-if="item.subtitle" class="badge bg-orange mb-3 fw-normal text-uppercase tracking-wider px-3 py-2">{{ item.subtitle }}</span>
                    <h2 class="display-4 fw-bold text-white mb-4 hero-title-text">{{ item.title }}</h2>
                    <a v-if="item.url" :href="item.url" class="btn btn-orange btn-lg px-5 py-3 rounded-1 fw-bold text-uppercase" style="font-size: 0.9rem; letter-spacing: 1px;">
                        Vezi Oferta <i class="bi bi-chevron-right ms-2"></i>
                    </a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <button class="carousel-control-prev" type="button" :data-bs-target="'#' + carouselId" data-bs-slide="prev" style="width: 5%;">
          <span class="carousel-control-prev-icon" aria-hidden="true" style="filter: drop-shadow(0px 0px 2px rgba(0,0,0,0.5));"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" :data-bs-target="'#' + carouselId" data-bs-slide="next" style="width: 5%;">
          <span class="carousel-control-next-icon" aria-hidden="true" style="filter: drop-shadow(0px 0px 2px rgba(0,0,0,0.5));"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({ items: [] })
  }
});

const carouselId = computed(() => `carousel-${Math.random().toString(36).substr(2, 9)}`);
</script>

<style scoped>
.hero-slide {
  height: 450px;
}
@media (max-width: 768px) {
  .hero-slide {
    height: 300px;
  }
  .hero-title-text {
      font-size: 1.5rem;
  }
}
.animate-fade-up {
    animation: fadeUp 0.8s ease-out forwards;
    opacity: 0;
    transform: translateY(20px);
}
.active .animate-fade-up {
    animation-play-state: running;
}
@keyframes fadeUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
