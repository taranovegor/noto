import { useCallback, useEffect, useState } from 'react';
import { ArrowUp, FolderOpen } from 'lucide-react';
import { DragDropZone } from '../../../shared/components/DragDropZone';
import { StashesList } from './StashesList';
import { useCreateStash } from '../hooks/useCreateStash';
import styles from './StashesListShell.module.css';

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
  const [textInput, setTextInput] = useState('');
  const [pendingFile, setPendingFile] = useState<File | null>(null);
  const { create, isLoading, error } = useCreateStash();

  const hasContent = !!textInput.trim() || !!pendingFile;

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

  const handleFileInputChange = useCallback(
    async (e: React.ChangeEvent<HTMLInputElement>) => {
      const files = Array.from(e.target.files ?? []);
      if (files.length > 0) await create(files);
      e.target.value = '';
    },
    [create],
  );

  const handleInputPaste = useCallback((e: React.ClipboardEvent<HTMLInputElement>) => {
    const { files } = parseClipboardItems(e.clipboardData.items);
    if (files.length > 0) {
      e.preventDefault();
      setPendingFile(files[0]);
      setTextInput(files[0].name);
    }
  }, []);

  const handleSubmit = useCallback(async () => {
    if (!hasContent || isLoading) return;
    if (pendingFile) {
      await create([pendingFile]);
    } else {
      await create([], textInput.trim());
    }
    setTextInput('');
    setPendingFile(null);
  }, [hasContent, isLoading, pendingFile, textInput, create]);

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

  return (
    <div>
      <div className={styles.header}>
        <h2 className={styles.headerTitle}>Stashes</h2>
      </div>

      <DragDropZone onDrop={handleDrop} disabled={isLoading} uploading={isLoading} />

      {error && (
        <div className="error-message" role="alert">
          {error}
        </div>
      )}

      <div className={styles.mobileActions}>
        <div className={styles.textInputRow}>
          <input
            className={styles.textInput}
            type="text"
            placeholder="Paste or type text..."
            value={textInput}
            onChange={(e) => {
              setTextInput(e.target.value);
              setPendingFile(null);
            }}
            onPaste={handleInputPaste}
            onKeyDown={(e) => {
              if (e.key === 'Enter') void handleSubmit();
            }}
            disabled={isLoading}
          />
          {hasContent ? (
            <button
              className={`btn btn-primary ${styles.actionBtn}`}
              onClick={handleSubmit}
              disabled={isLoading}
              aria-label="Upload"
            >
              <ArrowUp size={16} />
            </button>
          ) : (
            <label className={`btn btn-primary ${styles.actionBtn}`} aria-label="Choose file">
              <FolderOpen size={16} />
              <input
                type="file"
                multiple
                onChange={handleFileInputChange}
                style={{ display: 'none' }}
              />
            </label>
          )}
        </div>
      </div>

      <StashesList />
    </div>
  );
}
