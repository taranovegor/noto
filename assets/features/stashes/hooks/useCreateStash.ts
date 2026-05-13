import { useCallback, useState } from 'react';
import { useCreateStashMutation } from '../store/api';
import { useCreateAttachmentMutation, useConfirmAttachmentUploadMutation } from '../../attachments';

interface CreateStashState {
  isLoading: boolean;
  error: string | null;
}

export function useCreateStash() {
  const [createStash] = useCreateStashMutation();
  const [createAttachment] = useCreateAttachmentMutation();
  const [confirmUpload] = useConfirmAttachmentUploadMutation();
  const [state, setState] = useState<CreateStashState>({ isLoading: false, error: null });

  const create = useCallback(
    async (files: File[], text?: string) => {
      setState({ isLoading: true, error: null });
      try {
        if (files.length > 0) {
          const attachmentIds = await Promise.all(
            files.map(async (file) => {
              const attachment = await createAttachment({
                originFilename: file.name,
                mimeType: file.type || 'application/octet-stream',
                size: file.size,
              }).unwrap();

              const response = await fetch(attachment.uploadUrl, {
                method: 'PUT',
                body: file,
                headers: { 'Content-Type': file.type || 'application/octet-stream' },
              });

              if (!response.ok) {
                throw new Error(`Failed to upload ${file.name}`);
              }

              const confirmed = await confirmUpload(attachment.id).unwrap();
              return confirmed.id;
            }),
          );

          await createStash({ type: 'file', attachments: attachmentIds }).unwrap();
        } else if (text) {
          await createStash({ type: 'text', content: text }).unwrap();
        }
      } catch (err) {
        const message = err instanceof Error ? err.message : 'Upload failed';
        setState((s) => ({ ...s, error: message }));
        throw err;
      } finally {
        setState((s) => ({ ...s, isLoading: false }));
      }
    },
    [createStash, createAttachment, confirmUpload],
  );

  return { create, ...state };
}
