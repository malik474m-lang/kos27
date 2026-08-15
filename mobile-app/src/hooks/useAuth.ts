import { useState, useEffect, useCallback, createContext, useContext } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as endpoints from '../api/endpoints';

const AUTH_KEY = 'kosmozaim_auth';

interface AuthState {
  isLoggedIn: boolean;
  userName: string;
  email: string;
}

interface AuthContextType extends AuthState {
  login: (email: string, password: string) => Promise<void>;
  register: (email: string, password: string, name: string) => Promise<string>;
  verify: (email: string, code: string) => Promise<void>;
  logout: () => Promise<void>;
  loading: boolean;
}

const initialState: AuthState = {
  isLoggedIn: false,
  userName: '',
  email: '',
};

export function useAuthProvider(): AuthContextType {
  const [state, setState] = useState<AuthState>(initialState);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadAuth();
  }, []);

  const loadAuth = async () => {
    try {
      const raw = await AsyncStorage.getItem(AUTH_KEY);
      if (raw) {
        setState(JSON.parse(raw));
      }
    } catch (e) {
      console.warn('Failed to load auth state', e);
    }
  };

  const saveAuth = async (authState: AuthState) => {
    setState(authState);
    await AsyncStorage.setItem(AUTH_KEY, JSON.stringify(authState));
  };

  const login = useCallback(async (email: string, password: string) => {
    setLoading(true);
    try {
      const result = await endpoints.loginUser(email, password);
      await saveAuth({
        isLoggedIn: true,
        userName: result.name,
        email,
      });
    } finally {
      setLoading(false);
    }
  }, []);

  const register = useCallback(async (email: string, password: string, name: string) => {
    setLoading(true);
    try {
      const result = await endpoints.registerUser(email, password, name);
      return result.message;
    } finally {
      setLoading(false);
    }
  }, []);

  const verify = useCallback(async (email: string, code: string) => {
    setLoading(true);
    try {
      await endpoints.verifyUser(email, code);
      await saveAuth({
        isLoggedIn: true,
        userName: email.split('@')[0],
        email,
      });
    } finally {
      setLoading(false);
    }
  }, []);

  const logout = useCallback(async () => {
    setLoading(true);
    try {
      await endpoints.logoutUser().catch(() => {});
      await saveAuth(initialState);
    } finally {
      setLoading(false);
    }
  }, []);

  return {
    ...state,
    login,
    register,
    verify,
    logout,
    loading,
  };
}

// Context для глобального доступа
export const AuthContext = createContext<AuthContextType | null>(null);

export function useAuth(): AuthContextType {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
