import  api  from '../http';

export const fetchTickets = (params = {}) =>
  api.get('/account/tickets', { params }).then((r) => r.data);

export const fetchTicket = (id) =>
  api.get(`/account/tickets/${id}`).then((r) => r.data);

export const createTicket = (payload) =>
  api.post('/account/tickets', payload).then((r) => r.data);

export const addTicketMessage = (id, payload) =>
  api.post(`/account/tickets/${id}/messages`, payload).then((r) => r.data);
