import { useState, useEffect, useCallback, useRef } from 'react';
import { SW_PATH, VAPID_PUBLIC_KEY_META } from '../constants';
import { useSubscribePushMutation, useUnsubscribePushMutation } from '../store/api';
import { setSourceChecksum } from '../store/slice';
import { endpointChecksum } from '../utils';
import { useAppDispatch } from '../../../shared/store/hooks';
import type { PushPermissionState, PushSubscriptionData } from '../types';

function resolvePermission(state: NotificationPermission): PushPermissionState {
  if (state === 'granted') return 'granted';
  if (state === 'denied') return 'denied';
  return 'prompt';
}

function encodeServerKey(serverKey: string): Uint8Array {
  const padding = '='.repeat((4 - (serverKey.length % 4)) % 4);
  const base64 = (serverKey + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; i++) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

interface UsePushSubscriptionReturn {
  isSupported: boolean;
  permission: PushPermissionState;
  isSubscribed: boolean;
  isLoading: boolean;
  error: string | null;
  subscribe: () => Promise<void>;
  unsubscribe: () => Promise<void>;
}

export function usePushSubscription(): UsePushSubscriptionReturn {
  const [isSupported, setIsSupported] = useState(false);
  const [permission, setPermission] = useState<PushPermissionState>('prompt');
  const [isSubscribed, setIsSubscribed] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const registrationRef = useRef<ServiceWorkerRegistration | null>(null);
  const applicationServerKeyRef = useRef<Uint8Array | null>(null);

  const dispatch = useAppDispatch();
  const [subscribeMutation] = useSubscribePushMutation();
  const [unsubscribeMutation] = useUnsubscribePushMutation();

  useEffect(() => {
    const hasServiceWorker = 'serviceWorker' in navigator;
    const hasPushManager =
      'PushManager' in window ||
      (hasServiceWorker && 'pushManager' in ServiceWorkerRegistration.prototype);
    const supported = hasServiceWorker && hasPushManager;

    setIsSupported(supported);
    if (!supported) return;

    const meta = document.querySelector<HTMLMetaElement>(`meta[name="${VAPID_PUBLIC_KEY_META}"]`);
    const vapidKey = meta?.content;

    if (vapidKey) {
      try {
        applicationServerKeyRef.current = encodeServerKey(vapidKey);
      } catch {
        setError('Invalid VAPID public key');
        return;
      }
    }

    const onPermissionChange = () => {
      if ('Notification' in window) {
        const state = resolvePermission(Notification.permission);
        setPermission(state);
        if (state === 'denied') {
          setIsSubscribed(false);
        } else {
          navigator.serviceWorker
            .getRegistration(SW_PATH)
            .then((r) => r?.pushManager.getSubscription())
            .then((s) => setIsSubscribed(!!s))
            .catch(() => {});
        }
      }
    };

    if ('permissions' in navigator) {
      navigator.permissions
        .query({ name: 'notifications' })
        .then((status) => {
          setPermission(status.state as PushPermissionState);
          status.addEventListener('change', onPermissionChange);
        })
        .catch(() => {
          if ('Notification' in window) {
            setPermission(resolvePermission(Notification.permission));
          }
        });
    } else if ('Notification' in window) {
      setPermission(resolvePermission(Notification.permission));
    }

    navigator.serviceWorker
      .getRegistration(SW_PATH)
      .then((reg) => {
        if (reg) {
          registrationRef.current = reg;
          return reg.pushManager.getSubscription();
        }
        return navigator.serviceWorker.register(SW_PATH).then((r) => {
          registrationRef.current = r;
          return r.pushManager.getSubscription();
        });
      })
      .then((sub) => {
        setIsSubscribed(!!sub);
        dispatch(setSourceChecksum(sub ? endpointChecksum(sub.endpoint) : null));
      })
      .catch(() => {});
  }, [dispatch]);

  const ensureServiceWorker = useCallback(async (): Promise<ServiceWorkerRegistration> => {
    if (registrationRef.current) return registrationRef.current;
    const reg = await navigator.serviceWorker.register(SW_PATH);
    registrationRef.current = reg;
    return reg;
  }, []);

  const subscribe = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      const applicationServerKey = applicationServerKeyRef.current;
      if (!applicationServerKey) {
        throw new Error('VAPID public key not available');
      }

      const registration = await ensureServiceWorker();
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: applicationServerKey as BufferSource,
      });

      const subscriptionJSON = subscription.toJSON() as unknown as PushSubscriptionData;

      try {
        await subscribeMutation({ subscription: subscriptionJSON }).unwrap();
      } catch {
        await subscription.unsubscribe();
        throw new Error('Failed to register subscription on server');
      }

      dispatch(setSourceChecksum(endpointChecksum(subscriptionJSON.endpoint)));
      setIsSubscribed(true);
      setPermission('granted');
    } catch (err) {
      if (err instanceof DOMException && err.name === 'NotAllowedError') {
        setPermission('denied');
        setError('Notification permission denied');
      } else if (err instanceof DOMException && err.name === 'AbortError') {
        setError('Push subscription aborted');
      } else {
        setError(err instanceof Error ? err.message : 'Failed to subscribe');
      }
    } finally {
      setIsLoading(false);
    }
  }, [dispatch, ensureServiceWorker, subscribeMutation]);

  const unsubscribe = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      const registration = await ensureServiceWorker();
      const subscription = await registration.pushManager.getSubscription();

      if (subscription) {
        await subscription.unsubscribe();
        const subscriptionJSON = subscription.toJSON() as unknown as PushSubscriptionData;
        await unsubscribeMutation({ subscription: subscriptionJSON }).unwrap();
      }

      dispatch(setSourceChecksum(null));
      setIsSubscribed(false);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to unsubscribe');
    } finally {
      setIsLoading(false);
    }
  }, [dispatch, ensureServiceWorker, unsubscribeMutation]);

  return {
    isSupported,
    permission,
    isSubscribed,
    isLoading,
    error,
    subscribe,
    unsubscribe,
  };
}
