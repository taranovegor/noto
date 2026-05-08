import { useEffect, useRef } from 'react';
import { useCentrifuge } from './CentrifugoProvider';

const CHANNEL_PREFIX = 'noto';

type BroadcastEvent = 'created' | 'updated' | 'deleted';

interface BroadcastMeta {
  id: string;
  subject: string;
  event?: BroadcastEvent;
}

interface BroadcastMessage {
  meta: BroadcastMeta;
  data: Record<string, unknown>;
}

interface RealtimeCallbacks {
  onCreated?: (data: Record<string, unknown>) => void;
  onUpdated?: (data: Record<string, unknown>) => void;
  onDeleted?: (data: Record<string, unknown>) => void;
}

/**
 * Subscribe to broadcast events for a given entity namespace
 * and route them to typed callbacks based on meta.event.
 */
export function useRealtimeEvents(namespace: string, callbacks: RealtimeCallbacks): void {
  const centrifuge = useCentrifuge();
  const callbacksRef = useRef(callbacks);
  callbacksRef.current = callbacks;

  useEffect(() => {
    if (!centrifuge) return;

    const channel = `${CHANNEL_PREFIX}-${namespace}:events`;
    const sub = centrifuge.newSubscription(channel);

    sub.on('publication', (ctx) => {
      const msg = ctx.data as BroadcastMessage;
      const event = msg.meta?.event;
      const data = msg.data;

      if (!event || !data) return;

      const { onCreated, onUpdated, onDeleted } = callbacksRef.current;

      switch (event) {
        case 'created':
          onCreated?.(data);
          break;
        case 'updated':
          onUpdated?.(data);
          break;
        case 'deleted':
          onDeleted?.(data);
          break;
      }
    });

    sub.subscribe();

    return () => {
      sub.unsubscribe();
      centrifuge.removeSubscription(sub);
    };
  }, [centrifuge, namespace]);
}
