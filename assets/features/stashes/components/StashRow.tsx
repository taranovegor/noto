import { useState, useRef, useCallback, useEffect } from 'react';
import {
  FileText,
  Files,
  Bookmark,
  BookmarkCheck,
  Download,
  Copy,
  Check,
  Archive,
  Trash,
  Loader2,
  X,
} from 'lucide-react';
import { getMimeTypeIcon, type StashResponseDto } from '../index';
import type { PendingStashUpload } from '../hooks/useCreateStash';
import { RelativeTime } from '../../../shared/components';
import { formatFileSize } from '../../../shared/utils';
import styles from './StashRow.module.css';

interface StashRowProps {
  stash: StashResponseDto;
  onDownload?: (attachmentIds: string[]) => void;
  onCopy?: (stash: StashResponseDto) => void;
  onPin?: (stash: StashResponseDto) => void;
  onArchive?: (stash: StashResponseDto) => void;
  isArchiving?: boolean;
  isExpired?: boolean;
  last?: boolean;
}

export function StashRow({
  stash,
  onDownload,
  onCopy,
  onPin,
  onArchive,
  isArchiving,
  isExpired,
  last,
}: StashRowProps) {
  const [isExpanded, setIsExpanded] = useState(false);
  const [copied, setCopied] = useState(false);
  const copyTimerRef = useRef<ReturnType<typeof setTimeout>>(null);

  const handleCopy = useCallback(
    (e: React.MouseEvent) => {
      e.stopPropagation();
      onCopy?.(stash);
      setCopied(true);
      if (copyTimerRef.current) clearTimeout(copyTimerRef.current);
      copyTimerRef.current = setTimeout(() => setCopied(false), 1500);
    },
    [onCopy, stash],
  );

  useEffect(() => {
    return () => {
      if (copyTimerRef.current) clearTimeout(copyTimerRef.current);
    };
  }, []);

  const isFileStash = stash.type === 'file';
  const hasAttachments = stash.attachments && stash.attachments.length > 0;
  const firstAttachment = hasAttachments ? stash.attachments![0] : null;
  const attachmentCount = stash.attachments?.length ?? 0;
  const isMultiFile = isFileStash && attachmentCount > 1;

  let IconComponent = FileText;
  let title = '';

  if (isFileStash && firstAttachment) {
    IconComponent = isMultiFile ? Files : getMimeTypeIcon(firstAttachment.mimeType);
    title = isMultiFile
      ? stash.attachments!.map((a) => a.originFilename).join(' · ')
      : firstAttachment.originFilename;
  } else if (!isFileStash && stash.content) {
    title = stash.content.slice(0, 80).replace(/\n/g, ' ');
    if (stash.content.length > 80) title += '...';
  }

  const singleFileSize = isFileStash && !isMultiFile ? firstAttachment?.size : undefined;

  const rowContent = (
    <>
      <div className={styles.icon}>
        <IconComponent size={18} strokeWidth={1.75} />
      </div>

      <div className={styles.titleBlock}>
        <span className={styles.title}>{title || 'n/a'}</span>
        {isMultiFile && <span className={styles.badge}>{attachmentCount}</span>}
        {singleFileSize !== undefined && (
          <span className={styles.size}>{formatFileSize(singleFileSize)}</span>
        )}
      </div>

      <div className={styles.meta}>
        <span className={styles.date}>
          <RelativeTime date={stash.createdAt} />
        </span>
        {stash.expiresAt && (
          <span className={`${styles.expires} ${isExpired ? styles.expiresDanger : ''}`}>
            {isExpired ? 'expired' : 'expires'} <RelativeTime date={stash.expiresAt} />
          </span>
        )}
      </div>

      <div className={styles.actions}>
        {isFileStash && hasAttachments ? (
          <button
            className={`btn btn-ghost ${styles.actionBtn}`}
            onClick={(e) => {
              e.stopPropagation();
              onDownload?.(stash.attachments!.map((a) => a.id));
            }}
            title={isMultiFile ? 'Download all' : 'Download'}
            aria-label={isMultiFile ? 'Download all' : 'Download'}
          >
            <Download size={15} strokeWidth={1.75} />
          </button>
        ) : (
          <button
            className={`btn btn-ghost ${styles.actionBtn} ${copied ? styles.copySuccess : ''}`}
            onClick={handleCopy}
            title="Copy"
            aria-label="Copy"
          >
            {copied ? (
              <Check size={15} strokeWidth={1.75} />
            ) : (
              <Copy size={15} strokeWidth={1.75} />
            )}
          </button>
        )}
        <button
          className={`${styles.iconToggle} ${stash.pinned ? styles.pinned : ''}`}
          onClick={(e) => {
            e.stopPropagation();
            onPin?.(stash);
          }}
          title={stash.pinned ? 'Unpin' : 'Pin'}
          aria-label={stash.pinned ? 'Unpin' : 'Pin'}
        >
          {stash.pinned ? (
            <BookmarkCheck size={15} strokeWidth={1.75} />
          ) : (
            <Bookmark size={15} strokeWidth={1.75} />
          )}
        </button>
        <button
          className={`btn btn-ghost ${styles.actionBtn} ${isExpired ? styles.deleteBtn : ''}`}
          onClick={(e) => {
            e.stopPropagation();
            onArchive?.(stash);
          }}
          disabled={isArchiving}
          title={isExpired ? 'Delete' : 'Archive'}
          aria-label={isExpired ? 'Delete' : 'Archive'}
        >
          {isArchiving ? (
            <Loader2 size={15} strokeWidth={1.75} className={styles.spinner} />
          ) : isExpired ? (
            <Trash size={15} strokeWidth={1.75} />
          ) : (
            <Archive size={15} strokeWidth={1.75} />
          )}
        </button>
      </div>
    </>
  );

  return (
    <div className={`${styles.row} ${last ? styles.last : ''}`}>
      {isMultiFile ? (
        <div
          className={`${styles.mainRow} ${styles.mainRowClickable}`}
          onClick={() => setIsExpanded((v) => !v)}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              setIsExpanded((v) => !v);
            }
          }}
          aria-expanded={isExpanded}
        >
          {rowContent}
        </div>
      ) : (
        <div className={styles.mainRow}>{rowContent}</div>
      )}

      {isExpanded && (
        <div className={styles.fileList}>
          {stash.attachments!.map((attachment) => {
            const FileIcon = getMimeTypeIcon(attachment.mimeType);
            return (
              <div key={attachment.id} className={styles.fileRow}>
                <div className={styles.fileRowIcon}>
                  <FileIcon size={14} strokeWidth={1.75} />
                </div>
                <span className={styles.fileRowName}>{attachment.originFilename}</span>
                <span className={styles.fileRowSize}>{formatFileSize(attachment.size)}</span>
                <button
                  className={`btn btn-ghost ${styles.fileRowAction}`}
                  onClick={() => onDownload?.([attachment.id])}
                  title={`Download ${attachment.originFilename}`}
                  aria-label={`Download ${attachment.originFilename}`}
                >
                  <Download size={13} strokeWidth={1.75} />
                </button>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}

interface PendingStashRowProps {
  upload: PendingStashUpload;
  onCancel: (id: string) => void;
  last?: boolean;
}

export function PendingStashRow({ upload, onCancel, last }: PendingStashRowProps) {
  const Icon = upload.icon;
  const progress = upload.totalSize > 0 ? upload.loadedSize / upload.totalSize : 0;

  return (
    <div className={`${styles.row} ${last ? styles.last : ''}`}>
      <div className={styles.mainRow}>
        <div className={styles.icon}>
          <Icon size={18} strokeWidth={1.75} />
        </div>

        <div className={styles.titleBlock}>
          <span className={styles.title}>{upload.title}</span>
          {upload.count && <span className={styles.badge}>{upload.count}</span>}
        </div>

        <div className={styles.meta}>
          <span className={styles.date}>{Math.round(progress * 100)}%</span>
        </div>

        <div className={styles.actions}>
          <button
            className={`btn btn-ghost ${styles.actionBtn}`}
            onClick={() => onCancel(upload.id)}
            title="Cancel"
            aria-label="Cancel"
          >
            <X size={15} strokeWidth={1.75} />
          </button>
        </div>
      </div>
      <div className={styles.progressTrack}>
        <div className={styles.progressFill} style={{ width: `${Math.max(4, progress * 100)}%` }} />
      </div>
    </div>
  );
}
