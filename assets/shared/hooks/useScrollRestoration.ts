import { useEffect, useLayoutEffect, useRef, type RefObject } from 'react';
import { useNavigationType } from 'react-router-dom';

// Generated once per page load so sessionStorage entries don't
// survive a full-page reload, but remain valid across SPA navigation.
const sessionKey = Math.random().toString(36).slice(2, 8);

/**
 * Build a namespaced storage key compatible with `clearScrollKeys`.
 * Use this instead of raw strings to ensure the `namespace:…` format
 * is always followed.
 */
export function createScrollKey(namespace: string, ...parts: string[]): string {
  return `${namespace}:${parts.join(':')}`;
}

function getScrollPos(el: HTMLElement, horizontal: boolean): number {
  return horizontal ? el.scrollLeft : el.scrollTop;
}

function setScrollPos(el: HTMLElement, v: number, horizontal: boolean): void {
  // scrollTo with behavior:'instant' overrides CSS scroll-behavior:smooth
  if (horizontal) el.scrollTo({ left: v, behavior: 'instant' });
  else el.scrollTo({ top: v, behavior: 'instant' });
}

interface UseScrollRestorationOptions {
  /** Only restore on POP (back/forward) navigation. Default: true */
  popOnly?: boolean;
  /** When false, defer restoration until ready. Default: true */
  ready?: boolean;
  /** Scroll axis. Default: 'vertical' */
  direction?: 'vertical' | 'horizontal';
  /** When this value changes between renders, the saved position is cleared
   *  and scroll resets to 0. Use for filter/sort changes where stale scroll
   *  positions are meaningless. Must be a primitive — reference types will
   *  trigger on every render. */
  resetOnChange?: string | number | boolean | null | undefined;
}

export function useScrollRestoration(
  ref: RefObject<HTMLElement | null>,
  storageKey: string,
  options: UseScrollRestorationOptions = {},
): void {
  const { popOnly = true, ready = true, direction = 'vertical', resetOnChange } = options;
  const navigationType = useNavigationType();
  const shouldRestore = popOnly ? navigationType === 'POP' : true;
  const isHorizontal = direction === 'horizontal';

  const fullKey = `${sessionKey}:${storageKey}`;

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const handleScroll = () => {
      // Read ref.current at event time — more robust if the DOM element
      // is replaced without the effect re-running.
      const current = ref.current;
      if (current) {
        sessionStorage.setItem(fullKey, String(getScrollPos(current, isHorizontal)));
      }
    };
    el.addEventListener('scroll', handleScroll, { passive: true });
    return () => el.removeEventListener('scroll', handleScroll);
  }, [ref, fullKey, isHorizontal]);

  // Track resetOnChange across commits to clear saved position on change.
  const prevReset = useRef(resetOnChange);

  useLayoutEffect(() => {
    if (!ready) return;
    const el = ref.current;
    if (!el) return;

    // On forward navigation (PUSH/REPLACE) reset to 0 instead of restoring.
    if (!shouldRestore) {
      setScrollPos(el, 0, isHorizontal);
      return;
    }

    if (prevReset.current !== resetOnChange) {
      prevReset.current = resetOnChange;
      sessionStorage.removeItem(fullKey);
      setScrollPos(el, 0, isHorizontal);
      return;
    }

    const saved = sessionStorage.getItem(fullKey);
    if (saved === null) {
      setScrollPos(el, 0, isHorizontal);
      return;
    }

    const target = parseInt(saved, 10);
    if (isNaN(target)) return;

    setScrollPos(el, target, isHorizontal);

    // If the element isn't scrollable yet (layout hasn't settled),
    // retry before the next paint.
    if (getScrollPos(el, isHorizontal) !== target && target > 0) {
      const rafId = requestAnimationFrame(() => {
        setScrollPos(el, target, isHorizontal);
      });
      return () => cancelAnimationFrame(rafId);
    }
  }, [ready, shouldRestore, resetOnChange, fullKey, ref, isHorizontal]);
}

/**
 * Remove all sessionStorage entries under a given namespace for the
 * current page session. Only keys built with `createScrollKey(namespace, …)`
 * are affected (they match the `{sessionKey}:{namespace}:` prefix).
 */
export function clearScrollKeys(namespace: string): void {
  const prefix = `${sessionKey}:${namespace}:`;
  for (let i = sessionStorage.length - 1; i >= 0; i--) {
    const key = sessionStorage.key(i);
    if (key?.startsWith(prefix)) {
      sessionStorage.removeItem(key);
    }
  }
}
