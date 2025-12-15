// resources/js/services/account/companyUsers.js
import  api  from '../http';

export const fetchCompanyUsers = async () => {
  const { data } = await api.get('/account/company-users');
  return data;
};

export const inviteCompanyUser = async (payload) => {
  const { data } = await api.post('/account/company-users', payload);
  return data;
};

export const updateCompanyUser = async (id, payload) => {
  const { data } = await api.put(`/account/company-users/${id}`, payload);
  return data;
};

export const deleteCompanyUser = async (id) => {
  const { data } = await api.delete(`/account/company-users/${id}`);
  return data;
};
