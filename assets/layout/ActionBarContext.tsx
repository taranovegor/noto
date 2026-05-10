import {
  createContext,
  useCallback,
  useContext,
  useLayoutEffect,
  useMemo,
  useReducer,
  useRef,
} from 'react';
import type { PropsWithChildren } from 'react';
import type { LucideIcon } from 'lucide-react';

export type ActionBarButton = {
  icon: LucideIcon;
  label: string;
  primary?: boolean;
  disabled?: boolean;
  onPress: () => void;
};

export type ActionBarInputConfig = {
  ref: React.RefObject<HTMLInputElement>;
  value: string;
  placeholder?: string;
  disabled?: boolean;
  onChange: (value: string) => void;
  onSubmit: () => void;
  onClose: () => void;
};

export type ActionBarConfig = {
  buttons: ActionBarButton[];
  input?: ActionBarInputConfig | null;
};

type ContextValue = {
  configRef: React.RefObject<ActionBarConfig | null>;
  subscribe: (fn: () => void) => () => void;
  setConfig: (config: ActionBarConfig | null) => void;
};

const ActionBarContext = createContext<ContextValue>({
  configRef: { current: null },
  subscribe: () => () => {},
  setConfig: () => {},
});

export function ActionBarProvider({ children }: PropsWithChildren) {
  const configRef = useRef<ActionBarConfig | null>(null);
  const listenersRef = useRef<Set<() => void>>(new Set());

  const setConfig = useCallback((config: ActionBarConfig | null) => {
    configRef.current = config;
    listenersRef.current.forEach((fn) => fn());
  }, []);

  const subscribe = useCallback((fn: () => void) => {
    listenersRef.current.add(fn);
    return () => listenersRef.current.delete(fn);
  }, []);

  const value = useMemo(() => ({ configRef, subscribe, setConfig }), [subscribe, setConfig]);

  return <ActionBarContext.Provider value={value}>{children}</ActionBarContext.Provider>;
}

export function useActionBar(config: ActionBarConfig) {
  const { setConfig } = useContext(ActionBarContext);
  const ref = useRef(config);
  ref.current = config;
  useLayoutEffect(() => {
    setConfig(ref.current);
    return () => setConfig(null);
  });
}

export function useActionBarConfig(): ActionBarConfig | null {
  const { configRef, subscribe } = useContext(ActionBarContext);
  const [, forceUpdate] = useReducer((n: number) => n + 1, 0);
  useLayoutEffect(() => subscribe(forceUpdate), [subscribe]);
  return configRef.current;
}
