// resources/js/services/account/dashboard.js
import  api  from '../http';

export const fetchAccountDashboard = async () => {
  const { data } = await api.get('/account/dashboard');
  return data;
};
