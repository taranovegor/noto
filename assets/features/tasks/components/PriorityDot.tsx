import { TaskPriority } from '../types';
import styles from './PriorityDot.module.css';

interface PriorityDotProps {
  priority?: TaskPriority | null;
}

const PRIORITY_CLASS: Record<TaskPriority, string> = {
  low: styles.low,
  medium: styles.medium,
  high: styles.high,
};

export function PriorityDot({ priority }: PriorityDotProps) {
  const priorityClass = priority ? PRIORITY_CLASS[priority] : styles.none;
  return (
    <span
      className={`${styles.dot} ${priorityClass}`}
      aria-label={priority ? `Priority: ${priority}` : undefined}
    />
  );
}
