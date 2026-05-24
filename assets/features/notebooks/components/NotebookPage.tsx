import React, { useState, useEffect, useRef, useCallback } from 'react';
import { flushSync } from 'react-dom';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Save, SquarePen, Search } from 'lucide-react';
import {
  useGetNotebookQuery,
  useCreateNotebookMutation,
  useUpdateNotebookMutation,
} from '../store/api';
import { useDraftSave, useDraftRestore, useFormDirty } from '../../../shared/hooks';
import { useActionBar } from '../../../layout/ActionBarContext';
import { FormShell, SearchBar } from '../../../shared/components';
import { NOTEBOOK_DRAFT_KEY } from '../constants';
import { parseError } from '../../../shared/utils';
import { NotesList } from './NotesList';
import { NotebookPageSkeleton } from './NotebookPageSkeleton';
import styles from './NotebookPage.module.css';

interface FormState {
  title: string;
  description: string;
}

export function NotebookPage() {
  const { id: notebookId } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const isNew = !notebookId || notebookId === 'new';

  const {
    data: notebook,
    isLoading,
    error: loadError,
  } = useGetNotebookQuery(notebookId ?? '', { skip: isNew || !notebookId });

  const [createNotebook, { isLoading: isCreating }] = useCreateNotebookMutation();
  const [updateNotebook, { isLoading: isUpdating }] = useUpdateNotebookMutation();

  const [form, setForm] = useState<FormState>({ title: '', description: '' });
  const [error, setError] = useState<string | null>(null);
  const [notesSearchInput, setNotesSearchInput] = useState('');
  const [notesSearch, setNotesSearch] = useState<string | null>(null);

  const saving = isCreating || isUpdating;

  const { restore, clear } = useDraftSave(NOTEBOOK_DRAFT_KEY, form, isNew);
  const { isDirty, markSaved } = useFormDirty(form);

  const showSave = isNew || isDirty;

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
    if (notebook) {
      const state = { title: notebook.title, description: notebook.description };
      setForm(state);
      markSaved(state);
    }
  }, [notebook, markSaved]);

  useDraftRestore(isNew, restore, setForm, markSaved);

  const handleSave = async () => {
    if (!form.title.trim()) {
      setError('Title is required');
      return;
    }

    setError(null);

    try {
      if (isNew) {
        const created = await createNotebook({
          title: form.title.trim(),
          description: form.description.trim(),
        }).unwrap();
        clear();
        navigate(`/notebooks/${created.id}`);
      } else {
        if (!notebookId) return;
        await updateNotebook({
          id: notebookId,
          body: {
            title: form.title.trim(),
            description: form.description.trim(),
          },
        }).unwrap();
        markSaved({ title: form.title.trim(), description: form.description.trim() });
      }
    } catch (err: unknown) {
      setError(parseError(err).message);
    }
  };

  useActionBar({
    backButton: {
      icon: ArrowLeft,
      label: 'Back',
      onPress: () => (history.length > 1 ? navigate(-1) : navigate('/notebooks')),
    },
    buttons: [
      ...(showSave
        ? [
            {
              icon: Save,
              label: 'Save',
              primary: true,
              disabled: saving,
              onPress: handleSave,
            },
          ]
        : !isNew
          ? [
              {
                icon: SquarePen,
                label: 'New note',
                primary: true,
                onPress: () => navigate(`/notebooks/${notebookId}/notes/new`),
              },
            ]
          : []),
      ...(!isNew
        ? [
            {
              icon: Search,
              label: 'Search',
              onPress: openMobileSearch,
            },
          ]
        : []),
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

  const loadErrorMessage = loadError ? 'Failed to load notebook' : null;

  return (
    <FormShell
      backTo="/notebooks"
      error={error ?? loadErrorMessage}
      saving={saving}
      showSaveBar={isNew || isDirty}
      onSubmit={(e) => {
        e.preventDefault();
        handleSave();
      }}
    >
      <div className={styles.form}>
        <input
          className={styles.titleInput}
          type="text"
          value={form.title}
          onChange={(e) => setForm((p) => ({ ...p, title: e.target.value }))}
          placeholder="Notebook title"
          autoFocus={isNew}
        />

        <textarea
          className={styles.descriptionInput}
          value={form.description}
          onChange={(e) => setForm((p) => ({ ...p, description: e.target.value }))}
          placeholder="Description..."
        />
      </div>

      {!isNew && notebookId && (
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
          <NotesList notebookId={notebookId} search={notesSearch} />
        </div>
      )}
    </FormShell>
  );
}
