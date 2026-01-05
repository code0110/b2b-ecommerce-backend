<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">Raport Locații Agenți & Directori</h1>
      <button class="btn btn-primary" @click="fetchData">
        <i class="bi bi-arrow-clockwise me-1"></i> Actualizează
      </button>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Locații în Timp Real (Bazat pe ultima activitate)</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Utilizator</th>
              <th>Rol</th>
              <th>Status</th>
              <th>Locație / Client</th>
              <th>Coordonate</th>
              <th>Ultima Actualizare</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="agent in agents" :key="agent.id">
              <td>
                <div class="fw-bold">{{ agent.name }}</div>
              </td>
              <td>
                <span v-for="role in agent.roles" :key="role" class="badge bg-light text-dark border me-1">
                    {{ role }}
                </span>
              </td>
              <td>
                <span v-if="agent.status === 'in_visit'" class="badge bg-success rounded-pill px-3 position-relative">
                    <i class="bi bi-geo-alt-fill me-1"></i> În Vizită
                    <span v-if="isRecent(agent.last_seen)" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">Live</span>
                    </span>
                </span>
                <span v-else class="badge bg-secondary rounded-pill px-3">Idle</span>
              </td>
              <td>
                <div v-if="agent.customer_name">
                    <i class="bi bi-shop me-1 text-muted"></i> {{ agent.customer_name }}
                </div>
                <span v-else class="text-muted">-</span>
                
                <div v-if="agent.is_off_site" class="mt-1">
                    <span class="badge bg-danger" title="Distanță mare față de locația clientului">
                        <i class="bi bi-exclamation-triangle me-1"></i> Off-site
                    </span>
                    <small v-if="agent.distance_deviation" class="text-danger d-block" style="font-size: 0.75rem;">
                        Abatere: {{ agent.distance_deviation }}m
                    </small>
                </div>
              </td>
              <td>
                <div v-if="agent.latitude && agent.longitude">
                    <div class="mb-1 small font-monospace text-muted">
                        {{ parseFloat(agent.latitude).toFixed(5) }}, {{ parseFloat(agent.longitude).toFixed(5) }}
                    </div>
                    <a :href="`https://www.google.com/maps/search/?api=1&query=${agent.latitude},${agent.longitude}`" target="_blank" class="btn btn-sm btn-outline-primary me-1">
                        <i class="bi bi-map me-1"></i> Vezi Harta
                    </a>
                    <RouterLink :to="{ name: 'admin-reports-route-history', query: { agent_id: agent.id } }" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-clock-history me-1"></i> Istoric
                    </RouterLink>
                </div>
                <span v-else class="text-muted">-</span>
                
                <!-- Telemetrie Extinsă -->
                <div v-if="agent.telemetry" class="mt-2 pt-2 border-top small">
                     <div class="d-flex justify-content-between text-muted mb-1" v-if="agent.telemetry.battery_level !== null">
                        <span><i class="bi bi-battery-half"></i> Baterie:</span>
                        <span :class="{'text-danger': agent.telemetry.battery_level < 20, 'text-success': agent.telemetry.battery_level > 50}">
                            {{ agent.telemetry.battery_level }}%
                        </span>
                     </div>
                     <div class="d-flex justify-content-between text-muted mb-1" v-if="agent.telemetry.speed !== null">
                        <span><i class="bi bi-speedometer2"></i> Viteză:</span>
                        <span :class="{'text-danger fw-bold': agent.telemetry.speed > 8.3}">
                            {{ (agent.telemetry.speed * 3.6).toFixed(1) }} km/h
                        </span>
                     </div>
                     <div class="d-flex justify-content-between text-muted" v-if="agent.telemetry.network_type">
                        <span><i class="bi bi-wifi"></i> Rețea:</span>
                        <span class="text-uppercase">{{ agent.telemetry.network_type }}</span>
                     </div>
                     <div class="d-flex justify-content-between text-muted" v-if="agent.telemetry.accuracy">
                        <span><i class="bi bi-crosshair"></i> Acuratețe:</span>
                        <span :class="{'text-danger': agent.telemetry.accuracy > 50}">
                            {{ agent.telemetry.accuracy.toFixed(0) }}m
                        </span>
                     </div>
                </div>
              </td>
              <td>
                <div v-if="agent.last_seen">
                    <div :class="{'text-success fw-bold': isRecent(agent.last_seen)}">
                        {{ timeAgo(agent.last_seen) }}
                    </div>
                    <div class="small text-muted">{{ formatTime(agent.last_seen) }}</div>
                </div>
                <span v-else class="text-muted">-</span>
              </td>
            </tr>
            <tr v-if="agents.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">Nu există date disponibile.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { adminApi } from '@/services/http';
import { useToast } from 'vue-toastification';

const toast = useToast();
const agents = ref([]);

const fetchData = async () => {
    try {
        const response = await adminApi.get('/reports/locations');
        agents.value = response.data;
    } catch (error) {
        console.error('Error fetching locations:', error);
        toast.error('Nu s-au putut încărca locațiile.');
    }
};

const formatTime = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('ro-RO');
};

const timeAgo = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'acum câteva secunde';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `acum ${minutes} minute`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `acum ${hours} ore`;
    return '';
};

const isRecent = (dateString) => {
    if (!dateString) return false;
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    return seconds < 300; // Mai puțin de 5 minute
};

onMounted(() => {
    fetchData();
});
</script>
