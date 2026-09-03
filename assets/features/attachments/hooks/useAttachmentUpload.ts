import React, { useRef, useState } from 'react';
import { useCreateAttachmentMutation, useConfirmAttachmentUploadMutation } from '../store/api';
import type { AttachmentResponseDto } from '../types';
import { parseError } from '../../../shared/utils';
import { uploadWithProgress } from '../utils/uploadWithProgress';

interface UseAttachmentUploadOptions {
  isNew: boolean;
  existingAttachments: AttachmentResponseDto[] | null | undefined;
  onAttach: (attachmentId: string) => Promise<unknown>;
  onDetach: (attachmentId: string) => Promise<unknown>;
  onError: (message: string) => void;
}

export function useAttachmentUpload({
  isNew,
  existingAttachments,
  onAttach,
  onDetach,
  onError,
}: UseAttachmentUploadOptions) {
  const [createAttachment] = useCreateAttachmentMutation();
  const [confirmAttachmentUpload] = useConfirmAttachmentUploadMutation();
  const [pendingAttachments, setPendingAttachments] = useState<AttachmentResponseDto[]>([]);
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [uploadingFileName, setUploadingFileName] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const attachments = isNew ? pendingAttachments : (existingAttachments ?? []);

  const handleFileSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    e.target.value = '';

    setUploading(true);
    setUploadProgress(0);
    setUploadingFileName(file.name);
    const controller = new AbortController();

    try {
      const { uploadUrl, id } = await createAttachment({
        originFilename: file.name,
        mimeType: file.type || 'application/octet-stream',
        size: file.size,
      }).unwrap();

      await uploadWithProgress(uploadUrl, file, controller.signal, (loaded) =>
        setUploadProgress(file.size > 0 ? loaded / file.size : 0),
      );

      const confirmed = await confirmAttachmentUpload(id).unwrap();

      if (isNew) {
        setPendingAttachments((prev) => [...prev, confirmed]);
      } else {
        await onAttach(id);
      }
    } catch (err: unknown) {
      onError(parseError(err).message || 'Failed to upload attachment');
    } finally {
      setUploading(false);
      setUploadingFileName(null);
    }
  };

  const handleDetach = async (attachmentId: string) => {
    if (isNew) {
      setPendingAttachments((prev) => prev.filter((a) => a.id !== attachmentId));
      return;
    }

    try {
      await onDetach(attachmentId);
    } catch (err: unknown) {
      onError(parseError(err).message);
    }
  };

  return {
    attachments,
    pendingAttachments,
    uploading,
    uploadProgress,
    uploadingFileName,
    fileInputRef,
    handleFileSelect,
    handleDetach,
  };
}
