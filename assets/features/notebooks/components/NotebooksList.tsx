import { useNavigate } from 'react-router-dom';
import { parseError, shouldShowSkeleton } from '../../../shared/utils';
import { useAppSelector } from '../../../shared/store/hooks';
import { useNotebooks } from '../store/api';
import { useInfiniteScroll, useIsDataStale } from '../../../shared/hooks';
import { ListCardSkeleton, ListRow } from '../../../shared/components';

import styles from './NotebooksList.module.css';

export function NotebooksList() {
  const navigate = useNavigate();
  const activeSearch = useAppSelector((state) => state.ui.notebooksActiveSearch);

  const { data, isLoading, isFetching, isFetchingNextPage, hasNextPage, fetchNextPage, error } =
    useNotebooks(activeSearch);

  const isDataStale = useIsDataStale(activeSearch, isFetching);

  const notebooks = data?.pages.flatMap((p) => p.notebooks) ?? [];

  const { sentinelRef } = useInfiniteScroll(
    hasNextPage ?? false,
    isFetchingNextPage,
    fetchNextPage,
  );

  const errorMessage = error ? parseError(error).message : null;

  return (
    <>
      {errorMessage && (
        <div className="error-message" role="alert">
          {errorMessage}
        </div>
      )}

      {shouldShowSkeleton(isLoading, isFetching, isFetchingNextPage, !!data && !isDataStale) ? (
        <div className={styles.list}>
          <ListCardSkeleton count={5} showDate={false} />
        </div>
      ) : notebooks.length > 0 ? (
        <div className={styles.list} role="list">
          {notebooks.map((notebook, i) => (
            <ListRow
              key={notebook.id}
              title={notebook.title}
              description={notebook.description}
              last={i === notebooks.length - 1 && !isFetchingNextPage}
              onClick={() => navigate(`/notebooks/${notebook.id}`)}
            />
          ))}

          {isFetchingNextPage && <ListCardSkeleton count={3} showDate={false} />}

          <div ref={sentinelRef} className={styles.observerSentinel} />
        </div>
      ) : (
        <div className="empty-state">
          <p>
            A notebook is a place you return to — a course, a book, a topic. Give it a name and
            start filling it with notes.
          </p>
          <button
            className={`btn btn-primary ${styles.emptyButton}`}
            onClick={() => navigate('/notebooks/new')}
          >
            New notebook
          </button>
        </div>
      )}
    </>
  );
}
