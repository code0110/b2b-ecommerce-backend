// resources/js/services/salesReps.js
import  api  from './http';

export const fetchSalesRepresentatives = (params = {}) =>
  api.get('/sales-representatives', { params }).then((r) => r.data);
