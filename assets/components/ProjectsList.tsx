import React, { useEffect } from 'react';
import { formatDate } from '../utils/date';
import { useAppDispatch, useAppSelector } from '../store/hooks';
import { loadProjects } from '../store/slices/projects';

export function ProjectsList() {
  const dispatch = useAppDispatch();
  const { projects, loading, error, initialized } = useAppSelector(state => state.projects);

  useEffect(() => {
    if (!initialized) {
      dispatch(loadProjects());
    }
  }, [dispatch, initialized]);

  return (
    <div>
      <h2 style={{ marginBottom: '32px' }}>Projects</h2>

      {error && (
        <div className="error-message">
          {error}
        </div>
      )}

      {loading ? (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: '24px' }}>
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="card" style={{ padding: '24px' }}>
              <div className="skeleton skeleton-text tiny" style={{ marginBottom: '16px' }} />
              <div className="skeleton skeleton-text" style={{ marginBottom: '12px', height: '1.3rem' }} />
              <div className="skeleton skeleton-text short" />
            </div>
          ))}
        </div>
      ) : (
        <>
          {projects.length === 0 && (
            <div className="empty-state">
              <p>No projects yet.</p>
            </div>
          )}

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: '24px' }}>
            {projects.map((project, index) => (
              <div
                key={project.id}
                className="card"
                data-stagger-index={index}
                style={{ animationDelay: `${index * 30}ms` }}
              >
                <p className="text-mono" style={{ marginBottom: '8px' }}>{project.prefix}</p>
                <h3 style={{ marginBottom: '8px', fontSize: '1.1rem' }}>{project.name}</h3>
                <p className="text-secondary" style={{ marginBottom: 0 }}>
                  Created {formatDate(project.createdAt)}
                </p>
              </div>
            ))}
          </div>
        </>
      )}
    </div>
  );
}
