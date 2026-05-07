import { useEffect, useState } from 'react';
import { formatRelative } from '../utils';

const INTERVAL_MS = 60_000;

export function RelativeTime({ date }: { date: string }) {
  const [, setTick] = useState(0);

  useEffect(() => {
    const id = setInterval(() => setTick((t) => t + 1), INTERVAL_MS);
    return () => clearInterval(id);
  }, []);

  return <>{formatRelative(date)}</>;
}
