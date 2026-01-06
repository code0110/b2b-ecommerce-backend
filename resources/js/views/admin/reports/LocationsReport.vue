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
      <div class="card-body p-3">
          <div v-if="agents.length === 0" class="text-center text-muted py-4">
              Nu există date disponibile.
          </div>

          <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
              <div v-for="agent in agents" :key="agent.id" class="col">
                  <div class="card h-100 border shadow-sm">
                      <div class="card-body">
                          <!-- Header: Name & Roles -->
                          <div class="d-flex justify-content-between align-items-start mb-3">
                              <div>
                                  <h6 class="fw-bold mb-1">{{ agent.name }}</h6>
                                  <div class="d-flex flex-wrap gap-1">
                                      <span v-for="role in agent.roles" :key="role" class="badge bg-light text-dark border" style="font-size: 0.7rem;">
                                          {{ role }}
                                      </span>
                                  </div>
                              </div>
                              
                              <div class="text-end">
                                  <div v-if="agent.last_seen" class="small" :class="{'text-success fw-bold': isRecent(agent.last_seen), 'text-muted': !isRecent(agent.last_seen)}">
                                      {{ timeAgo(agent.last_seen) }}
                                  </div>
                                  <div v-if="agent.last_seen" class="text-muted" style="font-size: 0.7rem;">
                                      {{ formatTime(agent.last_seen) }}
                                  </div>
                              </div>
                          </div>

                          <!-- Status -->
                          <div class="mb-3">
                              <span v-if="agent.status === 'in_visit'" class="badge bg-success rounded-pill px-3 py-2 w-100 position-relative">
                                  <i class="bi bi-geo-alt-fill me-1"></i> În Vizită
                                  <span v-if="isRecent(agent.last_seen)" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                      <span class="visually-hidden">Live</span>
                                  </span>
                              </span>
                              <span v-else class="badge bg-secondary rounded-pill px-3 py-2 w-100">Idle</span>
                          </div>

                          <!-- Location Details -->
                          <div class="mb-3 small">
                              <div class="d-flex align-items-center mb-2">
                                  <i class="bi bi-shop me-2 text-muted"></i>
                                  <span class="fw-semibold" v-if="agent.customer_name">{{ agent.customer_name }}</span>
                                  <span class="text-muted" v-else>-</span>
                              </div>
                              
                              <div v-if="agent.is_off_site" class="alert alert-danger py-1 px-2 mb-2 d-flex align-items-center" role="alert">
                                  <i class="bi bi-exclamation-triangle me-2"></i>
                                  <div>
                                      <strong>Off-site</strong>
                                      <div v-if="agent.distance_deviation">Abatere: {{ agent.distance_deviation }}m</div>
                                  </div>
                              </div>

                              <div v-if="agent.latitude && agent.longitude" class="d-flex align-items-center text-muted font-monospace">
                                  <i class="bi bi-pin-map me-2"></i>
                                  {{ parseFloat(agent.latitude).toFixed(5) }}, {{ parseFloat(agent.longitude).toFixed(5) }}
                              </div>
                          </div>

                          <!-- Telemetry -->
                          <div v-if="agent.telemetry" class="bg-light rounded p-2 mb-3 small">
                              <div class="row g-2">
                                  <div class="col-6 d-flex justify-content-between" v-if="agent.telemetry.battery_level !== null">
                                      <span class="text-muted"><i class="bi bi-battery-half"></i> Bat:</span>
                                      <span :class="{'text-danger': agent.telemetry.battery_level < 20, 'text-success': agent.telemetry.battery_level > 50}">
                                          {{ agent.telemetry.battery_level }}%
                                      </span>
                                  </div>
                                  <div class="col-6 d-flex justify-content-between" v-if="agent.telemetry.speed !== null && agent.telemetry.speed !== undefined">
                                      <span class="text-muted"><i class="bi bi-speedometer2"></i> Vit:</span>
                                      <span :class="{'text-danger fw-bold': agent.telemetry.speed > 8.3}">
                                          {{ (Number(agent.telemetry.speed || 0) * 3.6).toFixed(1) }} km/h
                                      </span>
                                  </div>
                                  <div class="col-6 d-flex justify-content-between" v-if="agent.telemetry.network_type">
                                      <span class="text-muted"><i class="bi bi-wifi"></i> Net:</span>
                                      <span class="text-uppercase">{{ agent.telemetry.network_type }}</span>
                                  </div>
                                  <div class="col-6 d-flex justify-content-between" v-if="agent.telemetry.accuracy">
                                      <span class="text-muted"><i class="bi bi-crosshair"></i> Acc:</span>
                                      <span :class="{'text-danger': agent.telemetry.accuracy > 50}">
                                          {{ Number(agent.telemetry.accuracy || 0).toFixed(0) }}m
                                      </span>
                                  </div>
                              </div>
                          </div>

                          <!-- Actions -->
                          <div class="d-flex gap-2" v-if="agent.latitude && agent.longitude">
                              <a :href="`https://www.google.com/maps/search/?api=1&query=${agent.latitude},${agent.longitude}`" target="_blank" class="btn btn-sm btn-outline-primary flex-grow-1">
                                  <i class="bi bi-map me-1"></i> Harta
                              </a>
                              <RouterLink :to="{ name: historyRouteName, query: { agent_id: agent.id } }" class="btn btn-sm btn-outline-secondary flex-grow-1">
                                  <i class="bi bi-clock-history me-1"></i> Istoric
                              </RouterLink>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { adminApi } from '@/services/http';
import { useToast } from 'vue-toastification';
import { useAuthStore } from '@/store/auth';

const toast = useToast();
const authStore = useAuthStore();
const agents = ref([]);

const historyRouteName = computed(() => {
    return authStore.hasRole('admin') ? 'admin-reports-route-history' : 'account-route-history';
});

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
