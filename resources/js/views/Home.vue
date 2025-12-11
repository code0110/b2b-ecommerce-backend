<script setup>
import { ref, onMounted } from 'vue';
import { fetchHomepage } from '@/services/home';

const loading = ref(false);
const error = ref('');
const promotions = ref([]);
const newProducts = ref([]);
const recommended = ref([]);
const onSale = ref([]);

const loadHome = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchHomepage();
    promotions.value = data.promotions || [];
    newProducts.value = data.new_products || [];
    recommended.value = data.recommended || [];
    onSale.value = data.on_sale || [];
  } catch (e) {
    error.value = 'Nu s-au putut încărca datele pentru homepage.';
  } finally {
    loading.value = false;
  }
};

onMounted(loadHome);
</script>
