import styles from './TaskRowSkeleton.module.css';

interface TaskRowSkeletonProps {
  count?: number;
}

export function TaskRowSkeleton({ count = 6 }: TaskRowSkeletonProps) {
  return (
    <>
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className={`${styles.row} ${i === count - 1 ? styles.last : ''}`}>
          <span className={`skeleton ${styles.dot}`} />
          <span className={`skeleton ${styles.title}`} style={{ width: `${50 + (i % 3) * 15}%` }} />
          <span className={`skeleton ${styles.meta}`} />
          <span className={`skeleton ${styles.meta}`} />
        </div>
      ))}
    </>
  );
}
