import { useEffect, useRef, useState } from 'react';
import { Outlet, ScrollRestoration, useLocation } from 'react-router-dom';
import { Sidebar } from './Sidebar';
import { LayoutContext, type LayoutMode } from './LayoutContext';
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
  const mainRef = useRef<HTMLElement>(null);
  const [layoutMode, setLayoutMode] = useState<LayoutMode>('scroll');

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
    <LayoutContext.Provider value={{ setLayoutMode }}>
      <div className={styles.layout}>
        <ScrollRestoration storageKey="layout-scroll-restoration" />
        <Sidebar />
        <main
          ref={mainRef}
          tabIndex={-1}
          className={`${styles.main} ${layoutMode === 'fixed' ? styles.mainFixed : ''}`}
        >
          <div className="container">
            <Outlet />
          </div>
        </main>
      </div>
    </LayoutContext.Provider>
  );
}
