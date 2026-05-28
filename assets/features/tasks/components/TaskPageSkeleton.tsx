import React from 'react';
import { ArrowLeft } from 'lucide-react';
import formStyles from '../../../shared/components/FormShell.module.css';
import backStyles from '../../../shared/components/BackButton.module.css';
import styles from './TaskPage.module.css';

export function TaskPageSkeleton() {
  return (
    <div className={`${formStyles.form} ${styles.skeletonForm}`}>
      <button type="button" disabled className={backStyles.backBtn} aria-hidden>
        <ArrowLeft size={20} strokeWidth={1.75} />
      </button>
      <div className={styles.titleRow}>
        <div className={styles.nameColumn}>
          <div className="skeleton skeleton-text" style={{ height: '2rem', width: '70%' }} />
          <div
            className="skeleton skeleton-text tiny"
            style={{ width: '120px', marginTop: '12px' }}
          />
        </div>
      </div>
      <div className={styles.skeletonMeta}>
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="skeleton-form-row">
            <div className="skeleton skeleton-form-label" />
            <div style={{ flex: 1, display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
              <div
                className="skeleton skeleton-text"
                style={{ width: '100px', height: '1.5rem' }}
              />
              <div
                className="skeleton skeleton-text"
                style={{ width: '100px', height: '1.5rem' }}
              />
            </div>
          </div>
        ))}
      </div>
      <div className={styles.skeletonNoteSection}>
        <div className={`skeleton skeleton-text ${styles.skeletonNoteLabel}`} />
        <div className={`skeleton skeleton-text ${styles.skeletonNoteBody}`} />
      </div>
    </div>
  );
}
