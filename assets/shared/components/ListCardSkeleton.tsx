import React from 'react';
import { Skeleton } from './Skeleton';
import styles from './ListCardSkeleton.module.css';

interface ListCardSkeletonProps {
  count?: number;
}

export function ListCardSkeleton({ count = 5 }: ListCardSkeletonProps) {
  return (
    <div className={styles.list}>
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className={`skeleton-card ${styles.card}`}>
          <Skeleton style={{ height: '1rem', marginBottom: 'var(--space-sm)', width: '60%' }} />
          <Skeleton tiny style={{ marginBottom: 'var(--space-xs)' }} />
          <Skeleton tiny />
        </div>
      ))}
    </div>
  );
}
