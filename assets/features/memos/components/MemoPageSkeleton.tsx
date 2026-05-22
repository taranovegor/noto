import React from 'react';
import formStyles from '../../../shared/components/FormShell.module.css';
import styles from './MemoPage.module.css';

export function MemoPageSkeleton() {
  return (
    <div className={`${formStyles.form} ${styles.skeletonForm}`}>
      <div className={styles.skeletonBody}>
        <div className="skeleton skeleton-text" style={{ height: '16rem' }} />
      </div>
    </div>
  );
}
