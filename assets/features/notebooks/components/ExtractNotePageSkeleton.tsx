import React from 'react';
import { BackButton } from '../../../shared/components';
import formStyles from '../../../shared/components/FormShell.module.css';

export function ExtractNotePageSkeleton() {
  return (
    <div className={formStyles.form}>
      <BackButton disabled />
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
