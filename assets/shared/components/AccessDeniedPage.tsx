import styles from './AccessDeniedPage.module.css';

export function AccessDeniedPage() {
  return (
    <div className={styles.wrap}>
      <div className={styles.card}>
        <h1 className={styles.title}>Access not set up</h1>
        <p className={styles.message}>
          You&apos;re signed in with Cloudflare Access, but this account isn&apos;t set up in Noto
          yet. Ask an administrator to run <code>app:user:create</code> for your email.
        </p>
        <button className={styles.reloadBtn} onClick={() => window.location.reload()}>
          Reload
        </button>
      </div>
    </div>
  );
}
