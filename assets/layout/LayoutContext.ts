import { createContext, useContext, useLayoutEffect } from 'react';

export type LayoutMode = 'scroll' | 'fixed';

export const LayoutContext = createContext<{
  setLayoutMode: (mode: LayoutMode) => void;
}>({ setLayoutMode: () => {} });

export function useLayoutMode(mode: LayoutMode) {
  const { setLayoutMode } = useContext(LayoutContext);

  useLayoutEffect(() => {
    setLayoutMode(mode);
    return () => setLayoutMode('scroll');
  }, [mode, setLayoutMode]);
}
