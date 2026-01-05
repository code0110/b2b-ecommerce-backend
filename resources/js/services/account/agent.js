import http, { adminApi } from '@/services/http';

export default {
  getClients(params) {
    return http.get('/account/agent/clients', { params });
  },

  getAgents() {
    return http.get('/account/agent/agents');
  },

  getClientInvoices(clientId) {
    return http.get(`/account/agent/clients/${clientId}/invoices`);
  },

  getActiveReceiptBook() {
    return http.get('/account/agent/receipt-book/active');
  },

  storePayment(data) {
    return http.post('/account/agent/payments', data);
  },

  cancelReceipt(data) {
    return http.post('/account/agent/payments/cancel-receipt', data);
  },

  getRoutes(params) {
    return http.get('/account/agent/routes', { params });
  },
  
  getVisits(params) {
    // Reusing the admin API endpoint as it's accessible to agents and handles filtering
    return adminApi.get('/customer-visits', { params });
  }
};
