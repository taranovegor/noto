import { useNavigate } from 'react-router-dom';
import { formatDateTime, parseError, shouldShowSkeleton } from '../../../shared/utils';
import { useAppSelector } from '../../../shared/store/hooks';
import { useNotes } from '../store/api';
import { useInfiniteScroll, useIsDataStale } from '../../../shared/hooks';
import { NotesListSkeleton } from './NotesListSkeleton';
import { NotesLoadingMoreSkeleton } from './NotesLoadingMoreSkeleton';

import styles from './NotesList.module.css';

export function NotesList() {
  const navigate = useNavigate();
  const activeSearch = useAppSelector((state) => state.ui.notesActiveSearch);

  const { data, isLoading, isFetching, isFetchingNextPage, hasNextPage, fetchNextPage, error } =
    useNotes(activeSearch);

  const isDataStale = useIsDataStale(activeSearch, isFetching);

  const notes = data?.pages.flatMap((p) => p.notes) ?? [];

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
        <NotesListSkeleton />
      ) : notes.length > 0 ? (
        <div className={styles.list} role="list">
          {notes.map((note) => (
            <button
              key={note.id}
              className={`card ${styles.card}`}
              onClick={() => navigate(`/notes/${note.id}`)}
              role="listitem"
            >
              <div className={styles.cardTitle}>{note.title}</div>
              <div className={styles.cardPreview}>{note.content}</div>
              <div className={styles.cardDate}>{formatDateTime(note.updatedAt)}</div>
            </button>
          ))}

          {isFetchingNextPage && <NotesLoadingMoreSkeleton />}

          <div ref={sentinelRef} className={styles.observerSentinel} />
        </div>
      ) : (
        <div className="empty-state">
          <p>No notes yet.</p>
          <button
            className={`btn btn-primary ${styles.emptyButton}`}
            onClick={() => navigate('/notes/new')}
          >
            New note
          </button>
        </div>
      )}
    </>
  );
}
