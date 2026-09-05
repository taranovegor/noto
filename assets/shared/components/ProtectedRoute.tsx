import { useAuth } from '../../features/auth/hooks/useAuth';
import { AccessDeniedPage } from './AccessDeniedPage';

interface ProtectedRouteProps {
  children: React.ReactNode;
}

export function ProtectedRoute({ children }: ProtectedRouteProps) {
  const { isAuthenticated, isInitialized, isLoading } = useAuth();

  if (!isInitialized || isLoading) {
    return <div className="skeleton" style={{ minHeight: '100vh' }} />;
  }

  if (!isAuthenticated) {
    return <AccessDeniedPage />;
  }

  return <>{children}</>;
}
