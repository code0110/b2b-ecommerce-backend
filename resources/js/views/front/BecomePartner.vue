<template>
  <div>
    <div class="dd-page-header py-3 mb-3">
      <div class="container">
        <h1 class="h4 mb-1">Devino partener B2B</h1>
        <p class="text-muted small mb-0">
          Completează formularul de mai jos și un reprezentant de vânzări te va
          contacta pentru detalii comerciale.
        </p>
      </div>
    </div>

    <div class="container pb-4">
      <div class="row g-3">
        <div class="col-lg-7">

        <div v-if="success" class="alert alert-success small">
          {{ success }}
        </div>
        <div v-if="error" class="alert alert-danger small">
          {{ error }}
        </div>

        <div class="card">
          <div class="card-body">
            <form @submit.prevent="submit" class="vstack gap-3">
              <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label small">Denumire firmă *</label>
              <input
                v-model="form.company_name"
                type="text"
                class="form-control form-control-sm"
                required
              />
            </div>
            <div class="col-md-4">
              <label class="form-label small">CIF</label>
              <input
                v-model="form.cif"
                type="text"
                class="form-control form-control-sm"
              />
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small">Nr. Reg. Com.</label>
              <input
                v-model="form.reg_com"
                type="text"
                class="form-control form-control-sm"
              />
            </div>
            <div class="col-md-8">
              <label class="form-label small">IBAN</label>
              <input
                v-model="form.iban"
                type="text"
                class="form-control form-control-sm"
              />
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small">Persoană de contact *</label>
              <input
                v-model="form.contact_name"
                type="text"
                class="form-control form-control-sm"
                required
              />
            </div>
            <div class="col-md-6">
              <label class="form-label small">Telefon</label>
              <input
                v-model="form.phone"
                type="text"
                class="form-control form-control-sm"
              />
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small">Email *</label>
              <input
                v-model="form.email"
                type="email"
                class="form-control form-control-sm"
                required
              />
            </div>
            <div class="col-md-6">
              <label class="form-label small">Regiune / județ</label>
              <input
                v-model="form.region"
                type="text"
                class="form-control form-control-sm"
              />
            </div>
          </div>

          <div>
            <label class="form-label small">Tip activitate</label>
            <input
              v-model="form.activity_type"
              type="text"
              class="form-control form-control-sm"
              placeholder="ex: distribuitor, magazin online, instalator"
            />
          </div>

          <div>
            <label class="form-label small">Detalii suplimentare</label>
            <textarea
              v-model="form.notes"
              rows="3"
              class="form-control form-control-sm"
            ></textarea>
          </div>

          <div class="d-flex align-items-center gap-2">
            <button
              type="submit"
              class="btn btn-orange btn-sm"
              :disabled="loading"
            >
              <span
                v-if="loading"
                class="spinner-border spinner-border-sm me-1"
                role="status"
              ></span>
              Trimite cererea
            </button>
            <span class="small text-muted">
              Vei primi un răspuns în cel mai scurt timp.
            </span>
          </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card">
          <div class="card-body small">
            <h2 class="h6 fw-bold">Beneficii pentru parteneri</h2>
            <ul class="mb-3">
              <li>Liste de preț dedicate și discounturi pe volum;</li>
              <li>Termene de plată și limită de credit;</li>
              <li>Acces la promoții și campanii exclusive B2B;</li>
              <li>Suport dedicat prin reprezentanți de vânzări.</li>
            </ul>
            <p class="mb-0 text-muted">
              Dacă ești deja client, te poți autentifica în cont pentru a vedea
              condițiile comerciale existente.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { submitPartnerRequest } from '@/services/content';

const loading = ref(false);
const error = ref('');
const success = ref('');

const form = reactive({
  company_name: '',
  cif: '',
  reg_com: '',
  iban: '',
  contact_name: '',
  email: '',
  phone: '',
  region: '',
  activity_type: '',
  notes: '',
});

const resetForm = () => {
  Object.keys(form).forEach((key) => {
    form[key] = '';
  });
};

const submit = async () => {
  loading.value = true;
  error.value = '';
  success.value = '';

  try {
    await submitPartnerRequest({ ...form });
    success.value =
      'Cererea ta a fost înregistrată. Îți vom răspunde în curând.';
    resetForm();
  } catch (e) {
    console.error(e);
    error.value =
      e?.response?.data?.message ??
      'A apărut o eroare la trimiterea cererii.';
  } finally {
    loading.value = false;
  }
};
</script>
