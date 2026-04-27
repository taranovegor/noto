import { useMemo } from 'react';

export function useStaggerStyles(length: number, delayMs = 30): React.CSSProperties[] {
  return useMemo(
    () => Array.from({ length }, (_, i) => ({ '--stagger-delay': `${i * delayMs}ms` })),
    [length, delayMs],
  );
}
