// resources/js/services/account.js
import api from '@/services/http';

// Dashboard client (comenzi în derulare, produse frecvente, credit, promoții etc.)
export async function fetchAccountDashboard() {
  const { data } = await api.get('/account/dashboard');
  return data;
}



// Detaliu comandă
export async function fetchAccountOrder(id) {
  const { data } = await api.get(`/account/orders/${id}`);
  return data;
}

// Adrese (facturare + livrare)
export async function fetchAccountAddresses() {
  const { data } = await api.get('/account/addresses');
  return data;
}

export async function createAccountAddress(payload) {
  const { data } = await api.post('/account/addresses', payload);
  return data;
}

export async function updateAccountAddress(id, payload) {
  const { data } = await api.put(`/account/addresses/${id}`, payload);
  return data;
}

export async function deleteAccountAddress(id) {
  return api.delete(`/account/addresses/${id}`);
}

export const fetchAccountOrders = (userId, params = {}) => {
  return api
    .get('/account/orders', {
      params: {
        ...params,
        user_id: userId,
      },
    })
    .then((r) => r.data);
};

export const fetchAccountOrderDetails = (userId, orderId) => {
  return api
    .get(`/account/orders/${orderId}`, {
      params: {
        user_id: userId,
      },
    })
    .then((r) => r.data);
};