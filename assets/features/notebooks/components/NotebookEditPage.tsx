import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Save } from 'lucide-react';
import {
  useGetNotebookQuery,
  useCreateNotebookMutation,
  useUpdateNotebookMutation,
} from '../store/api';
import { useDraftSave, useDraftRestore, useFormDirty } from '../../../shared/hooks';
import { useActionBar } from '../../../layout/ActionBarContext';
import { FormShell } from '../../../shared/components';
import { NOTEBOOK_DRAFT_KEY } from '../constants';
import { parseError } from '../../../shared/utils';
import { NotebookPageSkeleton } from './NotebookPageSkeleton';
import styles from './NotebookPage.module.css';

interface FormState {
  title: string;
  description: string;
  extractionInstructions: string;
}

export function NotebookEditPage() {
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

  const [form, setForm] = useState<FormState>({
    title: '',
    description: '',
    extractionInstructions: '',
  });
  const [error, setError] = useState<string | null>(null);

  const saving = isCreating || isUpdating;
  const { restore, clear } = useDraftSave(NOTEBOOK_DRAFT_KEY, form, isNew);
  const { isDirty, markSaved } = useFormDirty(form);

  useEffect(() => {
    if (notebook) {
      const state = {
        title: notebook.title,
        description: notebook.description,
        extractionInstructions: notebook.extractionInstructions ?? '',
      };
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
          extractionInstructions: form.extractionInstructions.trim() || null,
        }).unwrap();
        clear();
        navigate(`/notebooks/${created.id}`);
      } else {
        await updateNotebook({
          id: notebookId,
          body: {
            title: form.title.trim(),
            description: form.description.trim(),
            extractionInstructions: form.extractionInstructions.trim() || null,
          },
        }).unwrap();
        markSaved({
          title: form.title.trim(),
          description: form.description.trim(),
          extractionInstructions: form.extractionInstructions.trim(),
        });
        navigate(`/notebooks/${notebookId}`);
      }
    } catch (err: unknown) {
      setError(parseError(err).message);
    }
  };

  useActionBar({
    backButton: {
      icon: ArrowLeft,
      label: 'Back',
      onPress: () => navigate(isNew ? '/notebooks' : `/notebooks/${notebookId}`),
    },
    buttons: [
      {
        icon: Save,
        label: 'Save',
        primary: true,
        disabled: saving || (!isNew && !isDirty),
        onPress: handleSave,
      },
    ],
  });

  if (isLoading) {
    return <NotebookPageSkeleton />;
  }

  const loadErrorMessage = loadError ? 'Failed to load notebook' : null;

  return (
    <FormShell
      backTo={isNew ? '/notebooks' : `/notebooks/${notebookId}`}
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
          placeholder="Untitled notebook"
          autoFocus={isNew}
        />

        <textarea
          className={styles.descriptionInput}
          value={form.description}
          onChange={(e) => setForm((p) => ({ ...p, description: e.target.value }))}
          placeholder="Description..."
        />

        <label className={styles.fieldLabel}>
          Extraction instructions
          <textarea
            className={styles.instructionsInput}
            value={form.extractionInstructions}
            onChange={(e) => setForm((p) => ({ ...p, extractionInstructions: e.target.value }))}
            placeholder="Instructions for AI when extracting notes from files in this notebook..."
          />
        </label>
      </div>
    </FormShell>
  );
}
