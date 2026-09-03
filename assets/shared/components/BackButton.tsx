import React from 'react';
import { ArrowLeft } from 'lucide-react';
import styles from './BackButton.module.css';

interface BackButtonProps {
  onClick?: () => void;
  disabled?: boolean;
}

export function BackButton({ onClick, disabled }: BackButtonProps) {
  return (
    <button
      type="button"
      onClick={onClick}
      disabled={disabled}
      className={styles.backBtn}
      aria-label={disabled ? undefined : 'Go back'}
      aria-hidden={disabled || undefined}
    >
      <ArrowLeft size={20} strokeWidth={1.75} />
    </button>
  );
}
