import React from 'react';
import { ArrowLeft } from 'lucide-react';
import formStyles from '../../../shared/components/FormShell.module.css';
import backStyles from '../../../shared/components/BackButton.module.css';
import styles from './NotePage.module.css';

export function NotePageSkeleton() {
  return (
    <div className={`${formStyles.form} ${styles.skeletonForm}`}>
      <button type="button" disabled className={backStyles.backBtn} aria-hidden>
        <ArrowLeft size={20} strokeWidth={1.75} />
      </button>
      <div className={styles.skeletonBody}>
        <div
          className="skeleton skeleton-text"
          style={{ height: '2rem', width: '50%', marginBottom: '1.5rem' }}
        />
        <div className="skeleton skeleton-text" style={{ height: '16rem' }} />
      </div>
    </div>
  );
}
