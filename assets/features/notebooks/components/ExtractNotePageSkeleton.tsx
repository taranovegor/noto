import React from 'react';
import { ArrowLeft } from 'lucide-react';
import formStyles from '../../../shared/components/FormShell.module.css';
import backStyles from '../../../shared/components/BackButton.module.css';

export function ExtractNotePageSkeleton() {
  return (
    <div className={formStyles.form}>
      <button type="button" disabled className={backStyles.backBtn} aria-hidden>
        <ArrowLeft size={20} strokeWidth={1.75} />
      </button>
      <div style={{ maxWidth: '64rem' }}>
        <div
          className="skeleton skeleton-text"
          style={{ height: '2rem', width: '50%', marginBottom: '1.5rem' }}
        />
        <div
          className="skeleton"
          style={{ height: '4rem', marginBottom: '1.5rem', borderRadius: 6 }}
        />
        <div
          className="skeleton skeleton-text"
          style={{ height: '0.75rem', width: '3rem', marginBottom: '0.5rem' }}
        />
        <div
          className="skeleton"
          style={{ height: '6rem', marginBottom: '1.5rem', borderRadius: 6 }}
        />
        <div
          className="skeleton skeleton-text"
          style={{ height: '0.75rem', width: '5rem', marginBottom: '0.5rem' }}
        />
        <div className="skeleton" style={{ height: '6rem', borderRadius: 6 }} />
      </div>
    </div>
  );
}
