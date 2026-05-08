import { useEffect, useRef } from 'react';
import { useCentrifuge } from './CentrifugoProvider';

const CHANNEL_PREFIX = 'noto';

/**
 * Subscribe to broadcast events for a given entity namespace.
 * The channel format matches the backend Broadcaster: `noto-{namespace}:events`
 */
export function useCentrifugeSubscription(
  namespace: string,
  onPublication: (data: Record<string, unknown>) => void,
): void {
  const centrifuge = useCentrifuge();
  const callbackRef = useRef(onPublication);
  callbackRef.current = onPublication;

  useEffect(() => {
    if (!centrifuge) return;

    const channel = `${CHANNEL_PREFIX}-${namespace}:events`;
    const sub = centrifuge.newSubscription(channel);

    sub.on('publication', (ctx) => {
      callbackRef.current(ctx.data as Record<string, unknown>);
    });

    sub.subscribe();

    return () => {
      sub.unsubscribe();
      centrifuge.removeSubscription(sub);
    };
  }, [centrifuge, namespace]);
}
