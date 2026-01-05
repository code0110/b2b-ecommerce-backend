import { defineStore } from 'pinia';
import { adminApi } from '@/services/http';

export const useTrackingStore = defineStore('tracking', {
  state: () => ({
    isShiftActive: false,
    activeRoute: null,
    watchId: null,
    loading: false,
    lastPingTime: 0,
    pingIntervalId: null,
  }),

  actions: {
    async checkStatus() {
        try {
            const { data } = await adminApi.get('/tracking/status');
            this.isShiftActive = data.active;
            this.activeRoute = data.route;
            
            if (this.isShiftActive) {
                this.startTracking();
            } else {
                this.stopTracking();
            }
        } catch (e) {
            console.error('Failed to check tracking status', e);
        }
    },

    async startDay() {
        this.loading = true;
        try {
            const { data } = await adminApi.post('/tracking/start-day');
            this.activeRoute = data;
            this.isShiftActive = true;
            this.startTracking();
            return data;
        } catch (e) {
            throw e;
        } finally {
            this.loading = false;
        }
    },

    async endDay() {
        this.loading = true;
        try {
            const { data } = await adminApi.post('/tracking/end-day');
            this.activeRoute = data;
            this.isShiftActive = false;
            this.stopTracking();
            return data;
        } catch (e) {
            throw e;
        } finally {
            this.loading = false;
        }
    },

    startTracking() {
        if (this.watchId) return; // Already tracking

        console.log('Global Tracking Started');

        if ("geolocation" in navigator) {
            this.watchId = navigator.geolocation.watchPosition(
                (pos) => this.handlePositionUpdate(pos),
                (err) => console.warn('Tracking error:', err),
                {
                    enableHighAccuracy: true,
                    maximumAge: 0,
                    timeout: 10000
                }
            );

            // Also ensure we ping at least every 60 seconds even if position doesn't change much
            // to keep the "connection" alive
            this.pingIntervalId = setInterval(() => {
                navigator.geolocation.getCurrentPosition(
                    (pos) => this.handlePositionUpdate(pos), 
                    null, 
                    { enableHighAccuracy: true, timeout: 5000 }
                );
            }, 60000); 
        }
    },

    stopTracking() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        if (this.pingIntervalId) {
            clearInterval(this.pingIntervalId);
            this.pingIntervalId = null;
        }
        console.log('Global Tracking Stopped');
    },

    async handlePositionUpdate(position) {
        // Debounce: Don't send more than one request every 10 seconds
        const now = Date.now();
        if (now - this.lastPingTime < 10000) {
            return;
        }
        this.lastPingTime = now;

        // Collect Telemetry
        let networkType = 'unknown';
        if (navigator.connection) {
            networkType = navigator.connection.effectiveType;
        }
        
        // Mock check (simple)
        const isMocked = position.coords.accuracy === 0;

        try {
            await adminApi.post('/tracking/ping', {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                speed: position.coords.speed,
                heading: position.coords.heading,
                battery_level: null, // Can add battery API here too
                is_mocked: isMocked
            });
            console.log('Tracking Ping Sent');
        } catch (e) {
            console.error('Tracking Ping Failed', e);
        }
    }
  },
  
  persist: true // If using pinia-plugin-persistedstate, handy but checkStatus is safer
});
