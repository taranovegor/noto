import { useEffect, RefObject } from 'react';

export function useAutoResize(ref: RefObject<HTMLTextAreaElement | null>, value: string): void {
  useEffect(() => {
    if (ref.current) {
      ref.current.style.height = 'auto';
      ref.current.style.height = ref.current.scrollHeight + 'px';
    }
  }, [value, ref]);
}
