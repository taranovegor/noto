import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useGetNoteQuery, useCreateNoteMutation, useUpdateNoteMutation } from '../store/api';
import { MarkdownEditor, FormShell } from '../../../shared/components';
import { useDraftSave, useDraftRestore, useFormDirty } from '../../../shared/hooks';
import { NOTE_DRAFT_KEY } from '../constants';
import { parseError } from '../../../shared/utils';
import { NotePageSkeleton } from './NotePageSkeleton';
import styles from './NotePage.module.css';

interface FormState {
  content: string;
}

export function NotePage() {
  const { id: noteId } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const isNew = !noteId || noteId === 'new';

  const {
    data: note,
    isLoading,
    error: loadError,
  } = useGetNoteQuery(noteId ?? '', { skip: isNew || !noteId });
  const [createNote, { isLoading: isCreating }] = useCreateNoteMutation();
  const [updateNote, { isLoading: isUpdating }] = useUpdateNoteMutation();

  const [form, setForm] = useState<FormState>({ content: isNew ? '# ' : '' });
  const [error, setError] = useState<string | null>(null);

  const saving = isCreating || isUpdating;

  const { restore, clear } = useDraftSave(NOTE_DRAFT_KEY, form, isNew);
  const { isDirty, markSaved } = useFormDirty(form);

  useEffect(() => {
    if (note) {
      const state = { content: note.content };
      setForm(state);
      markSaved(state);
    }
  }, [note, markSaved]);

  useDraftRestore(isNew, restore, setForm, markSaved);

  const handleSave = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    if (!form.content.trim()) {
      setError('Add content before saving');
      return;
    }

    setError(null);

    try {
      if (isNew) {
        const created = await createNote({
          content: form.content.trim(),
        }).unwrap();
        clear();
        navigate(`/notes/${created.id}`);
      } else {
        if (!noteId) return;
        await updateNote({
          id: noteId,
          body: { content: form.content.trim() },
        }).unwrap();
        markSaved({ content: form.content.trim() });
      }
    } catch (err: unknown) {
      setError(parseError(err).message);
    }
  };

  if (isLoading) {
    return <NotePageSkeleton />;
  }

  return (
    <FormShell
      backTo="/notes"
      error={error ?? (loadError ? 'Failed to load note' : null)}
      saving={saving}
      showSaveBar={isNew || isDirty}
      onSubmit={handleSave}
    >
      <div className={styles.editor}>
        <MarkdownEditor
          value={form.content}
          onChange={(content) => setForm((p) => ({ ...p, content }))}
          headingLevels={[1]}
          enforceFirstLineHeading
        />
      </div>
    </FormShell>
  );
}
