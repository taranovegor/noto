import React, { useRef, useEffect, useCallback } from 'react';
import { TaskStatus, TaskPriority } from '../types';
import { STATUS_LABEL_MAP, PRIORITY_OPTIONS } from '../constants';
import { formatDateShort } from '../../../shared/utils/date';
import { useInfiniteScroll, useStaggerStyles } from '../../../shared/hooks';
import { useTasks } from '../store/api';

import styles from './KanbanColumn.module.css';

interface KanbanColumnProps {
  status: TaskStatus;
  projectId: string | null;
  onTaskClick: (id: string) => void;
}

const priorityStylesMap = new Map<TaskPriority, React.CSSProperties>(
  PRIORITY_OPTIONS.map((o) => [
    o.value,
    { '--dot-bg': o.bg, '--dot-border': o.text } as React.CSSProperties,
  ]),
);

function KanbanCardSkeleton() {
  return (
    <div className={styles.skeletonCard}>
      <div className="skeleton skeleton-text" style={{ height: '1rem', marginBottom: '8px' }} />
      <div className="skeleton skeleton-text tiny" style={{ marginBottom: '4px' }} />
      <div className="skeleton skeleton-text tiny" style={{ marginBottom: 0, width: '30%' }} />
    </div>
  );
}

function KanbanColumnInner({ status, projectId, onTaskClick }: KanbanColumnProps) {
  const columnRef = useRef<HTMLDivElement>(null);
  const scrollKey = `kanban-scroll-${status}-${projectId ?? 'all'}`;

  useEffect(() => {
    const el = columnRef.current;
    if (!el) return;
    const saved = sessionStorage.getItem(scrollKey);
    if (saved !== null) {
      el.scrollTop = parseInt(saved, 10);
    }
  }, [scrollKey]);

  const handleScroll = useCallback(() => {
    const el = columnRef.current;
    if (!el) return;
    sessionStorage.setItem(scrollKey, String(el.scrollTop));
  }, [scrollKey]);

  const { data, isLoading, isFetching, isFetchingNextPage, hasNextPage, fetchNextPage } = useTasks({
    status,
    projectId,
  });

  const tasks = data?.pages.flat() ?? [];

  const { sentinelRef } = useInfiniteScroll(
    hasNextPage ?? false,
    isFetchingNextPage,
    fetchNextPage,
  );

  const staggerStyles = useStaggerStyles(tasks.length);

  const label = STATUS_LABEL_MAP.get(status) ?? status;

  const showSkeleton = isLoading || (isFetching && !isFetchingNextPage);

  return (
    <div className={styles.colWrapper}>
      <div className={styles.columnHeader}>
        <span className={styles.columnLabel}>{label}</span>
      </div>
      <div ref={columnRef} onScroll={handleScroll} className={`hide-scrollbar ${styles.column}`}>
        {showSkeleton
          ? Array.from({ length: 3 }).map((_, i) => <KanbanCardSkeleton key={i} />)
          : tasks.map((task, index) => (
              <button
                key={task.id}
                className={`card ${styles.card} animate-card-enter`}
                onClick={() => onTaskClick(task.id)}
                style={staggerStyles[index]}
              >
                <div className={styles.cardTitle}>
                  {task.priority && (
                    <span
                      className={styles.priorityDot}
                      style={priorityStylesMap.get(task.priority)}
                      aria-label={`Priority: ${task.priority}`}
                    />
                  )}
                  {task.name}
                </div>
                {(task.code || task.deadline) && (
                  <div className={styles.cardMeta}>
                    {task.code && <p className={styles.cardCode}>{task.code}</p>}
                    {task.deadline && <span>{formatDateShort(task.deadline)}</span>}
                  </div>
                )}
              </button>
            ))}
        {isFetchingNextPage && <KanbanCardSkeleton />}
        <div ref={sentinelRef} className={styles.sentinel} />
      </div>
    </div>
  );
}

export const KanbanColumn = React.memo(KanbanColumnInner);
