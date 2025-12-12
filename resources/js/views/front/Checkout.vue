<!-- resources/js/views/front/Checkout.vue -->
<template>
  <div class="container my-4">
    <h1 class="h4 mb-3">Checkout</h1>

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

    <div v-if="!loading && !error && cartItems.length > 0" class="row">
      <!-- Stânga: pașii de checkout -->
      <div class="col-lg-8">
        <!-- Pas 1: Adrese (simplificat: ID-uri din addresses) -->
        <div class="card mb-3">
          <div class="card-header">
            <strong>1. Adrese facturare și livrare</strong>
          </div>
          <div class="card-body">
            <p class="small text-muted">
              În backend, `CheckoutController@placeOrder` așteaptă
              identificatori de adresă existenți în tabela <code>addresses</code>
              (câmpurile <code>billing_address_id</code> și
              <code>shipping_address_id</code>).
              Până implementăm un API de adrese în frontend, poți folosi ID-uri
              existente din baza de date (ex: pentru testare).
            </p>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">ID adresă facturare</label>
                <input
                  type="number"
                  v-model.number="billingAddressId"
                  class="form-control"
                  min="1"
                  placeholder="ex: 1"
                />
              </div>
              <div class="col-md-6">
                <label class="form-label">ID adresă livrare</label>
                <input
                  type="number"
                  v-model.number="shippingAddressId"
                  class="form-control"
                  min="1"
                  placeholder="ex: 1"
                />
              </div>
            </div>

            <div class="small text-muted mt-2">
              După ce vom avea un endpoint pentru adrese în contul client,
              acest pas se poate transforma în dropdown-uri cu adresele
              salvate (livrare/facturare).
            </div>
          </div>
        </div>

        <!-- Pas 2: Metodă livrare -->
        <div class="card mb-3">
          <div class="card-header">
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

        <!-- Pas 3: Metodă plată -->
        <div class="card mb-3">
          <div class="card-header">
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

        <!-- Pas 4: Confirmare -->
        <div class="card mb-4">
          <div class="card-header">
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
              class="btn btn-primary"
              :disabled="submitting"
              @click="onPlaceOrder"
            >
              <span v-if="submitting">Se plasează comanda...</span>
              <span v-else>Plasează comanda</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Dreapta: sumar comandă -->
      <div class="col-lg-4">
        <div class="card mb-3">
          <div class="card-body">
            <h2 class="h6 mb-3">Sumar comandă</h2>

            <ul class="list-unstyled mb-3 small">
              <li
                v-for="item in cartItems"
                :key="item.id"
                class="d-flex justify-content-between"
              >
                <div>
                  <div class="fw-semibold">
                    {{ item.product?.name || 'Produs' }}
                  </div>
                  <div class="text-muted">
                    x {{ item.quantity }}
                  </div>
                </div>
                <div class="text-end">
                  {{ formatPrice(item.total) }} RON
                </div>
              </li>
            </ul>

            <hr />

            <dl class="row mb-0 small">
              <dt class="col-6">Subtotal produse</dt>
              <dd class="col-6 text-end">
                {{ formatPrice(subtotal) }} RON
              </dd>
              <dt class="col-6">Transport</dt>
              <dd class="col-6 text-end text-muted">
                Calculat pe baza regulilor definite la metoda selectată
                (de adăugat în backend ulterior)
              </dd>
            </dl>

            <hr />

            <div class="d-flex justify-content-between align-items-center">
              <div class="fw-semibold">Total estimativ</div>
              <div class="fw-bold h5 mb-0">
                {{ formatPrice(subtotal) }} RON
              </div>
            </div>
          </div>
        </div>

        <div class="small text-muted">
          Ruta de API pentru sumar: <code>GET /api/checkout/summary</code><br />
          Ruta pentru plasare comandă:
          <code>POST /api/checkout/place-order</code>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { getCheckoutSummary, placeOrder } from '@/services/cart';

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

const formatPrice = (value) => {
  const num = Number(value) || 0;
  return num.toLocaleString('ro-RO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const cartItems = computed(() => summary.value?.cart?.items ?? []);
const subtotal = computed(() => Number(summary.value?.subtotal) || 0);
const shippingMethods = computed(() => summary.value?.shipping_methods ?? []);

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
      error.value =
        'Trebuie să fii autentificat pentru a accesa checkout-ul. Te rugăm să te loghezi.';
    } else {
      error.value =
        e.response?.data?.message ||
        'Nu s-a putut încărca sumarul comenzii.';
    }
  } finally {
    loading.value = false;
  }
};

const onPlaceOrder = async () => {
  if (!billingAddressId.value || !shippingAddressId.value) {
    error.value =
      'Te rugăm să completezi atât ID-ul adresei de facturare, cât și al adresei de livrare.';
    return;
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
      billing_address_id: Number(billingAddressId.value),
      shipping_address_id: Number(shippingAddressId.value),
      payment_method: paymentMethod.value,
    };

    const order = await placeOrder(payload);

    successMessage.value = `Comanda #${order.id} a fost plasată cu succes.`;
    // opțional: poți goli local coșul din UI
    // sau lăsa backend-ul să îl marcheze ca "converted"
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
