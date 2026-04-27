import { useNavigate } from 'react-router-dom';
import { formatDateTime, parseError, isInitialOrRefetch } from '../../../shared/utils';
import { useAppSelector } from '../../../shared/store/hooks';
import { useNotes } from '../store/api';
import { useInfiniteScroll, useStaggerStyles } from '../../../shared/hooks';
import { NotesListSkeleton } from './NotesListSkeleton';
import { NotesLoadingMoreSkeleton } from './NotesLoadingMoreSkeleton';

import styles from './NotesList.module.css';

export function NotesList() {
  const navigate = useNavigate();
  const activeSearch = useAppSelector((state) => state.ui.notesActiveSearch);

  const { data, isLoading, isFetching, isFetchingNextPage, hasNextPage, fetchNextPage, error } =
    useNotes(activeSearch);

  const notes = data?.pages.flatMap((p) => p.notes) ?? [];

  const { sentinelRef } = useInfiniteScroll(
    hasNextPage ?? false,
    isFetchingNextPage,
    fetchNextPage,
  );

  const errMsg = error ? parseError(error).message : null;

  const staggerStyles = useStaggerStyles(notes.length);

  return (
    <>
      {errMsg && (
        <div className="error-message" role="alert">
          {errMsg}
        </div>
      )}

      {isInitialOrRefetch(isLoading, isFetching, isFetchingNextPage) ? (
        <NotesListSkeleton />
      ) : notes.length > 0 ? (
        <div className={styles.list} role="list">
          {notes.map((note, index) => (
            <button
              key={note.id}
              className={`card ${styles.card}`}
              onClick={() => navigate(`/notes/${note.id}`)}
              style={staggerStyles[index]}
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
