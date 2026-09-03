import { useNavigate } from 'react-router-dom';
import {
  formatDateTime,
  parseError,
  shouldShowSkeleton,
  renderPlainText,
} from '../../../shared/utils';
import { useAppSelector } from '../../../shared/store/hooks';
import { useMemos } from '../store/api';
import { useInfiniteScroll, useIsDataStale } from '../../../shared/hooks';
import { ListCardSkeleton } from '../../../shared/components';

import styles from './MemosList.module.css';

function extractMemoTitle(content: string): { title: string; body: string } {
  const lines = content.split('\n');
  const firstLine = lines[0] ?? '';
  const rawTitle = firstLine.startsWith('# ')
    ? firstLine.slice(2).trim()
    : firstLine.trim() || 'Untitled';
  const title = renderPlainText(rawTitle);
  const body = lines.slice(1).join('\n');
  return { title, body };
}

export function MemosList() {
  const navigate = useNavigate();
  const activeSearch = useAppSelector((state) => state.ui.memosActiveSearch);

  const { data, isLoading, isFetching, isFetchingNextPage, hasNextPage, fetchNextPage, error } =
    useMemos(activeSearch);

  const isDataStale = useIsDataStale(activeSearch, isFetching);

  const memos = data?.pages.flatMap((p) => p.memos) ?? [];

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
        <ListCardSkeleton count={5} />
      ) : memos.length > 0 ? (
        <div className={styles.list} role="list">
          {memos.map((memo) => {
            const { title, body } = extractMemoTitle(memo.content);

            return (
              <button
                key={memo.id}
                className={`card ${styles.card}`}
                onClick={() => navigate(`/memos/${memo.id}`)}
                role="listitem"
              >
                <div className={styles.cardTitle}>{title}</div>
                <div className={styles.cardPreview}>{body ? renderPlainText(body) : ''}</div>
                <div className={styles.cardDate}>{formatDateTime(memo.updatedAt)}</div>
              </button>
            );
          })}

          {isFetchingNextPage && <ListCardSkeleton count={3} />}

          <div ref={sentinelRef} className={styles.observerSentinel} />
        </div>
      ) : (
        <div className="empty-state">
          <p>No memos yet.</p>
          <button
            className={`btn btn-primary ${styles.emptyButton}`}
            onClick={() => navigate('/memos/new')}
          >
            New memo
          </button>
        </div>
      )}
    </>
  );
}
