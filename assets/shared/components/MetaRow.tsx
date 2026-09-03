import React from 'react';
import styles from './MetaRow.module.css';

interface MetaRowProps {
  label: string;
  children: React.ReactNode;
  divider?: boolean;
}

export function MetaRow({ label, children, divider = true }: MetaRowProps) {
  return (
    <div className={`${styles.row} ${divider ? '' : styles.noDivider}`}>
      <span className={styles.label}>{label}</span>
      <div className={styles.content}>{children}</div>
    </div>
  );
}
