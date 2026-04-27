import React from 'react';
import { ProjectResponseDto } from '../../projects';
import styles from './ProjectsFilterBar.module.css';

interface ProjectsFilterBarProps {
  projects: ProjectResponseDto[];
  loading: boolean;
  selectedProjectId: string | null;
  onToggle: (id: string) => void;
}

function ProjectsFilterBarInner({
  projects,
  loading,
  selectedProjectId,
  onToggle,
}: ProjectsFilterBarProps) {
  if (loading) {
    return (
      <div className={styles.bar}>
        {Array.from({ length: 3 }).map((_, i) => (
          <div
            key={i}
            className={`skeleton ${styles.skeleton}`}
            style={{ width: `${60 + i * 30}px` }}
          />
        ))}
      </div>
    );
  }

  if (projects.length === 0) return null;

  return (
    <div className={styles.bar}>
      {projects.map((project) => {
        const active = selectedProjectId === project.id;
        return (
          <button
            key={project.id}
            onClick={() => onToggle(project.id)}
            className={`${styles.badge} ${active ? styles.badgeActive : styles.badgeInactive}`}
          >
            {project.prefix}
            <span className={styles.badgeName}>{project.name}</span>
          </button>
        );
      })}
    </div>
  );
}

export const ProjectsFilterBar = React.memo(ProjectsFilterBarInner);
