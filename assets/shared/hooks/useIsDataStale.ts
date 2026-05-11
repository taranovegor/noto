import { useRef } from 'react';

/**
 * Detects whether the RTK Query `data` currently in scope was fetched for
 * a different set of arguments than the ones now active.  Returns `true`
 * from the moment `args` change until the fetch for the new args completes
 * (`isFetching` goes back to `false`).
 */
export function useIsDataStale(args: unknown, isFetching: boolean): boolean {
  const lastFetchedArgs = useRef(args);

  if (!isFetching) {
    lastFetchedArgs.current = args;
  }

  return lastFetchedArgs.current !== args;
}
