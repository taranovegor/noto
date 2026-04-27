import React from 'react';
import searchStyles from './TaskSearchResults.module.css';

interface TasksSearchSkeletonProps {
  count?: number;
}

export function TasksSearchSkeleton({ count = 6 }: TasksSearchSkeletonProps) {
  return (
    <div className={searchStyles.searchResults}>
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className={`card ${searchStyles.searchCard}`}>
          <div
            className="skeleton skeleton-text"
            style={{ height: '1rem', marginBottom: '12px', width: '75%' }}
          />
          <div className={searchStyles.searchCardMeta}>
            <div
              className="skeleton skeleton-text"
              style={{ width: '60px', height: '0.75rem', marginBottom: 0 }}
            />
            <div
              className="skeleton skeleton-text"
              style={{ width: '72px', height: '1.25rem', borderRadius: '9999px', marginBottom: 0 }}
            />
            <div
              className="skeleton skeleton-text"
              style={{ width: '56px', height: '1.25rem', borderRadius: '9999px', marginBottom: 0 }}
            />
            <div
              className="skeleton skeleton-text"
              style={{ width: '80px', height: '0.75rem', marginBottom: 0 }}
            />
          </div>
        </div>
      ))}
    </div>
  );
}
