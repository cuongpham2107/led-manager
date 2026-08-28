import AsyncStorage from '@react-native-async-storage/async-storage';
import { Platform } from 'react-native';

const STORAGE_KEY_API_URL = '@led_manager_api_url';

export const getDefaultApiHost = (): string => {
    if (Platform.OS === 'web' && typeof window !== 'undefined' && window.location?.origin) {
        return window.location.origin;
    }
    return Platform.OS === 'android' ? 'http://10.0.2.2:8000' : 'http://127.0.0.1:8000';
};

export const DEFAULT_API_HOST = getDefaultApiHost();

let cachedApiUrl: string = DEFAULT_API_HOST;

export const getApiBaseUrl = async (): Promise<string> => {
    try {
        const saved = await AsyncStorage.getItem(STORAGE_KEY_API_URL);
        if (saved) {
            cachedApiUrl = saved;
            return saved;
        }
    } catch {
        // Ignore error and return cached
    }
    cachedApiUrl = getDefaultApiHost();
    return cachedApiUrl;
};

export const setApiBaseUrl = async (url: string): Promise<void> => {
    const formatted = url.trim().replace(/\/+$/, '');
    cachedApiUrl = formatted;
    await AsyncStorage.setItem(STORAGE_KEY_API_URL, formatted);
};
