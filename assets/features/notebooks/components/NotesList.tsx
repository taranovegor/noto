import { useNavigate } from 'react-router-dom';
import { SquarePen } from 'lucide-react';
import {
  formatDateTime,
  parseError,
  shouldShowSkeleton,
  renderPlainText,
} from '../../../shared/utils';
import { useNotes } from '../store/api';
import { useInfiniteScroll, useIsDataStale } from '../../../shared/hooks';
import { NotesListSkeleton } from './NotesListSkeleton';
import { NotesLoadingMoreSkeleton } from './NotesLoadingMoreSkeleton';

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
      <div className={styles.sectionHeader}>
        <h2 className={styles.sectionTitle}>Notes</h2>
        <button
          className="btn btn-primary btn-icon hide-on-mobile"
          onClick={() => navigate(`/notebooks/${notebookId}/notes/new`)}
          aria-label="New note"
        >
          <SquarePen size={16} strokeWidth={1.75} />
        </button>
      </div>

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
              onClick={() => navigate(`/notebooks/${notebookId}/notes/${note.id}`)}
              role="listitem"
            >
              <div className={styles.cardTitle}>{note.title || 'Untitled'}</div>
              <div className={styles.cardPreview}>
                {note.content ? renderPlainText(note.content) : ''}
              </div>
              <div className={styles.cardDate}>{formatDateTime(note.updatedAt)}</div>
            </button>
          ))}

          {isFetchingNextPage && <NotesLoadingMoreSkeleton />}

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
