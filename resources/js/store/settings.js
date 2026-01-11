import { defineStore } from 'pinia';
import { fetchSettings, updateSettings } from '@/services/admin/settings';
import { useToast } from 'vue-toastification';

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    settings: [],
    loading: false,
  }),
  getters: {
    getSetting: (state) => (key) => {
      const s = state.settings.find((item) => item.key === key);
      return s ? s.value : null;
    },
    groupedSettings: (state) => {
      const groups = {};
      state.settings.forEach((s) => {
        if (!groups[s.group]) groups[s.group] = [];
        groups[s.group].push(s);
      });
      return groups;
    },
  },
  actions: {
    async loadSettings() {
      this.loading = true;
      try {
        const response = await fetchSettings();
        this.settings = response.data;
      } catch (error) {
        console.error('Failed to load settings', error);
      } finally {
        this.loading = false;
      }
    },
    async saveSettings(updatedSettings) {
      const toast = useToast();
      this.loading = true;
      try {
        await updateSettings(updatedSettings);
        toast.success('Setările au fost salvate cu succes!');
        await this.loadSettings(); // Reload to ensure sync
      } catch (error) {
        console.error('Failed to save settings', error);
        toast.error('Eroare la salvarea setărilor.');
      } finally {
        this.loading = false;
      }
    },
  },
});
