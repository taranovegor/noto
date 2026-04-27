import { useEffect, useCallback } from 'react';

const DEFAULT_DELAY = 800;

export function useDraftSave<T>(
  key: string,
  data: T,
  enabled: boolean,
  delay = DEFAULT_DELAY,
): { restore: () => T | null; clear: () => void } {
  const restore = useCallback((): T | null => {
    try {
      const raw = localStorage.getItem(key);
      return raw ? (JSON.parse(raw) as T) : null;
    } catch {
      return null;
    }
  }, [key]);

  const clear = useCallback(() => {
    localStorage.removeItem(key);
  }, [key]);

  useEffect(() => {
    if (!enabled) return;

    const timer = setTimeout(() => {
      localStorage.setItem(key, JSON.stringify(data));
    }, delay);

    return () => clearTimeout(timer);
  }, [key, data, enabled, delay]);

  return { restore, clear };
}
