import React from 'react';
import formStyles from '../../../shared/components/FormShell.module.css';
import styles from './NotebookPage.module.css';

export function NotebookPageSkeleton() {
  return (
    <div className={`${formStyles.form} ${styles.skeletonForm}`}>
      <div className={styles.skeletonBody}>
        <div
          className="skeleton skeleton-text"
          style={{ height: '2rem', width: '40%', marginBottom: '1rem' }}
        />
        <div className="skeleton skeleton-text" style={{ height: '8rem' }} />
      </div>
    </div>
  );
}
