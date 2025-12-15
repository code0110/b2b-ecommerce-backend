// resources/js/services/account/documents.js
import  api  from '../http';

export const fetchInvoices = async (params = {}) => {
  const { data } = await api.get('/account/invoices', { params });
  return data;
};

export const fetchInvoice = async (id) => {
  const { data } = await api.get(`/account/invoices/${id}`);
  return data;
};
