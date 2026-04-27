import React from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import styles from './Sidebar.module.css';

export function Sidebar() {
  const navigate = useNavigate();
  const location = useLocation();
  const currentPath = location.pathname;

  const isActive = (path: string) => currentPath === path || currentPath.startsWith(path + '/');

  return (
    <aside className={styles.sidebar}>
      <div className={styles.header}>
        <button onClick={() => navigate('/tasks')} className={styles.title} aria-label="Noto home">
          noto
        </button>
      </div>
      <nav className={styles.nav}>
        <button
          className={`${styles.navItem} ${isActive('/tasks') ? styles.navItemActive : ''}`}
          onClick={() => navigate('/tasks')}
          aria-label="Tasks"
          aria-current={isActive('/tasks') ? 'page' : undefined}
        >
          Tasks
        </button>
        <button
          className={`${styles.navItem} ${isActive('/notes') ? styles.navItemActive : ''}`}
          onClick={() => navigate('/notes')}
          aria-label="Notes"
          aria-current={isActive('/notes') ? 'page' : undefined}
        >
          Notes
        </button>
      </nav>
    </aside>
  );
}
