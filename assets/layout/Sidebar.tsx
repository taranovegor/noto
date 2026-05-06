import React from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../shared/store/hooks';
import { logout } from '../shared/store/authSlice';
import { useAuth } from '../features/auth/hooks/useAuth';
import { useLogoutMutation } from '../features/auth/store/api';
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

  const isActive = (path: string) => currentPath === path || currentPath.startsWith(path + '/');

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
        <div className={styles.userSection}>
          <div className={styles.userInfo}>
            <span className={styles.username} title={user.email}>
              {user.email}
            </span>
          </div>
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
