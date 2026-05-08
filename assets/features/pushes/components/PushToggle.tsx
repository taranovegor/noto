import React from 'react';
import { usePushSubscription } from '../hooks/usePushSubscription';
import styles from './PushToggle.module.css';

export function PushToggle() {
  const { isSupported, isSubscribed, isLoading, error, subscribe, unsubscribe } =
    usePushSubscription();

  if (!isSupported) return null;

  const handleToggle = async () => {
    if (isSubscribed) {
      await unsubscribe();
    } else {
      await subscribe();
    }
  };

  const label = isSubscribed ? 'Push on' : 'Push off';

  return (
    <div className={styles.wrapper}>
      <span className={styles.label}>Push</span>
      <button
        type="button"
        className={`${styles.toggle} ${isSubscribed ? styles.toggleOn : ''}`}
        onClick={handleToggle}
        disabled={isLoading}
        aria-label={label}
        aria-pressed={isSubscribed}
      />
      {error && <span className={styles.error}>{error}</span>}
    </div>
  );
}
