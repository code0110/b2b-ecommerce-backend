<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="h4 mb-0">Istoric Rute Zilnice</h2>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-bold">Agent</label>
            <select v-model="filters.agent_id" class="form-select" @change="fetchHistory">
              <option value="">Selectează agent...</option>
              <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                {{ agent.name }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-bold">Data</label>
            <input type="date" v-model="filters.date" class="form-control" @change="fetchHistory">
          </div>
          <div class="col-md-2 d-flex align-items-end">
             <button @click="fetchHistory" class="btn btn-primary w-100" :disabled="!filters.agent_id || !filters.date">
                <i class="bi bi-search me-1"></i> Caută
             </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Summary -->
    <div v-if="routeData" class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card bg-light border-0 h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small text-uppercase mb-1">Total Distanță</div>
                    <div class="h4 mb-0 text-primary">{{ routeData.total_distance.toFixed(2) }} km</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light border-0 h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small text-uppercase mb-1">Ora Start</div>
                    <div class="h4 mb-0">{{ formatTime(routeData.start_time) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light border-0 h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small text-uppercase mb-1">Ora Stop</div>
                    <div class="h4 mb-0">{{ formatTime(routeData.end_time) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light border-0 h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small text-uppercase mb-1">Puncte Track</div>
                    <div class="h4 mb-0">{{ routeData.route_points ? routeData.route_points.length : 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Map -->
    <div class="card shadow-sm" style="min-height: 500px;">
        <div class="card-body p-0 position-relative">
            <div v-if="loading" class="position-absolute w-100 h-100 bg-white bg-opacity-75 d-flex align-items-center justify-content-center" style="z-index: 5;">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div v-if="!routeData && !loading" class="d-flex align-items-center justify-content-center h-100 text-muted" style="min-height: 500px;">
                <div class="text-center">
                    <i class="bi bi-map fs-1"></i>
                    <p class="mt-2">Selectați un agent și o dată pentru a vizualiza ruta.</p>
                </div>
            </div>
            
            <div id="map" style="height: 600px; width: 100%;" v-show="routeData"></div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { adminApi } from '@/services/http';
import { setOptions, importLibrary } from "@googlemaps/js-api-loader";

import { useRoute } from 'vue-router';

const route = useRoute();
const agents = ref([]);
const filters = ref({
    agent_id: '',
    date: (() => {
        const d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    })()
});
const routeData = ref(null);
const loading = ref(false);
let map = null;
let polyline = null;
let markers = [];

// Initialize Google Maps options
setOptions({
    apiKey: import.meta.env.VITE_GOOGLE_MAPS_API_KEY,
    version: "weekly",
    libraries: ["maps"]
});

onMounted(async () => {
    // Check for query params
    if (route.query.agent_id) {
        filters.value.agent_id = parseInt(route.query.agent_id);
    }
    if (route.query.date) {
        filters.value.date = route.query.date;
    }

    // Load Agents
    try {
        const { data } = await adminApi.get('/reports/locations'); // Reuse existing endpoint to get list of agents
        agents.value = data.map(a => ({ id: a.id, name: a.name }));
        
        // Auto fetch if agent is selected
        if (filters.value.agent_id) {
            fetchHistory();
        }
    } catch (e) {
        console.error(e);
    }
});

const formatTime = (isoString) => {
    if (!isoString) return '--:--';
    return new Date(isoString).toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit' });
};

const initMap = async (center) => {
    const { Map } = await importLibrary("maps");
    
    if (!map) {
        map = new Map(document.getElementById("map"), {
            center: center || { lat: 46.0, lng: 25.0 }, // Romania center
            zoom: 8,
            // Explicitly ensure no mapId is passed
        });
    } else if (center) {
        map.setCenter(center);
        map.setZoom(12);
    }
};

const clearMap = () => {
    if (polyline) polyline.setMap(null);
    markers.forEach(m => m.setMap(null));
    markers = [];
};

const drawRoute = async () => {
    if (!routeData.value || !routeData.value.route_points || routeData.value.route_points.length === 0) return;

    // Ensure google is available
    if (!window.google || !window.google.maps) {
        console.error("Google Maps API not loaded");
        return;
    }

    const { Polyline } = await importLibrary("maps");

    const points = routeData.value.route_points.map(p => ({
        lat: parseFloat(p.latitude),
        lng: parseFloat(p.longitude)
    }));

    clearMap();

    // Init map if not exists
    await initMap(points[0]);

    // Draw Polyline
    polyline = new Polyline({
        path: points,
        geodesic: true,
        strokeColor: "#FF0000",
        strokeOpacity: 1.0,
        strokeWeight: 4,
    });
    polyline.setMap(map);

    // Add Start Marker using legacy google.maps.Marker
    const startMarker = new google.maps.Marker({
        map,
        position: points[0],
        title: "Start Program: " + formatTime(routeData.value.start_time),
    });
    markers.push(startMarker);

    // Add End Marker
    const endMarker = new google.maps.Marker({
        map,
        position: points[points.length - 1],
        title: "Stop Program: " + formatTime(routeData.value.end_time || routeData.value.route_points[points.length-1].recorded_at),
    });
    markers.push(endMarker);

    // Fit Bounds
    const bounds = new google.maps.LatLngBounds();
    points.forEach(p => bounds.extend(p));
    map.fitBounds(bounds);
};

const fetchHistory = async () => {
    if (!filters.value.agent_id || !filters.value.date) return;

    loading.value = true;
    routeData.value = null;
    clearMap();

    try {
        const { data } = await adminApi.get('/tracking/history', { params: filters.value });
        routeData.value = data;
        await drawRoute();
    } catch (e) {
        if (e.response && e.response.status === 404) {
            alert('Nu există date pentru această dată.');
        } else {
            console.error(e);
            alert('Eroare la încărcarea rutei.');
        }
    } finally {
        loading.value = false;
    }
};
</script>
