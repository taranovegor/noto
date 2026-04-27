import React from 'react';
import { TaskResponseDto } from '../types';
import {
  STATUS_OPTIONS,
  PRIORITY_OPTIONS,
  STATUS_LABEL_MAP,
  PRIORITY_LABEL_MAP,
} from '../constants';
import { formatDateShort } from '../../../shared/utils/date';
import { useStaggerStyles } from '../../../shared/hooks';

import styles from './TaskSearchResults.module.css';

interface TaskSearchResultsProps {
  tasks: TaskResponseDto[];
  onTaskClick: (id: string) => void;
}

const statusStyleMap = new Map(
  STATUS_OPTIONS.map((o) => [o.value, { '--badge-bg': o.bg, '--badge-text': o.text }]),
);
const fallbackStatusStyle = { '--badge-bg': '#f9f9f8', '--badge-text': '#787774' };

const priorityStyleMap = new Map(
  PRIORITY_OPTIONS.map((o) => [o.value, { '--badge-bg': o.bg, '--badge-text': o.text }]),
);
function TaskSearchResultsInner({ tasks, onTaskClick }: TaskSearchResultsProps) {
  const staggerStyles = useStaggerStyles(tasks.length);

  return (
    <div className={styles.searchResults}>
      {tasks.map((task, index) => {
        const priLabel = task.priority ? PRIORITY_LABEL_MAP.get(task.priority) : null;
        const statStyle = statusStyleMap.get(task.status) ?? fallbackStatusStyle;
        const statLabel = STATUS_LABEL_MAP.get(task.status) ?? task.status;
        return (
          <button
            key={task.id}
            className={`card ${styles.searchCard} animate-card-enter`}
            onClick={() => onTaskClick(task.id)}
            style={staggerStyles[index]}
          >
            <div className={styles.searchCardTitle}>{task.name}</div>
            <div className={styles.searchCardMeta}>
              {task.code && <span className={styles.searchCardCode}>{task.code}</span>}
              <span className={styles.statusBadge} style={statStyle}>
                {statLabel}
              </span>
              {priLabel && (
                <span className={styles.statusBadge} style={priorityStyleMap.get(task.priority!)}>
                  {priLabel}
                </span>
              )}
              {task.deadline && (
                <span className={styles.searchCardMetaItem}>{formatDateShort(task.deadline)}</span>
              )}
            </div>
          </button>
        );
      })}
    </div>
  );
}

export const TaskSearchResults = React.memo(TaskSearchResultsInner);
