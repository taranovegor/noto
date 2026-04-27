import { useRouteError, useNavigate } from 'react-router-dom';

export function RouteErrorBoundary() {
  const error = useRouteError() as Error | undefined;
  const navigate = useNavigate();

  return (
    <div style={{ padding: '40px', textAlign: 'center' }} role="alert">
      <h3 style={{ marginBottom: '8px' }}>Something went wrong</h3>
      <p
        style={{
          color: 'var(--color-text-secondary)',
          marginBottom: '16px',
          fontSize: '0.9rem',
        }}
      >
        {error?.message || 'An unexpected error occurred.'}
      </p>
      <button
        className="btn btn-primary"
        onClick={() =>
          navigate(window.location.pathname + window.location.search, { replace: true })
        }
      >
        Reload page
      </button>
    </div>
  );
}
