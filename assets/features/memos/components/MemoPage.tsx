import React, { useState, useEffect, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Save, Paperclip } from 'lucide-react';
import {
  useGetMemoQuery,
  useCreateMemoMutation,
  useUpdateMemoMutation,
  useAttachMemoAttachmentsMutation,
  useDetachMemoAttachmentMutation,
} from '../store/api';
import { useCreateAttachmentMutation, useConfirmAttachmentUploadMutation } from '../../attachments';
import type { AttachmentResponseDto } from '../../attachments';
import { MarkdownEditor, FormShell, AttachmentsList } from '../../../shared/components';
import { useDraftSave, useDraftRestore, useFormDirty } from '../../../shared/hooks';
import { useActionBar } from '../../../layout/ActionBarContext';
import { MEMO_DRAFT_KEY } from '../constants';
import { parseError } from '../../../shared/utils';
import { MemoPageSkeleton } from './MemoPageSkeleton';
import styles from './MemoPage.module.css';

interface FormState {
  content: string;
}

export function MemoPage() {
  const { id: memoId } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const isNew = !memoId || memoId === 'new';

  const {
    data: memo,
    isLoading,
    error: loadError,
  } = useGetMemoQuery(memoId ?? '', { skip: isNew || !memoId });

  const [createMemo, { isLoading: isCreating }] = useCreateMemoMutation();
  const [updateMemo, { isLoading: isUpdating }] = useUpdateMemoMutation();
  const [attachMemoAttachments] = useAttachMemoAttachmentsMutation();
  const [detachMemoAttachment] = useDetachMemoAttachmentMutation();
  const [createAttachment] = useCreateAttachmentMutation();
  const [confirmAttachmentUpload] = useConfirmAttachmentUploadMutation();

  const [form, setForm] = useState<FormState>({ content: isNew ? '# ' : '' });
  const [error, setError] = useState<string | null>(null);
  const [pendingAttachments, setPendingAttachments] = useState<AttachmentResponseDto[]>([]);
  const [attachUploading, setAttachUploading] = useState(false);

  const fileInputRef = useRef<HTMLInputElement>(null);
  const saving = isCreating || isUpdating;

  const attachments = isNew ? pendingAttachments : (memo?.attachments ?? []);

  const { restore, clear } = useDraftSave(MEMO_DRAFT_KEY, form, isNew);
  const { isDirty, markSaved } = useFormDirty(form);

  useEffect(() => {
    if (memo) {
      const state = { content: memo.content };
      setForm(state);
      markSaved(state);
    }
  }, [memo, markSaved]);

  useDraftRestore(isNew, restore, setForm, markSaved);

  const handleSave = async () => {
    if (!form.content.trim()) {
      setError('Add content before saving');
      return;
    }

    setError(null);

    try {
      if (isNew) {
        const created = await createMemo({
          content: form.content.trim(),
          attachments: pendingAttachments.map((a) => a.id),
        }).unwrap();
        clear();
        navigate(`/memos/${created.id}`);
      } else {
        if (!memoId) return;
        await updateMemo({
          id: memoId,
          body: { content: form.content.trim() },
        }).unwrap();
        markSaved({ content: form.content.trim() });
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
        await attachMemoAttachments({ memoId: memoId!, attachments: [id] }).unwrap();
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
      await detachMemoAttachment({ memoId: memoId!, attachmentId }).unwrap();
    } catch (err: unknown) {
      setError(parseError(err).message);
    }
  };

  useActionBar({
    backButton: {
      icon: ArrowLeft,
      label: 'Back',
      onPress: () => (history.length > 1 ? navigate(-1) : navigate('/memos')),
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
    return <MemoPageSkeleton />;
  }

  return (
    <FormShell
      backTo="/memos"
      error={error ?? (loadError ? 'Failed to load memo' : null)}
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
      <div className={styles.editor}>
        <MarkdownEditor
          value={form.content}
          onChange={(content) => setForm((p) => ({ ...p, content }))}
          headingLevels={[1]}
          enforceFirstLineHeading
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
