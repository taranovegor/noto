import { useAppSelector } from '../../../shared/store/hooks';
import { User } from '../types';

interface UseAuthReturn {
  user: User | null;
  accessToken: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
  isInitialized: boolean;
}

export function useAuth(): UseAuthReturn {
  const { user, accessToken, isLoading, error, isInitialized } = useAppSelector(
    (state) => state.auth,
  );

  return {
    user,
    accessToken,
    isAuthenticated: !!user && !!accessToken,
    isLoading,
    error,
    isInitialized,
  };
}
