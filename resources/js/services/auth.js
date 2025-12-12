import api from './http';

// LOGIN
export async function apiLogin(email, password, remember = false) {
  const { data } = await api.post('/auth/login', {
    email,
    password,
    remember,
  });

  return data; // { token, user }
}

// LOGOUT
export async function apiLogout() {
  await api.post('/auth/logout');
}

// ME
export async function apiFetchMe() {
  const { data } = await api.get('/auth/me');
  return data;
}

// REGISTER B2C
export async function apiRegisterB2C(payload) {
  const { data } = await api.post('/auth/register-b2c', payload);
  return data;
}

// REGISTER B2B
export async function apiRegisterB2B(payload) {
  const { data } = await api.post('/auth/register-b2b', payload);
  return data;
}
