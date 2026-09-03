import styles from './ListCardSkeleton.module.css';

interface ListCardSkeletonProps {
  count?: number;
  showDate?: boolean;
}

export function ListCardSkeleton({ count = 5, showDate = true }: ListCardSkeletonProps) {
  return (
    <>
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className={`${styles.row} ${i === count - 1 ? styles.last : ''}`}>
          <div className={styles.titleRow}>
            <span
              className={`skeleton ${styles.title}`}
              style={{ width: `${55 + (i % 3) * 15}%` }}
            />
            {showDate && <span className={`skeleton ${styles.date}`} />}
          </div>
          <span className={`skeleton ${styles.descLine}`} style={{ width: '40%' }} />
        </div>
      ))}
    </>
  );
}
