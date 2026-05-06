import { useCallback, useEffect, useState } from 'react';
import { ArrowUp, FolderOpen } from 'lucide-react';
import { DragDropZone } from '../../../shared/components/DragDropZone';
import { StashesList } from './StashesList';
import { useCreateStash } from '../hooks/useCreateStash';
import styles from './StashesListShell.module.css';

export function StashesListShell() {
  const [textInput, setTextInput] = useState('');
  const [pendingFile, setPendingFile] = useState<File | null>(null);
  const { create, isLoading } = useCreateStash();

  const hasContent = !!textInput.trim() || !!pendingFile;

  const handleDrop = useCallback(
    async (files: File[], text?: string) => {
      try {
        await create(files, text);
      } catch {
        // TODO: Show error toast
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
    const items = e.clipboardData.items;
    for (let i = 0; i < items.length; i++) {
      if (items[i].kind === 'file') {
        const file = items[i].getAsFile();
        if (file) {
          e.preventDefault();
          setPendingFile(file);
          setTextInput(file.name);
          return;
        }
      }
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

      const items = e.clipboardData?.items;
      if (!items) return;

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
