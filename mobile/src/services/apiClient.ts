import AsyncStorage from '@react-native-async-storage/async-storage';
import axios from 'axios';
import { getApiBaseUrl } from '../config/api';

const STORAGE_KEY_TOKEN = '@led_manager_auth_token';

export const apiClient = axios.create({
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

apiClient.interceptors.request.use(async (config) => {
  const baseURL = await getApiBaseUrl();
  config.baseURL = `${baseURL}/api/v1`;

  const token = await AsyncStorage.getItem(STORAGE_KEY_TOKEN);
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      await AsyncStorage.removeItem(STORAGE_KEY_TOKEN);
    }
    return Promise.reject(error);
  }
);

export const saveAuthToken = async (token: string): Promise<void> => {
  await AsyncStorage.setItem(STORAGE_KEY_TOKEN, token);
};

export const clearAuthToken = async (): Promise<void> => {
  await AsyncStorage.removeItem(STORAGE_KEY_TOKEN);
};

export const getStoredAuthToken = async (): Promise<string | null> => {
  return await AsyncStorage.getItem(STORAGE_KEY_TOKEN);
};
