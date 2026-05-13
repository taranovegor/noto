import React from 'react';
import formStyles from '../../../shared/components/FormShell.module.css';
import styles from './NotePage.module.css';

export function NotePageSkeleton() {
  return (
    <div className={`${formStyles.form} ${styles.skeletonForm}`}>
      <div className={styles.skeletonBody}>
        <div className="skeleton skeleton-text" style={{ height: '16rem' }} />
      </div>
    </div>
  );
}
