import { defineStore } from 'pinia';
import { adminApi } from '@/services/http';

export const useVisitStore = defineStore('visit', {
  state: () => ({
    activeVisit: JSON.parse(localStorage.getItem('active_visit') || 'null'),
    loading: false,
  }),

  getters: {
    hasActiveVisit: (state) => !!state.activeVisit,
  },

  actions: {
    async checkActiveVisit() {
      // Check backend for any active visit for current user
      try {
        const response = await adminApi.get('/customer-visits', { params: { status: 'in_progress', limit: 1 } });
        const visits = response.data.data;
        if (visits && visits.length > 0) {
          this.setActiveVisit(visits[0]);
        } else {
          this.clearActiveVisit();
        }
      } catch (e) {
        console.error('Failed to check active visit', e);
      }
    },

    setActiveVisit(visit) {
      this.activeVisit = visit;
      localStorage.setItem('active_visit', JSON.stringify(visit));
    },

    clearActiveVisit() {
      this.activeVisit = null;
      localStorage.removeItem('active_visit');
    },

    async startVisit(customerId) {
      this.loading = true;
      try {
        let coords = { latitude: null, longitude: null };
        
        // Try to get location
        if ("geolocation" in navigator) {
            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    });
                });
                coords.latitude = position.coords.latitude;
                coords.longitude = position.coords.longitude;
            } catch (e) {
                console.warn('Could not get location', e);
            }
        }

        const response = await adminApi.post('/customer-visits/start', { 
            customer_id: customerId,
            ...coords
        });
        this.setActiveVisit(response.data);
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async endVisit(data = {}) {
      if (!this.activeVisit) return;
      
      this.loading = true;
      try {
        const response = await adminApi.post(`/customer-visits/${this.activeVisit.id}/end`, data);
        this.clearActiveVisit();
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});
