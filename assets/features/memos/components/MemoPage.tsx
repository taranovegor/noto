import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Save, Paperclip } from 'lucide-react';
import {
  useGetMemoQuery,
  useCreateMemoMutation,
  useUpdateMemoMutation,
  useAttachMemoAttachmentsMutation,
  useDetachMemoAttachmentMutation,
} from '../store/api';
import { useAttachmentUpload } from '../../attachments';
import { MarkdownEditor, FormShell, AttachmentsList } from '../../../shared/components';
import { useDraftSave, useDraftRestore, useFormDirty } from '../../../shared/hooks';
import { useActionBar } from '../../../layout/ActionBarContext';
import { MEMO_DRAFT_KEY } from '../constants';
import { parseError } from '../../../shared/utils';
import { MemoPageSkeleton } from './MemoPageSkeleton';

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

  const [form, setForm] = useState<FormState>({ content: isNew ? '# ' : '' });
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
    existingAttachments: memo?.attachments,
    onAttach: (attachmentId) =>
      attachMemoAttachments({ memoId: memoId!, attachments: [attachmentId] }).unwrap(),
    onDetach: (attachmentId) => detachMemoAttachment({ memoId: memoId!, attachmentId }).unwrap(),
    onError: setError,
  });

  const saving = isCreating || isUpdating;

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
      <MarkdownEditor
        value={form.content}
        onChange={(content) => setForm((p) => ({ ...p, content }))}
        headingLevels={[1]}
        enforceFirstLineHeading
      />

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
