import React, { useState, useEffect } from 'react';
import { ProjectResponseDto } from '../types/api';
import { api } from '../api';
import { formatDate } from '../utils/date';

export function ProjectsList() {
  const [projects, setProjects] = useState<ProjectResponseDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.projects.list()
      .then((data) => setProjects(data.data))
      .catch((err: unknown) => {
        setError(err instanceof Error ? err.message : 'Failed to load projects');
      })
      .finally(() => setLoading(false));
  }, []);

  return (
    <div>
      <h2 style={{ marginBottom: '32px' }}>Projects</h2>

      {loading && (
        <div className="empty-state">
          <p>Loading projects...</p>
        </div>
      )}

      {error && (
        <div className="error-message">
          {error}
        </div>
      )}

      {!loading && projects.length === 0 && (
        <div className="empty-state">
          <p>No projects yet.</p>
        </div>
      )}

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: '24px' }}>
        {projects.map((project) => (
          <div
            key={project.id}
            className="card"
          >
            <p className="text-mono" style={{ marginBottom: '8px' }}>{project.prefix}</p>
            <h3 style={{ marginBottom: '8px', fontSize: '1.1rem' }}>{project.name}</h3>
            <p className="text-secondary" style={{ marginBottom: 0 }}>
              Created {formatDate(project.created_at)}
            </p>
          </div>
        ))}
      </div>
    </div>
  );
}
