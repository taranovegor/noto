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
} from 'lucide-react';
import { getMimeTypeIcon, type StashResponseDto } from '../index';
import { RelativeTime } from '../../../shared/components';
import styles from './StashCard.module.css';

interface StashCardProps {
  stash: StashResponseDto;
  onDownload?: (attachmentIds: string[]) => void;
  onCopy?: (stash: StashResponseDto) => void;
  onPin?: (stash: StashResponseDto) => void;
  onDelete?: (stash: StashResponseDto) => void;
  isDeleting?: boolean;
  isExpired?: boolean;
}

export function StashCard({
  stash,
  onDownload,
  onCopy,
  onPin,
  onDelete,
  isDeleting,
  isExpired,
}: StashCardProps) {
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

  const rowContent = (
    <>
      <div className={styles.icon}>
        <IconComponent size={18} />
      </div>

      <div className={styles.titleBlock}>
        <span className={styles.title}>{title || 'n/a'}</span>
        {isMultiFile && <span className={styles.badge}>{attachmentCount}</span>}
      </div>

      <div className={styles.meta}>
        <span className={styles.date}>
          <RelativeTime date={stash.createdAt} />
        </span>
        {stash.expiresAt && (
          <span className={styles.expires}>
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
            <Download size={15} />
          </button>
        ) : (
          <button
            className={`btn btn-ghost ${styles.actionBtn} ${copied ? styles.copySuccess : ''}`}
            onClick={handleCopy}
            title="Copy"
            aria-label="Copy"
          >
            {copied ? <Check size={15} /> : <Copy size={15} />}
          </button>
        )}
        <button
          className={`${styles.bookmarkButton} ${stash.pinned ? styles.bookmarked : ''}`}
          onClick={(e) => {
            e.stopPropagation();
            onPin?.(stash);
          }}
          title={stash.pinned ? 'Unbookmark' : 'Bookmark'}
          aria-label={stash.pinned ? 'Unbookmark' : 'Bookmark'}
        >
          {stash.pinned ? <BookmarkCheck size={15} /> : <Bookmark size={15} />}
        </button>
        <button
          className={`btn btn-ghost ${isExpired ? styles.deleteBtn : styles.archiveBtn}`}
          onClick={(e) => {
            e.stopPropagation();
            onDelete?.(stash);
          }}
          disabled={isDeleting}
          title={isExpired ? 'Delete' : 'Archive'}
          aria-label={isExpired ? 'Delete' : 'Archive'}
        >
          {isDeleting ? (
            <Loader2 size={15} className={styles.deleteSpinner} />
          ) : isExpired ? (
            <Trash size={15} />
          ) : (
            <Archive size={15} />
          )}
        </button>
      </div>
    </>
  );

  return (
    <div className={styles.card}>
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
                  <FileIcon size={14} />
                </div>
                <span className={styles.fileRowName}>{attachment.originFilename}</span>
                <button
                  className={`btn btn-ghost ${styles.fileRowAction}`}
                  onClick={() => onDownload?.([attachment.id])}
                  title={`Download ${attachment.originFilename}`}
                  aria-label={`Download ${attachment.originFilename}`}
                >
                  <Download size={13} />
                </button>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
