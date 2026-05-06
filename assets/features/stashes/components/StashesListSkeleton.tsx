import styles from './StashesList.module.css';

export function StashesListSkeleton() {
  return (
    <div className={styles.container}>
      <section className={styles.section}>
        <h2 className={styles.sectionTitle}>Active</h2>
        <div className={styles.grid}>
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className={styles.skeletonCard}>
              <div className={`skeleton ${styles.skeletonIcon}`} />
              <div
                className={`skeleton ${styles.skeletonTitle}`}
                style={{ width: `${40 + (i % 3) * 15}%` }}
              />
              <div className={`skeleton ${styles.skeletonMeta}`} />
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}
