import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAppDispatch } from '../../../shared/store/hooks';
import { setUser, setTokens, setIsLoading } from '../../../shared/store/authSlice';
import { useLoginMutation, authApi } from '../store/api';
import { TASKS_ROUTE } from '../constants';
import { parseError } from '../../../shared/utils';
import styles from './LoginPage.module.css';

export function LoginPage() {
  const navigate = useNavigate();
  const dispatch = useAppDispatch();
  const [login, { isLoading }] = useLoginMutation();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(false);
  const [error, setErrorMsg] = useState<string | null>(null);

  const handleSubmit = async (e: React.SubmitEvent<HTMLFormElement>) => {
    e.preventDefault();
    setErrorMsg(null);

    if (!username || !password) {
      setErrorMsg('Please enter username and password');
      return;
    }

    try {
      dispatch(setIsLoading(true));
      const response = await login({ username, password }).unwrap();

      dispatch(
        setTokens({
          accessToken: response.token,
          refreshToken: response.refresh_token,
          rememberMe,
        }),
      );

      try {
        const user = await dispatch(authApi.endpoints.getCurrentUser.initiate()).unwrap();
        dispatch(setUser(user));
      } catch {
        // User data will be fetched on app init if needed
      }

      navigate(TASKS_ROUTE, { replace: true });
    } catch (err) {
      const errorResult = parseError(err);
      setErrorMsg(errorResult.message);
    } finally {
      dispatch(setIsLoading(false));
    }
  };

  return (
    <div className={styles.container}>
      <div className={styles.card}>
        <h1 className={styles.title}>Noto</h1>

        <form onSubmit={handleSubmit} className={styles.form}>
          {error && <div className={styles.errorMessage}>{error}</div>}

          <input
            id="username"
            type="text"
            value={username}
            onChange={(e) => setUsername(e.target.value)}
            placeholder="Email"
            className={styles.input}
            disabled={isLoading}
            required
            autoFocus
            aria-label="Email"
          />

          <input
            id="password"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="Password"
            className={styles.input}
            disabled={isLoading}
            required
            aria-label="Password"
          />

          <div className={styles.rememberMe}>
            <input
              id="remember"
              type="checkbox"
              checked={rememberMe}
              onChange={(e) => setRememberMe(e.target.checked)}
              disabled={isLoading}
              aria-label="Remember me on this device"
            />
            <label htmlFor="remember" className={styles.rememberLabel}>
              Remember me
            </label>
          </div>

          <button type="submit" className={styles.submitButton} disabled={isLoading}>
            {isLoading ? 'Logging in…' : 'Login'}
          </button>
        </form>
      </div>
    </div>
  );
}
