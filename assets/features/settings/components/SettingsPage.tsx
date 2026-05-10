import { useNavigate } from 'react-router-dom';
import { LogOut } from 'lucide-react';
import { useAppDispatch, useAppSelector } from '../../../shared/store/hooks';
import { logout } from '../../../shared/store/authSlice';
import { useLogoutMutation } from '../../auth/store/api';
import { useAuth } from '../../auth/hooks/useAuth';
import { PushToggle } from '../../pushes';
import { LOGIN_ROUTE } from '../../auth/constants';
import { usePushSubscription } from '../../pushes/hooks/usePushSubscription';
import styles from './SettingsPage.module.css';

export function SettingsPage() {
  const navigate = useNavigate();
  const dispatch = useAppDispatch();
  const { user } = useAuth();
  const refreshToken = useAppSelector((state) => state.auth.refreshToken);
  const [logoutApi, { isLoading }] = useLogoutMutation();
  const { isSupported } = usePushSubscription();

  const handleLogout = async () => {
    try {
      if (refreshToken) {
        await logoutApi({ refresh_token: refreshToken }).unwrap();
      }
    } catch {
      // proceed with local logout regardless
    } finally {
      dispatch(logout());
      navigate(LOGIN_ROUTE, { replace: true });
    }
  };

  return (
    <div className={styles.page}>
      <h2 className={styles.title}>Settings</h2>

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
        <button className={styles.logoutBtn} onClick={handleLogout} disabled={isLoading}>
          <LogOut size={16} strokeWidth={1.75} />
          Log out
        </button>
      </div>
    </div>
  );
}
