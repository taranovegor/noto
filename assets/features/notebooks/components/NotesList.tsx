import { useNavigate } from 'react-router-dom';
import {
  formatDateTime,
  parseError,
  shouldShowSkeleton,
  renderPlainText,
} from '../../../shared/utils';
import { useNotes } from '../store/api';
import { useInfiniteScroll, useIsDataStale } from '../../../shared/hooks';
import { ListCardSkeleton, ListRow } from '../../../shared/components';

import styles from './NotesList.module.css';

interface NotesListProps {
  notebookId: string;
  search?: string | null;
}

export function NotesList({ notebookId, search = null }: NotesListProps) {
  const navigate = useNavigate();

  const { data, isLoading, isFetching, isFetchingNextPage, hasNextPage, fetchNextPage, error } =
    useNotes({ notebookId, search });

  const isDataStale = useIsDataStale(search, isFetching);

  const notes = data?.pages.flatMap((p) => p.notes) ?? [];

  const { sentinelRef } = useInfiniteScroll(
    hasNextPage ?? false,
    isFetchingNextPage,
    fetchNextPage,
  );

  const errorMessage = error ? parseError(error).message : null;

  return (
    <div className={styles.section}>
      {errorMessage && (
        <div className="error-message" role="alert">
          {errorMessage}
        </div>
      )}

      {shouldShowSkeleton(isLoading, isFetching, isFetchingNextPage, !!data && !isDataStale) ? (
        <div className={styles.list}>
          <ListCardSkeleton count={5} />
        </div>
      ) : notes.length > 0 ? (
        <div className={styles.list} role="list">
          {notes.map((note, i) => (
            <ListRow
              key={note.id}
              title={note.title || 'Untitled'}
              description={note.content ? renderPlainText(note.content) : undefined}
              date={formatDateTime(note.updatedAt)}
              last={i === notes.length - 1 && !isFetchingNextPage}
              onClick={() => navigate(`/notebooks/${notebookId}/notes/${note.id}`)}
            />
          ))}

          {isFetchingNextPage && <ListCardSkeleton count={3} />}

          <div ref={sentinelRef} className={styles.observerSentinel} />
        </div>
      ) : search ? (
        <div className="empty-state">
          <p>No notes match this search.</p>
        </div>
      ) : (
        <div className="empty-state">
          <p>
            Start writing. Give each note a clear title so it makes sense when you come back a month
            from now.
          </p>
          <button
            className={`btn btn-primary ${styles.emptyButton}`}
            onClick={() => navigate(`/notebooks/${notebookId}/notes/new`)}
          >
            New note
          </button>
        </div>
      )}
    </div>
  );
}
