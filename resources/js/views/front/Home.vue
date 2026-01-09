<template>
  <div class="home-page">
    <div v-if="loading" class="d-flex justify-content-center align-items-center py-5">
        <div class="spinner-border text-orange" role="status">
            <span class="visually-hidden">Se încarcă...</span>
        </div>
    </div>

    <div v-else>
        <!-- Render Dynamic Sections -->
        <div v-if="pageData && pageData.sections && pageData.sections.length > 0">
            <component
                v-for="(section, index) in pageData.sections"
                :key="index"
                :is="getComponent(section.type)"
                :data="section"
            />
        </div>

        <!-- Fallback if no sections are defined (e.g. migration period) -->
        <div v-else class="container py-5 text-center">
            <div class="alert alert-info">
                <h4 class="alert-heading">Bine ați venit!</h4>
                <p>Momentan nu există secțiuni configurate pentru pagina de pornire.</p>
                <hr>
                <p class="mb-0">Vă rugăm să configurați conținutul din panoul de administrare.</p>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, defineAsyncComponent } from 'vue';
import axios from 'axios';
import { useHead } from '@vueuse/head';

// Dynamic Components
const SectionHero = defineAsyncComponent(() => import('@/components/builder/SectionHero.vue'));
const SectionFeatures = defineAsyncComponent(() => import('@/components/builder/SectionFeatures.vue'));
const SectionCategories = defineAsyncComponent(() => import('@/components/builder/SectionCategories.vue'));
const SectionProducts = defineAsyncComponent(() => import('@/components/builder/SectionProducts.vue'));
const SectionHtml = defineAsyncComponent(() => import('@/components/builder/SectionHtml.vue'));

const loading = ref(true);
const pageData = ref(null);

const getComponent = (type) => {
    switch (type) {
        case 'hero': return SectionHero;
        case 'features': return SectionFeatures;
        case 'categories': return SectionCategories;
        case 'products': return SectionProducts;
        case 'html': return SectionHtml;
        default: return SectionHtml;
    }
};

onMounted(async () => {
    try {
        loading.value = true;
        // Fetch the 'acasa' or 'home' page. Adjust slug as needed based on seed data.
        // We'll try 'acasa' first as it's Romanian, then fallback to 'home' if needed logic existed, 
        // but for now let's assume 'acasa' is the convention or we create it.
        const response = await axios.get('/api/pages/acasa');
        pageData.value = response.data.data;
        
        useHead({
            title: pageData.value.meta_title || 'Acasă - B2B Ecommerce',
            meta: [
                { name: 'description', content: pageData.value.meta_description || '' }
            ]
        });
    } catch (error) {
        console.error('Failed to load home page content:', error);
    } finally {
        loading.value = false;
    }
});
</script>
