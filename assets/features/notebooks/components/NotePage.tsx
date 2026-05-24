import React, { useState, useEffect, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Save, Paperclip } from 'lucide-react';
import {
  useGetNoteQuery,
  useCreateNoteMutation,
  useUpdateNoteMutation,
  useAttachNoteAttachmentsMutation,
  useDetachNoteAttachmentMutation,
} from '../store/api';
import { useCreateAttachmentMutation, useConfirmAttachmentUploadMutation } from '../../attachments';
import type { AttachmentResponseDto } from '../../attachments';
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
  const [createAttachment] = useCreateAttachmentMutation();
  const [confirmAttachmentUpload] = useConfirmAttachmentUploadMutation();

  const [form, setForm] = useState<FormState>({ title: '', content: '' });
  const [error, setError] = useState<string | null>(null);
  const [pendingAttachments, setPendingAttachments] = useState<AttachmentResponseDto[]>([]);
  const [attachUploading, setAttachUploading] = useState(false);

  const fileInputRef = useRef<HTMLInputElement>(null);
  const saving = isCreating || isUpdating;

  const attachments = isNew ? pendingAttachments : (note?.attachments ?? []);

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

  const handleFileSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    e.target.value = '';

    setAttachUploading(true);
    setError(null);

    try {
      const { uploadUrl, id } = await createAttachment({
        originFilename: file.name,
        mimeType: file.type || 'application/octet-stream',
        size: file.size,
      }).unwrap();

      const putRes = await fetch(uploadUrl, {
        method: 'PUT',
        body: file,
        headers: { 'Content-Type': file.type || 'application/octet-stream' },
      });

      if (!putRes.ok) {
        throw new Error('Failed to upload file to storage');
      }

      const confirmed = await confirmAttachmentUpload(id).unwrap();

      if (isNew) {
        setPendingAttachments((prev) => [...prev, confirmed]);
      } else {
        await attachNoteAttachments({ notebookId, noteId: noteId!, attachments: [id] }).unwrap();
      }
    } catch (err: unknown) {
      setError(parseError(err).message || 'Failed to upload attachment');
    } finally {
      setAttachUploading(false);
    }
  };

  const handleDetach = async (attachmentId: string) => {
    if (isNew) {
      setPendingAttachments((prev) => prev.filter((a) => a.id !== attachmentId));
      return;
    }

    setError(null);
    try {
      await detachNoteAttachment({ notebookId, noteId: noteId!, attachmentId }).unwrap();
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
        placeholder="Note title"
        autoFocus={isNew}
      />

      <div className={styles.editor}>
        <MarkdownEditor
          value={form.content}
          onChange={(content) => setForm((p) => ({ ...p, content }))}
          headingLevels={[1, 2, 3]}
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
        onRemove={handleDetach}
      />
    </FormShell>
  );
}
