import { LogOut } from 'lucide-react';
import { useAuth } from '../../auth/hooks/useAuth';
import { PushToggle } from '../../pushes';
import { usePushSubscription } from '../../pushes/hooks/usePushSubscription';
import { PageShell } from '../../../shared/components/PageShell';
import styles from './SettingsPage.module.css';

export function SettingsPage() {
  const { user } = useAuth();
  const { isSupported } = usePushSubscription();

  const handleLogout = () => {
    const issuer = document.querySelector('meta[name="oauth-issuer"]')?.getAttribute('content');
    if (issuer) {
      window.location.href = `${issuer}/cdn-cgi/access/logout`;
    }
  };

  return (
    <PageShell title="Settings">
      <div className={styles.section}>
        {user && (
          <div className={styles.row}>
            <span className={styles.label}>Account</span>
            <span className={styles.value}>{user.email}</span>
          </div>
        )}

        {isSupported && (
          <div className={styles.row}>
            <span className={styles.label}>Push notifications</span>
            <PushToggle />
          </div>
        )}
      </div>

      <div className={styles.footer}>
        <button className={styles.logoutBtn} onClick={handleLogout}>
          <LogOut size={16} strokeWidth={1.75} />
          Log out
        </button>
      </div>
    </PageShell>
  );
}
