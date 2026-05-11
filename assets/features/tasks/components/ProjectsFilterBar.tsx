import React, { useRef } from 'react';
import { useScrollRestoration, createScrollKey } from '../../../shared/hooks';
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
  const barRef = useRef<HTMLDivElement>(null);
  useScrollRestoration(barRef, createScrollKey('tasks', 'projects-filter-bar'), {
    popOnly: false,
    direction: 'horizontal',
    ready: !loading,
  });

  // Always render the bar so the ref stays on the same DOM element
  // (otherwise the scroll listener is lost when projects go empty and return).
  const isEmpty = !loading && projects.length === 0;

  return (
    <div ref={barRef} className={styles.bar} style={isEmpty ? { display: 'none' } : undefined}>
      {loading
        ? Array.from({ length: 3 }).map((_, i) => (
            <div
              key={i}
              className={`skeleton ${styles.skeleton}`}
              style={{ width: `${60 + i * 30}px` }}
            />
          ))
        : projects.map((project) => {
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
