import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../shared/store/hooks';
import { logout } from '../shared/store/authSlice';
import { useAuth } from '../features/auth/hooks/useAuth';
import { useLogoutMutation } from '../features/auth/store/api';
import { PushToggle } from '../features/pushes';
import { LOGIN_ROUTE } from '../features/auth/constants';
import styles from './Sidebar.module.css';

export function Sidebar() {
  const navigate = useNavigate();
  const location = useLocation();
  const dispatch = useAppDispatch();
  const { user } = useAuth();
  const refreshToken = useAppSelector((state) => state.auth.refreshToken);
  const [logoutApi] = useLogoutMutation();
  const currentPath = location.pathname;

  const [drawerOpen, setDrawerOpen] = useState(false);
  const sidebarRef = useRef<HTMLElement>(null);
  const touchStartY = useRef(0);
  const touchDeltaY = useRef(0);

  const isActive = (path: string) => currentPath === path || currentPath.startsWith(path + '/');

  // Close drawer on route change
  useEffect(() => {
    setDrawerOpen(false);
  }, [location.pathname]);

  // Close drawer on click outside
  useEffect(() => {
    if (!drawerOpen) return;

    const handleClick = (e: MouseEvent) => {
      if (sidebarRef.current && !sidebarRef.current.contains(e.target as Node)) {
        setDrawerOpen(false);
      }
    };

    document.addEventListener('click', handleClick, { capture: true });
    return () => document.removeEventListener('click', handleClick, { capture: true });
  }, [drawerOpen]);

  const handleTouchStart = useCallback((e: React.TouchEvent) => {
    touchStartY.current = e.touches[0].clientY;
    touchDeltaY.current = 0;
  }, []);

  const handleTouchMove = useCallback((e: React.TouchEvent) => {
    touchDeltaY.current = touchStartY.current - e.touches[0].clientY;
  }, []);

  const handleTouchEnd = useCallback(() => {
    const delta = touchDeltaY.current;
    if (delta > 40 && !drawerOpen) {
      setDrawerOpen(true);
    } else if (delta < -30 && drawerOpen) {
      setDrawerOpen(false);
    }
    touchDeltaY.current = 0;
  }, [drawerOpen]);

  const handleLogout = async () => {
    try {
      if (refreshToken) {
        await logoutApi({ refresh_token: refreshToken }).unwrap();
      }
    } catch (error) {
      console.error('Logout failed:', error);
    } finally {
      dispatch(logout());
      navigate(LOGIN_ROUTE, { replace: true });
    }
  };

  return (
    <aside
      ref={sidebarRef}
      className={styles.sidebar}
      onTouchStart={handleTouchStart}
      onTouchMove={handleTouchMove}
      onTouchEnd={handleTouchEnd}
    >
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
        <button
          className={`${styles.navItem} ${isActive('/stashes') ? styles.navItemActive : ''}`}
          onClick={() => navigate('/stashes')}
          aria-label="Stashes"
          aria-current={isActive('/stashes') ? 'page' : undefined}
        >
          Stashes
        </button>
      </nav>
      {user && (
        <div className={`${styles.userSection} ${drawerOpen ? styles.userSectionOpen : ''}`}>
          <div className={styles.userInfo}>
            <span className={styles.username} title={user.email}>
              {user.email}
            </span>
          </div>
          <PushToggle />
          <button
            onClick={handleLogout}
            className={styles.logoutButton}
            aria-label="Logout"
            title="Logout"
          >
            ⎋
          </button>
        </div>
      )}
    </aside>
  );
}
