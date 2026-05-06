import { useRef, useCallback } from 'react';
import { Upload, Loader2 } from 'lucide-react';
import styles from './DragDropZone.module.css';

interface DragDropZoneProps {
  onDrop: (files: File[], text?: string) => void;
  disabled?: boolean;
  uploading?: boolean;
}

export function DragDropZone({ onDrop, disabled = false, uploading = false }: DragDropZoneProps) {
  const dropZoneRef = useRef<HTMLDivElement>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const isDraggingRef = useRef(false);

  const handleDragOver = useCallback((e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    isDraggingRef.current = true;
    if (dropZoneRef.current) {
      dropZoneRef.current.classList.add(styles.dragging);
    }
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    isDraggingRef.current = false;
    if (dropZoneRef.current) {
      dropZoneRef.current.classList.remove(styles.dragging);
    }
  }, []);

  const handleDrop = useCallback(
    (e: React.DragEvent<HTMLDivElement>) => {
      e.preventDefault();
      e.stopPropagation();
      isDraggingRef.current = false;
      if (dropZoneRef.current) {
        dropZoneRef.current.classList.remove(styles.dragging);
      }

      if (disabled) return;

      const files = Array.from(e.dataTransfer.files);
      const text = e.dataTransfer.getData('text/plain');

      if (files.length > 0 || text) {
        onDrop(files, text || undefined);
      }
    },
    [onDrop, disabled],
  );

  const handleClick = useCallback(() => {
    if (!disabled) {
      fileInputRef.current?.click();
    }
  }, [disabled]);

  const handleKeyDown = useCallback(
    (e: React.KeyboardEvent<HTMLDivElement>) => {
      if (disabled) return;
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        fileInputRef.current?.click();
      }
    },
    [disabled],
  );

  const handleFileInputChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const files = Array.from(e.target.files ?? []);
      if (files.length > 0) {
        onDrop(files);
      }
      // Reset input so the same file can be selected again
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    },
    [onDrop],
  );

  return (
    <>
      <input
        ref={fileInputRef}
        type="file"
        multiple
        onChange={handleFileInputChange}
        style={{ display: 'none' }}
        aria-hidden="true"
      />
      <div
        ref={dropZoneRef}
        className={`${styles.zone} ${disabled ? styles.disabled : ''} ${uploading ? styles.uploading : ''}`}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={handleClick}
        onKeyDown={handleKeyDown}
        role="button"
        tabIndex={disabled ? -1 : 0}
        aria-busy={uploading}
      >
        {uploading ? (
          <div className={styles.content}>
            <div className={`${styles.icon} ${styles.spinner}`}>
              <Loader2 size={28} />
            </div>
            <div className={styles.text}>
              <p className={styles.primary}>Uploading...</p>
            </div>
          </div>
        ) : (
          <div className={styles.content}>
            <div className={styles.icon}>
              <Upload size={40} />
            </div>
            <div className={styles.text}>
              <p className={styles.primary}>Drag files here or click to select</p>
              <p className={styles.secondary}>Also supports paste (Ctrl+V)</p>
            </div>
          </div>
        )}
      </div>
    </>
  );
}
