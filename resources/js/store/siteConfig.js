import { defineStore } from 'pinia';
import api from '@/services/http';

export const useSiteConfigStore = defineStore('siteConfig', {
  state: () => ({
    config: {
      site_name: 'MB2B Industry',
      site_description: '',
      site_logo: '/imgs/logo-3.png',
      contact_phone: '',
      contact_email: '',
      show_vat_toggle: true
    },
    loaded: false
  }),
  actions: {
    async fetchConfig() {
      if (this.loaded) return;
      try {
        const response = await api.get('/config/public');
        this.config = { ...this.config, ...response.data };
        this.loaded = true;
      } catch (error) {
        console.error('Failed to load site config', error);
      }
    }
  }
});
