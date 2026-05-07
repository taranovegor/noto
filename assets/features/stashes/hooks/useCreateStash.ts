import { useCallback, useState } from 'react';
import { useCreateStashMutation } from '../store/api';
import { useConfirmAttachmentUploadMutation } from '../../attachments';

interface CreateStashState {
  isLoading: boolean;
  error: string | null;
}

export function useCreateStash() {
  const [createStash] = useCreateStashMutation();
  const [confirmUpload] = useConfirmAttachmentUploadMutation();
  const [state, setState] = useState<CreateStashState>({ isLoading: false, error: null });

  const create = useCallback(
    async (files: File[], text?: string) => {
      setState({ isLoading: true, error: null });
      try {
        if (files.length > 0) {
          const stash = await createStash({
            type: 'file',
            attachments: files.map((f) => ({
              originFilename: f.name,
              mimeType: f.type || 'application/octet-stream',
              size: f.size,
            })),
          }).unwrap();

          if (stash.attachments) {
            const fileByName = new Map(files.map((f) => [f.name, f]));

            await Promise.all(
              stash.attachments.map(async (attachment) => {
                const file = fileByName.get(attachment.originFilename);
                if (!attachment.uploadUrl || !file) return;

                const response = await fetch(attachment.uploadUrl, {
                  method: 'PUT',
                  body: file,
                  headers: { 'Content-Type': file.type || 'application/octet-stream' },
                });

                if (!response.ok) {
                  throw new Error(`Failed to upload ${file.name}`);
                }
                await confirmUpload(attachment.id).unwrap();
              }),
            );
          }
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
    [createStash, confirmUpload],
  );

  return { create, ...state };
}
