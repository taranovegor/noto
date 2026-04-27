import { useState, useEffect, useCallback, useMemo } from 'react';
import { ActionCreatorWithPayload } from '@reduxjs/toolkit';
import { useAppDispatch } from '../store/hooks';

export function useSearchState(
  activeSearch: string | null,
  setActive: ActionCreatorWithPayload<string | null>,
): {
  input: string;
  setInput: React.Dispatch<React.SetStateAction<string>>;
  handleSearch: (query: string) => void;
  handleClear: () => void;
} {
  const dispatch = useAppDispatch();
  const [input, setInput] = useState('');

  useEffect(() => {
    setInput(activeSearch || '');
  }, [activeSearch]);

  const handleSearch = useCallback(
    (query: string) => {
      const trimmed = query.trim() || null;
      setInput(query);
      dispatch(setActive(trimmed));
    },
    [dispatch, setActive],
  );

  const handleClear = useCallback(() => {
    setInput('');
    dispatch(setActive(null));
  }, [dispatch, setActive]);

  return useMemo(
    () => ({ input, setInput, handleSearch, handleClear }),
    [input, handleSearch, handleClear],
  );
}
