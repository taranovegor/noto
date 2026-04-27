import React from 'react';
import styles from './NotesList.module.css';

export function NotesListSkeleton() {
  return (
    <div className={styles.skeletonList}>
      {Array.from({ length: 5 }).map((_, i) => (
        <div key={i} className={`skeleton-card ${styles.skeletonCard}`}>
          <div
            className="skeleton skeleton-text"
            style={{ height: '1rem', marginBottom: '8px', width: '60%' }}
          />
          <div className="skeleton skeleton-text tiny" style={{ marginBottom: '6px' }} />
          <div className="skeleton skeleton-text tiny" />
        </div>
      ))}
    </div>
  );
}
