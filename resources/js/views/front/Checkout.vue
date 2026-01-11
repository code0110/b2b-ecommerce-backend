<template>
  <div>
    <div class="dd-page-header py-3 mb-3">
      <div class="container">
        <h1 class="h4 mb-1">Checkout</h1>
        <p class="text-muted small mb-0">
          Completează datele și confirmă comanda.
        </p>
      </div>
    </div>

    <div class="container pb-4">

      <div v-if="loading" class="alert alert-info">
        Se încarcă sumarul comenzii...
      </div>

      <div v-if="error" class="alert alert-danger">
        {{ error }}
      </div>

      <div
        v-if="!loading && !error && successMessage"
        class="alert alert-success"
      >
        {{ successMessage }}
      </div>

      <div
        v-if="!loading && !error && !successMessage && !cartItems.length"
        class="alert alert-secondary"
      >
        Coșul este gol. Te rugăm să adaugi produse înainte de a finaliza comanda.
        <RouterLink to="/cos" class="alert-link ms-1">
          Mergi la coș
        </RouterLink>
      </div>

      <div v-if="!loading && !error && cartItems.length > 0" class="row g-3">
        <div class="col-lg-8">
          <div class="card mb-3">
            <div class="card-header py-2">
              <strong>1. Adrese facturare și livrare</strong>
            </div>
            <div class="card-body">
             <!-- Adresă Facturare -->
             <h6 class="mb-3 border-bottom pb-2">Adresă de facturare</h6>
             
             <div v-if="user && availableAddresses.length" class="mb-3">
               <div class="form-check form-check-inline">
                 <input class="form-check-input" type="radio" id="bill-existing" value="existing" v-model="billingMode">
                 <label class="form-check-label" for="bill-existing">Alege existentă</label>
               </div>
               <div class="form-check form-check-inline">
                 <input class="form-check-input" type="radio" id="bill-new" value="new" v-model="billingMode">
                 <label class="form-check-label" for="bill-new">Adaugă nouă</label>
               </div>
             </div>

             <div v-if="billingMode === 'existing' && availableAddresses.length" class="mb-3">
               <select v-model="billingAddressId" class="form-select">
                 <option :value="null">Selectează o adresă...</option>
                 <option v-for="addr in availableAddresses" :key="addr.id" :value="addr.id">
                   {{ addr.contact_name }} ({{ addr.city }}, {{ addr.street }})
                 </option>
               </select>
             </div>

             <div v-if="billingMode === 'new'" class="row g-2 mb-4">
                <div class="col-md-6">
                  <label class="form-label small">Nume Contact / Companie *</label>
                  <input type="text" class="form-control form-control-sm" v-model="newBillingAddress.contact_name">
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Telefon</label>
                  <input type="text" class="form-control form-control-sm" v-model="newBillingAddress.phone">
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Țară</label>
                  <input type="text" class="form-control form-control-sm" v-model="newBillingAddress.country">
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Județ</label>
                  <input type="text" class="form-control form-control-sm" v-model="newBillingAddress.county">
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Oraș *</label>
                  <input type="text" class="form-control form-control-sm" v-model="newBillingAddress.city">
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Cod Poștal</label>
                  <input type="text" class="form-control form-control-sm" v-model="newBillingAddress.postal_code">
                </div>
                <div class="col-12">
                  <label class="form-label small">Stradă și număr *</label>
                  <input type="text" class="form-control form-control-sm" v-model="newBillingAddress.street">
                </div>
             </div>

             <!-- Adresă Livrare -->
             <h6 class="mb-3 border-bottom pb-2 mt-4">Adresă de livrare</h6>
             
             <div v-if="user && availableAddresses.length" class="mb-3">
               <div class="form-check form-check-inline">
                 <input class="form-check-input" type="radio" id="ship-existing" value="existing" v-model="shippingMode">
                 <label class="form-check-label" for="ship-existing">Alege existentă</label>
               </div>
               <div class="form-check form-check-inline">
                 <input class="form-check-input" type="radio" id="ship-new" value="new" v-model="shippingMode">
                 <label class="form-check-label" for="ship-new">Adaugă nouă</label>
               </div>
             </div>

             <div v-if="shippingMode === 'existing' && availableAddresses.length" class="mb-3">
               <select v-model="shippingAddressId" class="form-select">
                 <option :value="null">Selectează o adresă...</option>
                 <option v-for="addr in availableAddresses" :key="addr.id" :value="addr.id">
                   {{ addr.contact_name }} ({{ addr.city }}, {{ addr.street }})
                 </option>
               </select>
             </div>

             <div v-if="shippingMode === 'new'" class="row g-2">
                <div class="col-md-6">
                  <label class="form-label small">Nume Destinatar *</label>
                  <input type="text" class="form-control form-control-sm" v-model="newShippingAddress.contact_name">
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Telefon</label>
                  <input type="text" class="form-control form-control-sm" v-model="newShippingAddress.phone">
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Țară</label>
                  <input type="text" class="form-control form-control-sm" v-model="newShippingAddress.country">
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Județ</label>
                  <input type="text" class="form-control form-control-sm" v-model="newShippingAddress.county">
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Oraș *</label>
                  <input type="text" class="form-control form-control-sm" v-model="newShippingAddress.city">
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Cod Poștal</label>
                  <input type="text" class="form-control form-control-sm" v-model="newShippingAddress.postal_code">
                </div>
                <div class="col-12">
                  <label class="form-label small">Stradă și număr *</label>
                  <input type="text" class="form-control form-control-sm" v-model="newShippingAddress.street">
                </div>
             </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header py-2">
            <strong>2. Metodă de livrare</strong>
          </div>
          <div class="card-body">
            <div v-if="!shippingMethods.length" class="text-muted small">
              Nu există încă metode de livrare active în sistem.
              Poți defini metode și reguli în zona de Admin &gt; Livrare.
            </div>

            <div
              v-for="method in shippingMethods"
              :key="method.id"
              class="form-check mb-2"
            >
              <input
                class="form-check-input"
                type="radio"
                :id="`ship-${method.id}`"
                :value="method.id"
                v-model="selectedShippingMethodId"
              />
              <label
                class="form-check-label"
                :for="`ship-${method.id}`"
              >
                <strong>{{ method.name }}</strong>
                <span class="text-muted small ms-1">
                  [{{ method.code }}] – tip: {{ method.type }}
                </span>
              </label>
            </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header py-2">
            <strong>3. Metodă de plată</strong>
          </div>
          <div class="card-body">
            <div class="form-check mb-2">
              <input
                class="form-check-input"
                type="radio"
                id="pay-card"
                value="card"
                v-model="paymentMethod"
              />
              <label class="form-check-label" for="pay-card">
                Card online
                <span class="small text-muted ms-1">
                  (redirectare către procesatorul de plăți)
                </span>
              </label>
            </div>
            <div class="form-check mb-2">
              <input
                class="form-check-input"
                type="radio"
                id="pay-op"
                value="op"
                v-model="paymentMethod"
              />
              <label class="form-check-label" for="pay-op">
                Ordin de plată (OP)
                <span class="small text-muted ms-1">
                  (se generează proformă cu datele firmei)
                </span>
              </label>
            </div>
            <div class="form-check mb-2">
              <input
                class="form-check-input"
                type="radio"
                id="pay-b2b"
                value="b2b_terms"
                v-model="paymentMethod"
              />
              <label class="form-check-label" for="pay-b2b">
                Condiții comerciale B2B
                <span class="small text-muted ms-1">
                  (termen de plată / limită de credit conform contractului)
                </span>
              </label>
            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header py-2">
            <strong>4. Confirmare comandă</strong>
          </div>
          <div class="card-body">
            <p class="small text-muted">
              Revizuiește sumarul comenzii din partea dreaptă și confirmă
              plasarea comenzii. După succes, comanda va fi vizibilă în
              „Cont &gt; Comenzi”.
            </p>

            <button
              type="button"
              class="btn btn-orange"
              :disabled="submitting"
              @click="onPlaceOrder"
            >
              <span v-if="submitting">Se plasează comanda...</span>
              <span v-else>Plasează comanda</span>
            </button>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card mb-3">
          <div class="card-body">
            <h2 class="h6 mb-3">Sumar comandă</h2>

            <ul class="list-unstyled mb-3 small">
              <li
                v-for="item in cartItems"
                :key="item.id"
                class="mb-3"
              >
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="fw-semibold">
                      {{ item.product_name || 'Produs' }}
                    </div>
                    <div class="text-muted" style="font-size: 0.85em;">
                      Cod: {{ item.product_variant?.sku || item.product?.internal_code || item.product?.sku || '-' }}
                    </div>
                    <div class="text-muted" style="font-size: 0.85em;" v-if="item.product_variant">
                      {{ item.product_variant.name || item.product_variant.sku }}
                    </div>
                    <div class="text-muted">
                      x {{ item.quantity }}
                    </div>
                    <!-- Afisare promotii aplicate -->
                    <div v-if="item.applied_promotions && item.applied_promotions.length" class="text-danger mt-1">
                <div v-for="promo in item.applied_promotions" :key="promo.id">
                  <i class="bi bi-tag-fill me-1"></i> {{ promo.name }} (-{{ promo.discount_amount }} RON)
                </div>
              </div>
                  </div>
                  <div class="text-end">
                    <div v-if="item.line_discount > 0" class="text-decoration-line-through text-muted" style="font-size: 0.9em;">
                      {{ formatPriceGlobal(item.line_base_total, item.product_vat_rate, item.product_vat_included) }}
                    </div>
                    <div :class="{'text-danger fw-bold': item.line_discount > 0}">
<<<<<<< HEAD
                      {{ formatPriceGlobal(item.line_final_total, item.product_vat_rate, item.product_vat_included) }}
=======
                      {{ formatPrice(item.line_final_total) }} RON
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
                    </div>
                  </div>
                </div>
              </li>
            </ul>

            <hr />

            <dl class="row mb-0 small">
              <dt class="col-6">Subtotal produse</dt>
              <dd class="col-6 text-end">
                {{ formatPriceGlobal(subtotal, 19, true) }}
              </dd>
              
              <template v-if="discountTotal > 0">
                <dt class="col-6 text-danger">Reduceri</dt>
                <dd class="col-6 text-end text-danger">
<<<<<<< HEAD
                  -{{ formatPriceGlobal(discountTotal, 19, true) }}
=======
                  -{{ formatPrice(discountTotal) }} RON
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
                </dd>
              </template>

              <dt class="col-6">Transport</dt>
              <dd class="col-6 text-end text-muted">
                {{ shippingTotal > 0 ? formatPriceGlobal(shippingTotal, 19, true) : 'Calculat ulterior' }}
              </dd>
            </dl>

            <hr />

            <div class="d-flex justify-content-between align-items-center">
              <div class="fw-semibold">Total de plată</div>
              <div class="fw-bold h5 mb-0">
                {{ formatPriceGlobal(grandTotal, 19, true) }}
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
import { ref, computed, onMounted, reactive, watch } from 'vue';
import { useRouter } from 'vue-router';
import { getCheckoutSummary, placeOrder } from '@/services/cart';
import { useVisitStore } from '@/store/visit';
import { usePrice } from '@/composables/usePrice';

const router = useRouter();
const visitStore = useVisitStore();
const { formatPrice: formatPriceGlobal } = usePrice();
const loading = ref(false);
const submitting = ref(false);
const error = ref('');
const successMessage = ref('');
const summary = ref(null);

// câmpuri de formular
const billingAddressId = ref(null);
const shippingAddressId = ref(null);
const selectedShippingMethodId = ref(null);
const paymentMethod = ref('card');

const billingMode = ref('new');
const shippingMode = ref('new');

const newBillingAddress = reactive({
  contact_name: '',
  phone: '',
  country: 'Romania',
  county: '',
  city: '',
  street: '',
  postal_code: '',
});

const newShippingAddress = reactive({
  contact_name: '',
  phone: '',
  country: 'Romania',
  county: '',
  city: '',
  street: '',
  postal_code: '',
});

const cartItems = computed(() => summary.value?.items ?? []);
const subtotal = computed(() => Number(summary.value?.subtotal) || 0);
const discountTotal = computed(() => Number(summary.value?.discount_total) || 0);
const shippingTotal = computed(() => Number(summary.value?.shipping_total) || 0);
const grandTotal = computed(() => Number(summary.value?.grand_total) || 0);
const shippingMethods = computed(() => summary.value?.shipping_methods ?? []);
const user = computed(() => summary.value?.user);
const availableAddresses = computed(() => summary.value?.addresses ?? []);

const loadSummary = async () => {
  loading.value = true;
  error.value = '';
  successMessage.value = '';

  try {
    const data = await getCheckoutSummary();
    summary.value = data;

    if (!selectedShippingMethodId.value && shippingMethods.value.length) {
      selectedShippingMethodId.value = shippingMethods.value[0].id;
    }
  } catch (e) {
    console.error('Checkout summary error', e);
    if (e.response?.status === 401) {
      // Allow guest checkout now, but maybe show login prompt?
      // Since we removed middleware, 401 shouldn't happen for summary unless token is invalid.
      // If no token, it just returns guest cart.
      // But if token is invalid, it might return 401.
      error.value = 'Sesiunea a expirat. Te rugăm să te loghezi din nou.';
    } else if (e.response?.status === 400 && e.response.data?.message === 'Coșul este gol.') {
      summary.value = { items: [] };
    } else {
      error.value =
        e.response?.data?.message ||
        'Nu s-a putut încărca sumarul comenzii.';
    }
  } finally {
    loading.value = false;
  }
};

watch(availableAddresses, (newVal) => {
  if (newVal && newVal.length > 0) {
    billingMode.value = 'existing';
    shippingMode.value = 'existing';
    if (!billingAddressId.value) billingAddressId.value = newVal[0].id;
    if (!shippingAddressId.value) shippingAddressId.value = newVal[0].id;
  } else {
    billingMode.value = 'new';
    shippingMode.value = 'new';
  }
});

const onPlaceOrder = async () => {
  if (billingMode.value === 'existing' && !billingAddressId.value) {
    error.value = 'Te rugăm să selectezi o adresă de facturare.';
    return;
  }
  if (shippingMode.value === 'existing' && !shippingAddressId.value) {
    error.value = 'Te rugăm să selectezi o adresă de livrare.';
    return;
  }

  if (billingMode.value === 'new') {
    if (!newBillingAddress.contact_name || !newBillingAddress.city || !newBillingAddress.street) {
       error.value = 'Te rugăm să completezi câmpurile obligatorii pentru facturare (Nume, Oraș, Stradă).';
       return;
    }
  }
  if (shippingMode.value === 'new') {
    if (!newShippingAddress.contact_name || !newShippingAddress.city || !newShippingAddress.street) {
       error.value = 'Te rugăm să completezi câmpurile obligatorii pentru livrare (Nume, Oraș, Stradă).';
       return;
    }
  }

  if (!selectedShippingMethodId.value) {
    error.value = 'Te rugăm să selectezi o metodă de livrare.';
    return;
  }

  submitting.value = true;
  error.value = '';
  successMessage.value = '';

  try {
    const payload = {
      shipping_method_id: Number(selectedShippingMethodId.value),
      payment_method: paymentMethod.value,
    };

    if (billingMode.value === 'existing') {
        payload.billing_address_id = Number(billingAddressId.value);
    } else {
        payload.billing_address = { ...newBillingAddress };
    }

    if (shippingMode.value === 'existing') {
        payload.shipping_address_id = Number(shippingAddressId.value);
    } else {
        payload.shipping_address = { ...newShippingAddress };
    }

    if (visitStore.activeVisit) {
        payload.customer_visit_id = visitStore.activeVisit.id;
    }

    const order = await placeOrder(payload);

    successMessage.value = `Comanda #${order.id} a fost plasată cu succes.`;
    // Ascundem formularul și coșul
    summary.value = null;
  } catch (e) {
    console.error('Place order error', e);

    if (e.response?.status === 422 && e.response.data?.errors) {
      const errors = e.response.data.errors;
      const flat = Object.values(errors).flat();
      error.value = flat.join(' ');
    } else if (e.response?.status === 401) {
      error.value =
        'Trebuie să fii autentificat pentru a plasa comanda. Te rugăm să te loghezi.';
    } else {
      error.value =
        e.response?.data?.message ||
        'A apărut o eroare la plasarea comenzii.';
    }
  } finally {
    submitting.value = false;
  }
};

onMounted(loadSummary);
</script>
