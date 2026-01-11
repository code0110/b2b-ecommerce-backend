import { adminApi } from '@/services/http';

export const fetchSettings = () => {
  return adminApi.get('/settings');
};

export const updateSettings = (settings) => {
  return adminApi.post('/settings', { settings });
};

export const uploadFile = (formData) => {
  return adminApi.post('/upload', formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  });
};

export const fetchPublicConfig = () => {
    return adminApi.get('/config/public');
}
