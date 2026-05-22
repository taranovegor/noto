import { useState, useEffect, useRef } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { Menu, X, Settings } from 'lucide-react';
import { useAuth } from '../features/auth/hooks/useAuth';
import { useActionBarConfig } from './ActionBarContext';
import styles from './Sidebar.module.css';

// ─── Desktop sidebar ──────────────────────────────────────────────────────────

function DesktopSidebar() {
  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useAuth();
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
          aria-current={isActive('/tasks') ? 'page' : undefined}
        >
          Tasks
        </button>
        <button
          className={`${styles.navItem} ${isActive('/memos') ? styles.navItemActive : ''}`}
          onClick={() => navigate('/memos')}
          aria-current={isActive('/memos') ? 'page' : undefined}
        >
          Memos
        </button>
        <button
          className={`${styles.navItem} ${isActive('/stashes') ? styles.navItemActive : ''}`}
          onClick={() => navigate('/stashes')}
          aria-current={isActive('/stashes') ? 'page' : undefined}
        >
          Stashes
        </button>
      </nav>
      {user && (
        <div className={styles.userSection}>
          <span className={styles.username} title={user.email}>
            {user.email}
          </span>
          <button
            onClick={() => navigate('/settings')}
            className={styles.settingsButton}
            aria-label="Settings"
            title="Settings"
          >
            <Settings size={14} strokeWidth={1.75} />
          </button>
        </div>
      )}
    </aside>
  );
}

// ─── Mobile ActionBar ─────────────────────────────────────────────────────────

const NAV_ITEMS = [
  { path: '/tasks', label: 'Tasks' },
  { path: '/memos', label: 'Memos' },
  { path: '/stashes', label: 'Stashes' },
  { path: '/settings', label: 'Settings' },
];

function MobileActionBar() {
  const config = useActionBarConfig();
  const navigate = useNavigate();
  const location = useLocation();

  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const actionBarRef = useRef<HTMLDivElement>(null);
  const wrapRef = useRef<HTMLDivElement>(null);

  const isHidden = config === null;

  const isInputOpen = !!config?.input;
  const isExpanded = isMenuOpen || isInputOpen;

  // Keep ActionBar above iOS keyboard
  useEffect(() => {
    const vv = window.visualViewport;
    if (!vv) return;
    const update = () => {
      const el = wrapRef.current;
      if (!el) return;
      const offset = Math.max(0, window.innerHeight - vv.offsetTop - vv.height);
      el.style.transform = offset > 0 ? `translateY(${-offset}px)` : '';
    };
    vv.addEventListener('resize', update);
    vv.addEventListener('scroll', update);
    return () => {
      vv.removeEventListener('resize', update);
      vv.removeEventListener('scroll', update);
    };
  }, []);

  // Close menu on route change
  useEffect(() => {
    setIsMenuOpen(false);
  }, [location.pathname]);

  // Close menu on outside click
  useEffect(() => {
    if (!isMenuOpen) return;
    const handler = (e: MouseEvent) => {
      if (actionBarRef.current && !actionBarRef.current.contains(e.target as Node)) {
        setIsMenuOpen(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [isMenuOpen]);

  const activeSection = location.pathname.split('/')[1];
  const isActive = (path: string) => path.split('/')[1] === activeSection;

  return (
    <div
      ref={wrapRef}
      className={`${styles.actionBarWrap} ${isHidden ? styles.actionBarHidden : ''}`}
    >
      <div
        ref={actionBarRef}
        className={`${styles.actionBar} ${isExpanded ? styles.actionBarExpanded : ''}`}
      >
        {/* Left button: back (editor mode) or menu (default) */}
        {!isExpanded &&
          (config?.backButton ? (
            <button
              className={styles.actionBarBtn}
              onClick={config.backButton.onPress}
              aria-label={config.backButton.label}
            >
              <config.backButton.icon size={20} strokeWidth={1.75} />
            </button>
          ) : (
            <button
              className={styles.actionBarBtn}
              onClick={() => setIsMenuOpen(true)}
              aria-label="Navigation menu"
            >
              <Menu size={20} strokeWidth={1.75} />
            </button>
          ))}

        {/* Nav list — shown when menu is open */}
        {isMenuOpen && (
          <>
            <nav className={styles.actionBarList}>
              {NAV_ITEMS.map(({ path, label }) => (
                <button
                  key={path}
                  className={`${styles.actionBarItem} ${isActive(path) ? styles.actionBarItemActive : ''}`}
                  onClick={() => {
                    navigate(path);
                    setIsMenuOpen(false);
                  }}
                  aria-current={isActive(path) ? 'page' : undefined}
                >
                  {label}
                </button>
              ))}
            </nav>
            <button
              className={styles.actionBarCloseBtn}
              onClick={() => setIsMenuOpen(false)}
              aria-label="Close"
            >
              <X size={16} strokeWidth={1.75} />
            </button>
          </>
        )}

        {/* Feature buttons — shown when not expanded */}
        {!isMenuOpen &&
          !isInputOpen &&
          config?.buttons.map((btn) => (
            <button
              key={btn.label}
              className={`${styles.actionBarBtn} ${btn.primary ? styles.actionBarBtnPrimary : ''}`}
              onClick={btn.onPress}
              disabled={btn.disabled}
              aria-label={btn.label}
            >
              <btn.icon size={20} strokeWidth={1.75} />
            </button>
          ))}

        {/* Input form — always in DOM so focus() works on iOS after flushSync */}
        <form
          className={styles.actionBarSearch}
          style={{ display: isInputOpen && !isMenuOpen ? 'flex' : 'none' }}
          onSubmit={(e) => {
            e.preventDefault();
            config?.input?.onSubmit();
          }}
        >
          <input
            ref={config?.input?.ref}
            className={styles.actionBarSearchInput}
            value={config?.input?.value ?? ''}
            placeholder={config?.input?.placeholder}
            disabled={config?.input?.disabled}
            onChange={(e) => config?.input?.onChange(e.target.value)}
            aria-label={config?.input?.placeholder ?? 'Input'}
          />
          <button
            type="button"
            className={styles.actionBarBtn}
            onClick={config?.input?.onClose}
            aria-label="Close"
          >
            <X size={18} strokeWidth={1.75} />
          </button>
        </form>
      </div>
    </div>
  );
}

// ─── Export ───────────────────────────────────────────────────────────────────

export function Sidebar() {
  return (
    <>
      <DesktopSidebar />
      <MobileActionBar />
    </>
  );
}
