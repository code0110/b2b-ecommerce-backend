<template>
  <div>
    <div class="dd-page-header py-3 mb-3">
      <div class="container">
        <h1 class="h4 mb-1">Reprezentanți vânzări</h1>
        <p class="text-muted small mb-0">
          Găsește persoana potrivită în funcție de regiune / județ.
        </p>
      </div>
    </div>

    <div class="container pb-4">
      <div class="card mb-3">
        <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label form-label-sm">Regiune</label>
            <select
              v-model="selectedRegion"
              class="form-select form-select-sm"
              @change="handleFilterChange"
            >
              <option value="">Toate regiunile</option>
              <option
                v-for="region in filters.regions"
                :key="region"
                :value="region"
              >
                {{ region }}
              </option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label form-label-sm">Județ</label>
            <select
              v-model="selectedCounty"
              class="form-select form-select-sm"
              @change="handleFilterChange"
            >
              <option value="">Toate județele</option>
              <option
                v-for="county in filters.counties"
                :key="county"
                :value="county"
              >
                {{ county }}
              </option>
            </select>
          </div>
          <div class="col-md-4 text-md-end mt-2 mt-md-0">
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm me-2"
              @click="resetFilters"
            >
              Resetează filtrele
            </button>
            <button
              type="button"
              class="btn btn-orange btn-sm"
              @click="loadReps"
            >
              Aplică
            </button>
          </div>
        </div>
    </div>
      </div>

      <div v-if="error" class="alert alert-danger py-2 mb-3">
        {{ error }}
      </div>

      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border spinner-border-sm" role="status" />
        <div class="small text-muted mt-2">Se încarcă reprezentanții...</div>
      </div>

      <div v-else>
        <div v-if="reps.length === 0" class="alert alert-info py-2">
          Nu există reprezentanți pentru filtrarea selectată.
        </div>

        <div class="row g-3">
          <div
            v-for="rep in reps"
            :key="rep.id"
            class="col-md-4 col-sm-6"
          >
            <div class="card h-100 shadow-sm dd-product-card">
              <div class="card-body d-flex flex-column">
                <h2 class="h6 mb-1 fw-bold">{{ rep.name }}</h2>
                <p class="small text-muted mb-2">
                  Regiune: {{ rep.region || 'Nespecificat' }}
                </p>

                <div class="small mb-2">
                  <div v-if="rep.counties && rep.counties.length" class="text-muted">
                    Județe: {{ rep.counties.join(', ') }}
                  </div>
                </div>

                <div class="mt-auto">
                  <div class="small mb-1">
                    <div v-if="rep.phone">
                      <strong>Tel:</strong>
                      <a :href="`tel:${rep.phone}`" class="text-decoration-none">{{ rep.phone }}</a>
                    </div>
                    <div v-if="rep.email">
                      <strong>Email:</strong>
                      <a :href="`mailto:${rep.email}`" class="text-decoration-none">{{ rep.email }}</a>
                    </div>
                  </div>
                  <a
                    v-if="rep.email"
                    :href="`mailto:${rep.email}?subject=Solicitare%20ofert%C4%83`"
                    class="btn btn-outline-secondary btn-sm"
                  >
                    Contactează
                  </a>
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
import { ref, onMounted } from 'vue';
import { fetchSalesRepresentatives } from '@/services/sales';

const reps = ref([]);
const filters = ref({
  regions: [],
  counties: [],
});

const selectedRegion = ref('');
const selectedCounty = ref('');

const loading = ref(false);
const error = ref('');

const loadReps = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchSalesRepresentatives({
      region: selectedRegion.value || undefined,
      county: selectedCounty.value || undefined,
    });

    reps.value = data.data ?? [];
    filters.value = data.filters ?? { regions: [], counties: [] };
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca reprezentanții.';
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  selectedRegion.value = '';
  selectedCounty.value = '';
  loadReps();
};

const handleFilterChange = () => {
  // dacă vrei autoload la schimbare, poți apela direct loadReps()
};

onMounted(loadReps);
</script>
