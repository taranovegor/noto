import { TaskResponseDto } from '../types';
import { formatDateShort } from '../../../shared/utils/date';
import { PriorityDot } from './PriorityDot';
import styles from './TaskRow.module.css';

interface TaskRowProps {
  task: TaskResponseDto;
  last?: boolean;
  onClick: (id: string) => void;
}

export function TaskRow({ task, last = false, onClick }: TaskRowProps) {
  const done = task.status === 'done';

  return (
    <button
      type="button"
      onClick={() => onClick(task.id)}
      className={[styles.row, last && styles.last, done && styles.done].filter(Boolean).join(' ')}
    >
      <PriorityDot priority={task.priority} />
      <span className={styles.title}>{task.name}</span>
      <span className={styles.code}>{task.code ?? ''}</span>
      <span className={styles.date}>{task.deadline ? formatDateShort(task.deadline) : ''}</span>
    </button>
  );
}
