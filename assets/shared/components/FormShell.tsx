import React from 'react';
import { useNavigate } from 'react-router-dom';
import styles from './FormShell.module.css';

interface FormShellProps {
  backTo: string;
  children: React.ReactNode;
  error?: string | null;
  saving?: boolean;
  showSaveBar: boolean;
  onSubmit: (e: React.SyntheticEvent) => void;
  extraActions?: React.ReactNode;
}

export function FormShell({
  backTo,
  children,
  error,
  saving,
  showSaveBar,
  onSubmit,
  extraActions,
}: FormShellProps) {
  const navigate = useNavigate();

  return (
    <form onSubmit={onSubmit} className={styles.form}>
      <button
        type="button"
        onClick={() => (history.length > 1 ? navigate(-1) : navigate(backTo))}
        className={styles.backBtn}
        aria-label="Go back"
      >
        ←
      </button>
      {children}
      {error && (
        <div className={`error-message ${styles.error}`} role="alert">
          {error}
        </div>
      )}
      {(showSaveBar || extraActions) && (
        <div className={styles.saveArea}>
          {showSaveBar && (
            <button type="submit" className="btn btn-primary" disabled={saving}>
              {saving ? 'Saving...' : 'Save'}
            </button>
          )}
          {extraActions}
        </div>
      )}
    </form>
  );
}
