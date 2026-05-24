import React from 'react';
import styles from './NotesList.module.css';

export function NotesLoadingMoreSkeleton() {
  return (
    <div className={styles.loadingMore}>
      {Array.from({ length: 3 }).map((_, i) => (
        <div key={`loading-${i}`} className="skeleton-card" style={{ height: '100px' }}>
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
