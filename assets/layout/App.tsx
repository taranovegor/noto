import { useEffect, useRef } from 'react';
import { Outlet, ScrollRestoration, useLocation } from 'react-router-dom';
import { Sidebar } from './Sidebar';
import { useAppDispatch } from '../shared/store/hooks';
import {
  setTasksActiveSearch,
  setNotesActiveSearch,
  setTasksSelectedProjectId,
} from '../shared/store/uiSlice';
import '../styles/tokens.css';
import '../styles/base.css';
import styles from './App.module.css';

export function App() {
  const location = useLocation();
  const dispatch = useAppDispatch();
  const currentPath = location.pathname;
  const mainRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const isTasksTab = currentPath.startsWith('/tasks');
    const isNotesTab = currentPath.startsWith('/notes');

    if (!isTasksTab) {
      dispatch(setTasksActiveSearch(null));
      dispatch(setTasksSelectedProjectId(null));
      for (let i = sessionStorage.length - 1; i >= 0; i--) {
        const key = sessionStorage.key(i);
        if (key?.startsWith('kanban-scroll-')) {
          sessionStorage.removeItem(key);
        }
      }
    }
    if (!isNotesTab) {
      dispatch(setNotesActiveSearch(null));
    }
  }, [currentPath, dispatch]);

  useEffect(() => {
    mainRef.current?.focus();
  }, [location.pathname]);

  return (
    <div className={styles.layout}>
      <ScrollRestoration storageKey="layout-scroll-restoration" />
      <Sidebar />

      <div className={styles.main}>
        <main className="main-content" ref={mainRef} tabIndex={-1}>
          <div className="container">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
