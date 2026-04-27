import { useEffect } from 'react';

export function useDraftRestore<T>(
  isNew: boolean,
  restore: () => T | null,
  setForm: (form: T) => void,
  markSaved: (form: T) => void,
): void {
  useEffect(() => {
    if (isNew) {
      const savedDraft = restore();
      if (savedDraft) {
        setForm(savedDraft);
        markSaved(savedDraft);
      }
    }
  }, [isNew, restore, setForm, markSaved]);
}
