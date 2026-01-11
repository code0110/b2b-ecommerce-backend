import { defineStore } from 'pinia';
import { adminApi } from '@/services/http';
import { useAuthStore } from './auth';

export const useVisitStore = defineStore('visit', {
  state: () => ({
    activeVisit: JSON.parse(localStorage.getItem('active_visit') || 'null'),
    loading: false,
    watchId: null,
    heartbeatInterval: null,
  }),

  getters: {
    hasActiveVisit: (state) => !!state.activeVisit,
  },

  actions: {
    async checkActiveVisit() {
      this.loading = true;
      try {
        const authStore = useAuthStore();
        if (!authStore.user) return;

        const response = await adminApi.get('/customer-visits', { 
            params: { 
                status: 'in_progress', 
                limit: 1,
                agent_id: authStore.user.id
            } 
        });
        const visits = response.data.data;
        console.log('Active Visits:', visits);
        if (visits && visits.length > 0) {
          this.setActiveVisit(visits[0]);
          this.startLocationTracking();
        } else {
          this.clearActiveVisit();
        }
      } catch (e) {
        console.error('Failed to check active visit', e);
        this.clearActiveVisit();
      } finally {
        this.loading = false;
      }
    },

    setActiveVisit(visit) {
      this.activeVisit = visit;
      localStorage.setItem('active_visit', JSON.stringify(visit));
    },

    clearActiveVisit() {
      this.activeVisit = null;
      localStorage.removeItem('active_visit');
      this.stopLocationTracking();
    },

    startLocationTracking() {
      if (!this.activeVisit) return;
      if (this.watchId) return; // Deja activ

      console.log('Starting location tracking for visit', this.activeVisit.id);

      if ("geolocation" in navigator) {
        // 1. Watch Position pentru update local și precizie
        this.watchId = navigator.geolocation.watchPosition(
          (position) => {
             // Opțional: putem actualiza un state local cu poziția curentă pentru UI
             console.log('Location update:', position.coords);
          },
          (error) => {
             console.warn('Watch position error:', error);
          },
          {
             enableHighAccuracy: true,
             maximumAge: 0,
             timeout: 10000
          }
        );

        // 2. Heartbeat periodic către server (ex: la fiecare 60 secunde)
        this.heartbeatInterval = setInterval(async () => {
             this.sendLocationHeartbeat();
        }, 60000); 

        // Trimitem unul imediat
        this.sendLocationHeartbeat();
      }
    },

    stopLocationTracking() {
      if (this.watchId) {
        navigator.geolocation.clearWatch(this.watchId);
        this.watchId = null;
      }
      if (this.heartbeatInterval) {
        clearInterval(this.heartbeatInterval);
        this.heartbeatInterval = null;
      }
      console.log('Location tracking stopped');
    },

    async sendLocationHeartbeat() {
        if (!this.activeVisit) return;

        // Get Network Type if available
        let networkType = 'unknown';
        if (navigator.connection) {
            networkType = navigator.connection.effectiveType; // '4g', '3g', '2g', 'slow-2g'
        }

        // Get Battery Level if available (using Battery Status API if supported)
        let batteryLevel = null;
        if (navigator.getBattery) {
             try {
                 const battery = await navigator.getBattery();
                 batteryLevel = Math.round(battery.level * 100);
             } catch (e) {
                 // Battery API not supported or blocked
             }
        }

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                try {
                    await adminApi.post(`/customer-visits/${this.activeVisit.id}/location`, {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        speed: position.coords.speed, // m/s
                        heading: position.coords.heading, // degrees
                        altitude: position.coords.altitude, // meters
                        provider: 'browser',
                        battery_level: batteryLevel,
                        network_type: networkType
                    });
                    console.log('Detailed location heartbeat sent');
                } catch (e) {
                    console.error('Failed to send location heartbeat', e);
                }
            },
            (err) => console.warn('Heartbeat location error', err),
            { enableHighAccuracy: true, timeout: 5000 }
        );
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
        this.startLocationTracking(); // Start tracking
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
        this.clearActiveVisit(); // Stops tracking automatically
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});
