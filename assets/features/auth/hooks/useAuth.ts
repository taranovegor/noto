import { useAppSelector } from '../../../shared/store/hooks';
import { User } from '../types';

interface UseAuthReturn {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
  isInitialized: boolean;
}

export function useAuth(): UseAuthReturn {
  const { user, isLoading, error, isInitialized } = useAppSelector((state) => state.auth);

  return {
    user,
    isAuthenticated: !!user,
    isLoading,
    error,
    isInitialized,
  };
}
