import React, { useState, useEffect, useCallback, useRef } from 'react';
import { NoteResponseDto } from '../types/api';
import { api } from '../api';
import { formatDateTime } from '../utils/date';

interface NotesListProps {
  onNoteClick: (noteId: string) => void;
  onNewNote: () => void;
}

const PAGE_SIZE = 10;

export function NotesList({ onNoteClick, onNewNote }: NotesListProps) {
  const [notes, setNotes] = useState<NoteResponseDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [hasMore, setHasMore] = useState(true);
  const [offset, setOffset] = useState(0);
  const [searchInput, setSearchInput] = useState('');
  const [activeSearch, setActiveSearch] = useState<string | null>(null);
  const observerTarget = useRef<HTMLDivElement>(null);

  const loadNotes = useCallback((isInitial: boolean = false) => {
    const currentOffset = isInitial ? 0 : offset;
    const isFirstLoad = isInitial || offset === 0;

    (isFirstLoad ? setLoading : setLoadingMore)(true);

    (activeSearch
      ? api.notes.search(activeSearch, PAGE_SIZE, currentOffset)
      : api.notes.list(PAGE_SIZE, currentOffset)
    )
      .then((data) => {
        const newNotes = isInitial ? data.data : [...notes, ...data.data];
        setNotes(newNotes);
        setOffset(currentOffset + PAGE_SIZE);
        setHasMore(currentOffset + PAGE_SIZE < data.pagination.total);
      })
      .catch((err: unknown) => {
        setError(err instanceof Error ? err.message : 'Failed to load notes');
      })
      .finally(() => {
        if (isFirstLoad) setLoading(false);
        else setLoadingMore(false);
      });
  }, [notes, offset, activeSearch]);

  const handleSearch = useCallback((query: string) => {
    setOffset(0);
    if (query.trim()) {
      setActiveSearch(query);
    } else {
      setActiveSearch(null);
    }
  }, []);

  const handleSearchKeyDown = useCallback((e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      handleSearch((e.target as HTMLInputElement).value);
    }
  }, [handleSearch]);

  const handleClearSearch = useCallback(() => {
    setOffset(0);
    setSearchInput('');
    setActiveSearch(null);
  }, []);

  useEffect(() => {
    loadNotes(true);
  }, [activeSearch]);

  useEffect(() => {
    const observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && hasMore && !loading && !loadingMore) {
        loadNotes(false);
      }
    }, { threshold: 0.1 });

    if (observerTarget.current) {
      observer.observe(observerTarget.current);
    }

    return () => observer.disconnect();
  }, [hasMore, loading, loadingMore, loadNotes]);

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <h2 style={{ marginBottom: 0 }}>Notes</h2>
        <button className="btn btn-primary" onClick={onNewNote}>New note</button>
      </div>

      <div style={{ display: 'flex', gap: '8px', marginBottom: '20px' }}>
        <input
          type="text"
          placeholder="Search notes..."
          value={searchInput}
          onChange={(e) => setSearchInput(e.target.value)}
          onKeyDown={handleSearchKeyDown}
          style={{
            flex: 1,
            padding: '8px 12px',
            borderRadius: '4px',
            border: '1px solid var(--color-border)',
            backgroundColor: 'var(--color-bg)',
            color: 'var(--color-text)',
            fontSize: '0.9rem',
            fontFamily: 'inherit',
          }}
        />
        <button
          className="btn btn-primary"
          onClick={() => handleSearch(searchInput)}
          style={{ whiteSpace: 'nowrap' }}
        >
          Search
        </button>
        {activeSearch && (
          <button
            className="btn"
            onClick={handleClearSearch}
            style={{ whiteSpace: 'nowrap' }}
          >
            Clear
          </button>
        )}
      </div>

      {error && (
        <div className="error-message">
          {error}
        </div>
      )}

      {loading ? (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="skeleton-card" style={{ height: '100px' }}>
              <div className="skeleton skeleton-text" style={{ height: '1rem', marginBottom: '8px', width: '60%' }} />
              <div className="skeleton skeleton-text tiny" style={{ marginBottom: '6px' }} />
              <div className="skeleton skeleton-text tiny" />
            </div>
          ))}
        </div>
      ) : notes.length > 0 ? (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
          {notes.map((note, index) => (
            <div
              key={note.id}
              className="card"
              onClick={() => onNoteClick(note.id)}
              data-stagger-index={index}
              style={{
                padding: '16px',
                cursor: 'pointer',
                animationDelay: `${index * 30}ms`,
                display: 'flex',
                flexDirection: 'column',
                gap: '8px',
              }}
            >
              <div style={{
                fontSize: '1rem',
                fontWeight: 600,
                color: 'var(--color-text)',
                lineHeight: 1.3,
              }}>
                {note.title}
              </div>
              <div style={{
                fontSize: '0.85rem',
                color: 'var(--color-text-secondary)',
                lineHeight: 1.4,
                display: '-webkit-box',
                WebkitLineClamp: 2,
                WebkitBoxOrient: 'vertical',
                overflow: 'hidden',
              }}>
                {note.content}
              </div>
              <div style={{
                fontSize: '0.75rem',
                color: 'var(--color-text-secondary)',
                marginTop: '4px',
              }}>
                {formatDateTime(note.updatedAt)}
              </div>
            </div>
          ))}

          {loadingMore && (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              {Array.from({ length: 3 }).map((_, i) => (
                <div key={`loading-${i}`} className="skeleton-card" style={{ height: '100px' }}>
                  <div className="skeleton skeleton-text" style={{ height: '1rem', marginBottom: '8px', width: '60%' }} />
                  <div className="skeleton skeleton-text tiny" style={{ marginBottom: '6px' }} />
                  <div className="skeleton skeleton-text tiny" />
                </div>
              ))}
            </div>
          )}

          <div ref={observerTarget} style={{ height: '20px', marginTop: '20px' }} />
        </div>
      ) : (
        <div style={{
          textAlign: 'center',
          padding: '40px 20px',
          color: 'var(--color-text-secondary)',
        }}>
          <p>No notes yet.</p>
          <button className="btn btn-primary" onClick={onNewNote} style={{ marginTop: '16px' }}>
            Create your first note
          </button>
        </div>
      )}
    </div>
  );
}
