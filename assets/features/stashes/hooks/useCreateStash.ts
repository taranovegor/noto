import { useCallback, useRef, useState } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Files } from 'lucide-react';
import { useCreateStashMutation } from '../store/api';
import { useCreateAttachmentMutation, useConfirmAttachmentUploadMutation } from '../../attachments';
import { getMimeTypeIcon } from '../constants';

export interface PendingStashUpload {
  id: string;
  title: string;
  count?: number;
  icon: LucideIcon;
  totalSize: number;
  loadedSize: number;
}

interface CreateStashState {
  pending: PendingStashUpload[];
  error: string | null;
}

function buildTitle(files: File[]): string {
  return files.length > 1 ? files.map((f) => f.name).join(' · ') : files[0].name;
}

function uploadWithProgress(
  url: string,
  file: File,
  signal: AbortSignal,
  onProgress: (loaded: number) => void,
): Promise<void> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('PUT', url);
    xhr.setRequestHeader('Content-Type', file.type || 'application/octet-stream');

    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable) onProgress(e.loaded);
    };
    xhr.onload = () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        onProgress(file.size);
        resolve();
      } else {
        reject(new Error(`Failed to upload ${file.name}`));
      }
    };
    xhr.onerror = () => reject(new Error(`Failed to upload ${file.name}`));
    xhr.onabort = () => reject(new DOMException('Aborted', 'AbortError'));

    if (signal.aborted) {
      xhr.abort();
      return;
    }
    signal.addEventListener('abort', () => xhr.abort());

    xhr.send(file);
  });
}

export function useCreateStash() {
  const [createStash] = useCreateStashMutation();
  const [createAttachment] = useCreateAttachmentMutation();
  const [confirmUpload] = useConfirmAttachmentUploadMutation();
  const [state, setState] = useState<CreateStashState>({ pending: [], error: null });
  const controllers = useRef<Map<string, AbortController>>(new Map());
  const loadedByFile = useRef<Map<string, number[]>>(new Map());

  const updateProgress = useCallback((id: string, loadedForFile: number, fileIndex: number) => {
    const perFile = loadedByFile.current.get(id) ?? [];
    perFile[fileIndex] = loadedForFile;
    loadedByFile.current.set(id, perFile);
    const loadedSize = perFile.reduce((a, b) => a + b, 0);

    setState((s) => ({
      ...s,
      pending: s.pending.map((p) => (p.id === id ? { ...p, loadedSize } : p)),
    }));
  }, []);

  const create = useCallback(
    async (files: File[], text?: string) => {
      if (files.length === 0) {
        if (!text) return;
        setState((s) => ({ ...s, error: null }));
        try {
          await createStash({ type: 'text', content: text }).unwrap();
        } catch (err) {
          const message = err instanceof Error ? err.message : 'Failed to create stash';
          setState((s) => ({ ...s, error: message }));
          throw err;
        }
        return;
      }

      const id = crypto.randomUUID();
      const controller = new AbortController();
      controllers.current.set(id, controller);

      const totalSize = files.reduce((sum, f) => sum + f.size, 0);
      const icon = files.length > 1 ? Files : getMimeTypeIcon(files[0].type);

      setState((s) => ({
        ...s,
        error: null,
        pending: [
          ...s.pending,
          {
            id,
            title: buildTitle(files),
            count: files.length > 1 ? files.length : undefined,
            icon,
            totalSize,
            loadedSize: 0,
          },
        ],
      }));

      try {
        const attachmentIds = await Promise.all(
          files.map(async (file, index) => {
            if (controller.signal.aborted) throw new DOMException('Aborted', 'AbortError');

            const attachment = await createAttachment({
              originFilename: file.name,
              mimeType: file.type || 'application/octet-stream',
              size: file.size,
            }).unwrap();

            await uploadWithProgress(attachment.uploadUrl, file, controller.signal, (loaded) =>
              updateProgress(id, loaded, index),
            );

            const confirmed = await confirmUpload(attachment.id).unwrap();
            return confirmed.id;
          }),
        );

        if (controller.signal.aborted) throw new DOMException('Aborted', 'AbortError');

        await createStash({ type: 'file', attachments: attachmentIds }).unwrap();
      } catch (err) {
        const aborted = err instanceof DOMException && err.name === 'AbortError';
        if (!aborted) {
          const message = err instanceof Error ? err.message : 'Upload failed';
          setState((s) => ({ ...s, error: message }));
        }
        if (!aborted) throw err;
      } finally {
        controllers.current.delete(id);
        loadedByFile.current.delete(id);
        setState((s) => ({ ...s, pending: s.pending.filter((p) => p.id !== id) }));
      }
    },
    [createStash, createAttachment, confirmUpload, updateProgress],
  );

  const cancel = useCallback((id: string) => {
    controllers.current.get(id)?.abort();
  }, []);

  return {
    create,
    cancel,
    pending: state.pending,
    isLoading: state.pending.length > 0,
    error: state.error,
  };
}
