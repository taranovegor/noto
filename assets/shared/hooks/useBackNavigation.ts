import { useNavigate } from 'react-router-dom';

export function useBackNavigation(fallbackPath: string) {
  const navigate = useNavigate();
  return () => (history.length > 1 ? navigate(-1) : navigate(fallbackPath));
}
