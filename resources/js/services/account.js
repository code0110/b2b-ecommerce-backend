// resources/js/services/account.js
import api from '@/services/http';

// Dashboard client (comenzi în derulare, produse frecvente, credit, promoții etc.)
export async function fetchAccountDashboard() {
  const { data } = await api.get('/account/dashboard');
  return data;
}

// Listă comenzi client
export async function fetchAccountOrders(params = {}) {
  const { data } = await api.get('/account/orders', { params });

  // suport pentru răspuns simplu sau paginat
  if (Array.isArray(data)) {
    return {
      items: data,
      meta: {
        current_page: 1,
        last_page: 1,
        total: data.length,
      },
    };
  }

  if (Array.isArray(data.data)) {
    return {
      items: data.data,
      meta: data.meta || {
        current_page: 1,
        last_page: 1,
        total: data.data.length,
      },
    };
  }

  if (Array.isArray(data.orders)) {
    return {
      items: data.orders,
      meta: data.meta || {
        current_page: 1,
        last_page: 1,
        total: data.orders.length,
      },
    };
  }

  return {
    items: [],
    meta: {
      current_page: 1,
      last_page: 1,
      total: 0,
    },
  };
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
