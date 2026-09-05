import { createSlice, PayloadAction } from '@reduxjs/toolkit';
import type { User, CentrifugoConfig } from '../../features/auth';

export type { User };

export interface AuthState {
  user: User | null;
  isLoading: boolean;
  error: string | null;
  isInitialized: boolean;
  centrifugoConfig: CentrifugoConfig | null;
}

const initialState: AuthState = {
  user: null,
  isLoading: false,
  error: null,
  isInitialized: false,
  centrifugoConfig: null,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setUser: (state, action: PayloadAction<User>) => {
      state.user = action.payload;
    },
    setIsLoading: (state, action: PayloadAction<boolean>) => {
      state.isLoading = action.payload;
    },
    setError: (state, action: PayloadAction<string | null>) => {
      state.error = action.payload;
    },
    logout: (state) => {
      state.user = null;
      state.error = null;
      state.centrifugoConfig = null;
    },
    setCentrifugoConfig: (state, action: PayloadAction<CentrifugoConfig | null>) => {
      state.centrifugoConfig = action.payload;
    },
    setIsInitialized: (state, action: PayloadAction<boolean>) => {
      state.isInitialized = action.payload;
    },
  },
});

export const authReducer = authSlice.reducer;
export const { setUser, setIsLoading, setError, logout, setCentrifugoConfig, setIsInitialized } =
  authSlice.actions;
