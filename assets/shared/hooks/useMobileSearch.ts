import { useRef, useState, useCallback, useEffect } from 'react';
import { flushSync } from 'react-dom';
import type { ActionCreatorWithPayload } from '@reduxjs/toolkit';
import { useAppDispatch } from '../store/hooks';

export function useMobileSearch(
  activeSearch: string | null,
  setActiveSearch: ActionCreatorWithPayload<string | null>,
) {
  const dispatch = useAppDispatch();
  const searchRef = useRef<HTMLInputElement>(null);
  const [searchOpen, setSearchOpen] = useState(false);
  const [searchValue, setSearchValue] = useState('');

  useEffect(() => {
    if (activeSearch !== null) {
      setSearchOpen(true);
      setSearchValue(activeSearch);
    } else {
      setSearchOpen(false);
      setSearchValue('');
    }
  }, [activeSearch]);

  const openSearch = useCallback(() => {
    flushSync(() => setSearchOpen(true));
    searchRef.current?.focus();
  }, []);

  const handleSubmit = useCallback(() => {
    dispatch(setActiveSearch(searchValue.trim() || null));
    searchRef.current?.blur();
  }, [searchValue, dispatch, setActiveSearch]);

  const handleClose = useCallback(() => {
    dispatch(setActiveSearch(null));
    setSearchOpen(false);
    setSearchValue('');
  }, [dispatch, setActiveSearch]);

  return {
    searchRef,
    searchOpen,
    searchValue,
    setSearchValue,
    openSearch,
    handleSubmit,
    handleClose,
  };
}
