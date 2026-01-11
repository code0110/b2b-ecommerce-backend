import { defineStore } from 'pinia';
import { ref } from 'vue';

export const usePreferencesStore = defineStore('preferences', () => {
  const showVat = ref(localStorage.getItem('preferences_show_vat') === 'false' ? false : true);

  const toggleVat = () => {
    showVat.value = !showVat.value;
    localStorage.setItem('preferences_show_vat', showVat.value);
  };

  const setShowVat = (value) => {
    showVat.value = value;
    localStorage.setItem('preferences_show_vat', showVat.value);
  };

  return {
    showVat,
    toggleVat,
    setShowVat
  };
});
