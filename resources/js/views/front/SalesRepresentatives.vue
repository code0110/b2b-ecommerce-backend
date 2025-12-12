<template>
  <div class="py-3">
    <div class="container">
      <h1 class="h4 mb-2">Reprezentanți vânzări</h1>
      <p class="text-muted small mb-3">
        Găsiți reprezentantul potrivit pentru regiunea sau județul dumneavoastră.
      </p>

      <!-- Filtre -->
      <div class="row mb-3">
        <div class="col-md-4 mb-2">
          <label class="small text-muted mb-1">Regiune</label>
          <select
            v-model="filters.region"
            class="form-select form-select-sm"
            @change="reload"
          >
            <option value="">Toate regiunile</option>
            <option
              v-for="region in regions"
              :key="region"
              :value="region"
            >
              {{ region }}
            </option>
          </select>
        </div>
        <div class="col-md-4 mb-2">
          <label class="small text-muted mb-1">Județ (căutare text)</label>
          <input
            v-model="filters.county"
            type="text"
            class="form-control form-control-sm"
            placeholder="ex. Cluj, Ilfov..."
            @keyup.enter="reload"
          />
        </div>
        <div class="col-md-4 mb-2 d-flex align-items-end">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm me-2"
            @click="reload"
          >
            Aplică filtre
          </button>
          <button
            v-if="filters.region || filters.county"
            type="button"
            class="btn btn-link btn-sm text-decoration-none"
            @click="resetFilters"
          >
            Resetează
          </button>
        </div>
      </div>

      <!-- Conținut -->
      <div v-if="loading" class="text-muted small py-3">
        Se încarcă reprezentanții...
      </div>
      <div v-else-if="error" class="alert alert-danger small py-2">
        {{ error }}
      </div>

      <div v-else class="row g-3">
        <div
          v-for="rep in reps"
          :key="rep.id"
          class="col-md-4 col-sm-6"
        >
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h2 class="h6 mb-0">{{ rep.name }}</h2>
                <span class="badge bg-light text-muted">
                  {{ rep.region || 'Nespecificat' }}
                </span>
              </div>

              <p class="small text-muted mb-2">
                <strong>Județe:</strong>
                {{ rep.counties || 'Nespecificat' }}
              </p>

              <div class="mt-auto small">
                <div v-if="rep.phone" class="mb-1">
                  Telefon:
                  <a
                    :href="`tel:${rep.phone}`"
                    class="text-decoration-none"
                  >
                    {{ rep.phone }}
                  </a>
                </div>
                <div v-if="rep.email">
                  E-mail:
                  <a
                    :href="`mailto:${rep.email}`"
                    class="text-decoration-none"
                  >
                    {{ rep.email }}
                  </a>
                </div>
              </div>

              <button
                v-if="rep.email"
                type="button"
                class="btn btn-outline-primary btn-sm mt-3"
                @click="contact(rep)"
              >
                Contactează
              </button>
            </div>
          </div>
        </div>

        <div v-if="!reps.length" class="col-12">
          <div class="alert alert-light border small mb-0">
            Nu am găsit reprezentanți pentru filtrele selectate.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { fetchSalesRepresentatives } from '@/services/salesReps';

const loading = ref(false);
const error = ref('');

const regions = ref([]);
const reps = ref([]);

const filters = reactive({
  region: '',
  county: '',
});

const load = async () => {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchSalesRepresentatives({
      region: filters.region || undefined,
      county: filters.county || undefined,
    });

    regions.value = data.filters?.regions || [];
    reps.value = data.representatives || [];
  } catch (e) {
    console.error(e);
    error.value = 'Nu s-au putut încărca reprezentanții de vânzări.';
  } finally {
    loading.value = false;
  }
};

const reload = () => {
  load();
};

const resetFilters = () => {
  filters.region = '';
  filters.county = '';
  load();
};

const contact = (rep) => {
  if (rep.email) {
    window.location.href = `mailto:${rep.email}`;
  }
};

onMounted(load);
</script>
