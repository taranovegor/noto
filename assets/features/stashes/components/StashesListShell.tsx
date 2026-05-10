import { useRef, useState, useCallback, useEffect } from 'react';
import { flushSync } from 'react-dom';
import { ClipboardPaste, FolderOpen } from 'lucide-react';
import { DragDropZone } from '../../../shared/components/DragDropZone';
import { PageShell } from '../../../shared/components/PageShell';
import { StashesList } from './StashesList';
import { useCreateStash } from '../hooks/useCreateStash';
import { useActionBar } from '../../../layout/ActionBarContext';

function parseClipboardItems(items: DataTransferItemList): {
  files: File[];
  textPromises: Promise<string>[];
} {
  const files: File[] = [];
  const textPromises: Promise<string>[] = [];

  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    if (item.kind === 'file') {
      const file = item.getAsFile();
      if (file) files.push(file);
    } else if (item.kind === 'string' && item.type === 'text/plain') {
      textPromises.push(new Promise<string>((resolve) => item.getAsString(resolve)));
    }
  }

  return { files, textPromises };
}

export function StashesListShell() {
  const { create, isLoading, error } = useCreateStash();

  // Mobile ActionBar: text input fallback
  const textInputRef = useRef<HTMLInputElement>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [textOpen, setTextOpen] = useState(false);
  const [textValue, setTextValue] = useState('');

  const handleClipboardPaste = useCallback(async () => {
    try {
      const text = await navigator.clipboard.readText();
      if (text.trim()) {
        await create([], text.trim());
        return;
      }
    } catch {
      // permission denied or not supported
    }
    flushSync(() => setTextOpen(true));
    textInputRef.current?.focus();
  }, [create]);

  const handleFileSelect = useCallback(
    async (e: React.ChangeEvent<HTMLInputElement>) => {
      const files = Array.from(e.target.files ?? []);
      if (files.length > 0) await create(files);
      e.target.value = '';
    },
    [create],
  );

  const handleTextSubmit = useCallback(async () => {
    const text = textValue.trim();
    if (!text || isLoading) return;
    await create([], text);
    setTextValue('');
    setTextOpen(false);
  }, [textValue, isLoading, create]);

  const handleTextClose = useCallback(() => {
    setTextValue('');
    setTextOpen(false);
  }, []);

  useActionBar({
    buttons: [
      {
        icon: ClipboardPaste,
        label: 'Paste',
        primary: true,
        disabled: isLoading,
        onPress: handleClipboardPaste,
      },
      {
        icon: FolderOpen,
        label: 'Choose file',
        disabled: isLoading,
        onPress: () => fileInputRef.current?.click(),
      },
    ],
    input: textOpen
      ? {
          ref: textInputRef,
          value: textValue,
          placeholder: 'Type and press Enter…',
          disabled: isLoading,
          onChange: setTextValue,
          onSubmit: handleTextSubmit,
          onClose: handleTextClose,
        }
      : null,
  });

  // Desktop: global paste listener
  useEffect(() => {
    const handlePaste = (e: ClipboardEvent) => {
      const target = e.target as HTMLElement;
      if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement) return;

      const { files, textPromises } = parseClipboardItems(
        e.clipboardData?.items ?? ({} as DataTransferItemList),
      );

      if (files.length === 0 && textPromises.length === 0) return;

      e.preventDefault();
      void Promise.all(textPromises).then((texts) => {
        const text = texts.filter(Boolean).join('\n') || undefined;
        void create(files, text);
      });
    };

    window.addEventListener('paste', handlePaste);
    return () => window.removeEventListener('paste', handlePaste);
  }, [create]);

  const handleDrop = useCallback(
    async (files: File[], text?: string) => {
      try {
        await create(files, text);
      } catch {
        // Error is surfaced via useCreateStash().error
      }
    },
    [create],
  );

  return (
    <PageShell title="Stashes">
      <input
        ref={fileInputRef}
        type="file"
        multiple
        onChange={handleFileSelect}
        style={{ display: 'none' }}
      />

      <DragDropZone onDrop={handleDrop} disabled={isLoading} uploading={isLoading} />

      {error && (
        <div className="error-message" role="alert">
          {error}
        </div>
      )}

      <StashesList />
    </PageShell>
  );
}
