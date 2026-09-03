import React from 'react';
import { useBackNavigation } from '../hooks';
import { BackButton } from './BackButton';
import styles from './FormShell.module.css';

interface FormShellProps {
  backTo: string;
  children: React.ReactNode;
  error?: string | null;
  saving?: boolean;
  showSaveBar: boolean;
  submitLabel?: string;
  onSubmit: (e: React.SyntheticEvent) => void;
  extraActions?: React.ReactNode;
}

export function FormShell({
  backTo,
  children,
  error,
  saving,
  showSaveBar,
  submitLabel,
  onSubmit,
  extraActions,
}: FormShellProps) {
  const handleBack = useBackNavigation(backTo);

  return (
    <form onSubmit={onSubmit} className={styles.form}>
      <BackButton onClick={handleBack} />
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
              {saving ? 'Saving...' : (submitLabel ?? 'Save')}
            </button>
          )}
          {extraActions}
        </div>
      )}
    </form>
  );
}
