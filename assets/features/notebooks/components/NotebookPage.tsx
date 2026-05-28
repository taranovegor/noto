import React, { useState, useRef, useCallback, useEffect } from 'react';
import { flushSync } from 'react-dom';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Search, SquarePen, Sparkles, MoreVertical } from 'lucide-react';
import { useGetNotebookQuery } from '../store/api';
import { useActionBar } from '../../../layout/ActionBarContext';
import { SearchBar } from '../../../shared/components';
import { useBackNavigation } from '../../../shared/hooks';
import backStyles from '../../../shared/components/BackButton.module.css';
import { NotesList } from './NotesList';
import { NotebookPageSkeleton } from './NotebookPageSkeleton';
import styles from './NotebookPage.module.css';

export function NotebookPage() {
  const { id: notebookId } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const handleBack = useBackNavigation('/notebooks');
  const { data: notebook, isLoading } = useGetNotebookQuery(notebookId ?? '');

  const [notesSearchInput, setNotesSearchInput] = useState('');
  const [notesSearch, setNotesSearch] = useState<string | null>(null);
  const [menuOpen, setMenuOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  const mobileSearchRef = useRef<HTMLInputElement>(null);
  const [mobileSearchOpen, setMobileSearchOpen] = useState(false);
  const [mobileSearchValue, setMobileSearchValue] = useState('');

  const openMobileSearch = useCallback(() => {
    flushSync(() => setMobileSearchOpen(true));
    mobileSearchRef.current?.focus();
  }, []);

  const submitMobileSearch = useCallback(() => {
    const v = mobileSearchValue.trim() || null;
    setNotesSearchInput(mobileSearchValue.trim());
    setNotesSearch(v);
    mobileSearchRef.current?.blur();
  }, [mobileSearchValue]);

  const closeMobileSearch = useCallback(() => {
    setNotesSearchInput('');
    setNotesSearch(null);
    setMobileSearchOpen(false);
    setMobileSearchValue('');
  }, []);

  useEffect(() => {
    if (!menuOpen) return;
    const handler = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setMenuOpen(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [menuOpen]);

  useActionBar({
    backButton: {
      icon: ArrowLeft,
      label: 'Back',
      onPress: handleBack,
    },
    buttons: [
      {
        icon: SquarePen,
        label: 'New note',
        primary: true,
        onPress: () => navigate(`/notebooks/${notebookId}/notes/new`),
      },
      {
        icon: Sparkles,
        label: 'Extract note',
        onPress: () => navigate(`/notebooks/${notebookId}/extract`),
      },
      {
        icon: Search,
        label: 'Search',
        onPress: openMobileSearch,
      },
    ],
    input: mobileSearchOpen
      ? {
          ref: mobileSearchRef,
          value: mobileSearchValue,
          placeholder: 'Search notes…',
          onChange: setMobileSearchValue,
          onSubmit: submitMobileSearch,
          onClose: closeMobileSearch,
        }
      : null,
  });

  if (isLoading) {
    return <NotebookPageSkeleton />;
  }

  if (!notebook) {
    return (
      <div className={styles.pageContainer}>
        <div>Notebook not found</div>
      </div>
    );
  }

  return (
    <div className={styles.pageContainer}>
      <button
        type="button"
        onClick={handleBack}
        className={backStyles.backBtn}
        aria-label="Go back"
      >
        <ArrowLeft size={20} strokeWidth={1.75} />
      </button>
      <div className={styles.notebookHeader}>
        <div className={styles.notebookContent}>
          <h1 className={styles.notebookTitle}>{notebook.title}</h1>
          {notebook.description && (
            <p className={styles.notebookDescription}>{notebook.description}</p>
          )}
        </div>
        <div className={styles.notebookMenu} ref={menuRef}>
          <button
            type="button"
            className={styles.menuButton}
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              setMenuOpen(!menuOpen);
            }}
            aria-label="More options"
          >
            <MoreVertical size={18} strokeWidth={1.75} />
          </button>
          {menuOpen && (
            <div className={styles.menuDropdown}>
              <button
                type="button"
                className={styles.menuItem}
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  navigate(`/notebooks/${notebookId}/edit`);
                  setMenuOpen(false);
                }}
              >
                Edit
              </button>
            </div>
          )}
        </div>
      </div>

      <div className={styles.notesSection}>
        <SearchBar
          className="hide-on-mobile"
          value={notesSearchInput}
          onChange={setNotesSearchInput}
          onSearch={() => setNotesSearch(notesSearchInput.trim() || null)}
          onClear={() => {
            setNotesSearchInput('');
            setNotesSearch(null);
          }}
          placeholder="Search notes..."
          hasActiveSearch={notesSearch !== null}
        />
        <NotesList notebookId={notebookId!} search={notesSearch} />
      </div>
    </div>
  );
}
