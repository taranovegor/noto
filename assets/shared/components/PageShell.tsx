import type { ReactNode } from 'react';
import styles from './PageShell.module.css';

interface PageShellProps {
  title: string;
  children: ReactNode;
  actions?: ReactNode;
  className?: string;
  padBottom?: boolean;
}

export function PageShell({
  title,
  children,
  actions,
  className,
  padBottom = true,
}: PageShellProps) {
  const shellClass = [styles.shell, className, !padBottom ? styles.noPadBottom : null]
    .filter(Boolean)
    .join(' ');

  return (
    <div className={shellClass}>
      <div className={styles.header}>
        <h2 className={styles.title}>{title}</h2>
        {actions && <div className={styles.actions}>{actions}</div>}
      </div>
      {children}
    </div>
  );
}
