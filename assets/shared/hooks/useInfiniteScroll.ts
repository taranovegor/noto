import { useEffect, useRef } from 'react';

export function useInfiniteScroll(
  hasMore: boolean,
  isFetching: boolean,
  onLoadMore: () => void,
): { sentinelRef: React.RefObject<HTMLDivElement | null> } {
  const sentinelRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const el = sentinelRef.current;
    if (!el) return;

    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting && hasMore && !isFetching) {
          onLoadMore();
        }
      },
      { threshold: 0.1 },
    );

    observer.observe(el);
    return () => observer.disconnect();
  }, [hasMore, isFetching, onLoadMore]);

  return { sentinelRef };
}
