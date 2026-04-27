import React, { useState, useEffect, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useGetNoteQuery, useCreateNoteMutation, useUpdateNoteMutation } from '../store/api';
import { MarkdownEditor, FormShell } from '../../../shared/components';
import { useAutoResize, useDraftSave, useDraftRestore, useFormDirty } from '../../../shared/hooks';
import { NOTE_DRAFT_KEY } from '../constants';
import { parseError } from '../../../shared/utils';
import { NotePageSkeleton } from './NotePageSkeleton';
import styles from './NotePage.module.css';

interface FormState {
  title: string;
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

  const [form, setForm] = useState<FormState>({ title: '', content: '' });
  const [error, setError] = useState<string | null>(null);
  const titleRef = useRef<HTMLTextAreaElement>(null);

  const saving = isCreating || isUpdating;

  useAutoResize(titleRef, form.title);
  const { restore, clear } = useDraftSave(NOTE_DRAFT_KEY, form, isNew);
  const { isDirty, markSaved } = useFormDirty(form);

  useEffect(() => {
    if (note) {
      const state = { title: note.title, content: note.content };
      setForm(state);
      markSaved(state);
    }
  }, [note, markSaved]);

  useDraftRestore(isNew, restore, setForm, markSaved);

  const handleSave = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    if (!form.title.trim()) {
      setError('Add a title');
      return;
    }
    if (!form.content.trim()) {
      setError('Add content before saving');
      return;
    }

    setError(null);

    try {
      if (isNew) {
        const created = await createNote({
          title: form.title.trim(),
          content: form.content.trim(),
        }).unwrap();
        clear();
        navigate(`/notes/${created.id}`);
      } else {
        if (!noteId) return;
        await updateNote({
          id: noteId,
          body: { title: form.title.trim(), content: form.content.trim() },
        }).unwrap();
        markSaved({ title: form.title.trim(), content: form.content.trim() });
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
      <div className={styles.titleRow}>
        <textarea
          ref={titleRef}
          value={form.title}
          onChange={(e) => setForm((p) => ({ ...p, title: e.target.value }))}
          placeholder="Title"
          maxLength={255}
          autoFocus={isNew}
          className={styles.titleInput}
          rows={1}
        />
      </div>

      <div className={styles.editor}>
        <MarkdownEditor
          value={form.content}
          onChange={(content) => setForm((p) => ({ ...p, content }))}
          placeholder="Write..."
        />
      </div>
    </FormShell>
  );
}
