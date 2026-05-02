import { createSlice, PayloadAction } from '@reduxjs/toolkit';
import type { User } from '../../features/auth';
import { tokenStorage } from '../utils/tokenStorage';

export type { User };

export interface AuthState {
  user: User | null;
  accessToken: string | null;
  refreshToken: string | null;
  rememberMe: boolean;
  isLoading: boolean;
  error: string | null;
  isInitialized: boolean;
}

const initialState: AuthState = {
  user: null,
  accessToken: tokenStorage.getAccessToken(),
  refreshToken: tokenStorage.getRefreshToken(),
  rememberMe: tokenStorage.getRememberMe(),
  isLoading: false,
  error: null,
  isInitialized: false,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setUser: (state, action: PayloadAction<User>) => {
      state.user = action.payload;
    },
    setTokens: (
      state,
      action: PayloadAction<{ accessToken: string; refreshToken: string; rememberMe: boolean }>,
    ) => {
      const { accessToken, refreshToken, rememberMe } = action.payload;
      state.accessToken = accessToken;
      state.refreshToken = refreshToken;
      state.rememberMe = rememberMe;
      tokenStorage.saveTokens(accessToken, refreshToken, rememberMe);
    },
    setAccessToken: (state, action: PayloadAction<string>) => {
      state.accessToken = action.payload;
      if (state.refreshToken) {
        tokenStorage.saveTokens(action.payload, state.refreshToken, state.rememberMe);
      }
    },
    setIsLoading: (state, action: PayloadAction<boolean>) => {
      state.isLoading = action.payload;
    },
    setError: (state, action: PayloadAction<string | null>) => {
      state.error = action.payload;
    },
    logout: (state) => {
      state.user = null;
      state.accessToken = null;
      state.refreshToken = null;
      state.rememberMe = false;
      state.error = null;
      tokenStorage.clearAll();
    },
    setIsInitialized: (state, action: PayloadAction<boolean>) => {
      state.isInitialized = action.payload;
    },
  },
});

export const authReducer = authSlice.reducer;
export const {
  setUser,
  setTokens,
  setAccessToken,
  setIsLoading,
  setError,
  logout,
  setIsInitialized,
} = authSlice.actions;
