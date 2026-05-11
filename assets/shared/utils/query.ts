export function shouldShowSkeleton(
  isLoading: boolean,
  isFetching: boolean,
  isFetchingNextPage: boolean,
  hasData: boolean,
): boolean {
  // isLoading means no cached entry for the current args — always show skeleton.
  // hasData guards the background-refetch case: skip skeleton when cached data exists.
  return isLoading || (!hasData && isFetching && !isFetchingNextPage);
}
