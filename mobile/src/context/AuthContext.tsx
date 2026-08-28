import React, { createContext, useContext, useEffect, useState } from 'react';
import { apiClient, clearAuthToken, getStoredAuthToken, saveAuthToken } from '../services/apiClient';
import { User, Warehouse } from '../types';

interface AuthContextType {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  warehouses: Warehouse[];
  selectedWarehouseId: number | null;
  setSelectedWarehouseId: (id: number | null) => void;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshProfile: () => Promise<void>;
  loadWarehouses: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [warehouses, setWarehouses] = useState<Warehouse[]>([]);
  const [selectedWarehouseId, setSelectedWarehouseId] = useState<number | null>(null);

  const loadWarehouses = async () => {
    try {
      const response = await apiClient.get('/warehouses');
      if (response.data?.success) {
        setWarehouses(response.data.data);
      }
    } catch {
      // Ignore warehouse fetch error if offline or unauthorized
    }
  };

  const refreshProfile = async () => {
    try {
      const response = await apiClient.get('/auth/me');
      if (response.data?.success) {
        const userData: User = response.data.data;
        setUser(userData);
        if (userData.warehouse?.id) {
          setSelectedWarehouseId(userData.warehouse.id);
        }
      }
    } catch {
      await logout();
    }
  };

  useEffect(() => {
    const initAuth = async () => {
      try {
        const storedToken = await getStoredAuthToken();
        if (storedToken) {
          setToken(storedToken);
          const response = await apiClient.get('/auth/me');
          if (response.data?.success) {
            const userData: User = response.data.data;
            setUser(userData);
            if (userData.warehouse?.id) {
              setSelectedWarehouseId(userData.warehouse.id);
            }
            await loadWarehouses();
          }
        }
      } catch {
        await clearAuthToken();
        setToken(null);
        setUser(null);
      } finally {
        setIsLoading(false);
      }
    };

    initAuth();
  }, []);

  const login = async (email: string, password: string) => {
    const response = await apiClient.post('/auth/login', {
      email,
      password,
      device_name: 'LED-Mobile-App',
    });

    if (response.data?.success) {
      const { token: receivedToken, user: receivedUser } = response.data.data;
      await saveAuthToken(receivedToken);
      setToken(receivedToken);
      setUser(receivedUser);
      if (receivedUser.warehouse?.id) {
        setSelectedWarehouseId(receivedUser.warehouse.id);
      }
      await loadWarehouses();
    }
  };

  const logout = async () => {
    try {
      if (token) {
        await apiClient.post('/auth/logout');
      }
    } catch {
      // Ignore error
    } finally {
      await clearAuthToken();
      setToken(null);
      setUser(null);
    }
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isLoading,
        warehouses,
        selectedWarehouseId,
        setSelectedWarehouseId,
        login,
        logout,
        refreshProfile,
        loadWarehouses,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
