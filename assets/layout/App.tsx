import { useEffect, useRef, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { Sidebar } from './Sidebar';
import { LayoutContext, type LayoutMode } from './LayoutContext';
import { ActionBarProvider } from './ActionBarContext';
import { useAppDispatch } from '../shared/store/hooks';
import { useScrollRestoration, clearScrollKeys, createScrollKey } from '../shared/hooks';
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

  useScrollRestoration(mainRef, createScrollKey('main', location.pathname, location.search));

  useEffect(() => {
    const isTasksTab = currentPath.startsWith('/tasks');
    const isNotesTab = currentPath.startsWith('/notes');

    if (!isTasksTab) {
      dispatch(setTasksActiveSearch(null));
      dispatch(setTasksSelectedProjectId(null));
      clearScrollKeys('tasks');
    }
    if (!isNotesTab) {
      dispatch(setNotesActiveSearch(null));
      clearScrollKeys('notes');
    }
  }, [currentPath, dispatch]);

  useEffect(() => {
    mainRef.current?.focus();
  }, [location.pathname]);

  return (
    <ActionBarProvider>
      <LayoutContext.Provider value={{ setLayoutMode }}>
        <div className={styles.layout}>
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
    </ActionBarProvider>
  );
}
