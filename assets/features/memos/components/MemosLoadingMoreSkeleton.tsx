import React from 'react';
import styles from './MemosList.module.css';

export function MemosLoadingMoreSkeleton() {
  return (
    <div className={styles.loadingMore}>
      {Array.from({ length: 3 }).map((_, i) => (
        <div key={`loading-${i}`} className="skeleton-card" style={{ height: '100px' }}>
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
