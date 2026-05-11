import React, { useRef, useState, useCallback } from 'react';
import { useAppSelector } from '../../../shared/store/hooks';
import { STATUS_OPTIONS } from '../constants';
import { useMediaQuery, useScrollRestoration, createScrollKey } from '../../../shared/hooks';
import { KanbanColumn } from './KanbanColumn';

import styles from './TaskKanban.module.css';

interface TaskKanbanProps {
  onTaskClick: (id: string) => void;
}

function TaskKanbanInner({ onTaskClick }: TaskKanbanProps) {
  const selectedProjectId = useAppSelector((state) => state.ui.tasksSelectedProjectId);
  const isMobile = useMediaQuery('(max-width: 768px)');
  const [activeCol, setActiveCol] = useState(0);
  const scrollRef = useRef<HTMLDivElement>(null);

  useScrollRestoration(
    scrollRef,
    createScrollKey('tasks', 'kanban-carousel', selectedProjectId ?? 'all'),
    {
      popOnly: false,
      direction: 'horizontal',
    },
  );

  const handleCarouselScroll = useCallback(() => {
    if (!scrollRef.current) return;
    const width = scrollRef.current.clientWidth;
    if (width === 0) return;
    setActiveCol(Math.round(scrollRef.current.scrollLeft / width));
  }, []);

  const scrollToCol = useCallback((i: number) => {
    if (!scrollRef.current) return;
    scrollRef.current.scrollTo({ left: i * scrollRef.current.clientWidth, behavior: 'smooth' });
  }, []);

  const renderKanbanColumns = (mobileStyle: boolean) =>
    STATUS_OPTIONS.map((o) => (
      <div key={o.value} className={mobileStyle ? styles.kanbanColumn : undefined}>
        <KanbanColumn status={o.value} projectId={selectedProjectId} onTaskClick={onTaskClick} />
      </div>
    ));

  return (
    <div className={`${styles.page} ${isMobile ? styles.mobilePage : ''}`}>
      {isMobile ? (
        <div className={styles.mobileFlexArea}>
          <div ref={scrollRef} className={styles.kanbanWrapper} onScroll={handleCarouselScroll}>
            {renderKanbanColumns(true)}
          </div>
          <div className={styles.carouselDots}>
            {STATUS_OPTIONS.map((_, i) => (
              <button
                key={i}
                onClick={() => scrollToCol(i)}
                className={i === activeCol ? styles.dotActive : styles.dot}
                aria-label={`Show ${STATUS_OPTIONS[i].label}`}
              />
            ))}
          </div>
        </div>
      ) : (
        <div className={styles.kanban}>{renderKanbanColumns(false)}</div>
      )}
    </div>
  );
}

export const TaskKanban = React.memo(TaskKanbanInner);
