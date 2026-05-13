import { useNavigate } from 'react-router-dom';
import {
  formatDateTime,
  parseError,
  shouldShowSkeleton,
  renderPlainText,
} from '../../../shared/utils';
import { useAppSelector } from '../../../shared/store/hooks';
import { useNotes } from '../store/api';
import { useInfiniteScroll, useIsDataStale } from '../../../shared/hooks';
import { NotesListSkeleton } from './NotesListSkeleton';
import { NotesLoadingMoreSkeleton } from './NotesLoadingMoreSkeleton';

import styles from './NotesList.module.css';

function extractNoteTitle(content: string): { title: string; body: string } {
  const lines = content.split('\n');
  const firstLine = lines[0] ?? '';
  const rawTitle = firstLine.startsWith('# ')
    ? firstLine.slice(2).trim()
    : firstLine.trim() || 'Untitled';
  const title = renderPlainText(rawTitle);
  const body = lines.slice(1).join('\n');
  return { title, body };
}

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
          {notes.map((note) => {
            const { title, body } = extractNoteTitle(note.content);

            return (
              <button
                key={note.id}
                className={`card ${styles.card}`}
                onClick={() => navigate(`/notes/${note.id}`)}
                role="listitem"
              >
                <div className={styles.cardTitle}>{title}</div>
                <div className={styles.cardPreview}>{body ? renderPlainText(body) : ''}</div>
                <div className={styles.cardDate}>{formatDateTime(note.updatedAt)}</div>
              </button>
            );
          })}

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
