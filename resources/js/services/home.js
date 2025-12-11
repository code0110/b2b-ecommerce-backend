import api from './http';

export async function fetchHomepage() {
  const { data } = await api.get('/home');
  return data;
}
