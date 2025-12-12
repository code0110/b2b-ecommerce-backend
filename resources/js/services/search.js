import api  from './http';

export const searchCatalog = (params = {}) =>
  api.get('/search', { params }).then((r) => r.data);
