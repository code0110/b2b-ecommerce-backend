import  api  from './http';

// Pagini statice
export const fetchStaticPage = (slug) =>
  api.get(`/pages/${slug}`).then((r) => r.data);

// Blog
export const fetchBlogList = (params = {}) =>
  api.get('/blog', { params }).then((r) => r.data);

export const fetchBlogPost = (slug) =>
  api.get(`/blog/${slug}`).then((r) => r.data);

// Devino partener
export const submitPartnerRequest = (payload) =>
  api.post('/partner-requests', payload).then((r) => r.data);
