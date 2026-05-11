export function shouldShowSkeleton(
  isLoading: boolean,
  isFetching: boolean,
  isFetchingNextPage: boolean,
  hasData: boolean,
): boolean {
  return !hasData && (isLoading || (isFetching && !isFetchingNextPage));
}
