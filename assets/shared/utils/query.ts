export function isInitialOrRefetch(
  loading: boolean,
  fetching: boolean,
  fetchingNext: boolean,
): boolean {
  return loading || (fetching && !fetchingNext);
}
