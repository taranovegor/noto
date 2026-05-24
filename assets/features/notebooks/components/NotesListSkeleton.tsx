import React from 'react';
import styles from './NotesList.module.css';

export function NotesListSkeleton() {
  return (
    <div className={styles.skeletonList}>
      {Array.from({ length: 5 }).map((_, i) => (
        <div key={i} className={`skeleton-card ${styles.skeletonCard}`}>
          <div
            className="skeleton skeleton-text"
            style={{ height: '1rem', marginBottom: 'var(--space-sm)', width: '60%' }}
          />
          <div
            className="skeleton skeleton-text tiny"
            style={{ marginBottom: 'var(--space-xs)' }}
          />
          <div className="skeleton skeleton-text tiny" />
        </div>
      ))}
    </div>
  );
}
