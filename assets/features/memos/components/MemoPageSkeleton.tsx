import React from 'react';
import { ArrowLeft } from 'lucide-react';
import formStyles from '../../../shared/components/FormShell.module.css';
import backStyles from '../../../shared/components/BackButton.module.css';
import styles from './MemoPage.module.css';

export function MemoPageSkeleton() {
  return (
    <div className={`${formStyles.form} ${styles.skeletonForm}`}>
      <button type="button" disabled className={backStyles.backBtn} aria-hidden>
        <ArrowLeft size={20} strokeWidth={1.75} />
      </button>
      <div className={styles.skeletonBody}>
        <div className="skeleton skeleton-text" style={{ height: '16rem' }} />
      </div>
    </div>
  );
}
