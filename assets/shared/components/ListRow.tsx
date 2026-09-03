import styles from './ListRow.module.css';

interface ListRowProps {
  title: string;
  description?: string | null;
  date?: string | null;
  last?: boolean;
  onClick: () => void;
}

export function ListRow({ title, description, date, last = false, onClick }: ListRowProps) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`${styles.row} ${last ? styles.last : ''}`}
      role="listitem"
    >
      <div className={styles.titleRow}>
        <span className={styles.title}>{title}</span>
        {date && <span className={styles.date}>{date}</span>}
      </div>
      {description && <div className={styles.description}>{description}</div>}
    </button>
  );
}
