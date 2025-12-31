import http from '@/services/http';

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
  }
};
