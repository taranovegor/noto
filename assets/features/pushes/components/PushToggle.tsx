import React from 'react';
import { BellRing, BellOff } from 'lucide-react';
import { usePushSubscription } from '../hooks/usePushSubscription';
import styles from './PushToggle.module.css';

export function PushToggle() {
  const { isSupported, isSubscribed, isLoading, subscribe, unsubscribe } = usePushSubscription();

  if (!isSupported) return null;

  const handleToggle = async () => {
    if (isSubscribed) {
      await unsubscribe();
    } else {
      await subscribe();
    }
  };

  return (
    <button
      type="button"
      className={`${styles.toggle} ${isSubscribed ? styles.toggleOn : ''}`}
      onClick={handleToggle}
      disabled={isLoading}
      aria-label={isSubscribed ? 'Disable push notifications' : 'Enable push notifications'}
      aria-pressed={isSubscribed}
    >
      {isSubscribed ? <BellRing size={16} /> : <BellOff size={16} />}
    </button>
  );
}
