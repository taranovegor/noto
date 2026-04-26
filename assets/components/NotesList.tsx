import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { formatDateTime } from '../utils/date';
import { useAppDispatch, useAppSelector } from '../store/hooks';
import { loadNotes, loadMoreNotes, setActiveSearch, setScrollPosition } from '../store/slices/notes';

export function NotesList() {
  const navigate = useNavigate();
  const location = useLocation();
  const dispatch = useAppDispatch();
  const { notes, loading, loadingMore, error, hasMore, initialized, offset, activeSearch, lastSearchQuery, scrollPosition } = useAppSelector(state => state.notes);
  const [searchInput, setSearchInput] = useState('');
  const observerTarget = useRef<HTMLDivElement>(null);

  useEffect(() => {
    setSearchInput(activeSearch || '');
  }, [activeSearch]);

  useEffect(() => {
    if (!initialized && !activeSearch) {
      dispatch(loadNotes(null));
    }
  }, [dispatch, initialized, activeSearch]);

  useEffect(() => {
    if (activeSearch !== null && activeSearch !== lastSearchQuery) {
      dispatch(loadNotes(activeSearch));
    }
  }, [activeSearch, lastSearchQuery, dispatch]);


  useEffect(() => {
    const observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && hasMore && !loadingMore) {
        dispatch(loadMoreNotes({ search: activeSearch, offset }));
      }
    }, { threshold: 0.1 });

    if (observerTarget.current) observer.observe(observerTarget.current);
    return () => observer.disconnect();
  }, [hasMore, loadingMore, offset, activeSearch, dispatch]);

  const handleSearch = (query: string) => {
    const trimmed = query.trim() || null;
    setSearchInput(query);
    dispatch(setActiveSearch(trimmed));
  };

  const handleSearchKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') handleSearch((e.target as HTMLInputElement).value);
  };

  const handleClearSearch = () => {
    setSearchInput('');
    dispatch(setActiveSearch(null));
  };

  useEffect(() => {
    const handleScroll = () => {
      dispatch(setScrollPosition(window.scrollY));
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, [dispatch]);

  useEffect(() => {
    if (scrollPosition > 0) {
      window.scrollTo(0, scrollPosition);
    }
  }, []);

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <h2 style={{ marginBottom: 0 }}>Notes</h2>
        <button className="btn btn-primary" onClick={() => navigate('/notes/new')}>New note</button>
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
          <button className="btn" onClick={handleClearSearch} style={{ whiteSpace: 'nowrap' }}>
            Clear
          </button>
        )}
      </div>

      {error && <div className="error-message">{error}</div>}

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
              onClick={() => navigate(`/notes/${note.id}`)}
              data-stagger-index={index}
              style={{ padding: '16px', cursor: 'pointer', animationDelay: `${index * 30}ms`, display: 'flex', flexDirection: 'column', gap: '8px' }}
            >
              <div style={{ fontSize: '1rem', fontWeight: 600, color: 'var(--color-text)', lineHeight: 1.3 }}>
                {note.title}
              </div>
              <div style={{ fontSize: '0.85rem', color: 'var(--color-text-secondary)', lineHeight: 1.4, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                {note.content}
              </div>
              <div style={{ fontSize: '0.75rem', color: 'var(--color-text-secondary)', marginTop: '4px' }}>
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
        <div style={{ textAlign: 'center', padding: '40px 20px', color: 'var(--color-text-secondary)' }}>
          <p>No notes yet.</p>
          <button className="btn btn-primary" onClick={() => navigate('/notes/new')} style={{ marginTop: '16px' }}>
            Create your first note
          </button>
        </div>
      )}
    </div>
  );
}
