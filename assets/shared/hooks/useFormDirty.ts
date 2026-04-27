import { useState, useMemo, useCallback } from 'react';

export function useFormDirty<T>(current: T): {
  isDirty: boolean;
  markSaved: (saved: T) => void;
} {
  const [baseline, setBaseline] = useState<string | null>(null);

  const isDirty = useMemo(
    () => baseline !== null && JSON.stringify(current) !== baseline,
    [current, baseline],
  );

  const markSaved = useCallback((saved: T) => {
    setBaseline(JSON.stringify(saved));
  }, []);

  return { isDirty, markSaved };
}
