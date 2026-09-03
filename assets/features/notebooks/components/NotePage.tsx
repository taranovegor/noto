import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Save, Paperclip } from 'lucide-react';
import {
  useGetNoteQuery,
  useCreateNoteMutation,
  useUpdateNoteMutation,
  useAttachNoteAttachmentsMutation,
  useDetachNoteAttachmentMutation,
} from '../store/api';
import { useAttachmentUpload } from '../../attachments';
import { MarkdownEditor, FormShell, AttachmentsList } from '../../../shared/components';
import { useDraftSave, useDraftRestore, useFormDirty } from '../../../shared/hooks';
import { useActionBar } from '../../../layout/ActionBarContext';
import { NOTE_DRAFT_KEY } from '../constants';
import { parseError } from '../../../shared/utils';
import { NotePageSkeleton } from './NotePageSkeleton';
import styles from './NotePage.module.css';

interface FormState {
  title: string;
  content: string;
}

export function NotePage() {
  const { notebookId, id: noteId } = useParams<{ notebookId: string; id: string }>();
  const navigate = useNavigate();
  const isNew = !noteId || noteId === 'new';

  if (!notebookId) {
    return (
      <FormShell
        backTo="/notebooks"
        error="Notebook not found"
        onSubmit={() => {}}
        showSaveBar={false}
      >
        <div />
      </FormShell>
    );
  }

  return (
    <NotePageInner
      notebookId={notebookId}
      noteId={noteId ?? null}
      isNew={isNew}
      navigate={navigate}
    />
  );
}

interface NotePageInnerProps {
  notebookId: string;
  noteId: string | null;
  isNew: boolean;
  navigate: ReturnType<typeof useNavigate>;
}

function NotePageInner({ notebookId, noteId, isNew, navigate }: NotePageInnerProps) {
  const {
    data: note,
    isLoading,
    error: loadError,
  } = useGetNoteQuery({ notebookId, noteId: noteId ?? '' }, { skip: isNew || !noteId });

  const [createNote, { isLoading: isCreating }] = useCreateNoteMutation();
  const [updateNote, { isLoading: isUpdating }] = useUpdateNoteMutation();
  const [attachNoteAttachments] = useAttachNoteAttachmentsMutation();
  const [detachNoteAttachment] = useDetachNoteAttachmentMutation();

  const [form, setForm] = useState<FormState>({ title: '', content: '' });
  const [error, setError] = useState<string | null>(null);

  const {
    attachments,
    pendingAttachments,
    uploading: attachUploading,
    uploadProgress,
    uploadingFileName,
    fileInputRef,
    handleFileSelect,
    handleDetach,
  } = useAttachmentUpload({
    isNew,
    existingAttachments: note?.attachments,
    onAttach: (attachmentId) =>
      attachNoteAttachments({ notebookId, noteId: noteId!, attachments: [attachmentId] }).unwrap(),
    onDetach: (attachmentId) =>
      detachNoteAttachment({ notebookId, noteId: noteId!, attachmentId }).unwrap(),
    onError: setError,
  });

  const saving = isCreating || isUpdating;

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

  const handleSave = async () => {
    if (!form.title.trim()) {
      setError('Title is required');
      return;
    }

    setError(null);

    try {
      if (isNew) {
        const created = await createNote({
          notebookId,
          body: {
            title: form.title.trim(),
            content: form.content.trim(),
            attachments: pendingAttachments.map((a) => a.id),
          },
        }).unwrap();
        clear();
        navigate(`/notebooks/${notebookId}/notes/${created.id}`);
      } else {
        if (!noteId) return;
        await updateNote({
          notebookId,
          noteId,
          body: {
            title: form.title.trim(),
            content: form.content.trim(),
          },
        }).unwrap();
        markSaved({ title: form.title.trim(), content: form.content.trim() });
      }
    } catch (err: unknown) {
      setError(parseError(err).message);
    }
  };

  useActionBar({
    backButton: {
      icon: ArrowLeft,
      label: 'Back',
      onPress: () => (history.length > 1 ? navigate(-1) : navigate(`/notebooks/${notebookId}`)),
    },
    buttons: [
      {
        icon: Save,
        label: 'Save',
        primary: true,
        disabled: saving || attachUploading || (!isNew && !isDirty),
        onPress: handleSave,
      },
      {
        icon: Paperclip,
        label: 'Attach file',
        disabled: attachUploading,
        onPress: () => fileInputRef.current?.click(),
      },
    ],
  });

  if (isLoading) {
    return <NotePageSkeleton />;
  }

  return (
    <FormShell
      backTo={`/notebooks/${notebookId}`}
      error={error ?? (loadError ? 'Failed to load note' : null)}
      saving={saving}
      showSaveBar={isNew || isDirty}
      onSubmit={(e) => {
        e.preventDefault();
        handleSave();
      }}
      extraActions={
        <button
          type="button"
          className="btn"
          onClick={() => fileInputRef.current?.click()}
          disabled={attachUploading}
        >
          <Paperclip size={16} strokeWidth={1.75} />
          <span style={{ marginLeft: 6 }}>Attach file</span>
        </button>
      }
    >
      <input
        className={styles.titleInput}
        type="text"
        value={form.title}
        onChange={(e) => setForm((p) => ({ ...p, title: e.target.value }))}
        placeholder="Untitled note"
        autoFocus={isNew}
      />

      <div className={styles.editor}>
        <MarkdownEditor
          value={form.content}
          onChange={(content) => setForm((p) => ({ ...p, content }))}
          headingLevels={[1, 2, 3]}
          placeholder="Start writing..."
        />
      </div>

      <input
        ref={fileInputRef}
        type="file"
        style={{ display: 'none' }}
        onChange={handleFileSelect}
      />

      <AttachmentsList
        attachments={attachments}
        uploading={attachUploading}
        uploadProgress={attachUploading ? uploadProgress : undefined}
        uploadingFileName={uploadingFileName}
        onRemove={handleDetach}
      />
    </FormShell>
  );
}
