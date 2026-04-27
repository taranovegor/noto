import React from 'react';
import styles from './MetaRow.module.css';

export function MetaRow({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className={styles.row}>
      <span className={styles.label}>{label}</span>
      <div className={styles.content}>{children}</div>
    </div>
  );
}
