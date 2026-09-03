import React, { useState, useRef, useCallback } from 'react';
import { flushSync } from 'react-dom';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Search, SquarePen, Sparkles } from 'lucide-react';
import { useGetNotebookQuery } from '../store/api';
import { useActionBar } from '../../../layout/ActionBarContext';
import { SearchBar, BackButton, Menu } from '../../../shared/components';
import { useBackNavigation } from '../../../shared/hooks';
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
      <BackButton onClick={handleBack} />
      <div className={styles.notebookHeader}>
        <div className={styles.notebookContent}>
          <h1 className={styles.notebookTitle}>{notebook.title}</h1>
          {notebook.description && (
            <p className={styles.notebookDescription}>{notebook.description}</p>
          )}
        </div>
        <Menu
          items={[{ label: 'Edit', onClick: () => navigate(`/notebooks/${notebookId}/edit`) }]}
        />
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
